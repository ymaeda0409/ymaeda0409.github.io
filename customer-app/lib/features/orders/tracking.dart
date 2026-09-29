import 'dart:math' as math;

import 'package:bento_core/bento_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

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
    this.rider,
  });

  factory Tracking.fromJson(Map<String, dynamic> json) {
    final driver = json['driver'] as Map<String, dynamic>?;
    final dropoff = json['dropoff'] as Map<String, dynamic>;
    return Tracking(
      status: json['status'] as String,
      dropoffLat: (dropoff['latitude'] as num).toDouble(),
      dropoffLng: (dropoff['longitude'] as num).toDouble(),
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
  final RiderPosition? rider;

  /// Straight-line distance rider → customer.
  double? get riderDistanceKm {
    final r = rider;
    if (r == null) return null;
    const earth = 6371.0;
    double rad(double d) => d * math.pi / 180;
    final dLat = rad(dropoffLat - r.latitude);
    final dLng = rad(dropoffLng - r.longitude);
    final a =
        math.pow(math.sin(dLat / 2), 2) +
        math.cos(rad(r.latitude)) *
            math.cos(rad(dropoffLat)) *
            math.pow(math.sin(dLng / 2), 2);
    return earth * 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a));
  }
}

final trackingProvider = FutureProvider.autoDispose.family<Tracking, int>(
  (ref, orderId) => ref.watch(orderRepositoryProvider).tracking(orderId),
);

/// 13 Order Tracking: rider position (map when Google Maps is configured, text otherwise).
class TrackingCard extends ConsumerWidget {
  const TrackingCard({super.key, required this.orderId});

  final int orderId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final tracking = ref.watch(trackingProvider(orderId)).value;
    final rider = tracking?.rider;
    if (tracking == null || rider == null) return const SizedBox.shrink();

    final l = context.l10n;
    final vehicle = switch (rider.vehicleType) {
      'MOTORBIKE' => l.vehicle_motorbike,
      'BICYCLE' => l.vehicle_bicycle,
      'CAR' => l.vehicle_car,
      _ => rider.vehicleType,
    };
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              l.tracking_title,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            if (AppConfig.mapsEnabled) ...[
              const SizedBox(height: 8),
              SizedBox(
                height: 200,
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(12),
                  child: GoogleMap(
                    initialCameraPosition: CameraPosition(
                      target: LatLng(rider.latitude, rider.longitude),
                      zoom: 14,
                    ),
                    markers: {
                      Marker(
                        markerId: const MarkerId('rider'),
                        position: LatLng(rider.latitude, rider.longitude),
                      ),
                      Marker(
                        markerId: const MarkerId('home'),
                        position: LatLng(
                          tracking.dropoffLat,
                          tracking.dropoffLng,
                        ),
                        icon: BitmapDescriptor.defaultMarkerWithHue(
                          BitmapDescriptor.hueGreen,
                        ),
                      ),
                    },
                    zoomControlsEnabled: false,
                  ),
                ),
              ),
            ],
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
