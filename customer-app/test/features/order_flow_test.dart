import 'package:bento_core/bento_core.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:malawi_bento_customer/core/providers.dart';
import 'package:malawi_bento_customer/features/auth/user.dart';
import 'package:malawi_bento_customer/features/cart/cart.dart';
import 'package:malawi_bento_customer/features/checkout/order_repository.dart';
import 'package:malawi_bento_customer/features/location/address.dart';
import 'package:malawi_bento_customer/features/location/address_repository.dart';
import 'package:malawi_bento_customer/features/orders/order.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:malawi_bento_customer/features/orders/tracking.dart';
import 'package:malawi_bento_customer/features/payment/payment.dart';
import 'package:malawi_bento_customer/features/payment/payment_screen.dart';

import '../helpers.dart';

const _located = {
  'delivery_location':
      '{"latitude":-13.97,"longitude":33.78,"label":"Home","address_id":1}',
};

const _home = Address(
  id: 1,
  name: 'Home',
  latitude: -13.97,
  longitude: 33.78,
  landmark: 'Blue gate',
  isDefault: true,
);

Map<String, dynamic> _orderJson({
  String status = 'NEW',
  String paymentMethod = 'CASH',
}) => {
  'id': 42,
  'order_number': 'LLW-CENTRAL-260929-0001',
  'status': status,
  'payment_method': paymentMethod,
  'payment_status': 'PENDING',
  'currency': 'MWK',
  'subtotal': 700000,
  'delivery_fee': 150000,
  'service_fee': 0,
  'discount': 0,
  'total': 850000,
  'ordered_at': '2026-09-29T10:00:00Z',
  'delivery_pin': '4821',
  'store': {'name': 'Lilongwe Central Store'},
  'items': [
    {'name': 'チキン弁当', 'quantity': 2, 'total': 700000, 'options': []},
  ],
  'timeline': [
    {'status': 'NEW', 'at': '2026-09-29T10:00:00Z'},
    if (status != 'NEW') {'status': status, 'at': '2026-09-29T10:05:00Z'},
  ],
};

class FakeOrderRepository implements OrderRepository {
  PlaceOrderRequest? placed;
  String status = 'NEW';
  ApiException? quoteError;

  @override
  Future<Quote> quote(Cart cart, int addressId) async {
    if (quoteError != null) throw quoteError!;
    return Quote(
      currency: 'MWK',
      subtotal: cart.subtotal,
      deliveryFee: 150000,
      serviceFee: 20000,
      discount: 0,
      total: cart.subtotal + 170000,
    );
  }

  @override
  Future<Order> place(PlaceOrderRequest request) async {
    placed = request;
    return Order.fromJson(_orderJson());
  }

  @override
  Future<Order> detail(int id) async => Order.fromJson(
    _orderJson(
      status: status,
      paymentMethod: placed?.paymentMethod.code ?? 'CASH',
    ),
  );

  @override
  Future<List<Order>> list() async => [
    Order.fromJson(_orderJson(status: status)),
  ];

  Map<String, dynamic>? rider;

  @override
  Future<Tracking> tracking(int id) async => Tracking.fromJson({
    'status': status,
    'dropoff': {'latitude': -13.97, 'longitude': 33.78},
    'driver': rider,
  });

  @override
  Future<Order> cancel(int id) async =>
      Order.fromJson(_orderJson(status: status = 'CANCELLED'));
}

class FakePaymentRepository implements PaymentRepository {
  final List<String> statuses = ['PENDING', 'PENDING', 'PAID'];
  String? phone;

  Payment _payment(String status) => Payment(
    id: 5,
    status: status,
    method: 'AIRTEL_MONEY',
    amount: 850000,
    currency: 'MWK',
    phone: phone,
  );

  @override
  Future<Payment> start(int orderId, String? phone) async {
    this.phone = phone;
    return _payment(statuses.removeAt(0));
  }

  @override
  Future<Payment> status(int paymentId) async =>
      _payment(statuses.length > 1 ? statuses.removeAt(0) : statuses.first);
}

class FakeAddressRepository implements AddressRepository {
  @override
  Future<List<Address>> list() async => [_home];
  @override
  Future<Address> create(Map<String, dynamic> data) async => _home;
  @override
  Future<Address> update(int id, Map<String, dynamic> data) async => _home;
  @override
  Future<void> delete(int id) async {}
}

