import 'dart:async';

import 'package:bento_core/bento_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/ui/code_labels.dart';
import '../../core/ui/widgets.dart';
import 'driver_controller.dart';
import 'launcher.dart';
import 'location_source.dart';
import 'models.dart';
import 'sync_scope.dart';

/// 03 Home: ONLINE/OFFLINE (04), new request (05), pickup (06–07), delivery (08–09), PIN (10).
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    final state = ref.watch(driverControllerProvider);
    final active = state.active;

    return DriverSyncScope(
      child: Scaffold(
        appBar: AppBar(
          title: Text(l.driver_home_title),
          actions: [
            IconButton(
              tooltip: l.driver_history_title,
              icon: const Icon(Icons.history),
              onPressed: () => context.push('/history'),
            ),
            IconButton(
              tooltip: l.driver_settings,
              icon: const Icon(Icons.settings_outlined),
              onPressed: () => context.push('/settings'),
            ),
          ],
        ),
        body: SafeArea(
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (state.pendingSync)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Card(
                    color: Theme.of(context).colorScheme.tertiaryContainer,
                    child: ListTile(
                      leading: const Icon(Icons.cloud_upload_outlined),
                      title: Text(l.driver_pending_sync),
                    ),
                  ),
                ),
              if (active != null)
                _ActiveDelivery(delivery: active)
              else ...[
                _AvailabilityCard(online: state.online),
                const SizedBox(height: 16),
                if (state.online && state.request != null)
                  _RequestCard(request: state.request!),
                if (state.online && state.request == null)
                  Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      children: [
                        const CircularProgressIndicator(),
                        const SizedBox(height: 16),
                        Text(
                          l.driver_waiting_requests,
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                if (!state.online)
                  Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text(
                      l.driver_offline_hint,
                      textAlign: TextAlign.center,
                    ),
                  ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _AvailabilityCard extends ConsumerStatefulWidget {
  const _AvailabilityCard({required this.online});

  final bool online;

  @override
  ConsumerState<_AvailabilityCard> createState() => _AvailabilityCardState();
}

class _AvailabilityCardState extends ConsumerState<_AvailabilityCard> {
  bool _busy = false;

  Future<void> _toggle() async {
    setState(() => _busy = true);
    final controller = ref.read(driverControllerProvider.notifier);
    try {
      widget.online
          ? await controller.goOffline()
          : await controller.goOnline();
    } on LocationPermissionDenied {
      if (mounted) showMessage(context, context.l10n.driver_location_required);
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    final scheme = Theme.of(context).colorScheme;
    return Card(
      color: widget.online
          ? scheme.primaryContainer
          : scheme.surfaceContainerHighest,
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                Icon(
                  widget.online ? Icons.circle : Icons.circle_outlined,
                  color: widget.online ? Colors.green : Colors.grey,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    widget.online
                        ? l.driver_status_online
                        : l.driver_status_offline,
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            FilledButton(
              style: widget.online
                  ? FilledButton.styleFrom(
                      backgroundColor: Colors.grey.shade700,
                    )
                  : null,
              onPressed: _busy ? null : _toggle,
              child: Text(
                widget.online ? l.driver_go_offline : l.driver_go_online,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// 05 New Delivery Request with a live countdown.
class _RequestCard extends ConsumerStatefulWidget {
  const _RequestCard({required this.request});

  final DeliveryRequest request;

  @override
  ConsumerState<_RequestCard> createState() => _RequestCardState();
}

class _RequestCardState extends ConsumerState<_RequestCard> {
  Timer? _timer;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    HapticFeedback.heavyImpact();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) => setState(() {}));
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _run(Future<void> Function() action) async {
    setState(() => _busy = true);
    try {
      await action();
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    final d = widget.request.delivery;
    final seconds = widget.request.expiresAt
        .difference(DateTime.now())
        .inSeconds
        .clamp(0, 999);
    final controller = ref.read(driverControllerProvider.notifier);
    String km(double v) => formatDecimal(v, context.locale, digits: 1);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Wrap, not Row: the countdown text length varies a lot between languages.
            Wrap(
              spacing: 8,
              runSpacing: 4,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                Text(
                  l.driver_new_delivery,
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
                Chip(label: Text(l.driver_request_expires_in(seconds))),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              d.pickup.name ?? '',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            if (widget.request.distanceToPickupKm != null)
              Text(
                l.driver_distance_to_pickup(
                  km(widget.request.distanceToPickupKm!),
                ),
              ),
            Text(l.driver_delivery_distance(km(d.distanceKm))),
            Text(l.driver_items_count(d.itemCount)),
            const SizedBox(height: 8),
            _PaymentLine(delivery: d),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _busy || seconds == 0
                  ? null
                  : () => _run(controller.accept),
              child: Text(l.driver_accept),
            ),
            const SizedBox(height: 8),
            OutlinedButton(
              onPressed: _busy ? null : () => _run(controller.decline),
              child: Text(l.driver_decline),
            ),
          ],
        ),
      ),
    );
  }
}

class _PaymentLine extends StatelessWidget {
  const _PaymentLine({required this.delivery});

  final Delivery delivery;

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    final collect = delivery.amountToCollect > 0;
    return Row(
      children: [
        Icon(collect ? Icons.payments_outlined : Icons.check_circle_outline),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            collect
                ? l.driver_collect_cash(
                    formatMoney(
                      delivery.amountToCollect,
                      delivery.currency,
                      context.locale,
                    ),
                  )
                : l.driver_prepaid,
            style: Theme.of(context).textTheme.titleMedium,
          ),
        ),
      ],
    );
  }
}

/// 06–10: the steps of the current delivery, chosen by its status.
class _ActiveDelivery extends ConsumerWidget {
  const _ActiveDelivery({required this.delivery});

  final Delivery delivery;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Wrap(
          spacing: 8,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            Text(
              l.driver_order_number(delivery.orderNumber),
              style: Theme.of(context).textTheme.titleMedium,
            ),
            Chip(label: Text(deliveryStatusText(l, delivery.status))),
          ],
        ),
        const SizedBox(height: 12),
        switch (delivery.status) {
          'RIDER_ASSIGNED' => _PickupStep(delivery: delivery),
          'PICKED_UP' || 'ON_THE_WAY' => _DropoffStep(delivery: delivery),
          'ARRIVED' => _PinStep(delivery: delivery),
          _ => const LoadingView(),
        },
      ],
    );
  }
}

class _PlaceCard extends ConsumerWidget {
  const _PlaceCard({required this.title, required this.place});

  final String title;
  final Place place;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    final launch = ref.read(externalLauncherProvider);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(title, style: Theme.of(context).textTheme.labelLarge),
            const SizedBox(height: 4),
            if (place.name != null)
              Text(place.name!, style: Theme.of(context).textTheme.titleLarge),
            for (final line in place.lines) Text(line),
            if (place.landmark?.isNotEmpty ?? false) ...[
              const SizedBox(height: 8),
              Text(
                l.driver_landmark,
                style: Theme.of(context).textTheme.labelMedium,
              ),
              Text(
                place.landmark!,
                style: Theme.of(context).textTheme.titleMedium,
              ),
            ],
            if (place.note?.isNotEmpty ?? false) ...[
              const SizedBox(height: 8),
              Text(
                l.driver_note,
                style: Theme.of(context).textTheme.labelMedium,
              ),
              Text(place.note!),
            ],
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                FilledButton.tonalIcon(
                  onPressed: () =>
                      launch(navigationUri(place.latitude, place.longitude)),
                  icon: const Icon(Icons.navigation_outlined),
                  label: Text(l.driver_navigate),
                ),
                if (place.phone != null)
                  FilledButton.tonalIcon(
                    onPressed: () => launch(phoneUri(place.phone!)),
                    icon: const Icon(Icons.call_outlined),
                    label: Text(l.driver_call),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _StepButton extends StatefulWidget {
  const _StepButton({required this.label, required this.onPressed});

  final String label;
  final Future<void> Function() onPressed;

  @override
  State<_StepButton> createState() => _StepButtonState();
}

class _StepButtonState extends State<_StepButton> {
  bool _busy = false;

  @override
  Widget build(BuildContext context) {
    return FilledButton(
      onPressed: _busy
          ? null
          : () async {
              setState(() => _busy = true);
              try {
                await widget.onPressed();
              } catch (e) {
                if (context.mounted) showError(context, e);
              } finally {
                if (mounted) setState(() => _busy = false);
              }
            },
      child: Text(widget.label),
    );
  }
}

/// 06 Pickup Navigation + 07 Pickup Confirmation
class _PickupStep extends ConsumerWidget {
  const _PickupStep({required this.delivery});

  final Delivery delivery;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _PlaceCard(title: l.driver_pickup_at, place: delivery.pickup),
        const SizedBox(height: 12),
        Text(
          l.driver_items_count(delivery.itemCount),
          style: Theme.of(context).textTheme.titleMedium,
        ),
        const SizedBox(height: 16),
        _StepButton(
          label: l.driver_confirm_pickup,
          onPressed: () => ref.read(driverControllerProvider.notifier).pickup(),
        ),
      ],
    );
  }
}

