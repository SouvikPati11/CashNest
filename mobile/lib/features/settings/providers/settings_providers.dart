import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/di/providers.dart';
import '../../offerwall/application/paginated_list_state.dart';
import '../application/edit_profile_controller.dart';
import '../application/profile_controller.dart';
import '../application/support_tickets_controller.dart';
import '../data/settings_repository.dart';
import '../data/settings_repository_impl.dart';
import '../models/app_version_info.dart';
import '../models/cms_page.dart';
import '../models/faq.dart';
import '../models/support_ticket.dart';
import '../models/support_ticket_detail.dart';
import '../models/user_profile.dart';

/// Riverpod wiring for the settings feature. Reuses the foundation API client
/// and shared paginated infrastructure; no completed module is modified.

final settingsRepositoryProvider = Provider<SettingsRepository>(
  (ref) => SettingsRepositoryImpl(ref.watch(apiClientProvider)),
);

final profileControllerProvider =
    StateNotifierProvider<ProfileController, AsyncValue<UserProfile>>(
  (ref) => ProfileController(ref.watch(settingsRepositoryProvider)),
);

final editProfileControllerProvider =
    StateNotifierProvider.autoDispose.family<EditProfileController, EditProfileState, UserProfile>(
  (ref, initial) => EditProfileController(ref.watch(settingsRepositoryProvider), initial),
);

final supportTicketsControllerProvider =
    StateNotifierProvider<SupportTicketsController, PaginatedListState<SupportTicket>>(
  (ref) => SupportTicketsController(ref.watch(settingsRepositoryProvider)),
);

final appVersionProvider = FutureProvider<AppVersionInfo>((ref) async {
  final result = await ref.watch(settingsRepositoryProvider).fetchAppVersion();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final cmsPageProvider = FutureProvider.family<CmsPage, String>((ref, slug) async {
  final result = await ref.watch(settingsRepositoryProvider).fetchCmsPage(slug);
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final faqsProvider = FutureProvider<List<FaqCategory>>((ref) async {
  final result = await ref.watch(settingsRepositoryProvider).fetchFaqs();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final ticketDetailProvider =
    FutureProvider.family<SupportTicketDetail, String>((ref, uuid) async {
  final result = await ref.watch(settingsRepositoryProvider).fetchTicket(uuid);
  return result.when(success: (data) => data, failure: (error) => throw error);
});
