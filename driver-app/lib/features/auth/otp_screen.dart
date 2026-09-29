import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import '../../core/ui/code_labels.dart';
import '../../core/ui/widgets.dart';
import 'auth_repository.dart';

/// 02 Login (OTP)
class OtpScreen extends ConsumerStatefulWidget {
  const OtpScreen({super.key, required this.phone});

  final String phone;

  @override
  ConsumerState<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends ConsumerState<OtpScreen> {
  static const _resendSeconds = 60;

  final _controller = TextEditingController();
  Timer? _timer;
  int _remaining = _resendSeconds;
  bool _busy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _startCountdown();
  }

  void _startCountdown() {
    _timer?.cancel();
    setState(() => _remaining = _resendSeconds);
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (_remaining <= 1) t.cancel();
      if (mounted) setState(() => _remaining--);
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  Future<void> _verify() async {
    if (_controller.text.length != 6 || _busy) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final token = await ref
          .read(authRepositoryProvider)
          .verifyOtp(widget.phone, _controller.text);
      await ref.read(sessionProvider.notifier).signIn(token);
      // Keep the account language (used for push/SMS) in sync with the app choice.
      await ref
          .read(localeProvider.notifier)
          .select(ref.read(effectiveLocaleProvider));
    } on NotADriver {
      if (mounted) setState(() => _error = context.l10n.auth_not_a_driver);
    } catch (e) {
      if (mounted) setState(() => _error = errorText(context.l10n, e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _resend() async {
    try {
      await ref.read(authRepositoryProvider).sendOtp(widget.phone);
      _startCountdown();
    } catch (e) {
      if (mounted) showError(context, e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    return Scaffold(
      appBar: AppBar(),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            Text(
              l.auth_otp_title,
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: 8),
            Text(l.auth_otp_sent_to(widget.phone)),
            const SizedBox(height: 24),
            TextField(
              controller: _controller,
              autofocus: true,
              keyboardType: TextInputType.number,
              textAlign: TextAlign.center,
              maxLength: 6,
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              style: Theme.of(context).textTheme.headlineMedium
                  ?.copyWith(letterSpacing: 12),
              decoration: InputDecoration(
                counterText: '',
                errorText: _error,
                errorMaxLines: 3,
              ),
              onChanged: (v) {
                if (v.length == 6) _verify();
              },
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _busy ? null : _verify,
              child: Text(l.auth_verify),
            ),
            const SizedBox(height: 12),
            TextButton(
              onPressed: _remaining > 0 ? null : _resend,
              child: Text(
                _remaining > 0 ? l.auth_resend_in(_remaining) : l.auth_resend,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
