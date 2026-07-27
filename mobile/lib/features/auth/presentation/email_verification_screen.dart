import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/error_mapper.dart';
import '../../../core/network/api_result.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_text_field.dart';
import '../l10n/auth_strings.dart';
import '../providers/auth_providers.dart';
import '../validation/auth_validators.dart';
import 'widgets/auth_form_widgets.dart';
import 'widgets/auth_scaffold.dart';

/// Email verification via 6-digit OTP. On success the session is established and
/// the router guard routes to home.
class EmailVerificationScreen extends ConsumerStatefulWidget {
  const EmailVerificationScreen({required this.email, super.key});

  final String email;

  @override
  ConsumerState<EmailVerificationScreen> createState() => _EmailVerificationScreenState();
}

class _EmailVerificationScreenState extends ConsumerState<EmailVerificationScreen> {
  final _formKey = GlobalKey<FormState>();
  final _otp = TextEditingController();

  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _otp.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) {
      return;
    }
    setState(() {
      _submitting = true;
      _error = null;
    });

    final result = await ref.read(authControllerProvider.notifier).verifyEmail(
          email: widget.email,
          otp: _otp.text,
        );

    if (!mounted) {
      return;
    }
    setState(() => _submitting = false);
    if (result case ApiFailure(:final error)) {
      final message = ErrorMapper.toMessage(error, context.l10n);
      setState(() => _error = message);
    }
    // On success the auth guard redirects to home automatically.
  }

  @override
  Widget build(BuildContext context) {
    final strings = AuthStrings.of(context);

    return AuthScaffold(
      title: strings.verifyTitle,
      subtitle: '${strings.verifySubtitle}\n${widget.email}',
      showBack: true,
      children: [
        Form(
          key: _formKey,
          child: AppTextField(
            controller: _otp,
            label: strings.otpLabel,
            keyboardType: TextInputType.number,
            textInputAction: TextInputAction.done,
            prefixIcon: const Icon(Icons.pin_outlined),
            inputFormatters: [
              FilteringTextInputFormatter.digitsOnly,
              LengthLimitingTextInputFormatter(6),
            ],
            onSubmitted: (_) => _submit(),
            validator: (v) {
              final error = AuthValidators.otp(v);
              return error == null ? null : strings.errorFor(error);
            },
          ),
        ),
        if (_error != null) AuthErrorText(_error!),
        const SizedBox(height: AppSpacing.lg),
        AppButton(label: strings.verify, onPressed: _submit, isLoading: _submitting),
      ],
    );
  }
}
