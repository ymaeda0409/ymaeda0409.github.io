import 'dart:async';
import 'dart:math' as math;

import 'package:bento_core/bento_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:latlong2/latlong.dart';

import '../../core/config/app_config.dart';
import '../../core/ui/widgets.dart';
import '../checkout/order_repository.dart';

class RiderPosition {
  const RiderPosition({
    required this.vehicleType,
    required this.latitude,
    required this.longitude,
    this.name,
    this.updatedAt,
  });

  final String? name;
  final String vehicleType;
  final double latitude;
  final double longitude;
  final DateTime? updatedAt;
}

class Tracking {
  const Tracking({
    required this.status,
    required this.dropoffLat,
    required this.dropoffLng,
    this.pickupLat,
    this.pickupLng,
    this.rider,
  });

  factory Tracking.fromJson(Map<String, dynamic> json) {
    final driver = json['driver'] as Map<String, dynamic>?;
    final dropoff = json['dropoff'] as Map<String, dynamic>;
    final pickup = json['pickup'] as Map<String, dynamic>?;
    return Tracking(
      status: json['status'] as String,
      dropoffLat: (dropoff['latitude'] as num).toDouble(),
      dropoffLng: (dropoff['longitude'] as num).toDouble(),
      pickupLat: (pickup?['latitude'] as num?)?.toDouble(),
      pickupLng: (pickup?['longitude'] as num?)?.toDouble(),
      rider: driver == null || driver['latitude'] == null
          ? null
          : RiderPosition(
              name: driver['name'] as String?,
              vehicleType: driver['vehicle_type'] as String,
              latitude: (driver['latitude'] as num).toDouble(),
              longitude: (driver['longitude'] as num).toDouble(),
              updatedAt: driver['updated_at'] == null
                  ? null
                  : DateTime.parse(driver['updated_at'] as String),
            ),
    );
  }

  final String status;
  final double dropoffLat;
  final double dropoffLng;
  final double? pickupLat;
  final double? pickupLng;
  final RiderPosition? rider;

  /// Straight-line distance rider → customer.
  double? get riderDistanceKm {
    final r = rider;
    if (r == null) return null;
    return distanceKm(r.latitude, r.longitude, dropoffLat, dropoffLng);
  }

  /// Rough arrival estimate while the rider is on the way: straight-line distance
  /// × 1.3 (city roads) at a typical speed for the vehicle, never below 1 minute.
  int? get etaMinutes {
    final km = riderDistanceKm;
    if (km == null || !const {'PICKED_UP', 'ON_THE_WAY'}.contains(status)) {
      return null;
    }
    final kmh = switch (rider!.vehicleType) {
      'BICYCLE' => 12.0,
      'CAR' => 20.0,
      _ => 25.0, // motorbike
    };
    return math.max(1, (km * 1.3 / kmh * 60).ceil());
  }

  static double distanceKm(double lat1, double lng1, double lat2, double lng2) {
    const earth = 6371.0;
    double rad(double d) => d * math.pi / 180;
    final dLat = rad(lat2 - lat1);
    final dLng = rad(lng2 - lng1);
    final a =
        math.pow(math.sin(dLat / 2), 2) +
        math.cos(rad(lat1)) *
            math.cos(rad(lat2)) *
            math.pow(math.sin(dLng / 2), 2);
    return earth * 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a));
  }
}

final trackingProvider = FutureProvider.autoDispose.family<Tracking, int>(
  (ref, orderId) => ref.watch(orderRepositoryProvider).tracking(orderId),
);

/// How often the rider position is refreshed while a rider is assigned.
final trackingRefreshProvider = Provider<Duration>(
  (ref) => const Duration(seconds: 10),
);

/// Map tiles are fetched from the network; tests switch them off.
final mapTilesEnabledProvider = Provider<bool>((ref) => true);

/// 13 Order Tracking: live map (OpenStreetMap tiles, no API key) with the rider,
/// the store and the delivery address, plus distance and arrival estimate.
class TrackingCard extends ConsumerStatefulWidget {
  const TrackingCard({super.key, required this.orderId});

  final int orderId;

  @override
  ConsumerState<TrackingCard> createState() => _TrackingCardState();
}

