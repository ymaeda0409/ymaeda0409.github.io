/// Build-time configuration (`--dart-define`).
class AppConfig {
  const AppConfig._();

  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api',
  );

  /// Country calling code shown in front of the phone field (data, not UI text).
  static const phoneCountryCode = '+265';
}
