import 'package:flutter/foundation.dart';

import '../network/api_client.dart';

/// Source of the push (FCM) registration token. The default returns null so the apps run
/// without a Firebase project; plug in `firebase_messaging` by implementing this.
abstract class PushTokenSource {
  Future<String?> token();
}

class NoPushTokenSource implements PushTokenSource {
  const NoPushTokenSource();

  @override
  Future<String?> token() async => null;
}

/// Registers this device's push token with the API (`POST /devices`) after sign-in and
/// removes it on sign-out, so notifications reach the right person in their language.
class DeviceRegistrar {
  DeviceRegistrar(this._api, this._source, {required this.app});

  final ApiClient _api;
  final PushTokenSource _source;

  /// `customer` or `driver`.
  final String app;

  static String get platform => kIsWeb
      ? 'web'
      : switch (defaultTargetPlatform) {
          TargetPlatform.iOS => 'ios',
          _ => 'android',
        };

  /// Best effort: failures never block sign-in.
  Future<bool> register() async {
    try {
      final token = await _source.token();
      if (token == null) return false;
      await _api.post<Object?>(
        '/devices',
        body: {'token': token, 'platform': platform, 'app': app},
      );
      return true;
    } catch (_) {
      return false;
    }
  }

  Future<void> unregister() async {
    try {
      final token = await _source.token();
      if (token != null) {
        await _api.delete<Object?>(
          '/devices?token=${Uri.encodeQueryComponent(token)}',
        );
      }
    } catch (_) {
      // Signing out works offline too; the server drops dead tokens when FCM reports them.
    }
  }
}
