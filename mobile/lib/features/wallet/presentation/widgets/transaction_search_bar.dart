import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../l10n/wallet_strings.dart';

/// Search field for client-side filtering of the loaded transactions.
class TransactionSearchBar extends StatefulWidget {
  const TransactionSearchBar({
    required this.onChanged,
    this.initialValue = '',
    super.key,
  });

  final ValueChanged<String> onChanged;
  final String initialValue;

  @override
  State<TransactionSearchBar> createState() => _TransactionSearchBarState();
}

class _TransactionSearchBarState extends State<TransactionSearchBar> {
  late final TextEditingController _controller =
      TextEditingController(text: widget.initialValue);

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final s = WalletStrings.of(context);
    return TextField(
      controller: _controller,
      textInputAction: TextInputAction.search,
      onChanged: widget.onChanged,
      decoration: InputDecoration(
        hintText: s.searchHint,
        prefixIcon: const Icon(Icons.search),
        isDense: true,
        border: OutlineInputBorder(borderRadius: AppRadius.pillAll),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.lg,
          vertical: AppSpacing.md,
        ),
        suffixIcon: ValueListenableBuilder<TextEditingValue>(
          valueListenable: _controller,
          builder: (context, value, _) {
            if (value.text.isEmpty) {
              return const SizedBox.shrink();
            }
            return IconButton(
              icon: const Icon(Icons.close),
              tooltip: s.clear,
              onPressed: () {
                _controller.clear();
                widget.onChanged('');
              },
            );
          },
        ),
      ),
    );
  }
}
