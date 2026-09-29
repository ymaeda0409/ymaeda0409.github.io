import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'core/providers.dart';
import 'core/ui/widgets.dart';
import 'features/account/account_screen.dart';
import 'features/account/addresses_screen.dart';
import 'features/account/language_settings_screen.dart';
import 'features/auth/otp_screen.dart';
import 'features/auth/phone_screen.dart';
import 'features/cart/cart.dart';
import 'features/cart/cart_screen.dart';
import 'features/catalog/home_screen.dart';
import 'features/catalog/product_detail_screen.dart';
import 'features/catalog/product_list_screen.dart';
import 'features/checkout/checkout_screen.dart';
import 'features/language/language_selection_screen.dart';
import 'features/location/address_form_screen.dart';
import 'features/location/delivery_location_screen.dart';
import 'features/orders/order_complete_screen.dart';
import 'features/orders/order_detail_screen.dart';
import 'features/orders/order_history_screen.dart';
import 'features/payment/payment_screen.dart';
import 'features/splash/splash_screen.dart';

/// Routes that require a signed-in customer.
const _protected = [
  '/checkout',
  '/account/addresses',
  '/location/new',
  '/orders/',
];

final routerProvider = Provider<GoRouter>((ref) {
  final refresh = ValueNotifier<int>(0);
  ref.listen(sessionProvider, (_, _) => refresh.value++);
  ref.onDispose(refresh.dispose);

  return GoRouter(
    initialLocation: '/',
    refreshListenable: refresh,
    redirect: (context, state) {
      final path = state.uri.path;
      final signedIn = ref.read(sessionProvider).isSignedIn;
      if (!signedIn && _protected.any(path.startsWith)) {
        return Uri(
          path: '/login',
          queryParameters: {'from': state.uri.toString()},
        ).toString();
      }
      return null;
    },
    routes: [
      GoRoute(path: '/', builder: (_, _) => const SplashScreen()),
      GoRoute(
        path: '/welcome',
        builder: (_, _) => const LanguageSelectionScreen(),
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, shell) => _MainShell(shell: shell),
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(path: '/home', builder: (_, _) => const HomeScreen()),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/orders',
                builder: (_, _) => const OrderHistoryScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(path: '/cart', builder: (_, _) => const CartScreen()),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/account',
                builder: (_, _) => const AccountScreen(),
              ),
            ],
          ),
        ],
      ),
      GoRoute(
        path: '/location',
        builder: (_, _) => const DeliveryLocationScreen(),
      ),
      GoRoute(
        path: '/location/new',
        builder: (_, _) => const AddressFormScreen(),
      ),
      GoRoute(
        path: '/category/:id',
        builder: (_, state) => ProductListScreen(
          categoryId: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/product/:id',
        builder: (_, state) => ProductDetailScreen(
          productId: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(path: '/checkout', builder: (_, _) => const CheckoutScreen()),
      GoRoute(
        path: '/orders/:id',
        builder: (_, state) =>
            OrderDetailScreen(orderId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/orders/:id/pay',
        builder: (_, state) =>
            PaymentScreen(orderId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/orders/:id/complete',
        builder: (_, state) => OrderCompleteScreen(
          orderId: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/login',
        builder: (_, state) =>
            PhoneScreen(from: state.uri.queryParameters['from']),
      ),
      GoRoute(
        path: '/login/otp',
        builder: (_, state) => OtpScreen(
          phone: state.uri.queryParameters['phone']!,
          from: state.uri.queryParameters['from'],
        ),
      ),
      GoRoute(
        path: '/account/addresses',
        builder: (_, _) => const AddressesScreen(),
      ),
      GoRoute(
        path: '/account/language',
        builder: (_, _) => const LanguageSettingsScreen(),
      ),
    ],
  );
});

class _MainShell extends ConsumerWidget {
  const _MainShell({required this.shell});

  final StatefulNavigationShell shell;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    final count = ref.watch(cartProvider.select((c) => c.itemCount));
    return Scaffold(
      body: shell,
      bottomNavigationBar: NavigationBar(
        selectedIndex: shell.currentIndex,
        onDestinationSelected: (i) =>
            shell.goBranch(i, initialLocation: i == shell.currentIndex),
        destinations: [
          NavigationDestination(
            icon: const Icon(Icons.storefront_outlined),
            label: l.nav_home,
          ),
          NavigationDestination(
            icon: const Icon(Icons.receipt_long_outlined),
            label: l.nav_orders,
          ),
          NavigationDestination(
            icon: Badge(
              isLabelVisible: count > 0,
              label: Text('$count'),
              child: const Icon(Icons.shopping_bag_outlined),
            ),
            label: l.nav_cart,
          ),
          NavigationDestination(
            icon: const Icon(Icons.person_outline),
            label: l.nav_account,
          ),
        ],
      ),
    );
  }
}
