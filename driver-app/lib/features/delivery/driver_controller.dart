import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import 'driver_repository.dart';
import 'location_source.dart';
import 'models.dart';
import 'offline_queues.dart';

final locationBufferProvider = Provider<LocationBuffer>(
  (ref) => LocationBuffer(ref.watch(sharedPreferencesProvider)),
);
final actionOutboxProvider = Provider<ActionOutbox>(
  (ref) => ActionOutbox(ref.watch(sharedPreferencesProvider)),
);

class DriverState {
  const DriverState({
    this.profile,
    this.online = false,
    this.active,
    this.request,
    this.pendingSync = false,
  });

  final DriverProfile? profile;
  final bool online;

  /// Current delivery (also cached on the phone so it survives restarts without signal).
  final Delivery? active;
  final DeliveryRequest? request;

  /// Actions or GPS points are waiting for connectivity.
  final bool pendingSync;

  DriverState copyWith({
    DriverProfile? profile,
    bool? online,
    Delivery? Function()? active,
    DeliveryRequest? Function()? request,
    bool? pendingSync,
  }) => DriverState(
    profile: profile ?? this.profile,
    online: online ?? this.online,
    active: active != null ? active() : this.active,
    request: request != null ? request() : this.request,
    pendingSync: pendingSync ?? this.pendingSync,
  );
}

final driverControllerProvider =
    NotifierProvider<DriverController, DriverState>(DriverController.new);

class DriverController extends Notifier<DriverState> {
  static const _activeKey = 'active_delivery';

  DriverRepository get _repo => ref.read(driverRepositoryProvider);

  @override
  DriverState build() {
    final cached = ref.read(sharedPreferencesProvider).getString(_activeKey);
    return DriverState(
      active: cached == null
          ? null
          : Delivery.fromJson(jsonDecode(cached) as Map<String, dynamic>),
    );
  }

  /// Periodic sync (driven by the home screen): replay offline work, then fetch state.
  Future<void> tick() async {
    final queued = !await ref.read(actionOutboxProvider).flush(_repo);
    final buffered = !await ref.read(locationBufferProvider).flush(_repo);
    state = state.copyWith(pendingSync: queued || buffered);
    if (!queued) await refresh();
  }

  Future<void> refresh() async {
    final snapshot = await _repo.me();
    final request = snapshot.profile.isOnline && snapshot.active == null
        ? (await _repo.requests()).firstOrNull
        : null;
    await _setActive(snapshot.active);
    state = state.copyWith(
      profile: snapshot.profile,
      online: snapshot.profile.isOnline,
      request: () => request,
    );
  }

  Future<void> goOnline() async {
    final position = await ref.read(locationSourceProvider).current();
    final profile = await _repo.goOnline(position);
    state = state.copyWith(profile: profile, online: true);
    await refresh();
  }

  Future<void> goOffline() async {
    final profile = await _repo.goOffline();
    state = state.copyWith(
      profile: profile,
      online: false,
      request: () => null,
    );
  }

  Future<void> accept() async {
    final request = state.request;
    if (request == null) return;
    try {
      final delivery = await _repo.accept(request.orderId);
      await _setActive(delivery);
    } finally {
      state = state.copyWith(request: () => null);
    }
  }

  Future<void> decline() async {
    final request = state.request;
    if (request == null) return;
    state = state.copyWith(request: () => null);
    await _repo.decline(request.orderId);
  }

  Future<void> pickup() =>
      _statusAction('pickup', 'ON_THE_WAY', (id) => _repo.pickup(id));

  Future<void> arrive() =>
      _statusAction('arrive', 'ARRIVED', (id) => _repo.arrive(id));

  /// Needs connectivity (the server checks the PIN). Returns the delivered order.
  Future<Delivery> complete(String pin) async {
    final delivered = await _repo.complete(state.active!.id, pin);
    await _setActive(null);
    return delivered;
  }

  Future<void> fail(String reasonCode) async {
    await _repo.fail(state.active!.id, reasonCode);
    await _setActive(null);
  }

  Future<void> recordPosition(GeoPoint point) async {
    await ref
        .read(locationBufferProvider)
        .add(
          GeoPoint(
            point.latitude,
            point.longitude,
            point.at,
            orderId: state.active?.id,
          ),
        );
  }

  /// Tries the action now; without signal it is queued and applied locally so the
  /// rider can keep going (the queue replays it later).
  Future<void> _statusAction(
    String action,
    String optimisticStatus,
    Future<Delivery> Function(int) call,
  ) async {
    final delivery = state.active!;
    try {
      await _setActive(await call(delivery.id));
    } catch (e) {
      if (!isConnectivityError(e)) rethrow;
      await ref.read(actionOutboxProvider).enqueue(action, delivery.id);
      await _setActive(delivery.withStatus(optimisticStatus));
      state = state.copyWith(pendingSync: true);
    }
  }

  Future<void> _setActive(Delivery? delivery) async {
    final prefs = ref.read(sharedPreferencesProvider);
    if (delivery == null) {
      await prefs.remove(_activeKey);
    } else {
      await prefs.setString(_activeKey, jsonEncode(_toJson(delivery)));
    }
    state = state.copyWith(active: () => delivery);
  }

  static Map<String, dynamic> _toJson(Delivery d) => {
    'id': d.id,
    'order_number': d.orderNumber,
    'status': d.status,
    'payment_method': d.paymentMethod,
    'currency': d.currency,
    'amount_to_collect': d.amountToCollect,
    'item_count': d.itemCount,
    'distance_km': d.distanceKm,
    'pin_attempts_left': d.pinAttemptsLeft,
    'delivered_at': d.deliveredAt?.toIso8601String(),
    'pickup': {
      'name': d.pickup.name,
      'phone': d.pickup.phone,
      'address': d.pickup.lines.firstOrNull,
      'latitude': d.pickup.latitude,
      'longitude': d.pickup.longitude,
    },
    'dropoff': {
      'name': d.dropoff.name,
      'phone': d.dropoff.phone,
      'latitude': d.dropoff.latitude,
      'longitude': d.dropoff.longitude,
      'address': {
        'area': d.dropoff.lines.join(', '),
        'landmark': d.dropoff.landmark,
        'delivery_note': d.dropoff.note,
      },
    },
  };
}
