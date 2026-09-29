import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';

import 'models.dart';

final locationSourceProvider = Provider<LocationSource>(
  (ref) => const GeolocatorLocationSource(),
);

class LocationPermissionDenied implements Exception {
  const LocationPermissionDenied();
}

/// GPS abstraction so screens and the tracker are testable.
abstract class LocationSource {
  Future<GeoPoint> current();

  /// Position updates while delivering (every ~30 m of movement).
  Stream<GeoPoint> watch();
}

class GeolocatorLocationSource implements LocationSource {
  const GeolocatorLocationSource();

  Future<void> _ensurePermission() async {
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      throw const LocationPermissionDenied();
    }
  }

  @override
  Future<GeoPoint> current() async {
    await _ensurePermission();
    final p = await Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.high,
        timeLimit: Duration(seconds: 20),
      ),
    );
    return GeoPoint(p.latitude, p.longitude, p.timestamp);
  }

  @override
  Stream<GeoPoint> watch() async* {
    await _ensurePermission();
    yield* Geolocator.getPositionStream(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.high,
        distanceFilter: 30,
      ),
    ).map((p) => GeoPoint(p.latitude, p.longitude, p.timestamp));
  }
}
