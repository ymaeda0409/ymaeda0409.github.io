import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../core/providers.dart';
import 'user.dart';

final authRepositoryProvider = Provider<AuthRepository>((ref) => AuthRepository(ref.watch(apiClientProvider)));

class OtpRequest {
  const OtpRequest({required this.phone, required this.expiresIn, required this.resendIn});

  final String phone;
  final int expiresIn;
  final int resendIn;
}

class AuthResult {
  const AuthResult({required this.token, required this.user, required this.isNewUser});

  final String token;
  final AppUser user;
  final bool isNewUser;
}

class AuthRepository {
  AuthRepository(this._api);

  final ApiClient _api;

  Future<OtpRequest> sendOtp(String phone) async {
    final data = await _api.post<Map<String, dynamic>>('/auth/send-otp', body: {'phone': phone});
    return OtpRequest(
      phone: data['phone'] as String,
      expiresIn: data['expires_in'] as int,
      resendIn: data['resend_in'] as int,
    );
  }

  Future<AuthResult> verifyOtp(String phone, String code, {required String language}) async {
    final data = await _api.post<Map<String, dynamic>>('/auth/verify-otp', body: {
      'phone': phone,
      'code': code,
      'device_name': 'customer-app',
      'preferred_language': language,
    });
    return AuthResult(
      token: data['token'] as String,
      user: AppUser.fromJson(data['user'] as Map<String, dynamic>),
      isNewUser: data['is_new_user'] as bool,
    );
  }

  Future<AppUser> me() async => AppUser.fromJson(await _api.get<Map<String, dynamic>>('/account'));

  Future<AppUser> updateName(String name) async =>
      AppUser.fromJson(await _api.put<Map<String, dynamic>>('/account', body: {'name': name}));

  Future<void> logout() => _api.post<Object?>('/auth/logout');
}
