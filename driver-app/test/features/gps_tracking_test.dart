import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:geolocator/geolocator.dart';
import 'package:malawi_bento_driver/features/delivery/driver_controller.dart';
import 'package:malawi_bento_driver/features/delivery/location_source.dart';

import '../helpers.dart';

void main() {
  testWidgets(
    'a waiting rider who does not move still reports GPS (heartbeat)',
    (tester) async {
      final repo = FakeDriverRepository()..hasRequest = false;
      final container = await pumpRider(tester, repo: repo);
      final controller = container.read(driverControllerProvider.notifier);

      // Offline riders are never tracked.
      await controller.tick();
      expect(repo.sentLocations, isEmpty);

      await tester.tap(find.text('GO ONLINE'));
      // Waiting for requests shows a spinner, so pump a fixed time instead of settling.
      await tester.pump(const Duration(seconds: 1));
      repo.sentLocations.clear();

      // No movement events at all: the sync tick takes a fix by itself.
      await controller.tick();
      expect(repo.sentLocations.expand((b) => b), hasLength(1));
      expect(repo.sentLocations.single.single.latitude, -13.96);

      // Within the heartbeat interval nothing extra is sent.
      await controller.tick();
      expect(repo.sentLocations.expand((b) => b), hasLength(1));

      await unmount(tester);
    },
  );

  group('background tracking settings', () {
    const notice = BackgroundNotice(
      title: 'Sharing your location',
      text: 'Only while you are online.',
    );

    tearDown(() => debugDefaultTargetPlatformOverride = null);

    test('Android keeps GPS alive with a foreground-service notification', () {
      debugDefaultTargetPlatformOverride = TargetPlatform.android;
      final settings =
          GeolocatorLocationSource.trackingSettings(notice) as AndroidSettings;
      expect(settings.distanceFilter, 30);
      expect(settings.intervalDuration, const Duration(seconds: 10));
      expect(
        settings.foregroundNotificationConfig?.notificationTitle,
        'Sharing your location',
      );
      expect(settings.foregroundNotificationConfig?.setOngoing, isTrue);
    });

    test('iOS allows background updates and shows the indicator', () {
      debugDefaultTargetPlatformOverride = TargetPlatform.iOS;
      final settings =
          GeolocatorLocationSource.trackingSettings(notice) as AppleSettings;
      expect(settings.allowBackgroundLocationUpdates, isTrue);
      expect(settings.pauseLocationUpdatesAutomatically, isFalse);
      expect(settings.showBackgroundLocationIndicator, isTrue);
    });
  });
}
