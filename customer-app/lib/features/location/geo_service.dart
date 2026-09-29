import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';

final geoServiceProvider = Provider<GeoService>((ref) => const GeoService());

class GeoPoint {
  const GeoPoint(this.latitude, this.longitude);

  final double latitude;
  final double longitude;
}

class LocationPermissionDenied implements Exception {
  const LocationPermissionDenied();
}

/// Wraps the GPS plugin so screens stay testable.
class GeoService {
  const GeoService();

  Future<GeoPoint> current() async {
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
      throw const LocationPermissionDenied();
    }
    final p = await Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(accuracy: LocationAccuracy.high, timeLimit: Duration(seconds: 20)),
    );
    return GeoPoint(p.latitude, p.longitude);
  }
}
