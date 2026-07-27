import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/error/error_mapper.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_text_field.dart';
import '../l10n/auth_strings.dart';
import '../providers/auth_providers.dart';
import '../routing/auth_routes.dart';
import '../validation/auth_validators.dart';
import 'widgets/auth_form_widgets.dart';
import 'widgets/auth_scaffold.dart';

/// Forgot-password placeholder.
///
/// Sends the (enumeration-safe) reset request and shows a generic confirmation.
/// The full reset-token form is intentionally out of scope for this module.
class ForgotPasswordScreen extends ConsumerStatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  ConsumerState<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends ConsumerState<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();

  bool _submitting = false;
  bool _sent = false;
  String? _error;

  @override
  void dispose() {
    _email.dispose();
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

    final result = await ref
        .read(authControllerProvider.notifier)
        .requestPasswordReset(email: _email.text);

    if (!mounted) {
      return;
    }
    setState(() => _submitting = false);
    result.when(
      success: (_) => setState(() => _sent = true),
      failure: (error) => setState(() => _error = ErrorMapper.toMessage(error, context.l10n)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final strings = AuthStrings.of(context);

    return AuthScaffold(
      title: strings.forgotTitle,
      subtitle: strings.forgotSubtitle,
      showBack: true,
      children: [
        if (_sent)
          AuthErrorText(strings.resetLinkSent)
        else ...[
          Form(
            key: _formKey,
            child: AppTextField(
              controller: _email,
              label: strings.email,
              keyboardType: TextInputType.emailAddress,
              textInputAction: TextInputAction.done,
              prefixIcon: const Icon(Icons.mail_outline),
              onSubmitted: (_) => _submit(),
              validator: (v) {
                final error = AuthValidators.email(v);
                return error == null ? null : strings.errorFor(error);
              },
            ),
          ),
          if (_error != null) AuthErrorText(_error!),
          const SizedBox(height: AppSpacing.lg),
          AppButton(label: strings.sendResetLink, onPressed: _submit, isLoading: _submitting),
        ],
        const SizedBox(height: AppSpacing.md),
        TextButton(
          onPressed: () => context.go(AuthRoutes.login),
          child: Text(strings.backToLogin),
        ),
      ],
    );
  }
}
