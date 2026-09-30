import 'dart:convert';
import 'dart:math' as math;
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter/services.dart' show rootBundle;
import 'package:shared_preferences/shared_preferences.dart';

import '../features/location/geo_service.dart';

/// In-browser stand-in for the Laravel API, used by `--dart-define=DEMO_MODE=true`
/// builds (the public web demo). It answers the same endpoints with the same JSON
/// envelope, so the app runs its real screens, repositories and parsing.
///
/// Catalog data in `assets/demo/*.json` was captured from the real API in each
/// language. Orders, addresses and the signed-in user are kept in
/// SharedPreferences, so a reload keeps them. Orders move through the real
/// status flow on a timer (kitchen, rider and PIN steps are simulated).
class DemoBackend implements HttpClientAdapter {
  DemoBackend(
    this._prefs, {
    DateTime Function()? clock,
    this.latency = const Duration(milliseconds: 250),
  }) : _now = clock ?? DateTime.now;

  final SharedPreferences _prefs;
  final DateTime Function() _now;

  /// Simulated network delay so loading states are visible.
  final Duration latency;

  static const _stateKey = 'demo.backend.v1';
  static const _locales = ['en', 'ny', 'ja'];
  static const demoOtp = '123456';

  /// Seconds after the kitchen starts (payment received or cash order placed).
  static const stages = <(String, int)>[
    ('CONFIRMED', 6),
    ('COOKING', 15),
    ('READY_FOR_PICKUP', 35),
    ('RIDER_ASSIGNED', 42),
    ('PICKED_UP', 55),
    ('ON_THE_WAY', 57),
    ('ARRIVED', 100),
    ('DELIVERED', 125),
  ];

  // Delivery zone of the seeded Lilongwe store.
  static const _baseFee = 150000;
  static const _baseKm = 3.0;
  static const _perKm = 30000;
  static const _maxKm = 10.0;

  final Map<String, Object?> _assets = {};
  Map<String, dynamic>? _state;

