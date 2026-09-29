import 'package:bento_core/bento_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/providers.dart';
import '../../core/ui/widgets.dart';
import '../cart/cart.dart';
import '../cart/cart_screen.dart';
import '../cart/cart_summary.dart';
import '../location/address.dart';
import '../location/address_repository.dart';
import '../location/delivery_location_controller.dart';
import '../orders/order_providers.dart';
import 'order_repository.dart';

/// 10 Checkout: address, payment method, delivery time (ASAP / scheduled), summary.
class CheckoutScreen extends ConsumerStatefulWidget {
  const CheckoutScreen({super.key});

  @override
  ConsumerState<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends ConsumerState<CheckoutScreen> {
  PaymentMethod _payment = PaymentMethod.cash;
  DateTime? _scheduledAt;
  int? _addressId;
  bool _busy = false;

  Address? _selectedAddress(List<Address> addresses) {
    final preferred =
        _addressId ?? ref.read(deliveryLocationProvider)?.addressId;
    return addresses.where((a) => a.id == preferred).firstOrNull ??
        addresses.where((a) => a.isDefault).firstOrNull ??
        addresses.firstOrNull;
  }

  Future<void> _pickSchedule() async {
    final now = DateTime.now();
    final date = await showDatePicker(
      context: context,
      firstDate: now,
      lastDate: now.add(const Duration(days: 7)),
      initialDate: now,
    );
    if (date == null || !mounted) return;
    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(now.add(const Duration(hours: 1))),
    );
    if (time == null) return;
    setState(
      () => _scheduledAt = DateTime(
        date.year,
        date.month,
        date.day,
        time.hour,
        time.minute,
      ),
    );
  }

