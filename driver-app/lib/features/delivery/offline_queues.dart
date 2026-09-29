import 'dart:convert';

import 'package:bento_core/bento_core.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'driver_repository.dart';
import 'models.dart';

bool isConnectivityError(Object e) =>
    e is ApiException &&
    (e.code == ApiException.network || e.code == ApiException.timeout);

/// GPS points kept on the phone until they reach the server (patchy mobile data).
class LocationBuffer {
  LocationBuffer(this._prefs);

  static const _key = 'gps_buffer';

  /// Keep at most this many points (~4 h at one point/minute); oldest are dropped first.
  static const capacity = 500;

  final SharedPreferences _prefs;

  List<GeoPoint> get points => (_prefs.getStringList(_key) ?? const [])
      .map((s) => GeoPoint.fromJson(jsonDecode(s) as Map<String, dynamic>))
      .toList();

  Future<void> add(GeoPoint point) async {
    final all = [
      ...(_prefs.getStringList(_key) ?? const <String>[]),
      jsonEncode(point.toJson()),
    ];
    await _prefs.setStringList(
      _key,
      all.length > capacity ? all.sublist(all.length - capacity) : all,
    );
  }

  /// Sends everything buffered; keeps the points if the network is down.
  Future<bool> flush(DriverRepository repository) async {
    final pending = points;
    if (pending.isEmpty) return true;
    try {
      await repository.sendLocations(pending);
    } catch (e) {
      if (isConnectivityError(e)) return false;
      rethrow;
    }
    final remaining = (_prefs.getStringList(_key) ?? const <String>[])
        .skip(pending.length)
        .toList();
    await _prefs.setStringList(_key, remaining);
    return true;
  }
}

/// Status actions (pickup / arrive) done without signal are replayed when back online.
/// The PIN hand-over is never queued: it needs the server to verify the PIN.
class ActionOutbox {
  ActionOutbox(this._prefs);

  static const _key = 'outbox';

  final SharedPreferences _prefs;

  List<({String action, int orderId})> get pending =>
      (_prefs.getStringList(_key) ?? const [])
          .map((s) => jsonDecode(s) as Map<String, dynamic>)
          .map(
            (m) =>
                (action: m['action'] as String, orderId: m['order_id'] as int),
          )
          .toList();

  Future<void> enqueue(String action, int orderId) async {
    await _prefs.setStringList(_key, [
      ...(_prefs.getStringList(_key) ?? const <String>[]),
      jsonEncode({'action': action, 'order_id': orderId}),
    ]);
  }

  /// Replays queued actions in order. Returns true when the queue is empty afterwards.
  Future<bool> flush(DriverRepository repository) async {
    final queue = pending;
    var done = 0;
    for (final item in queue) {
      try {
        await _run(repository, item.action, item.orderId);
      } catch (e) {
        if (isConnectivityError(e)) break;
        // Already applied (e.g. retried after a lost response) or no longer valid: drop it.
        if (e is! ApiException) rethrow;
      }
      done++;
    }
    await _prefs.setStringList(
      _key,
      (_prefs.getStringList(_key) ?? const <String>[]).skip(done).toList(),
    );
    return done == queue.length;
  }

  static Future<Delivery> _run(
    DriverRepository repository,
    String action,
    int orderId,
  ) => switch (action) {
    'pickup' => repository.pickup(orderId),
    'arrive' => repository.arrive(orderId),
    _ => throw ArgumentError(action),
  };
}
