import 'package:bento_core/bento_core.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:malawi_bento_customer/demo/demo_backend.dart';
import 'package:malawi_bento_customer/features/auth/auth_repository.dart';
import 'package:malawi_bento_customer/features/catalog/catalog_repository.dart';
import 'package:malawi_bento_customer/features/location/address_repository.dart';
import 'package:malawi_bento_customer/features/orders/order.dart';
import 'package:malawi_bento_customer/features/orders/tracking.dart';
import 'package:malawi_bento_customer/features/payment/payment.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// The public web demo answers the API in the browser. These tests run it
/// through the app's real ApiClient, repositories and JSON parsing.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late DateTime now;
  late String locale;
  late String? token;
  late ApiClient api;
  late SharedPreferences prefs;

  ApiClient client() => ApiClient(
    baseUrl: 'https://demo.invalid/api',
    localeTag: () => locale,
    token: () => token,
    dio: Dio()
      ..httpClientAdapter = DemoBackend(
        prefs,
        clock: () => now,
        latency: Duration.zero,
      ),
  );

  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    prefs = await SharedPreferences.getInstance();
    now = DateTime.utc(2026, 9, 30, 10);
    locale = 'en';
    token = null;
    api = client();
  });

  Future<int> signInWithAddress() async {
    final auth = AuthRepository(api);
    await auth.sendOtp('+265991234567');
    await expectLater(
      auth.verifyOtp('+265991234567', '000000', language: 'en'),
      throwsA(isA<ApiException>().having((e) => e.code, 'code', 'OTP_INVALID')),
    );
    final result = await auth.verifyOtp(
      '+265991234567',
      DemoBackend.demoOtp,
      language: 'en',
    );
    token = result.token;
    final address = await ApiAddressRepository(api).create({
      'name': 'Home',
      'latitude': -13.97,
      'longitude': 33.78,
      'area': 'Area 47',
      'landmark': 'Near the blue gate',
      'phone': '0991234567',
    });
    return address.id;
  }

  Future<Order> place(int addressId, String method) async => Order.fromJson(
    await api.post<Map<String, dynamic>>(
      '/orders',
      body: {
        'store_id': 1,
        'delivery_address_id': addressId,
        'payment_method': method,
        'items': [
          {
            'product_id': 1,
            'quantity': 1,
            'option_ids': [1, 6],
          },
          {'product_id': 5, 'quantity': 2},
        ],
      },
    ),
  );

  Future<Order> detail(int id) async =>
      Order.fromJson(await api.get<Map<String, dynamic>>('/orders/$id'));

  test('catalog is served in the requested language', () async {
    final catalog = ApiCatalogRepository(api);
    final stores = await catalog.availableStores(-13.97, 33.78);
    expect(stores, hasLength(1));
    expect((await catalog.products(1)).first.name, 'Chicken & Beef Combo');

    locale = 'ja';
    expect((await catalog.product(1, 1)).name, 'チキン＆ビーフのコンボ弁当');
    expect(
      (await catalog.product(1, 1)).imageUrl,
      endsWith('assets/demo/menu/combo-chicken-beef.jpg'),
    );
    locale = 'ny';
    expect((await catalog.categories(1)), isNotEmpty);

    // Far from Lilongwe: no store delivers.
    expect(await catalog.availableStores(-15.78, 35.0), isEmpty);
  });

  test(
    'mobile money order: pay, then the kitchen and rider move it to delivered',
    () async {
      final addressId = await signInWithAddress();
      final quote = Quote.fromJson(
        await api.post<Map<String, dynamic>>(
          '/orders/quote',
          body: {
            'store_id': 1,
            'delivery_address_id': addressId,
            'items': [
              {
                'product_id': 1,
                'quantity': 1,
                'option_ids': [1, 6],
              },
              {'product_id': 5, 'quantity': 2},
            ],
          },
        ),
      );
      // Same figures as the real API for this basket (captured fixture).
      expect(quote.subtotal, 1360000);
      expect(quote.deliveryFee, 150000);
      expect(quote.total, 1510000);

      final order = await place(addressId, 'AIRTEL_MONEY');
      expect(order.status, 'NEW');
      expect(order.total, 1510000);

      // Unpaid orders do not move.
      now = now.add(const Duration(minutes: 5));
      expect((await detail(order.id)).status, 'NEW');

      final payments = ApiPaymentRepository(api);
      final payment = await payments.start(order.id, '0991234567');
      expect(payment.status, 'PENDING');
      now = now.add(const Duration(seconds: 5));
      expect((await payments.status(payment.id)).status, 'PAID');

      now = now.add(const Duration(seconds: 20));
      expect((await detail(order.id)).status, 'COOKING');

      now = now.add(const Duration(seconds: 55));
      final tracking = Tracking.fromJson(
        await api.get<Map<String, dynamic>>('/orders/${order.id}/tracking'),
      );
      expect(tracking.status, 'ON_THE_WAY');
      expect(tracking.rider, isNotNull);
      expect(tracking.riderDistanceKm, greaterThan(0));

      now = now.add(const Duration(minutes: 2));
      final done = await detail(order.id);
      expect(done.status, 'DELIVERED');
      expect(done.paymentStatus, 'PAID');
      expect(
        done.timeline.map((t) => t.status),
        containsAllInOrder(['NEW', 'CONFIRMED', 'COOKING', 'DELIVERED']),
      );

      // State survives a reload (new backend instance, same storage).
      api = client();
      expect(
        (await api.get<List<dynamic>>('/orders')).single['status'],
        'DELIVERED',
      );
    },
  );

  test('declined payment and cancellation behave like the real API', () async {
    final addressId = await signInWithAddress();
    // Required choices (staple, portion) are enforced like the real API.
    await expectLater(
      api.post<Map<String, dynamic>>(
        '/orders/quote',
        body: {
          'store_id': 1,
          'delivery_address_id': addressId,
          'items': [
            {
              'product_id': 1,
              'quantity': 1,
              'option_ids': [6],
            },
          ],
        },
      ),
      throwsA(
        isA<ApiException>().having((e) => e.code, 'code', 'VALIDATION_FAILED'),
      ),
    );

    final order = await place(addressId, 'TNM_MPAMBA');

    final declined = await ApiPaymentRepository(api)
        .start(order.id, '0881230000');
    expect(declined.status, 'FAILED');

    final cancelled = Order.fromJson(
      await api.post<Map<String, dynamic>>('/orders/${order.id}/cancel'),
    );
    expect(cancelled.status, 'CANCELLED');

    final cash = await place(addressId, 'CASH');
    now = now.add(const Duration(minutes: 1));
    await expectLater(
      api.post<Map<String, dynamic>>('/orders/${cash.id}/cancel'),
      throwsA(
        isA<ApiException>().having(
          (e) => e.code,
          'code',
          'INVALID_STATUS_TRANSITION',
        ),
      ),
    );
  });

  test('signed-out calls are rejected like the real API', () async {
    await expectLater(
      api.get<List<dynamic>>('/orders'),
      throwsA(
        isA<ApiException>().having((e) => e.code, 'code', 'UNAUTHENTICATED'),
      ),
    );
  });
}