  // ------------------------------------------------------------------ adapter

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    await Future<void>.delayed(latency);
    final locale = _locale(options.headers['Accept-Language']);
    final body = options.data is Map
        ? Map<String, dynamic>.from(options.data as Map)
        : <String, dynamic>{};
    try {
      final data = await _route(
        options.method.toUpperCase(),
        Uri.parse(options.path).path,
        options.queryParameters,
        body,
        locale,
      );
      return _json(200, {
        'success': true,
        'data': data,
        'meta': {'locale': locale},
      });
    } on _DemoError catch (e) {
      return _error(e.code, e.status);
    } catch (_) {
      // Same envelope the real API uses for unexpected failures.
      return _error('SERVER_ERROR', 500);
    }
  }

  ResponseBody _error(String code, int status) => _json(status, {
    'success': false,
    'error': {'code': code, 'message': code, 'fields': null},
  });

  @override
  void close({bool force = false}) {}

  ResponseBody _json(int status, Object body) => ResponseBody.fromString(
    jsonEncode(body),
    status,
    headers: {
      Headers.contentTypeHeader: [Headers.jsonContentType],
    },
  );

  String _locale(Object? header) {
    final code = (header?.toString() ?? '').split(RegExp('[-_,;]')).first;
    return _locales.contains(code) ? code : 'en';
  }

  // ------------------------------------------------------------------ routes

  Future<Object?> _route(
    String method,
    String path,
    Map<String, dynamic> query,
    Map<String, dynamic> body,
    String locale,
  ) async {
    final state = await _load();
    final parts = path.split('/').where((p) => p.isNotEmpty).toList();
    String seg(int i) => i < parts.length ? parts[i] : '';

    switch ((method, seg(0))) {
      case ('GET', 'languages'):
        return _asset('languages');
      case ('GET', 'stores') when seg(1) == 'available':
        return _availableStores(query, locale);
      case ('GET', 'categories'):
        return _asset('categories_$locale');
      case ('GET', 'products') when parts.length == 1:
        return _products(query, locale);
      case ('GET', 'products'):
        final detail = (await _asset('product_details_$locale') as Map)[seg(1)];
        return detail ?? (throw _DemoError.notFound());
      case ('POST', 'auth') when seg(1) == 'send-otp':
        return {
          'phone': _phone(body['phone']),
          'expires_in': 300,
          'resend_in': 60,
        };
      case ('POST', 'auth') when seg(1) == 'verify-otp':
        return _verifyOtp(state, body);
      case ('POST', 'auth') when seg(1) == 'logout':
        return null;
      case ('POST', 'devices') || ('DELETE', 'devices'):
        return null;
    }

    // Everything below needs a signed-in customer.
    final user = state['user'] as Map<String, dynamic>?;
    if (user == null) throw _DemoError('UNAUTHENTICATED', 401);

    switch ((method, seg(0))) {
      case ('GET', 'account'):
        return user;
      case ('PUT', 'account') when seg(1) == 'language':
        user['preferred_language'] = body['language'];
        await _save();
        return user;
      case ('PUT', 'account'):
        if (body.containsKey('name')) user['name'] = body['name'];
        await _save();
        return user;
      case ('GET', 'addresses'):
        return _addresses(state);
      case ('POST', 'addresses'):
        return _saveAddress(state, null, body);
      case ('PUT', 'addresses'):
        return _saveAddress(state, int.parse(seg(1)), body);
      case ('DELETE', 'addresses'):
        _addresses(state).removeWhere((a) => a['id'] == int.parse(seg(1)));
        await _save();
        return null;
      case ('POST', 'orders') when seg(1) == 'quote':
        return (await _price(state, body, locale)).quote;
      case ('POST', 'orders') when parts.length == 1:
        return _placeOrder(state, body, locale);
      case ('GET', 'orders') when parts.length == 1:
        final orders = _orders(state).map(_advance).toList().reversed.toList();
        await _save();
        return orders;
      case ('GET', 'orders') when seg(2) == 'tracking':
        return _tracking(_order(state, seg(1)));
      case ('GET', 'orders'):
        final order = _advance(_order(state, seg(1)));
        await _save();
        return order;
      case ('POST', 'orders') when seg(2) == 'cancel':
        return _cancel(_order(state, seg(1)));
      case ('POST', 'payments'):
        return _startPayment(state, body);
      case ('GET', 'payments'):
        return _paymentStatus(state, int.parse(seg(1)));
    }
    throw _DemoError('ROUTE_NOT_FOUND', 404);
  }

  // ------------------------------------------------------------------ catalog

  Future<List<dynamic>> _availableStores(
    Map<String, dynamic> query,
    String locale,
  ) async {
    final lat = double.parse('${query['latitude']}');
    final lng = double.parse('${query['longitude']}');
    final stores = await _asset('stores_$locale') as List;
    final result = <dynamic>[];
    for (final raw in stores) {
      final entry = Map<String, dynamic>.from(raw as Map);
      final store = Map<String, dynamic>.from(entry['store'] as Map);
      final distance = _distanceKm(
        lat,
        lng,
        (store['latitude'] as num).toDouble(),
        (store['longitude'] as num).toDouble(),
      );
      if (distance > _maxKm) continue;
      // The demo store is always open so the flow works at any hour.
      store['is_open'] = true;
      result.add({
        ...entry,
        'store': store,
        'distance_km': double.parse(distance.toStringAsFixed(2)),
        'delivery_fee': _fee(distance),
        'is_open': true,
      });
    }
    return result;
  }

  Future<List<dynamic>> _products(
    Map<String, dynamic> query,
    String locale,
  ) async {
    final category = query['category_id'];
    final featured = '${query['featured'] ?? ''}' == '1';
    return (await _asset('products_$locale') as List)
        .where((p) => category == null || '${p['category_id']}' == '$category')
        .where((p) => !featured || p['is_featured'] == true)
        .toList();
  }

  // ------------------------------------------------------------------ auth

  Map<String, dynamic> _verifyOtp(
    Map<String, dynamic> state,
    Map<String, dynamic> body,
  ) {
    if ('${body['code']}' != demoOtp) {
      throw _DemoError('OTP_INVALID', 422);
    }
    final phone = _phone(body['phone']);
    final existing = state['user'] as Map<String, dynamic>?;
    final isNew = existing == null || existing['phone'] != phone;
    final user = isNew
        ? <String, dynamic>{
            'id': 1,
            'name': null,
            'phone': phone,
            'email': null,
            'role': 'CUSTOMER',
            'preferred_language': body['preferred_language'] ?? 'en',
          }
        : existing;
    state['user'] = user;
    _save();
    return {'token': 'demo-token', 'is_new_user': isNew, 'user': user};
  }

  String _phone(Object? raw) {
    final digits = '$raw'.replaceAll(RegExp(r'\D'), '');
    if (digits.startsWith('265')) return '+$digits';
    return '+265${digits.replaceFirst(RegExp('^0'), '')}';
  }

  // ------------------------------------------------------------------ addresses

  List<Map<String, dynamic>> _addresses(Map<String, dynamic> state) =>
      (state['addresses'] as List).cast<Map<String, dynamic>>();

  Future<Map<String, dynamic>> _saveAddress(
    Map<String, dynamic> state,
    int? id,
    Map<String, dynamic> body,
  ) async {
    final list = _addresses(state);
    final existing = id == null
        ? null
        : list.firstWhere(
            (a) => a['id'] == id,
            orElse: () => throw _DemoError.notFound(),
          );
    final address = existing ?? <String, dynamic>{'id': _nextId(state)};
    for (final key in [
      'name',
      'latitude',
      'longitude',
      'area',
      'street',
      'building',
      'landmark',
      'delivery_note',
    ]) {
      if (body.containsKey(key)) address[key] = body[key];
      address.putIfAbsent(key, () => null);
    }
    if (body.containsKey('phone')) address['phone'] = _phone(body['phone']);
    address.putIfAbsent('phone', () => null);
    final makeDefault = body['is_default'] == true || list.isEmpty;
    if (makeDefault) {
      for (final a in list) {
        a['is_default'] = false;
      }
    }
    address['is_default'] = makeDefault || (address['is_default'] ?? false);
    if (existing == null) list.add(address);
    await _save();
    return address;
  }

  // ------------------------------------------------------------------ orders

  Future<_Priced> _price(
    Map<String, dynamic> state,
    Map<String, dynamic> body,
    String locale,
  ) async {
    final address = _addresses(state).firstWhere(
      (a) => a['id'] == body['delivery_address_id'],
      orElse: () => throw _DemoError.notFound(),
    );
    final store =
        ((await _asset('stores_$locale') as List).first as Map)['store'] as Map;
    final distance = _distanceKm(
      (address['latitude'] as num).toDouble(),
      (address['longitude'] as num).toDouble(),
      (store['latitude'] as num).toDouble(),
      (store['longitude'] as num).toDouble(),
    );
    if (distance > _maxKm) throw _DemoError('OUT_OF_DELIVERY_AREA', 422);

    final details = await _asset('product_details_$locale') as Map;
    final items = <Map<String, dynamic>>[];
    for (final raw in body['items'] as List) {
      final item = raw as Map;
      final product = details['${item['product_id']}'] as Map?;
      if (product == null) throw _DemoError('PRODUCT_NOT_AVAILABLE', 422);
      final optionIds = ((item['option_ids'] as List?) ?? const [])
          .map((e) => '$e')
          .toSet();
      // Same rules as the API: every group's min/max, and only this product's options.
      final options = <Map<String, dynamic>>[];
      final known = <String>{};
      for (final raw in (product['option_groups'] as List? ?? const [])) {
        final group = raw as Map;
        final chosen = [
          for (final option in group['options'] as List)
            if (optionIds.contains('${(option as Map)['id']}')) option,
        ];
        known.addAll([
          for (final o in group['options'] as List) '${(o as Map)['id']}',
        ]);
        if (chosen.length < (group['min_select'] as int) ||
            chosen.length > (group['max_select'] as int)) {
          throw _DemoError('VALIDATION_FAILED', 422);
        }
        options.addAll([
          for (final o in chosen) {'name': o['name'], 'price': o['price']},
        ]);
      }
      if (!known.containsAll(optionIds)) {
        throw _DemoError('VALIDATION_FAILED', 422);
      }
      final quantity = item['quantity'] as int;
      final unit = product['price'] as int;
      final optionAmount = options.fold<int>(
        0,
        (sum, o) => sum + (o['price'] as int),
      );
      items.add({
        'product_id': product['id'],
        'name': product['name'],
        'quantity': quantity,
        'unit_price': unit,
        'option_amount': optionAmount,
        'total': (unit + optionAmount) * quantity,
        'options': options,
      });
    }
    final subtotal = items.fold<int>(0, (s, i) => s + (i['total'] as int));
    final fee = _fee(distance);
    return _Priced(
      store: store,
      address: address,
      items: items,
      quote: {
        'store_id': store['id'],
        'delivery_zone_id': 1,
        'distance_km': double.parse(distance.toStringAsFixed(2)),
        'currency': 'MWK',
        'items': [for (final i in items) Map.of(i)..remove('options')],
        'subtotal': subtotal,
        'delivery_fee': fee,
        'service_fee': 0,
        'discount': 0,
        'total': subtotal + fee,
      },
    );
  }

  Future<Map<String, dynamic>> _placeOrder(
    Map<String, dynamic> state,
    Map<String, dynamic> body,
    String locale,
  ) async {
    final priced = await _price(state, body, locale);
    final now = _now().toUtc();
    final id = _nextId(state);
    final method = '${body['payment_method']}';
    final address = priced.address;
    final order = <String, dynamic>{
      'id': id,
      'order_number':
          'LLW-CENTRAL-${_yymmdd(now)}-${(_orders(state).length + 1).toString().padLeft(4, '0')}',
      'status': 'NEW',
      'payment_status': 'PENDING',
      'payment_method': method,
      'currency': 'MWK',
      'subtotal': priced.quote['subtotal'],
      'delivery_fee': priced.quote['delivery_fee'],
      'service_fee': 0,
      'discount': 0,
      'total': priced.quote['total'],
      'delivery_pin': (1000 + math.Random().nextInt(9000)).toString(),
      'delivery_address': {
        for (final k in [
          'name',
          'area',
          'street',
          'building',
          'landmark',
          'delivery_note',
          'phone',
        ])
          k: address[k],
      },
      'delivery_latitude': address['latitude'],
      'delivery_longitude': address['longitude'],
      'store': {
        'id': priced.store['id'],
        'name': priced.store['name'],
        'phone': priced.store['phone'],
      },
      'items': priced.items,
      'item_count': priced.items.fold<int>(
        0,
        (s, i) => s + (i['quantity'] as int),
      ),
      'timeline': <dynamic>[
        <String, dynamic>{'status': 'NEW', 'at': now.toIso8601String()},
      ],
      'cancel_reason_code': null,
      'scheduled_at': body['scheduled_at'],
      'ordered_at': now.toIso8601String(),
      'delivered_at': null,
      // Demo bookkeeping (not part of the API response shape).
      '_kitchen_start': method == 'CASH' ? now.toIso8601String() : null,
      '_pickup': {
        'latitude': priced.store['latitude'],
        'longitude': priced.store['longitude'],
      },
    };
    _orders(state).add(order);
    await _save();
    return _public(order);
  }

  List<Map<String, dynamic>> _orders(Map<String, dynamic> state) =>
      (state['orders'] as List).cast<Map<String, dynamic>>();

  Map<String, dynamic> _order(Map<String, dynamic> state, String id) =>
      _orders(state).firstWhere(
        (o) => '${o['id']}' == id,
        orElse: () => throw _DemoError.notFound(),
      );

  /// Moves the order along the status flow according to elapsed time and returns
  /// the public JSON.
  Map<String, dynamic> _advance(Map<String, dynamic> order) {
    final start = order['_kitchen_start'] as String?;
    if (start != null && order['status'] != 'CANCELLED') {
      final began = DateTime.parse(start);
      final elapsed = _now().toUtc().difference(began).inSeconds;
      final timeline = order['timeline'] as List;
      final reached = timeline.map((t) => (t as Map)['status']).toSet();
      for (final (status, seconds) in stages) {
        if (elapsed < seconds) break;
        if (reached.contains(status)) continue;
        final at = began.add(Duration(seconds: seconds)).toIso8601String();
        timeline.add({'status': status, 'at': at});
        order['status'] = status;
        if (status == 'DELIVERED') {
          order['delivered_at'] = at;
          order['payment_status'] = 'PAID';
        }
      }
    }
    return _public(order);
  }

  Map<String, dynamic> _public(Map<String, dynamic> order) => {
    for (final e in order.entries)
      if (!e.key.startsWith('_')) e.key: e.value,
  };

  Map<String, dynamic> _cancel(Map<String, dynamic> order) {
    _advance(order);
    if (!{'NEW', 'CONFIRMED'}.contains(order['status'])) {
      throw _DemoError('INVALID_STATUS_TRANSITION', 422);
    }
    final now = _now().toUtc().toIso8601String();
    order['status'] = 'CANCELLED';
    order['cancel_reason_code'] = 'CUSTOMER_CANCELLED';
    (order['timeline'] as List).add({'status': 'CANCELLED', 'at': now});
    if (order['payment_status'] == 'PAID') order['payment_status'] = 'REFUNDED';
    order['_kitchen_start'] = null;
    _save();
    return _public(order);
  }

  Map<String, dynamic> _tracking(Map<String, dynamic> order) {
    final public = _advance(order);
    final status = public['status'] as String;
    final pickup = order['_pickup'] as Map;
    final dropoff = {
      'latitude': order['delivery_latitude'],
      'longitude': order['delivery_longitude'],
    };
    Map<String, dynamic>? driver;
    const riding = {'RIDER_ASSIGNED', 'PICKED_UP', 'ON_THE_WAY', 'ARRIVED'};
    if (riding.contains(status)) {
      // The rider rides from the store to the customer between PICKED_UP and ARRIVED.
      final began = DateTime.parse(order['_kitchen_start'] as String);
      final elapsed = _now().toUtc().difference(began).inSeconds;
      final t = ((elapsed - 55) / (100 - 55)).clamp(0.0, 1.0);
      double lerp(String key) =>
          (pickup[key] as num).toDouble() +
          ((dropoff[key] as num).toDouble() - (pickup[key] as num).toDouble()) *
              t;
      driver = {
        'name': 'Chikondi Banda',
        'vehicle_type': 'MOTORBIKE',
        'latitude': double.parse(lerp('latitude').toStringAsFixed(6)),
        'longitude': double.parse(lerp('longitude').toStringAsFixed(6)),
        'updated_at': _now().toUtc().toIso8601String(),
      };
    }
    _save();
    return {
      'order_id': order['id'],
      'status': status,
      'pickup': pickup,
      'dropoff': dropoff,
      'driver': driver,
      'timeline': public['timeline'],
    };
  }

  // ------------------------------------------------------------------ payments

  Future<Map<String, dynamic>> _startPayment(
    Map<String, dynamic> state,
    Map<String, dynamic> body,
  ) async {
    final order = _order(state, '${body['order_id']}');
    if (order['payment_method'] == 'CASH' ||
        order['payment_status'] == 'PAID' ||
        order['status'] == 'CANCELLED') {
      throw _DemoError('PAYMENT_NOT_REQUIRED', 409);
    }
    final payments = (state['payments'] as List).cast<Map<String, dynamic>>();
    final pending = payments.where(
      (p) => p['order_id'] == order['id'] && p['status'] == 'PENDING',
    );
    if (pending.isNotEmpty) return _public(pending.first);

    final phone = _phone(
      body['phone'] ?? (order['delivery_address'] as Map)['phone'],
    );
    // Same test numbers as the development gateway: …0000 declines, …9999 stays pending.
    final declined = phone.endsWith('0000');
    final payment = <String, dynamic>{
      'id': _nextId(state),
      'order_id': order['id'],
      'method': order['payment_method'],
      'status': declined ? 'FAILED' : 'PENDING',
      'amount': order['total'],
      'currency': 'MWK',
      'phone': phone,
      'reference': 'DEMO${DateTime.now().millisecondsSinceEpoch}',
      'failure_code': declined ? 'INSUFFICIENT_FUNDS' : null,
      'paid_at': null,
      '_created': _now().toUtc().toIso8601String(),
    };
    payments.add(payment);
    await _save();
    return _public(payment);
  }

  Future<Map<String, dynamic>> _paymentStatus(
    Map<String, dynamic> state,
    int id,
  ) async {
    final payment = (state['payments'] as List)
        .cast<Map<String, dynamic>>()
        .firstWhere(
          (p) => p['id'] == id,
          orElse: () => throw _DemoError.notFound(),
        );
    final age = _now().toUtc().difference(
      DateTime.parse(payment['_created'] as String),
    );
    // The customer "approves on the phone" after a few seconds.
    if (payment['status'] == 'PENDING' &&
        !(payment['phone'] as String).endsWith('9999') &&
        age.inSeconds >= 4) {
      final now = _now().toUtc().toIso8601String();
      payment['status'] = 'PAID';
      payment['paid_at'] = now;
      final order = _order(state, '${payment['order_id']}');
      order['payment_status'] = 'PAID';
      order['_kitchen_start'] = now;
      await _save();
    }
    return _public(payment);
  }

  // ------------------------------------------------------------------ helpers

  int _fee(double distanceKm) {
    final extra = math.max(0.0, distanceKm - _baseKm);
    return _baseFee + (double.parse(extra.toStringAsFixed(6))).ceil() * _perKm;
  }

  static double _distanceKm(
    double lat1,
    double lng1,
    double lat2,
    double lng2,
  ) {
    const r = 6371.0;
    double rad(double d) => d * math.pi / 180;
    final dLat = rad(lat2 - lat1);
    final dLng = rad(lng2 - lng1);
    final a =
        math.pow(math.sin(dLat / 2), 2) +
        math.cos(rad(lat1)) *
            math.cos(rad(lat2)) *
            math.pow(math.sin(dLng / 2), 2);
    return 2 * r * math.asin(math.sqrt(a));
  }

  String _yymmdd(DateTime d) =>
      '${(d.year % 100).toString().padLeft(2, '0')}'
      '${d.month.toString().padLeft(2, '0')}'
      '${d.day.toString().padLeft(2, '0')}';

  int _nextId(Map<String, dynamic> state) {
    final next = (state['next_id'] as int) + 1;
    state['next_id'] = next;
    return next;
  }

  Future<Object?> _asset(String name) async => _assets[name] ??= _localImages(
    jsonDecode(await rootBundle.loadString('assets/demo/$name.json')),
  );

  /// Captured catalog points image_url at the API server; the demo serves the same
  /// photos from its own bundle (`assets/demo/menu/`), next to the web app.
  static Object? _localImages(Object? json) {
    if (json is List) return json.map(_localImages).toList();
    if (json is! Map) return json;
    return {
      for (final e in json.entries)
        e.key: e.key == 'image_url' && e.value is String
            ? Uri.base
                  .resolve(
                    'assets/assets/demo/menu/${Uri.parse(e.value as String).pathSegments.last}',
                  )
                  .toString()
            : _localImages(e.value),
    };
  }

  Future<Map<String, dynamic>> _load() async {
    if (_state != null) return _state!;
    final saved = _prefs.getString(_stateKey);
    _state = saved == null
        ? <String, dynamic>{
            'user': null,
            'addresses': <dynamic>[],
            'orders': <dynamic>[],
            'payments': <dynamic>[],
            'next_id': 0,
          }
        : jsonDecode(saved) as Map<String, dynamic>;
    return _state!;
  }

  Future<void> _save() async {
    if (_state != null) {
      await _prefs.setString(_stateKey, jsonEncode(_state));
    }
  }
}

class _Priced {
  _Priced({
    required this.store,
    required this.address,
    required this.items,
    required this.quote,
  });

  final Map store;
  final Map<String, dynamic> address;
  final List<Map<String, dynamic>> items;
  final Map<String, dynamic> quote;
}

class _DemoError implements Exception {
  _DemoError(this.code, this.status);

  _DemoError.notFound() : this('RESOURCE_NOT_FOUND', 404);

  final String code;
  final int status;
}

/// Demo "GPS": Area 47, Lilongwe, inside the demo store's delivery zone, so
/// visitors anywhere in the world can walk through the order flow.
class DemoGeoService extends GeoService {
  const DemoGeoService();

  static const lilongwe = GeoPoint(-13.97, 33.78);

  @override
  Future<GeoPoint> current() async => lilongwe;
}
