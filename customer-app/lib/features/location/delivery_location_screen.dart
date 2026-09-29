import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/providers.dart';
import '../../core/ui/widgets.dart';
import 'address.dart';
import 'address_repository.dart';
import 'delivery_location_controller.dart';
import 'geo_service.dart';
import 'location_picker.dart';

/// 06 Delivery Location: pin/GPS, or pick a saved address.
class DeliveryLocationScreen extends ConsumerStatefulWidget {
  const DeliveryLocationScreen({super.key});

  @override
  ConsumerState<DeliveryLocationScreen> createState() =>
      _DeliveryLocationScreenState();
}

class _DeliveryLocationScreenState
    extends ConsumerState<DeliveryLocationScreen> {
  GeoPoint? _point;

  @override
  void initState() {
    super.initState();
    final current = ref.read(deliveryLocationProvider);
    if (current != null) _point = GeoPoint(current.latitude, current.longitude);
  }

  Future<void> _use(DeliveryLocation location) async {
    await ref.read(deliveryLocationProvider.notifier).set(location);
    if (mounted) context.pop();
  }

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    final signedIn = ref.watch(sessionProvider).isSignedIn;
    final addresses = ref.watch(addressesProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l.location_title)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          LocationPicker(
            value: _point,
            onChanged: (p) => setState(() => _point = p),
          ),
          const SizedBox(height: 12),
          FilledButton(
            onPressed: _point == null
                ? null
                : () => _use(
                    DeliveryLocation(
                      latitude: _point!.latitude,
                      longitude: _point!.longitude,
                    ),
                  ),
            child: Text(l.location_confirm),
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: Text(
                  l.location_saved_addresses,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
              ),
              TextButton.icon(
                onPressed: () => context.push('/location/new'),
                icon: const Icon(Icons.add),
                label: Text(l.location_new_address),
              ),
            ],
          ),
          if (signedIn)
            addresses.when(
              loading: () => const LoadingView(),
              error: (e, _) => ErrorView(
                error: e,
                onRetry: () => ref.invalidate(addressesProvider),
              ),
              data: (items) => items.isEmpty
                  ? Padding(
                      padding: const EdgeInsets.all(8),
                      child: Text(l.address_empty),
                    )
                  : Column(
                      children: [
                        for (final a in items)
                          Card(
                            child: ListTile(
                              leading: Icon(
                                a.isDefault
                                    ? Icons.home
                                    : Icons.location_on_outlined,
                              ),
                              title: Text(a.name),
                              subtitle: a.summary.isEmpty
                                  ? null
                                  : Text(a.summary),
                              onTap: () =>
                                  _use(DeliveryLocation.fromAddress(a)),
                            ),
                          ),
                      ],
                    ),
            ),
        ],
      ),
    );
  }
}
