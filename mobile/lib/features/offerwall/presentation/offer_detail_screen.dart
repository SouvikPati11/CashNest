import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../core/theme/app_radius.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../l10n/offerwall_strings.dart';
import '../models/offer.dart';
import '../providers/offerwall_providers.dart';
import '../resources/offer_formatters.dart';

/// Offer detail with a "start offer" action that records a click and surfaces
/// the tracking link (copied to the clipboard; the app has no external browser
/// launcher dependency).
class OfferDetailScreen extends ConsumerStatefulWidget {
  const OfferDetailScreen({required this.offer, super.key});

  final Offer offer;

  @override
  ConsumerState<OfferDetailScreen> createState() => _OfferDetailScreenState();
}

class _OfferDetailScreenState extends ConsumerState<OfferDetailScreen> {
  bool _busy = false;

  Future<void> _start() async {
    if (_busy) {
      return;
    }
    setState(() => _busy = true);
    final s = OfferwallStrings.of(context);
    try {
      final result = await ref.read(offerwallRepositoryProvider).clickOffer(widget.offer.uuid);
      if (!mounted) {
        return;
      }
      switch (result) {
        case ApiSuccess(:final data):
          await Clipboard.setData(ClipboardData(text: data.redirectUrl));
          if (mounted) {
            _snack('${s.redirectReady} ${s.linkCopied}');
          }
        case ApiFailure(:final error):
          _snack(error.message);
      }
    } on AppException catch (e) {
      if (mounted) {
        _snack(e.message);
      }
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  void _snack(String message) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final s = OfferwallStrings.of(context);
    final offer = widget.offer;
    final localeCode = Localizations.localeOf(context).languageCode;

    return AppScaffold(
      appBar: AppBar(title: Text(s.offerDetails)),
      body: ListView(
        padding: const EdgeInsets.all(AppSpacing.screen),
        children: [
          Row(
            children: [
              ClipRRect(
                borderRadius: AppRadius.mdAll,
                child: SizedBox(
                  width: 64,
                  height: 64,
                  child: (offer.iconUrl != null && offer.iconUrl!.isNotEmpty)
                      ? Image.network(offer.iconUrl!, fit: BoxFit.cover,
                          errorBuilder: (c, e, st) => _iconFallback(context))
                      : _iconFallback(context),
                ),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Text(offer.title, style: context.textTheme.titleLarge),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.lg),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(s.reward, style: context.textTheme.titleMedium),
                  Text(
                    '+${OfferFormatters.coins(offer.payoutCoins, localeCode: localeCode)} ${s.coins}',
                    style: context.textTheme.titleMedium?.copyWith(
                      color: context.colors.primary,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
          ),
          if (offer.goal != null && offer.goal!.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.lg),
            Text(s.goal, style: context.textTheme.titleSmall),
            const SizedBox(height: AppSpacing.xs),
            Text(offer.goal!, style: context.textTheme.bodyMedium),
          ],
          if (offer.description != null && offer.description!.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.lg),
            Text(offer.description!, style: context.textTheme.bodyMedium),
          ],
          const SizedBox(height: AppSpacing.xl),
          FilledButton.icon(
            onPressed: _busy ? null : _start,
            icon: _busy
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                : const Icon(Icons.open_in_new),
            label: Text(_busy ? s.opening : s.open),
          ),
        ],
      ),
    );
  }

  Widget _iconFallback(BuildContext context) {
    return ColoredBox(
      color: context.colors.secondaryContainer,
      child: Icon(Icons.local_offer, color: context.colors.onSecondaryContainer),
    );
  }
}
