import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/error/app_exception.dart';
import '../../l10n/referral_strings.dart';
import '../../providers/referral_providers.dart';

/// Shows a dialog to enter and apply a referral code.
Future<void> showApplyCodeDialog(BuildContext context) {
  return showDialog<void>(
    context: context,
    builder: (context) => const _ApplyCodeDialog(),
  );
}

class _ApplyCodeDialog extends ConsumerStatefulWidget {
  const _ApplyCodeDialog();

  @override
  ConsumerState<_ApplyCodeDialog> createState() => _ApplyCodeDialogState();
}

class _ApplyCodeDialogState extends ConsumerState<_ApplyCodeDialog> {
  final TextEditingController _controller = TextEditingController();
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _apply() async {
    final code = _controller.text.trim();
    if (code.isEmpty) {
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    final s = ReferralStrings.of(context);
    try {
      final applied = await ref.read(referralInfoControllerProvider.notifier).applyCode(code);
      if (!mounted) {
        return;
      }
      Navigator.of(context).pop();
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(applied ? s.applied : s.errorTitle)));
    } on AppException catch (e) {
      if (mounted) {
        setState(() {
          _busy = false;
          _error = e.message;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = ReferralStrings.of(context);
    return AlertDialog(
      title: Text(s.haveACode),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          TextField(
            controller: _controller,
            autofocus: true,
            textCapitalization: TextCapitalization.characters,
            decoration: InputDecoration(
              hintText: s.enterCode,
              errorText: _error,
            ),
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: _busy ? null : () => Navigator.of(context).pop(),
          child: Text(context.materialCancel),
        ),
        FilledButton(
          onPressed: _busy ? null : _apply,
          child: _busy
              ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
              : Text(s.apply),
        ),
      ],
    );
  }
}

extension on BuildContext {
  String get materialCancel => MaterialLocalizations.of(this).cancelButtonLabel;
}
