/// Build-time configuration (`--dart-define`), so one binary per environment
/// can be produced without code changes.
class AppConfig {
  const AppConfig._();

  /// e.g. `--dart-define=API_BASE_URL=https://api.malawibento.mw/api`
  /// Default points at the Android emulator's alias for the host machine.
  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api',
  );

  /// Google Maps needs platform API keys; without them the location screen
  /// falls back to GPS + saved addresses.
  static const mapsEnabled = bool.fromEnvironment('MAPS_ENABLED');

  /// Country calling code shown in front of the phone field (data, not UI text).
  static const phoneCountryCode = '+265';
}