void main() {
  test(
    'order model: cancellable only before cooking; final statuses are inactive',
    () {
      expect(Order.fromJson(_orderJson()).canCancel, isTrue);
      expect(Order.fromJson(_orderJson(status: 'COOKING')).canCancel, isFalse);
      expect(Order.fromJson(_orderJson(status: 'DELIVERED')).isActive, isFalse);
      expect(Order.fromJson(_orderJson()).itemCount, 2);
    },
  );

  Future<(FakeOrderRepository, dynamic)> signedInApp(
    WidgetTester tester,
    String lang,
  ) async {
    final orders = FakeOrderRepository();
    final container = await pumpApp(
      tester,
      prefs: {'locale': lang, ..._located},
      token: 'token',
      overrides: [
        orderRepositoryProvider.overrideWithValue(orders),
        addressRepositoryProvider.overrideWithValue(FakeAddressRepository()),
      ],
    );
    await container
        .read(sessionProvider.notifier)
        .signIn(
          'token',
          const AppUser(
            id: 1,
            phone: '+265991234567',
            role: 'CUSTOMER',
            preferredLanguage: 'en',
          ),
        );
    final fake = FakeCatalogRepository(() => lang);
    await container
        .read(cartProvider.notifier)
        .add(
          store: fake.store,
          product: fake.productFor(lang),
          options: const [],
          quantity: 2,
        );
    await tester.pumpAndSettle();
    return (orders, container);
  }

  testWidgets(
    'checkout shows the server quote, places the order and shows the PIN',
    (tester) async {
      final (orders, container) = await signedInApp(tester, 'en');

      await tester.tap(find.text('Cart'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Checkout'));
      await tester.pumpAndSettle();

      expect(
        find.text('Home'),
        findsWidgets,
        reason: 'default saved address preselected',
      );
      await tester.scrollUntilVisible(find.text('MK 200'), 200);
      expect(
        find.text('MK 200'),
        findsOneWidget,
        reason: 'service fee comes from the server quote',
      );

      await tester.scrollUntilVisible(find.text('Place Order'), 200);
      await tester.tap(find.text('Place Order'));
      await tester.pumpAndSettle();

      expect(orders.placed!.addressId, 1);
      expect(orders.placed!.paymentMethod, PaymentMethod.cash);
      expect(find.text('4821'), findsOneWidget);
      expect(find.text('LLW-CENTRAL-260929-0001'), findsOneWidget);
      expect((container as dynamic).read(cartProvider).isEmpty, isTrue);
    },
  );

  testWidgets('a quote error (e.g. out of area) blocks placing the order', (
    tester,
  ) async {
    final (orders, _) = await signedInApp(tester, 'ja');
    orders.quoteError = const ApiException('OUT_OF_DELIVERY_AREA');

    await tester.tap(find.text('カート'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('レジに進む'));
    await tester.pumpAndSettle();

    await tester.scrollUntilVisible(find.text('この場所は配達エリア外です。'), 200);
    expect(find.text('この場所は配達エリア外です。'), findsOneWidget);
    await tester.scrollUntilVisible(find.text('注文する'), 200);
    await tester.tap(find.text('注文する'));
    await tester.pumpAndSettle();
    expect(orders.placed, isNull);
  });

  testWidgets('order detail shows translated status timeline and can cancel', (
    tester,
  ) async {
    final (orders, _) = await signedInApp(tester, 'ny');
    orders.status = 'CONFIRMED';

    await tester.tap(find.text('Ma oda'));
    await tester.pumpAndSettle();
    expect(find.text('Yatsimikizidwa'), findsWidgets);

    await tester.tap(find.text('Lilongwe Central Store'));
    await tester.pumpAndSettle();
    expect(find.text('Oda yalandiridwa'), findsOneWidget);
    expect(find.text('4821'), findsOneWidget);

    await tester.scrollUntilVisible(find.text('Letsani oda'), 200);
    await tester.tap(find.text('Letsani oda'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Letsani oda').last);
    await tester.pumpAndSettle();

    expect(orders.status, 'CANCELLED');
    expect(find.text('Yathetsedwa'), findsWidgets);
  });

  testWidgets('order detail shows the rider position while on the way', (
    tester,
  ) async {
    final (orders, _) = await signedInApp(tester, 'ja');
    orders.status = 'ON_THE_WAY';
    orders.rider = {
      'name': 'Driver One',
      'vehicle_type': 'MOTORBIKE',
      'latitude': -13.9610,
      'longitude': 33.7800,
      'updated_at': '2026-09-29T10:20:00Z',
    };

    await tester.tap(find.text('注文履歴').last);
    await tester.pumpAndSettle();
    await tester.tap(find.text('Lilongwe Central Store'));
    await tester.pumpAndSettle();

    expect(find.text('配達状況'), findsOneWidget);
    expect(find.textContaining('バイク'), findsOneWidget);
    expect(find.textContaining('あと 1.0 km'), findsOneWidget);
    // 1.0 km × 1.3 at 25 km/h ≈ 3.1 → 4 minutes.
    expect(find.text('あと約 4 分で到着'), findsOneWidget);
    expect(find.byType(FlutterMap), findsOneWidget);

    // The rider moves; the card refreshes on its own every 10 seconds.
    orders.rider = {...orders.rider!, 'latitude': -13.9655};
    await tester.pump(const Duration(seconds: 11));
    await tester.pumpAndSettle();
    expect(find.textContaining('あと 0.5 km'), findsOneWidget);
  });

  test('arrival estimate depends on vehicle and only shows on the way', () {
    Tracking at(String status, String vehicle) => Tracking.fromJson({
      'status': status,
      'pickup': {'latitude': -13.9626, 'longitude': 33.7741},
      'dropoff': {'latitude': -13.97, 'longitude': 33.78},
      'driver': {
        'vehicle_type': vehicle,
        'latitude': -13.9610,
        'longitude': 33.7800,
      },
    });
    expect(at('ON_THE_WAY', 'MOTORBIKE').etaMinutes, 4);
    expect(at('ON_THE_WAY', 'BICYCLE').etaMinutes, 7);
    expect(at('RIDER_ASSIGNED', 'MOTORBIKE').etaMinutes, isNull);
    expect(at('ON_THE_WAY', 'MOTORBIKE').pickupLat, -13.9626);
  });

  testWidgets('mobile money: order → approve on phone → paid → PIN shown', (
    tester,
  ) async {
    final payments = FakePaymentRepository();
    final orders = FakeOrderRepository();
    final container = await pumpApp(
      tester,
      prefs: {'locale': 'en', ..._located},
      token: 'token',
      overrides: [
        orderRepositoryProvider.overrideWithValue(orders),
        addressRepositoryProvider.overrideWithValue(FakeAddressRepository()),
        paymentRepositoryProvider.overrideWithValue(payments),
        paymentPollIntervalProvider.overrideWithValue(
          const Duration(milliseconds: 100),
        ),
      ],
    );
    await container
        .read(sessionProvider.notifier)
        .signIn(
          'token',
          const AppUser(
            id: 1,
            phone: '+265991234567',
            role: 'CUSTOMER',
            preferredLanguage: 'en',
          ),
        );
    final fake = FakeCatalogRepository(() => 'en');
    await container
        .read(cartProvider.notifier)
        .add(
          store: fake.store,
          product: fake.productFor('en'),
          options: const [],
          quantity: 2,
        );
    await tester.pumpAndSettle();

    await tester.tap(find.text('Cart'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Checkout'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Airtel Money'));
    await tester.pumpAndSettle();
    await tester.scrollUntilVisible(find.text('Place Order'), 200);
    await tester.tap(find.text('Place Order'));
    await tester.pumpAndSettle();

    expect(orders.placed!.paymentMethod, PaymentMethod.airtelMoney);
    expect(find.text('Payment'), findsOneWidget);
    await tester.tap(find.text('Pay with Airtel Money'));
    await tester.pump();
    expect(
      find.text('Check your phone and approve the payment with your PIN.'),
      findsOneWidget,
    );
    expect(payments.phone, '0991234567');

    await tester.pump(const Duration(milliseconds: 250));
    await tester.pumpAndSettle();
    expect(
      find.text('4821'),
      findsOneWidget,
      reason: 'order complete screen with PIN after payment',
    );
  });
}
