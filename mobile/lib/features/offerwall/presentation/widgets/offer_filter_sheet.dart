import 'package:flutter/material.dart';

import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/offerwall_strings.dart';
import '../../models/offer_filter.dart';
import '../../models/offer_provider.dart';

/// Bottom sheet for editing the offer [OfferFilter]. Returns the new filter on
/// Apply, `null` on dismiss, or [OfferFilter.none] on Clear.
Future<OfferFilter?> showOfferFilterSheet(
  BuildContext context,
  OfferFilter current, {
  required List<OfferProvider> providers,
  bool showCategory = true,
  bool showCountry = false,
}) {
  return showModalBottomSheet<OfferFilter>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (context) => _OfferFilterSheet(
      initial: current,
      providers: providers,
      showCategory: showCategory,
      showCountry: showCountry,
    ),
  );
}

class _OfferFilterSheet extends StatefulWidget {
  const _OfferFilterSheet({
    required this.initial,
    required this.providers,
    required this.showCategory,
    required this.showCountry,
  });

  final OfferFilter initial;
  final List<OfferProvider> providers;
  final bool showCategory;
  final bool showCountry;

  @override
  State<_OfferFilterSheet> createState() => _OfferFilterSheetState();
}

class _OfferFilterSheetState extends State<_OfferFilterSheet> {
  late String? _provider = widget.initial.provider;
  late final TextEditingController _category =
      TextEditingController(text: widget.initial.category ?? '');
  late final TextEditingController _country =
      TextEditingController(text: widget.initial.country ?? '');

  @override
  void dispose() {
    _category.dispose();
    _country.dispose();
    super.dispose();
  }

  void _apply() {
    final category = _category.text.trim();
    final country = _country.text.trim();
    Navigator.of(context).pop(
      OfferFilter(
        provider: _provider,
        category: widget.showCategory && category.isNotEmpty ? category : null,
        country: widget.showCountry && country.isNotEmpty ? country.toUpperCase() : null,
      ),
    );
  }

  void _clear() => Navigator.of(context).pop(OfferFilter.none);

  @override
  Widget build(BuildContext context) {
    final s = OfferwallStrings.of(context);

    return SafeArea(
      child: Padding(
        padding: EdgeInsets.only(
          left: AppSpacing.lg,
          right: AppSpacing.lg,
          bottom: MediaQuery.viewInsetsOf(context).bottom + AppSpacing.lg,
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(s.filters, style: context.textTheme.titleLarge),
              const SizedBox(height: AppSpacing.lg),
              if (widget.providers.isNotEmpty) ...[
                Text(s.provider, style: context.textTheme.titleSmall),
                const SizedBox(height: AppSpacing.sm),
                Wrap(
                  spacing: AppSpacing.sm,
                  runSpacing: AppSpacing.xs,
                  children: [
                    ChoiceChip(
                      label: Text(s.all),
                      selected: _provider == null,
                      onSelected: (_) => setState(() => _provider = null),
                    ),
                    for (final p in widget.providers)
                      ChoiceChip(
                        label: Text(p.name),
                        selected: _provider == p.slug,
                        onSelected: (_) => setState(() => _provider = p.slug),
                      ),
                  ],
                ),
                const SizedBox(height: AppSpacing.lg),
              ],
              if (widget.showCategory) ...[
                Text(s.category, style: context.textTheme.titleSmall),
                const SizedBox(height: AppSpacing.sm),
                TextField(
                  controller: _category,
                  decoration: InputDecoration(hintText: s.category, isDense: true),
                ),
                const SizedBox(height: AppSpacing.lg),
              ],
              if (widget.showCountry) ...[
                Text('Country', style: context.textTheme.titleSmall),
                const SizedBox(height: AppSpacing.sm),
                TextField(
                  controller: _country,
                  textCapitalization: TextCapitalization.characters,
                  decoration: const InputDecoration(hintText: 'US', isDense: true),
                ),
                const SizedBox(height: AppSpacing.lg),
              ],
              Row(
                children: [
                  Expanded(child: OutlinedButton(onPressed: _clear, child: Text(s.clear))),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(child: FilledButton(onPressed: _apply, child: Text(s.apply))),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
