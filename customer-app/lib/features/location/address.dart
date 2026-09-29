class Address {
  const Address({
    required this.id,
    required this.name,
    required this.latitude,
    required this.longitude,
    this.area,
    this.street,
    this.building,
    this.landmark,
    this.deliveryNote,
    this.phone,
    required this.isDefault,
  });

  factory Address.fromJson(Map<String, dynamic> json) => Address(
    id: json['id'] as int,
    name: json['name'] as String,
    latitude: (json['latitude'] as num).toDouble(),
    longitude: (json['longitude'] as num).toDouble(),
    area: json['area'] as String?,
    street: json['street'] as String?,
    building: json['building'] as String?,
    landmark: json['landmark'] as String?,
    deliveryNote: json['delivery_note'] as String?,
    phone: json['phone'] as String?,
    isDefault: json['is_default'] as bool,
  );

  final int id;
  final String name;
  final double latitude;
  final double longitude;
  final String? area;
  final String? street;
  final String? building;
  final String? landmark;
  final String? deliveryNote;
  final String? phone;
  final bool isDefault;

  /// One-line summary built from user-entered parts (no UI text involved).
  String get summary => [
    area,
    street,
    building,
    landmark,
  ].whereType<String>().where((s) => s.isNotEmpty).join(', ');
}

/// Where the customer wants delivery. `addressId` is set when a saved address was chosen.
class DeliveryLocation {
  const DeliveryLocation({
    required this.latitude,
    required this.longitude,
    this.label,
    this.addressId,
  });

  factory DeliveryLocation.fromJson(Map<String, dynamic> json) =>
      DeliveryLocation(
        latitude: (json['latitude'] as num).toDouble(),
        longitude: (json['longitude'] as num).toDouble(),
        label: json['label'] as String?,
        addressId: json['address_id'] as int?,
      );

  factory DeliveryLocation.fromAddress(Address a) => DeliveryLocation(
    latitude: a.latitude,
    longitude: a.longitude,
    label: a.name,
    addressId: a.id,
  );

  final double latitude;
  final double longitude;
  final String? label;
  final int? addressId;

  Map<String, dynamic> toJson() => {
    'latitude': latitude,
    'longitude': longitude,
    'label': label,
    'address_id': addressId,
  };
}