/// 08 Delivery Navigation + 09 Customer Arrival
class _DropoffStep extends ConsumerWidget {
  const _DropoffStep({required this.delivery});

  final Delivery delivery;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _PlaceCard(title: l.driver_deliver_to, place: delivery.dropoff),
        const SizedBox(height: 12),
        _PaymentLine(delivery: delivery),
        const SizedBox(height: 16),
        _StepButton(
          label: l.driver_arrived,
          onPressed: () => ref.read(driverControllerProvider.notifier).arrive(),
        ),
      ],
    );
  }
}

/// 10 Delivery PIN
class _PinStep extends ConsumerStatefulWidget {
  const _PinStep({required this.delivery});

  final Delivery delivery;

  @override
  ConsumerState<_PinStep> createState() => _PinStepState();
}

class _PinStepState extends ConsumerState<_PinStep> {
  String _pin = '';
  String? _error;
  bool _busy = false;

  void _press(String digit) {
    if (_pin.length < 4) setState(() => _pin += digit);
  }

  Future<void> _confirm() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(driverControllerProvider.notifier).complete(_pin);
      if (mounted) context.go('/complete');
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = errorText(context.l10n, e);
          _pin = '';
        });
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _fail() async {
    final l = context.l10n;
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        content: Text(l.driver_fail_confirm),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(l.common_cancel),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(l.common_ok),
          ),
        ],
      ),
    );
    if (ok != true) return;
    try {
      await ref
          .read(driverControllerProvider.notifier)
          .fail('CUSTOMER_UNAVAILABLE');
    } catch (e) {
      if (mounted) showError(context, e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    final theme = Theme.of(context);
    Widget key(String label, VoidCallback? onTap, {IconData? icon}) => Expanded(
      child: Padding(
        padding: const EdgeInsets.all(4),
        child: SizedBox(
          height: 64,
          child: FilledButton.tonal(
            onPressed: onTap,
            child: icon != null
                ? Icon(icon)
                : Text(label, style: theme.textTheme.headlineSmall),
          ),
        ),
      ),
    );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _PaymentLine(delivery: widget.delivery),
        const SizedBox(height: 16),
        Text(l.driver_pin_title, style: theme.textTheme.titleLarge),
        Text(l.driver_pin_hint),
        const SizedBox(height: 12),
        Text(
          List.generate(4, (i) => i < _pin.length ? _pin[i] : '•').join(' '),
          textAlign: TextAlign.center,
          style: theme.textTheme.displaySmall?.copyWith(
            fontWeight: FontWeight.bold,
          ),
        ),
        if (_error != null)
          Text(
            _error!,
            textAlign: TextAlign.center,
            style: TextStyle(color: theme.colorScheme.error),
          ),
        Text(
          l.driver_pin_attempts_left(widget.delivery.pinAttemptsLeft),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 8),
        for (final row in const [
          ['1', '2', '3'],
          ['4', '5', '6'],
          ['7', '8', '9'],
        ])
          Row(children: [for (final d in row) key(d, () => _press(d))]),
        Row(
          children: [
            key(
              '',
              _pin.isEmpty
                  ? null
                  : () => setState(
                      () => _pin = _pin.substring(0, _pin.length - 1),
                    ),
              icon: Icons.backspace_outlined,
            ),
            key('0', () => _press('0')),
            key('', null, icon: Icons.lock_outline),
          ],
        ),
        const SizedBox(height: 12),
        FilledButton(
          onPressed: _busy || _pin.length != 4 ? null : _confirm,
          child: Text(l.driver_confirm_delivery),
        ),
        const SizedBox(height: 8),
        TextButton(
          onPressed: _busy ? null : _fail,
          child: Text(l.driver_delivery_failed),
        ),
      ],
    );
  }
}
