import 'dart:async';

import 'package:bento_core/bento_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:malawi_bento_driver/app.dart';
import 'package:malawi_bento_driver/core/providers.dart';
import 'package:malawi_bento_driver/features/delivery/driver_repository.dart';
import 'package:malawi_bento_driver/features/delivery/launcher.dart';
import 'package:malawi_bento_driver/features/delivery/location_source.dart';
import 'package:malawi_bento_driver/features/delivery/models.dart';
import 'package:malawi_bento_driver/features/delivery/sync_scope.dart';
import 'package:shared_preferences/shared_preferences.dart';

Map<String, dynamic> deliveryJson({
  int id = 7,
  String status = 'RIDER_ASSIGNED',
  int pinAttemptsLeft = 5,
}) => {
  'id': id,
  'order_number': 'LLW-CENTRAL-260929-0007',
  'status': status,
  'payment_method': 'CASH',
  'currency': 'MWK',
  'amount_to_collect': 950000,
  'item_count': 3,
  'pickup': {
    'name': 'Lilongwe Central Store',
    'phone': '+265999000200',
    'address': 'City Centre',
    'latitude': -13.9626,
    'longitude': 33.7741,
  },
  'dropoff': {
    'name': 'Test Customer',
    'phone': '+265991234567',
    'address': {
      'area': 'Area 10',
      'landmark': 'Near the blue gate',
      'delivery_note': 'Call on arrival',
    },
    'latitude': -13.97,
    'longitude': 33.78,
  },
  'distance_km': 1.04,
  'pin_attempts_left': pinAttemptsLeft,
};

/// In-memory server: enough behaviour to drive the rider flow end to end.
class FakeDriverRepository implements DriverRepository {
  bool online = false;
  bool offlineNetwork = false;
  bool hasRequest = true;
  String pin = '1234';
  int attemptsLeft = 5;
  Delivery? active;
  final List<List<GeoPoint>> sentLocations = [];
  final List<String> calls = [];

  void _net() {
    if (offlineNetwork) throw const ApiException(ApiException.network);
  }

  DriverProfile get _profile => DriverProfile(
    id: 1,
    name: 'Driver One',
    vehicleType: 'MOTORBIKE',
    isOnline: online,
    preferredLanguage: 'en',
  );

  Delivery _set(String status) => active = Delivery.fromJson(
    deliveryJson(status: status, pinAttemptsLeft: attemptsLeft),
  );

  @override
  Future<DriverSnapshot> me() async {
    _net();
    return DriverSnapshot(profile: _profile, active: active);
  }

  @override
  Future<DriverProfile> goOnline(GeoPoint? position) async {
    _net();
    online = true;
    return _profile;
  }

  @override
  Future<DriverProfile> goOffline() async {
    _net();
    online = false;
    return _profile;
  }

  @override
  Future<List<DeliveryRequest>> requests() async {
    _net();
    if (!hasRequest || active != null) return [];
    return [
      DeliveryRequest.fromJson({
        'order_id': 7,
        'expires_in': 60,
        'distance_to_pickup_km': 0.5,
        'delivery': deliveryJson(status: 'READY_FOR_PICKUP'),
      }),
    ];
  }

  @override
  Future<Delivery> accept(int orderId) async {
    _net();
    calls.add('accept');
    hasRequest = false;
    return _set('RIDER_ASSIGNED');
  }

  @override
  Future<void> decline(int orderId) async {
    _net();
    calls.add('decline');
    hasRequest = false;
  }

  @override
  Future<Delivery> pickup(int orderId) async {
    _net();
    calls.add('pickup');
    if (active!.status != 'RIDER_ASSIGNED') {
      throw const ApiException('INVALID_STATUS_TRANSITION');
    }
    return _set('ON_THE_WAY');
  }

  @override
  Future<Delivery> arrive(int orderId) async {
    _net();
    calls.add('arrive');
    return _set('ARRIVED');
  }

  @override
  Future<Delivery> complete(int orderId, String pin) async {
    _net();
    calls.add('complete');
    if (pin != this.pin) {
      attemptsLeft--;
      _set('ARRIVED');
      throw const ApiException('DELIVERY_PIN_INVALID');
    }
    final done = Delivery.fromJson(deliveryJson(status: 'DELIVERED'));
    active = null;
    return done;
  }

  @override
  Future<Delivery> fail(int orderId, String reasonCode) async {
    _net();
    calls.add('fail:$reasonCode');
    active = null;
    return Delivery.fromJson(deliveryJson(status: 'FAILED_DELIVERY'));
  }

  @override
  Future<List<Delivery>> history() async => [
    Delivery.fromJson(deliveryJson(status: 'DELIVERED')),
  ];

  @override
  Future<void> sendLocations(List<GeoPoint> points) async {
    _net();
    sentLocations.add(points);
  }
}

class FakeLocationSource implements LocationSource {
  final controller = StreamController<GeoPoint>.broadcast();

  @override
  Future<GeoPoint> current() async => GeoPoint(-13.96, 33.77, DateTime.now());

  @override
  Stream<GeoPoint> watch() => controller.stream;
}

Future<ProviderContainer> pumpRider(
  WidgetTester tester, {
  required FakeDriverRepository repo,
  Map<String, Object> prefs = const {'locale': 'en'},
  String? token = 'token',
  Size size = const Size(360, 740),
  double textScale = 1.0,
  List launched = const [],
}) async {
  SharedPreferences.setMockInitialValues(prefs);
  final sharedPrefs = await SharedPreferences.getInstance();
  tester.view.physicalSize = size * tester.view.devicePixelRatio;
  tester.platformDispatcher.textScaleFactorTestValue = textScale;
  addTearDown(tester.view.reset);
  addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);

  final container = ProviderContainer(
    retry: (_, _) => null,
    overrides: [
      sharedPreferencesProvider.overrideWithValue(sharedPrefs),
      tokenStoreProvider.overrideWithValue(MemoryTokenStore(token)),
      initialTokenProvider.overrideWithValue(token),
      driverRepositoryProvider.overrideWithValue(repo),
      locationSourceProvider.overrideWithValue(FakeLocationSource()),
      syncIntervalProvider.overrideWithValue(const Duration(hours: 1)),
      externalLauncherProvider.overrideWithValue((uri) async {
        (launched as List<Uri>).add(uri);
        return true;
      }),
    ],
  );
  addTearDown(container.dispose);

  await tester.pumpWidget(
    UncontrolledProviderScope(container: container, child: const RiderApp()),
  );
  await tester.pumpAndSettle();
  return container;
}

/// Removes the app so periodic timers are cancelled before the test ends.
Future<void> unmount(WidgetTester tester) async {
  await tester.pumpWidget(const SizedBox());
  await tester.pump();
}
