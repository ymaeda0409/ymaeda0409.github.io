import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_exception.dart';
import '../../core/ui/widgets.dart';
import 'address.dart';
import 'address_repository.dart';
import 'delivery_location_controller.dart';
import 'geo_service.dart';
import 'location_picker.dart';

/// New saved address (GPS point + landmark-oriented fields, which matter more than
/// street names in many Malawian neighbourhoods).
class AddressFormScreen extends ConsumerStatefulWidget {
  const AddressFormScreen({super.key});

  @override
  ConsumerState<AddressFormScreen> createState() => _AddressFormScreenState();
}

class _AddressFormScreenState extends ConsumerState<AddressFormScreen> {
  final _form = GlobalKey<FormState>();
  final _fields = {
    for (final f in ['name', 'area', 'street', 'building', 'landmark', 'delivery_note']) f: TextEditingController(),
  };
  GeoPoint? _point;
  ApiException? _error;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    final current = ref.read(deliveryLocationProvider);
    if (current != null) _point = GeoPoint(current.latitude, current.longitude);
  }

  @override
  void dispose() {
    for (final c in _fields.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    if (!_form.currentState!.validate() || _point == null) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final address = await ref.read(addressRepositoryProvider).create({
        for (final e in _fields.entries)
          if (e.value.text.trim().isNotEmpty) e.key: e.value.text.trim(),
        'latitude': _point!.latitude,
        'longitude': _point!.longitude,
      });
      ref.invalidate(addressesProvider);
      await ref.read(deliveryLocationProvider.notifier).set(DeliveryLocation.fromAddress(address));
      if (mounted) context.pop();
    } on ApiException catch (e) {
      setState(() => _error = e);
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Widget _field(String key, String label, {String? hint, bool required = false, int maxLines = 1}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextFormField(
        controller: _fields[key],
        maxLines: maxLines,
        decoration: InputDecoration(labelText: label, hintText: hint, errorText: _error?.fieldMessage(key)),
        validator: required ? (v) => (v == null || v.trim().isEmpty) ? context.l10n.common_required : null : null,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    return Scaffold(
      appBar: AppBar(title: Text(l.location_new_address)),
      body: Form(
        key: _form,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            LocationPicker(value: _point, onChanged: (p) => setState(() => _point = p), height: 220),
            const SizedBox(height: 16),
            _field('name', l.location_name_label, required: true),
            _field('landmark', l.location_landmark_label, hint: l.location_landmark_hint),
            _field('area', l.location_area_label),
            _field('street', l.location_street_label),
            _field('building', l.location_building_label),
            _field('delivery_note', l.location_note_label, maxLines: 2),
          ],
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: FilledButton(
            onPressed: _busy || _point == null ? null : _save,
            child: Text(l.common_save),
          ),
        ),
      ),
    );
  }
}
