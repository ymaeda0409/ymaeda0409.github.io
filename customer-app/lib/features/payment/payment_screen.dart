import 'dart:async';

import 'package:bento_core/bento_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/config/app_config.dart';
import '../../core/providers.dart';
import '../../core/ui/code_labels.dart';
import '../../core/ui/widgets.dart';
import '../orders/order_providers.dart';
import 'payment.dart';

/// Poll interval while waiting for the customer to approve the USSD prompt.
final paymentPollIntervalProvider = Provider<Duration>(
  (ref) => const Duration(seconds: 3),
);

/// 11 Payment: Airtel Money / TNM Mpamba. The customer approves the charge on their phone
/// (operator prompt + PIN); the app polls until it is PAID or FAILED.
class PaymentScreen extends ConsumerStatefulWidget {
  const PaymentScreen({super.key, required this.orderId});

  final int orderId;

  @override
  ConsumerState<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends ConsumerState<PaymentScreen> {
  final _phone = TextEditingController();
  Payment? _payment;
  Object? _error;
  bool _busy = false;
  Timer? _poll;

  @override
  void initState() {
    super.initState();
    final phone = ref.read(sessionProvider).user?.phone;
    if (phone != null && phone.startsWith(AppConfig.phoneCountryCode)) {
      _phone.text = '0${phone.substring(AppConfig.phoneCountryCode.length)}';
    }
  }

  @override
  void dispose() {
    _poll?.cancel();
    _phone.dispose();
    super.dispose();
  }

  Future<void> _pay() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final digits = _phone.text.replaceAll(RegExp(r'\D'), '');
      final payment = await ref
          .read(paymentRepositoryProvider)
          .start(widget.orderId, digits.isEmpty ? null : digits);
      _update(payment);
    } catch (e) {
      setState(() => _error = e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _update(Payment payment) {
    if (!mounted) return;
    setState(() => _payment = payment);
    _poll?.cancel();
    if (payment.isPending) {
      _poll = Timer.periodic(ref.read(paymentPollIntervalProvider), (_) async {
        try {
          _update(await ref.read(paymentRepositoryProvider).status(payment.id));
        } catch (_) {
          // Keep waiting; the next poll retries.
        }
      });
    } else if (payment.isPaid) {
      ref.invalidate(orderDetailProvider(widget.orderId));
      context.go('/orders/${widget.orderId}/complete');
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    final theme = Theme.of(context);
    final order = ref.watch(orderDetailProvider(widget.orderId));
    final payment = _payment;

    return Scaffold(
      appBar: AppBar(
        title: Text(l.payment_title),
        leading: closeToHomeIfRoot(context),
      ),
      body: order.when(
        loading: () => const LoadingView(),
        error: (e, _) => ErrorView(
          error: e,
          onRetry: () => ref.invalidate(orderDetailProvider(widget.orderId)),
        ),
        data: (o) => ListView(
          padding: const EdgeInsets.all(24),
          children: [
            Text(l.payment_amount, style: theme.textTheme.titleMedium),
            Text(
              formatMoney(o.total, o.currency, context.locale),
              style: theme.textTheme.displaySmall,
            ),
            const SizedBox(height: 8),
            Text(
              paymentMethodText(l, o.paymentMethod),
              style: theme.textTheme.titleMedium,
            ),
            const SizedBox(height: 24),
            if (payment?.isPending ?? false) ...[
              const Center(child: CircularProgressIndicator()),
              const SizedBox(height: 16),
              Text(
                l.payment_waiting,
                textAlign: TextAlign.center,
                style: theme.textTheme.titleMedium,
              ),
            ] else ...[
              TextField(
                controller: _phone,
                keyboardType: TextInputType.phone,
                inputFormatters: [
                  FilteringTextInputFormatter.allow(RegExp(r'[0-9 ]')),
                ],
                decoration: InputDecoration(labelText: l.payment_phone_label),
              ),
              const SizedBox(height: 16),
              if (payment != null && payment.status == 'FAILED')
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Text(
                    l.payment_status_failed,
                    style: TextStyle(color: theme.colorScheme.error),
                  ),
                ),
              if (_error != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Text(
                    errorText(l, _error),
                    style: TextStyle(color: theme.colorScheme.error),
                  ),
                ),
              FilledButton(
                onPressed: _busy ? null : _pay,
                child: Text(
                  payment == null
                      ? l.payment_pay_with(
                          paymentMethodText(l, o.paymentMethod),
                        )
                      : l.payment_retry,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
