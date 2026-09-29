import 'package:bento_core/bento_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

import '../../core/config/app_config.dart';
import '../../core/ui/widgets.dart';
import 'geo_service.dart';

/// Pin picker: Google Maps with a fixed centre pin when maps are configured,
/// otherwise GPS-only (useful on low-end devices and before API keys exist).
class LocationPicker extends ConsumerStatefulWidget {
  const LocationPicker({
    super.key,
    required this.value,
    required this.onChanged,
    this.height = 260,
  });

  final GeoPoint? value;
  final ValueChanged<GeoPoint> onChanged;
  final double height;

  @override
  ConsumerState<LocationPicker> createState() => _LocationPickerState();
}

class _LocationPickerState extends ConsumerState<LocationPicker> {
  // Lilongwe city centre: initial camera when nothing is known yet.
  static const _defaultCenter = LatLng(-13.9626, 33.7741);

  GoogleMapController? _map;
  LatLng? _center;
  bool _locating = false;

  Future<void> _useCurrent() async {
    setState(() => _locating = true);
    try {
      final point = await ref.read(geoServiceProvider).current();
      widget.onChanged(point);
      await _map?.animateCamera(
        CameraUpdate.newLatLngZoom(LatLng(point.latitude, point.longitude), 16),
      );
    } on LocationPermissionDenied {
      if (mounted) {
        showMessage(context, context.l10n.location_permission_denied);
      }
    } catch (_) {
      if (mounted) showMessage(context, context.l10n.error_timeout);
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    final value = widget.value;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (AppConfig.mapsEnabled) ...[
          SizedBox(
            height: widget.height,
            child: ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: Stack(
                alignment: Alignment.center,
                children: [
                  GoogleMap(
                    initialCameraPosition: CameraPosition(
                      target: value == null
                          ? _defaultCenter
                          : LatLng(value.latitude, value.longitude),
                      zoom: 15,
                    ),
                    myLocationButtonEnabled: false,
                    zoomControlsEnabled: false,
                    onMapCreated: (c) => _map = c,
                    onCameraMove: (p) => _center = p.target,
                    onCameraIdle: () {
                      if (_center != null) {
                        widget.onChanged(
                          GeoPoint(_center!.latitude, _center!.longitude),
                        );
                      }
                    },
                  ),
                  const Padding(
                    padding: EdgeInsets.only(bottom: 36),
                    child: Icon(
                      Icons.location_pin,
                      size: 44,
                      color: Colors.redAccent,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            l.location_move_map,
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ],
        if (value != null && !AppConfig.mapsEnabled)
          Card(
            child: ListTile(
              leading: const Icon(Icons.my_location),
              title: Text(
                l.location_coordinates(
                  formatDecimal(value.latitude, context.locale),
                  formatDecimal(value.longitude, context.locale),
                ),
              ),
            ),
          ),
        const SizedBox(height: 8),
        OutlinedButton.icon(
          onPressed: _locating ? null : _useCurrent,
          icon: _locating
              ? const SizedBox.square(
                  dimension: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.my_location),
          label: Text(l.location_use_current),
        ),
      ],
    );
  }
}
