import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/config/app_config.dart';
import '../../core/ui/widgets.dart';
import 'auth_repository.dart';

/// 03 Login / Phone
class PhoneScreen extends ConsumerStatefulWidget {
  const PhoneScreen({super.key, this.from});

  final String? from;

  @override
  ConsumerState<PhoneScreen> createState() => _PhoneScreenState();
}

class _PhoneScreenState extends ConsumerState<PhoneScreen> {
  final _controller = TextEditingController();
  bool _busy = false;
  String? _fieldError;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final digits = _controller.text.replaceAll(RegExp(r'\D'), '');
    if (digits.length < 8 || digits.length > 10) {
      setState(() => _fieldError = context.l10n.auth_phone_invalid);
      return;
    }
    setState(() {
      _busy = true;
      _fieldError = null;
    });
    try {
      final request = await ref
          .read(authRepositoryProvider)
          .sendOtp(
            '${AppConfig.phoneCountryCode}${digits.replaceFirst(RegExp('^0'), '')}',
          );
      if (!mounted) return;
      context.push(
        Uri(
          path: '/login/otp',
          queryParameters: {
            'phone': request.phone,
            if (widget.from != null) 'from': widget.from,
          },
        ).toString(),
      );
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    return Scaffold(
      appBar: AppBar(leading: closeToHomeIfRoot(context)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            Text(
              l.auth_phone_title,
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            if (widget.from != null) ...[
              const SizedBox(height: 8),
              Text(l.auth_login_required),
            ],
            const SizedBox(height: 24),
            TextField(
              controller: _controller,
              keyboardType: TextInputType.phone,
              autofocus: true,
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'[0-9 ]')),
              ],
              style: Theme.of(context).textTheme.titleLarge,
              decoration: InputDecoration(
                labelText: l.auth_phone_label,
                prefixText: '${AppConfig.phoneCountryCode} ',
                errorText: _fieldError,
              ),
              onSubmitted: (_) => _submit(),
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _busy ? null : _submit,
              child: _busy
                  ? const SizedBox.square(
                      dimension: 22,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : Text(l.auth_send_code),
            ),
          ],
        ),
      ),
    );
  }
}
