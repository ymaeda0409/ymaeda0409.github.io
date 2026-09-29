import 'package:bento_core/bento_core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepository(ref.watch(apiClientProvider)),
);

class NotADriver implements Exception {
  const NotADriver();
}

class AuthRepository {
  AuthRepository(this._api);

  final ApiClient _api;

  Future<String> sendOtp(String phone) async =>
      (await _api.post<Map<String, dynamic>>(
            '/auth/send-otp',
            body: {'phone': phone},
          ))['phone']
          as String;

  /// Returns the token. Only DRIVER accounts may use this app.
  Future<String> verifyOtp(String phone, String code) async {
    final data = await _api.post<Map<String, dynamic>>(
      '/auth/verify-otp',
      body: {'phone': phone, 'code': code, 'device_name': 'driver-app'},
    );
    final user = data['user'] as Map<String, dynamic>;
    if (user['role'] != 'DRIVER') throw const NotADriver();
    return data['token'] as String;
  }

  Future<void> logout() => _api.post<Object?>('/auth/logout');
}