class _TrackingCardState extends ConsumerState<TrackingCard> {
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(ref.read(trackingRefreshProvider), (_) {
      // Only poll while a rider is actually moving towards the customer.
      if (ref.read(trackingProvider(widget.orderId)).value?.rider != null) {
        ref.invalidate(trackingProvider(widget.orderId));
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final tracking = ref.watch(trackingProvider(widget.orderId)).value;
    final rider = tracking?.rider;
    if (tracking == null || rider == null) return const SizedBox.shrink();

    final l = context.l10n;
    final theme = Theme.of(context);
    final vehicle = switch (rider.vehicleType) {
      'MOTORBIKE' => l.vehicle_motorbike,
      'BICYCLE' => l.vehicle_bicycle,
      'CAR' => l.vehicle_car,
      _ => rider.vehicleType,
    };
    final eta = tracking.etaMinutes;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(l.tracking_title, style: theme.textTheme.titleMedium),
            if (eta != null) ...[
              const SizedBox(height: 4),
              Text(
                l.tracking_eta(eta),
                style: theme.textTheme.titleLarge?.copyWith(
                  color: theme.colorScheme.primary,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
            const SizedBox(height: 8),
            Semantics(
              label: l.tracking_map_label,
              child: SizedBox(
                height: 220,
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(12),
                  child: _TrackingMap(tracking: tracking),
                ),
              ),
            ),
            ListTile(
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.delivery_dining, size: 36),
              title: Text(
                [
                  l.tracking_rider,
                  if (rider.name != null) rider.name!,
                ].join(' · '),
              ),
              subtitle: Text(
                [
                  vehicle,
                  l.tracking_distance(
                    formatDecimal(
                      tracking.riderDistanceKm!,
                      context.locale,
                      digits: 1,
                    ),
                  ),
                  if (rider.updatedAt != null)
                    l.tracking_updated(
                      formatTime(rider.updatedAt!, context.locale),
                    ),
                ].join('\n'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TrackingMap extends ConsumerWidget {
  const _TrackingMap({required this.tracking});

  final Tracking tracking;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final scheme = Theme.of(context).colorScheme;
    final rider = tracking.rider!;
    final riderPoint = LatLng(rider.latitude, rider.longitude);
    final home = LatLng(tracking.dropoffLat, tracking.dropoffLng);
    final store = tracking.pickupLat == null
        ? null
        : LatLng(tracking.pickupLat!, tracking.pickupLng!);

    Marker pin(LatLng at, IconData icon, Color color, {double size = 36}) =>
        Marker(
          point: at,
          width: size,
          height: size,
          child: DecoratedBox(
            decoration: BoxDecoration(
              color: color,
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white, width: 2),
              boxShadow: const [
                BoxShadow(blurRadius: 4, color: Colors.black26),
              ],
            ),
            child: Icon(icon, color: Colors.white, size: size * 0.55),
          ),
        );

    return FlutterMap(
      options: MapOptions(
        initialCameraFit: CameraFit.coordinates(
          coordinates: [riderPoint, home, ?store],
          padding: const EdgeInsets.all(36),
          maxZoom: 16,
        ),
        interactionOptions: const InteractionOptions(
          flags: InteractiveFlag.pinchZoom | InteractiveFlag.drag,
        ),
      ),
      children: [
        if (ref.watch(mapTilesEnabledProvider))
          TileLayer(
            urlTemplate: AppConfig.mapTileUrl,
            userAgentPackageName: 'mw.malawibento.customer',
          ),
        PolylineLayer(
          polylines: [
            Polyline(
              points: [riderPoint, home],
              color: scheme.primary.withValues(alpha: 0.7),
              strokeWidth: 4,
              pattern: StrokePattern.dashed(segments: const [10, 8]),
            ),
          ],
        ),
        MarkerLayer(
          markers: [
            if (store != null)
              pin(store, Icons.storefront, scheme.secondary, size: 30),
            pin(home, Icons.home, scheme.tertiary, size: 32),
            pin(riderPoint, Icons.delivery_dining, scheme.primary, size: 40),
          ],
        ),
        // Map data licence (ODbL) requires visible credit.
        Align(
          alignment: AlignmentDirectional.bottomEnd,
          child: Container(
            color: Colors.white70,
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
            child: const Text(
              '© OpenStreetMap',
              style: TextStyle(fontSize: 10, color: Colors.black87),
            ),
          ),
        ),
      ],
    );
  }
}
