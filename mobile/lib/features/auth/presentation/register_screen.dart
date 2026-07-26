import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/error/error_mapper.dart';
import '../../../core/network/api_result.dart';
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

/// Email registration. On success routes to email verification with the email.
class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _referral = TextEditingController();

  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    _referral.dispose();
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

    final result = await ref.read(authControllerProvider.notifier).register(
          name: _name.text,
          email: _email.text,
          password: _password.text,
          referralCode: _referral.text.trim(),
        );

    if (!mounted) {
      return;
    }
    setState(() => _submitting = false);

    result.when(
      success: (_) => context.go(AuthRoutes.verify, extra: _email.text.trim()),
      failure: (error) => setState(() => _error = ErrorMapper.toMessage(error, context.l10n)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final strings = AuthStrings.of(context);

    return AuthScaffold(
      title: strings.registerTitle,
      subtitle: strings.registerSubtitle,
      showBack: true,
      children: [
        Form(
          key: _formKey,
          child: Column(
            children: [
              AppTextField(
                controller: _name,
                label: strings.name,
                textInputAction: TextInputAction.next,
                prefixIcon: const Icon(Icons.person_outline),
                validator: (v) => _mapError(AuthValidators.name(v), strings),
              ),
              const SizedBox(height: AppSpacing.md),
              AppTextField(
                controller: _email,
                label: strings.email,
                keyboardType: TextInputType.emailAddress,
                textInputAction: TextInputAction.next,
                prefixIcon: const Icon(Icons.mail_outline),
                validator: (v) => _mapError(AuthValidators.email(v), strings),
              ),
              const SizedBox(height: AppSpacing.md),
              AppTextField(
                controller: _password,
                label: strings.password,
                obscureText: true,
                textInputAction: TextInputAction.next,
                prefixIcon: const Icon(Icons.lock_outline),
                validator: (v) => _mapError(AuthValidators.password(v), strings),
              ),
              const SizedBox(height: AppSpacing.md),
              AppTextField(
                controller: _referral,
                label: strings.referralOptional,
                textInputAction: TextInputAction.done,
                prefixIcon: const Icon(Icons.card_giftcard_outlined),
                onSubmitted: (_) => _submit(),
              ),
            ],
          ),
        ),
        if (_error != null) AuthErrorText(_error!),
        const SizedBox(height: AppSpacing.lg),
        AppButton(label: strings.createAccount, onPressed: _submit, isLoading: _submitting),
        const SizedBox(height: AppSpacing.md),
        TextButton(
          onPressed: () => context.go(AuthRoutes.login),
          child: Text(strings.haveAccountPrompt),
        ),
      ],
    );
  }

  String? _mapError(AuthFieldError? error, AuthStrings strings) =>
      error == null ? null : strings.errorFor(error);
}
