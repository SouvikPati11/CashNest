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

/// Email/password sign-in with Google sign-in and links to register/forgot.
///
/// On success the auth state flips to authenticated and the router guard
/// redirects to home, so no manual navigation is needed here.
class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();

  bool _submitting = false;
  bool _googleBusy = false;
  String? _error;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
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

    final result = await ref.read(authControllerProvider.notifier).loginWithEmail(
          email: _email.text,
          password: _password.text,
        );

    if (!mounted) {
      return;
    }
    setState(() => _submitting = false);
    if (result case ApiFailure(:final error)) {
      final message = ErrorMapper.toMessage(error, context.l10n);
      setState(() => _error = message);
    }
  }

  Future<void> _google() async {
    setState(() {
      _googleBusy = true;
      _error = null;
    });

    final result = await ref.read(authControllerProvider.notifier).signInWithGoogle();

    if (!mounted) {
      return;
    }
    setState(() => _googleBusy = false);
    if (result == null) {
      setState(() => _error = AuthStrings.of(context).googleCancelled);
    } else if (result case ApiFailure(:final error)) {
      final message = ErrorMapper.toMessage(error, context.l10n);
      setState(() => _error = message);
    }
  }

  @override
  Widget build(BuildContext context) {
    final strings = AuthStrings.of(context);

    return AuthScaffold(
      title: strings.loginTitle,
      subtitle: strings.loginSubtitle,
      children: [
        Form(
          key: _formKey,
          child: Column(
            children: [
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
                textInputAction: TextInputAction.done,
                prefixIcon: const Icon(Icons.lock_outline),
                onSubmitted: (_) => _submit(),
                validator: (v) => _mapError(AuthValidators.password(v), strings),
              ),
            ],
          ),
        ),
        Align(
          alignment: Alignment.centerRight,
          child: TextButton(
            onPressed: () => context.push(AuthRoutes.forgot),
            child: Text(strings.forgotPassword),
          ),
        ),
        if (_error != null) AuthErrorText(_error!),
        const SizedBox(height: AppSpacing.sm),
        AppButton(label: strings.signIn, onPressed: _submit, isLoading: _submitting),
        const SizedBox(height: AppSpacing.md),
        AuthOrDivider(label: strings.orDivider),
        const SizedBox(height: AppSpacing.md),
        AppButton(
          label: strings.continueWithGoogle,
          onPressed: _google,
          variant: AppButtonVariant.secondary,
          icon: Icons.login,
          isLoading: _googleBusy,
        ),
        const SizedBox(height: AppSpacing.lg),
        TextButton(
          onPressed: () => context.push(AuthRoutes.register),
          child: Text(strings.noAccountPrompt),
        ),
      ],
    );
  }

  String? _mapError(AuthFieldError? error, AuthStrings strings) =>
      error == null ? null : strings.errorFor(error);
}
