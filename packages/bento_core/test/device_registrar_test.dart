import 'dart:convert';
import 'dart:typed_data';

import 'package:bento_core/bento_core.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';

class _Adapter implements HttpClientAdapter {
  final requests = <RequestOptions>[];

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    requests.add(options);
    return ResponseBody.fromString(
      jsonEncode({'success': true, 'data': null}),
      200,
      headers: {
        Headers.contentTypeHeader: ['application/json'],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

class _Token implements PushTokenSource {
  const _Token(this.value);

  final String? value;

  @override
  Future<String?> token() async => value;
}

void main() {
  late _Adapter adapter;
  late ApiClient api;

  setUp(() {
    adapter = _Adapter();
    api = ApiClient(
      baseUrl: 'http://api.test/api',
      localeTag: () => 'en',
      token: () => 't',
      dio: Dio()..httpClientAdapter = adapter,
    );
  });

  test('registers the push token for the app', () async {
    expect(
      await DeviceRegistrar(
        api,
        const _Token('fcm-123'),
        app: 'driver',
      ).register(),
      isTrue,
    );
    expect(adapter.requests.single.path, '/devices');
    expect(adapter.requests.single.data, {
      'token': 'fcm-123',
      'platform': DeviceRegistrar.platform,
      'app': 'driver',
    });
  });

  test('does nothing without a push provider', () async {
    expect(
      await DeviceRegistrar(
        api,
        const NoPushTokenSource(),
        app: 'customer',
      ).register(),
      isFalse,
    );
    expect(adapter.requests, isEmpty);
  });

  test('unregisters on sign-out', () async {
    await DeviceRegistrar(
      api,
      const _Token('fcm-123'),
      app: 'customer',
    ).unregister();
    expect(adapter.requests.single.method, 'DELETE');
    expect(adapter.requests.single.uri.queryParameters['token'], 'fcm-123');
  });
}
