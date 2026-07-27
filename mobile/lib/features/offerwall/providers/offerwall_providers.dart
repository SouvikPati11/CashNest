import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/di/providers.dart';
import '../application/offers_controller.dart';
import '../application/offerwall_history_controller.dart';
import '../application/paginated_list_state.dart';
import '../data/offerwall_repository.dart';
import '../data/offerwall_repository_impl.dart';
import '../models/offer.dart';
import '../models/offer_provider.dart';
import '../models/offerwall_history_entry.dart';

/// Riverpod wiring for the offerwall + CPA feature. Reuses the foundation API
/// client; no completed module is modified.

final offerwallRepositoryProvider = Provider<OfferwallRepository>(
  (ref) => OfferwallRepositoryImpl(ref.watch(apiClientProvider)),
);

/// Active offerwall providers (for filter chips).
final offerwallProvidersListProvider = FutureProvider<List<OfferProvider>>((ref) async {
  final result = await ref.watch(offerwallRepositoryProvider).fetchProviders();
  return result.dataOrNull ?? const [];
});

final offersControllerProvider =
    StateNotifierProvider<OffersController, PaginatedListState<Offer>>(
  (ref) => OffersController(ref.watch(offerwallRepositoryProvider)),
);

final cpaOffersControllerProvider =
    StateNotifierProvider<OffersController, PaginatedListState<Offer>>(
  (ref) => OffersController(ref.watch(offerwallRepositoryProvider), source: OfferSource.cpa),
);

final offerwallHistoryControllerProvider = StateNotifierProvider<OfferwallHistoryController,
    PaginatedListState<OfferwallHistoryEntry>>(
  (ref) => OfferwallHistoryController(ref.watch(offerwallRepositoryProvider)),
);

/// Single offer detail by uuid (offerwall source).
final offerDetailProvider = FutureProvider.family<Offer, String>((ref, uuid) async {
  final result = await ref.watch(offerwallRepositoryProvider).fetchOffer(uuid);
  return result.when(success: (data) => data, failure: (error) => throw error);
});
