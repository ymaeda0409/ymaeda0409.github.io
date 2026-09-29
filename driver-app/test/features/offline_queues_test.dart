import 'package:flutter_test/flutter_test.dart';
import 'package:malawi_bento_driver/features/delivery/models.dart';
import 'package:malawi_bento_driver/features/delivery/offline_queues.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../helpers.dart';

void main() {
  late SharedPreferences prefs;

  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    prefs = await SharedPreferences.getInstance();
  });

  test(
    'GPS points survive a network outage and are sent later in one batch',
    () async {
      final buffer = LocationBuffer(prefs);
      final repo = FakeDriverRepository()..offlineNetwork = true;
      await buffer.add(
        GeoPoint(-13.97, 33.78, DateTime.utc(2026, 9, 29, 10), orderId: 7),
      );
      await buffer.add(
        GeoPoint(-13.96, 33.77, DateTime.utc(2026, 9, 29, 10, 1), orderId: 7),
      );

      expect(await buffer.flush(repo), isFalse);
      expect(buffer.points, hasLength(2));

      repo.offlineNetwork = false;
      expect(await buffer.flush(repo), isTrue);
      expect(buffer.points, isEmpty);
      expect(repo.sentLocations.single.map((p) => p.at), [
        DateTime.utc(2026, 9, 29, 10),
        DateTime.utc(2026, 9, 29, 10, 1),
      ]);
      expect(repo.sentLocations.single.first.orderId, 7);
    },
  );

  test('buffer keeps only the newest points', () async {
    final buffer = LocationBuffer(prefs);
    for (var i = 0; i < LocationBuffer.capacity + 10; i++) {
      await buffer.add(
        GeoPoint(
          -13.9,
          33.7,
          DateTime.utc(2026, 1, 1).add(Duration(minutes: i)),
        ),
      );
    }
    expect(buffer.points, hasLength(LocationBuffer.capacity));
    expect(
      buffer.points.first.at,
      DateTime.utc(2026, 1, 1).add(const Duration(minutes: 10)),
    );
  });

  test(
    'queued actions replay in order; already-applied ones are dropped',
    () async {
      final outbox = ActionOutbox(prefs);
      final repo = FakeDriverRepository()
        ..active = Delivery.fromJson(deliveryJson());
      await outbox.enqueue('pickup', 7);
      await outbox.enqueue('arrive', 7);

      repo.offlineNetwork = true;
      expect(await outbox.flush(repo), isFalse);
      expect(outbox.pending, hasLength(2));

      repo.offlineNetwork = false;
      expect(await outbox.flush(repo), isTrue);
      expect(repo.calls, ['pickup', 'arrive']);
      expect(outbox.pending, isEmpty);

      // A replay of an action the server already applied is discarded, not retried forever.
      await outbox.enqueue('pickup', 7);
      expect(await outbox.flush(repo), isTrue);
      expect(outbox.pending, isEmpty);
    },
  );
}
