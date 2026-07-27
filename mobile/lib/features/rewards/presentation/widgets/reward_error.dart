import 'package:flutter/material.dart';

import '../../../../core/error/app_exception.dart';
import '../../../../shared/widgets/error_view.dart';
import '../../l10n/rewards_strings.dart';

/// A scrollable error view so it works inside a [RefreshIndicator]
/// (pull-to-refresh) even when the content is short.
class RewardErrorView extends StatelessWidget {
  const RewardErrorView({required this.error, required this.onRetry, super.key});

  final Object error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    final err = error;
    return LayoutBuilder(
      builder: (context, constraints) => SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: ConstrainedBox(
          constraints: BoxConstraints(minHeight: constraints.maxHeight),
          child: ErrorView(
            message: err is AppException ? err.message : s.errorTitle,
            onRetry: onRetry,
          ),
        ),
      ),
    );
  }
}
