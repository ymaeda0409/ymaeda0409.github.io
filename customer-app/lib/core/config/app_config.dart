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

  /// Public web demo: the API is answered in the browser by `DemoBackend`
  /// (`--dart-define=DEMO_MODE=true`). Never enabled for store builds.
  static const demoMode = bool.fromEnvironment('DEMO_MODE');

  /// Map tiles for live tracking (no API key). OpenStreetMap's public servers suit
  /// low traffic only; production should point this at a tile provider.
  static const mapTileUrl = String.fromEnvironment(
    'MAP_TILE_URL',
    defaultValue: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
  );

  /// Country calling code shown in front of the phone field (data, not UI text).
  static const phoneCountryCode = '+265';
}
