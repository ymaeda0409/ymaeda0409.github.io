import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:malawi_bento_customer/core/network/api_client.dart';
import 'package:malawi_bento_customer/core/network/api_exception.dart';

class _FakeAdapter implements HttpClientAdapter {
  _FakeAdapter(this.handler);

  final Future<ResponseBody> Function(RequestOptions options) handler;
  final requests = <RequestOptions>[];

  @override
  Future<ResponseBody> fetch(RequestOptions options, Stream<Uint8List>? requestStream, Future<void>? cancelFuture) {
    requests.add(options);
    return handler(options);
  }

  @override
  void close({bool force = false}) {}
}

ResponseBody _json(int status, Object body) => ResponseBody.fromString(
      jsonEncode(body),
      status,
      headers: {Headers.contentTypeHeader: ['application/json']},
    );

void main() {
  late _FakeAdapter adapter;
  late ApiClient client;
  var unauthorized = 0;

  ApiClient build(Future<ResponseBody> Function(RequestOptions) handler, {String? token}) {
    adapter = _FakeAdapter(handler);
    unauthorized = 0;
    return ApiClient(
      baseUrl: 'http://api.test/api',
      localeTag: () => 'ny',
      token: () => token,
      onUnauthorized: () => unauthorized++,
      dio: Dio()..httpClientAdapter = adapter,
    );
  }

  test('unwraps the success envelope and sends locale + token headers', () async {
    client = build((_) async => _json(200, {'success': true, 'data': {'id': 1}, 'meta': {'locale': 'ny'}}), token: 'abc');

    final data = await client.get<Map<String, dynamic>>('/account');

    expect(data['id'], 1);
    expect(adapter.requests.single.headers['Accept-Language'], 'ny');
    expect(adapter.requests.single.headers['Authorization'], 'Bearer abc');
  });

  test('null query parameters are dropped', () async {
    client = build((_) async => _json(200, {'success': true, 'data': []}));
    await client.get<List<dynamic>>('/products', query: {'store_id': 1, 'category_id': null});
    expect(adapter.requests.single.queryParameters, {'store_id': 1});
  });

  test('error envelope becomes ApiException with code and field messages', () async {
    client = build((_) async => _json(422, {
          'success': false,
          'error': {'code': 'VALIDATION_FAILED', 'message': 'x', 'fields': {'phone': ['Nambala si yolondola']}},
        }));

    final error = await client.post<Object?>('/auth/send-otp').then<ApiException?>((_) => null, onError: (e) => e as ApiException);

    expect(error!.code, 'VALIDATION_FAILED');
    expect(error.statusCode, 422);
    expect(error.fieldMessage('phone'), 'Nambala si yolondola');
  });

  test('401 notifies the session', () async {
    client = build((_) async => _json(401, {'success': false, 'error': {'code': 'UNAUTHENTICATED'}}));
    await expectLater(client.get<Object?>('/account'), throwsA(isA<ApiException>().having((e) => e.code, 'code', 'UNAUTHENTICATED')));
    expect(unauthorized, 1);
  });

  test('connection failures map to NETWORK and GETs are retried once', () async {
    client = build((options) async => throw DioException.connectionError(requestOptions: options, reason: 'offline'));

    await expectLater(client.get<Object?>('/products'), throwsA(isA<ApiException>().having((e) => e.code, 'code', ApiException.network)));
    expect(adapter.requests, hasLength(2));

    await expectLater(client.post<Object?>('/orders'), throwsA(isA<ApiException>()));
    expect(adapter.requests, hasLength(3), reason: 'non-idempotent requests are not retried');
  });

  test('non-envelope responses are UNKNOWN', () async {
    client = build((_) async => _json(500, '<html>'));
    await expectLater(client.get<Object?>('/x'), throwsA(isA<ApiException>().having((e) => e.code, 'code', ApiException.unknown)));
  });
}
