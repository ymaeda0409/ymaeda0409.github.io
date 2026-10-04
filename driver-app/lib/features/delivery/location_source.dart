import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';

import 'models.dart';

final locationSourceProvider = Provider<LocationSource>(
  (ref) => const GeolocatorLocationSource(),
);

/// Text of the ongoing notification Android requires while location is tracked with
/// the screen off (foreground service). Passed in already translated.
class BackgroundNotice {
  const BackgroundNotice({required this.title, required this.text});

  final String title;
  final String text;
}

class LocationPermissionDenied implements Exception {
  const LocationPermissionDenied();
}

/// GPS abstraction so screens and the tracker are testable.
abstract class LocationSource {
  Future<GeoPoint> current();

  /// Position updates while online (every ~30 m of movement), continuing with the
  /// screen locked or the app in the background.
  Stream<GeoPoint> watch({BackgroundNotice? notice});
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
  Stream<GeoPoint> watch({BackgroundNotice? notice}) async* {
    await _ensurePermission();
    yield* Geolocator.getPositionStream(
      locationSettings: trackingSettings(notice),
    ).map((p) => GeoPoint(p.latitude, p.longitude, p.timestamp));
  }

  /// Android: a foreground service with an ongoing notification keeps GPS running when
  /// the rider locks the phone. iOS: background location updates (blue status bar
  /// indicator) with the "location" background mode in Info.plist.
  @visibleForTesting
  static LocationSettings trackingSettings(BackgroundNotice? notice) {
    const accuracy = LocationAccuracy.high;
    const distanceFilter = 30;
    if (kIsWeb) {
      return const LocationSettings(
        accuracy: accuracy,
        distanceFilter: distanceFilter,
      );
    }
    return switch (defaultTargetPlatform) {
      TargetPlatform.android => AndroidSettings(
        accuracy: accuracy,
        distanceFilter: distanceFilter,
        intervalDuration: const Duration(seconds: 10),
        foregroundNotificationConfig: notice == null
            ? null
            : ForegroundNotificationConfig(
                notificationTitle: notice.title,
                notificationText: notice.text,
                enableWakeLock: true,
                setOngoing: true,
              ),
      ),
      TargetPlatform.iOS => AppleSettings(
        accuracy: accuracy,
        distanceFilter: distanceFilter,
        activityType: ActivityType.otherNavigation,
        pauseLocationUpdatesAutomatically: false,
        allowBackgroundLocationUpdates: true,
        showBackgroundLocationIndicator: true,
      ),
      _ => const LocationSettings(
        accuracy: accuracy,
        distanceFilter: distanceFilter,
      ),
    };
  }
}
