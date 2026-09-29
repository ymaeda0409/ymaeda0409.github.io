import 'package:bento_core/bento_core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import 'models.dart';

final driverRepositoryProvider = Provider<DriverRepository>(
  (ref) => ApiDriverRepository(ref.watch(apiClientProvider)),
);

class DriverSnapshot {
  const DriverSnapshot({required this.profile, this.active});

  final DriverProfile profile;
  final Delivery? active;
}

abstract class DriverRepository {
  Future<DriverSnapshot> me();
  Future<DriverProfile> goOnline(GeoPoint? position);
  Future<DriverProfile> goOffline();
  Future<List<DeliveryRequest>> requests();
  Future<Delivery> accept(int orderId);
  Future<void> decline(int orderId);
  Future<Delivery> pickup(int orderId);
  Future<Delivery> arrive(int orderId);
  Future<Delivery> complete(int orderId, String pin);
  Future<Delivery> fail(int orderId, String reasonCode);
  Future<List<Delivery>> history();
  Future<void> sendLocations(List<GeoPoint> points);
}

class ApiDriverRepository implements DriverRepository {
  ApiDriverRepository(this._api);

  final ApiClient _api;

  @override
  Future<DriverSnapshot> me() async {
    final data = await _api.get<Map<String, dynamic>>('/driver/me');
    final active = data['active_delivery'] as Map<String, dynamic>?;
    return DriverSnapshot(
      profile: DriverProfile.fromJson(data['driver'] as Map<String, dynamic>),
      active: active == null ? null : Delivery.fromJson(active),
    );
  }

  @override
  Future<DriverProfile> goOnline(GeoPoint? position) async =>
      DriverProfile.fromJson(
        await _api.post<Map<String, dynamic>>(
          '/driver/online',
          body: position == null
              ? {}
              : {
                  'latitude': position.latitude,
                  'longitude': position.longitude,
                },
        ),
      );

  @override
  Future<DriverProfile> goOffline() async => DriverProfile.fromJson(
    await _api.post<Map<String, dynamic>>('/driver/offline'),
  );

  @override
  Future<List<DeliveryRequest>> requests() async =>
      (await _api.get<List<dynamic>>('/driver/delivery-requests'))
          .map((r) => DeliveryRequest.fromJson(r as Map<String, dynamic>))
          .toList();

  @override
  Future<Delivery> accept(int orderId) => _action(orderId, 'accept');

  @override
  Future<void> decline(int orderId) =>
      _api.post<Object?>('/driver/deliveries/$orderId/decline');

  @override
  Future<Delivery> pickup(int orderId) => _action(orderId, 'pickup');

  @override
  Future<Delivery> arrive(int orderId) => _action(orderId, 'arrive');

  @override
  Future<Delivery> complete(int orderId, String pin) =>
      _action(orderId, 'complete', {'pin': pin});

  @override
  Future<Delivery> fail(int orderId, String reasonCode) =>
      _action(orderId, 'fail', {'reason_code': reasonCode});

  @override
  Future<List<Delivery>> history() async =>
      (await _api.get<List<dynamic>>('/driver/deliveries'))
          .map((d) => Delivery.fromJson(d as Map<String, dynamic>))
          .toList();

  @override
  Future<void> sendLocations(List<GeoPoint> points) => _api.post<Object?>(
    '/driver/location',
    body: {'points': points.map((p) => p.toJson()).toList()},
  );

  Future<Delivery> _action(
    int orderId,
    String action, [
    Map<String, dynamic>? body,
  ]) async => Delivery.fromJson(
    await _api.post<Map<String, dynamic>>(
      '/driver/deliveries/$orderId/$action',
      body: body ?? {},
    ),
  );
}
