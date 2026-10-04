import 'dart:async';

import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/ui/widgets.dart';
import 'driver_controller.dart';
import 'location_source.dart';

final syncIntervalProvider = Provider<Duration>(
  (ref) => const Duration(seconds: 10),
);

/// Keeps the rider in sync while the home screen is visible: periodic polling
/// (requests, cancellations, offline replay) and GPS tracking while online.
/// Timers/streams live in the widget so they stop when the screen goes away.
class DriverSyncScope extends ConsumerStatefulWidget {
  const DriverSyncScope({super.key, required this.child});

  final Widget child;

  @override
  ConsumerState<DriverSyncScope> createState() => _DriverSyncScopeState();
}

class _DriverSyncScopeState extends ConsumerState<DriverSyncScope> {
  Timer? _timer;
  StreamSubscription<Object?>? _gps;

  @override
  void initState() {
    super.initState();
    _tick();
    _timer = Timer.periodic(ref.read(syncIntervalProvider), (_) => _tick());
  }

  Future<void> _tick() async {
    try {
      await ref.read(driverControllerProvider.notifier).tick();
    } catch (_) {
      // Offline or server hiccup: the next tick retries; cached delivery stays usable.
    }
  }

  void _updateTracking(bool online) {
    if (online && _gps == null) {
      _gps = ref
          .read(locationSourceProvider)
          .watch(
            notice: BackgroundNotice(
              title: context.l10n.driver_gps_notice_title,
              text: context.l10n.driver_gps_notice_text,
            ),
          )
          .listen(
            (p) =>
                ref.read(driverControllerProvider.notifier).recordPosition(p),
            onError: (_) {},
          );
    } else if (!online && _gps != null) {
      _gps!.cancel();
      _gps = null;
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    _gps?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final online = ref.watch(driverControllerProvider.select((s) => s.online));
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _updateTracking(online);
    });
    return widget.child;
  }
}
