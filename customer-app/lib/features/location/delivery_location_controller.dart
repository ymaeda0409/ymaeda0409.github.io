import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import 'address.dart';

/// Current delivery point, persisted so the app opens where the customer left off.
final deliveryLocationProvider =
    NotifierProvider<DeliveryLocationController, DeliveryLocation?>(DeliveryLocationController.new);

class DeliveryLocationController extends Notifier<DeliveryLocation?> {
  static const _key = 'delivery_location';

  @override
  DeliveryLocation? build() {
    final raw = ref.read(sharedPreferencesProvider).getString(_key);
    return raw == null ? null : DeliveryLocation.fromJson(jsonDecode(raw) as Map<String, dynamic>);
  }

  Future<void> set(DeliveryLocation location) async {
    state = location;
    await ref.read(sharedPreferencesProvider).setString(_key, jsonEncode(location.toJson()));
  }
}