  Future<void> _placeOrder(Address address) async {
    setState(() => _busy = true);
    try {
      final order = await ref
          .read(orderRepositoryProvider)
          .place(
            PlaceOrderRequest(
              cart: ref.read(cartProvider),
              addressId: address.id,
              paymentMethod: _payment,
              scheduledAt: _scheduledAt,
            ),
          );
      await ref.read(cartProvider.notifier).clear();
      if (mounted) {
        context.go(
          _payment.isMobileMoney
              ? '/orders/${order.id}/pay'
              : '/orders/${order.id}/complete',
        );
      }
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    final theme = Theme.of(context);
    final cart = ref.watch(cartProvider);
    final addresses = ref.watch(addressesProvider);
    final address = _selectedAddress(addresses.value ?? const []);
    final phone = ref.watch(sessionProvider).user?.phone;
    // Server-side validation + totals before the customer commits.
    final quote = ref.watch(checkoutQuoteProvider(address?.id));
    final quoteOk = address != null && quote.hasValue && quote.value != null;

    return Scaffold(
      appBar: AppBar(title: Text(l.checkout_title)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _Section(
            title: l.checkout_delivery_address,
            trailing: TextButton(
              onPressed: () => _chooseAddress(addresses.value ?? const []),
              child: Text(l.checkout_change),
            ),
            child: addresses.isLoading
                ? const LoadingView()
                : address == null
                ? ListTile(
                    leading: const Icon(Icons.add_location_alt_outlined),
                    title: Text(l.checkout_select_address),
                    onTap: () => context.push('/location/new'),
                  )
                : ListTile(
                    leading: const Icon(Icons.location_on_outlined),
                    title: Text(address.name),
                    subtitle: address.summary.isEmpty
                        ? null
                        : Text(address.summary),
                  ),
          ),
          _Section(
            title: l.checkout_payment_method,
            child: RadioGroup<PaymentMethod>(
              groupValue: _payment,
              onChanged: (v) => setState(() => _payment = v!),
              child: Column(
                children: [
                  for (final method in PaymentMethod.values)
                    RadioListTile<PaymentMethod>(
                      value: method,
                      title: Text(_paymentLabel(method)),
                    ),
                  if (_payment.isMobileMoney && phone != null)
                    ListTile(
                      title: Text(l.checkout_mobile_money_phone),
                      subtitle: Text(phone),
                    ),
                ],
              ),
            ),
          ),
          _Section(
            title: l.checkout_delivery_time,
            child: RadioGroup<bool>(
              groupValue: _scheduledAt != null,
              onChanged: (scheduled) => scheduled!
                  ? _pickSchedule()
                  : setState(() => _scheduledAt = null),
              child: Column(
                children: [
                  RadioListTile<bool>(
                    value: false,
                    title: Text(l.checkout_asap),
                  ),
                  RadioListTile<bool>(
                    value: true,
                    title: Text(l.checkout_schedule),
                    subtitle: _scheduledAt == null
                        ? null
                        : Text(
                            l.checkout_scheduled_for(
                              formatDateTime(_scheduledAt!, context.locale),
                            ),
                          ),
                  ),
                ],
              ),
            ),
          ),
          _Section(
            title: l.checkout_order_summary,
            child: Column(
              children: [
                for (final line in cart.lines)
                  ListTile(
                    dense: true,
                    title: Text(localizedLine(ref, line, cart.storeId!).name),
                    leading: Text(
                      '${line.quantity}×',
                      style: theme.textTheme.titleMedium,
                    ),
                    trailing: Text(
                      formatMoney(line.total, cart.currency, context.locale),
                    ),
                  ),
              ],
            ),
          ),
          quote.when(
            loading: () => const LoadingView(),
            error: (e, _) => ErrorView(
              error: e,
              onRetry: () => ref.invalidate(checkoutQuoteProvider(address?.id)),
            ),
            data: (q) => q == null
                ? const CartSummary()
                : CartSummary(
                    currency: q.currency,
                    totals: CartTotals(
                      subtotal: q.subtotal,
                      deliveryFee: q.deliveryFee,
                      serviceFee: q.serviceFee,
                      discount: q.discount,
                    ),
                  ),
          ),
          const SizedBox(height: 8),
          Text(l.checkout_estimate_note, style: theme.textTheme.bodySmall),
        ],
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: FilledButton(
            onPressed: _busy || !quoteOk || cart.isEmpty
                ? null
                : () => _placeOrder(address),
            child: _busy
                ? const SizedBox.square(
                    dimension: 22,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : Text(l.order_place_order),
          ),
        ),
      ),
    );
  }

  String _paymentLabel(PaymentMethod method) {
    final l = context.l10n;
    return switch (method) {
      PaymentMethod.cash => l.payment_cash,
      PaymentMethod.airtelMoney => l.payment_airtel_money,
      PaymentMethod.tnmMpamba => l.payment_tnm_mpamba,
    };
  }

  Future<void> _chooseAddress(List<Address> addresses) async {
    final chosen = await showModalBottomSheet<Object>(
      context: context,
      builder: (context) => SafeArea(
        child: ListView(
          shrinkWrap: true,
          children: [
            for (final a in addresses)
              ListTile(
                leading: const Icon(Icons.location_on_outlined),
                title: Text(a.name),
                subtitle: a.summary.isEmpty ? null : Text(a.summary),
                onTap: () => Navigator.pop(context, a),
              ),
            ListTile(
              leading: const Icon(Icons.add),
              title: Text(context.l10n.location_new_address),
              onTap: () => Navigator.pop(context, 'new'),
            ),
          ],
        ),
      ),
    );
    if (!mounted) return;
    if (chosen is Address) {
      setState(() => _addressId = chosen.id);
      await ref
          .read(deliveryLocationProvider.notifier)
          .set(DeliveryLocation.fromAddress(chosen));
    } else if (chosen == 'new') {
      context.push('/location/new');
    }
  }
}

class _Section extends StatelessWidget {
  const _Section({required this.title, required this.child, this.trailing});

  final String title;
  final Widget child;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  title,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
              ),
              ?trailing,
            ],
          ),
          const SizedBox(height: 6),
          Card(child: child),
        ],
      ),
    );
  }
}
