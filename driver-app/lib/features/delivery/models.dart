/// A delivery as seen by the rider (no customer PIN — the customer says it at hand-over).
class Place {
  const Place({
    this.name,
    this.phone,
    required this.latitude,
    required this.longitude,
    this.lines = const [],
    this.landmark,
    this.note,
  });

  final String? name;
  final String? phone;
  final double latitude;
  final double longitude;

  /// User-entered address parts (area, street, building …), shown as-is.
  final List<String> lines;
  final String? landmark;
  final String? note;
}

class Delivery {
  const Delivery({
    required this.id,
    required this.orderNumber,
    required this.status,
    required this.paymentMethod,
    required this.currency,
    required this.amountToCollect,
    required this.itemCount,
    required this.pickup,
    required this.dropoff,
    required this.distanceKm,
    required this.pinAttemptsLeft,
    this.deliveredAt,
  });

  factory Delivery.fromJson(Map<String, dynamic> json) {
    final pickup = json['pickup'] as Map<String, dynamic>;
    final dropoff = json['dropoff'] as Map<String, dynamic>;
    final address =
        (dropoff['address'] as Map?)?.cast<String, dynamic>() ?? const {};
    return Delivery(
      id: json['id'] as int,
      orderNumber: json['order_number'] as String,
      status: json['status'] as String,
      paymentMethod: json['payment_method'] as String,
      currency: json['currency'] as String,
      amountToCollect: json['amount_to_collect'] as int,
      itemCount: json['item_count'] as int,
      pickup: Place(
        name: pickup['name'] as String?,
        phone: pickup['phone'] as String?,
        latitude: (pickup['latitude'] as num).toDouble(),
        longitude: (pickup['longitude'] as num).toDouble(),
        lines: [if (pickup['address'] != null) pickup['address'] as String],
      ),
      dropoff: Place(
        name: dropoff['name'] as String?,
        phone: dropoff['phone'] as String?,
        latitude: (dropoff['latitude'] as num).toDouble(),
        longitude: (dropoff['longitude'] as num).toDouble(),
        lines: [
          for (final key in ['name', 'area', 'street', 'building'])
            if ((address[key] as String?)?.isNotEmpty ?? false)
              address[key] as String,
        ],
        landmark: address['landmark'] as String?,
        note: address['delivery_note'] as String?,
      ),
      distanceKm: (json['distance_km'] as num).toDouble(),
      pinAttemptsLeft: json['pin_attempts_left'] as int,
      deliveredAt: json['delivered_at'] == null
          ? null
          : DateTime.parse(json['delivered_at'] as String),
    );
  }

  final int id;
  final String orderNumber;
  final String status;
  final String paymentMethod;
  final String currency;
  final int amountToCollect;
  final int itemCount;
  final Place pickup;
  final Place dropoff;
  final double distanceKm;
  final int pinAttemptsLeft;
  final DateTime? deliveredAt;

  Delivery withStatus(String next) => Delivery(
    id: id,
    orderNumber: orderNumber,
    status: next,
    paymentMethod: paymentMethod,
    currency: currency,
    amountToCollect: amountToCollect,
    itemCount: itemCount,
    pickup: pickup,
    dropoff: dropoff,
    distanceKm: distanceKm,
    pinAttemptsLeft: pinAttemptsLeft,
    deliveredAt: deliveredAt,
  );
}

class DeliveryRequest {
  const DeliveryRequest({
    required this.orderId,
    required this.expiresAt,
    required this.distanceToPickupKm,
    required this.delivery,
  });

  factory DeliveryRequest.fromJson(
    Map<String, dynamic> json, {
    DateTime? now,
  }) => DeliveryRequest(
    orderId: json['order_id'] as int,
    // Server-relative expiry avoids trusting the phone's clock.
    expiresAt: (now ?? DateTime.now()).add(
      Duration(seconds: json['expires_in'] as int),
    ),
    distanceToPickupKm: (json['distance_to_pickup_km'] as num?)?.toDouble(),
    delivery: Delivery.fromJson(json['delivery'] as Map<String, dynamic>),
  );

  final int orderId;
  final DateTime expiresAt;
  final double? distanceToPickupKm;
  final Delivery delivery;
}

class DriverProfile {
  const DriverProfile({
    required this.id,
    this.name,
    required this.vehicleType,
    required this.isOnline,
    required this.preferredLanguage,
  });

  factory DriverProfile.fromJson(Map<String, dynamic> json) => DriverProfile(
    id: json['id'] as int,
    name: json['name'] as String?,
    vehicleType: json['vehicle_type'] as String,
    isOnline: json['is_online'] as bool,
    preferredLanguage: json['preferred_language'] as String? ?? 'en',
  );

  final int id;
  final String? name;
  final String vehicleType;
  final bool isOnline;
  final String preferredLanguage;
}

/// A GPS fix, possibly buffered while offline (sent later with its original time).
class GeoPoint {
  const GeoPoint(this.latitude, this.longitude, this.at, {this.orderId});

  factory GeoPoint.fromJson(Map<String, dynamic> json) => GeoPoint(
    (json['latitude'] as num).toDouble(),
    (json['longitude'] as num).toDouble(),
    DateTime.parse(json['timestamp'] as String),
    orderId: json['order_id'] as int?,
  );

  final double latitude;
  final double longitude;
  final DateTime at;
  final int? orderId;

  Map<String, dynamic> toJson() => {
    'latitude': latitude,
    'longitude': longitude,
    'timestamp': at.toUtc().toIso8601String(),
    'order_id': orderId,
  };
}
