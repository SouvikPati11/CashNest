import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/offerwall/models/page_result.dart';
import 'package:cashnest/features/settings/data/settings_repository.dart';
import 'package:cashnest/features/settings/models/app_version_info.dart';
import 'package:cashnest/features/settings/models/cms_page.dart';
import 'package:cashnest/features/settings/models/faq.dart';
import 'package:cashnest/features/settings/models/settings_preferences.dart';
import 'package:cashnest/features/settings/models/support_ticket.dart';
import 'package:cashnest/features/settings/models/support_ticket_detail.dart';
import 'package:cashnest/features/settings/models/user_profile.dart';

/// A configurable fake [SettingsRepository] for controller tests.
class FakeSettingsRepository implements SettingsRepository {
  ApiResult<UserProfile> profile = const ApiResult.success(
    UserProfile(uuid: 'u1', name: 'Asha', email: 'asha@x.io', countryCode: 'IN'),
  );
  ApiResult<UserProfile> updateResult = const ApiResult.success(
    UserProfile(uuid: 'u1', name: 'Asha Rao', email: 'asha@x.io', countryCode: 'US'),
  );

  String? lastStatus;
  String? lastName;
  String? lastCountry;

  @override
  Future<ApiResult<UserProfile>> fetchProfile() async => profile;

  @override
  Future<ApiResult<UserProfile>> updateProfile({String? name, String? countryCode, String? locale}) async {
    lastName = name;
    lastCountry = countryCode;
    return updateResult;
  }

  @override
  Future<ApiResult<SettingsPreferences>> fetchPreferences() async =>
      const ApiResult.success(SettingsPreferences(language: 'en', themeMode: 'system'));

  @override
  Future<ApiResult<SettingsPreferences>> updatePreferences({String? language, String? themeMode}) async =>
      const ApiResult.success(SettingsPreferences());

  @override
  Future<ApiResult<CmsPage>> fetchCmsPage(String slug) async =>
      ApiResult.success(CmsPage(slug: slug, title: 'Title', body: 'Body'));

  @override
  Future<ApiResult<List<FaqCategory>>> fetchFaqs() async => const ApiResult.success([
        FaqCategory(category: 'General', items: [FaqItem(question: 'Q', answer: 'A')]),
      ]);

  @override
  Future<ApiResult<PageResult<SupportTicket>>> fetchTickets({
    String? status,
    String? cursor,
    int limit = 20,
  }) async {
    lastStatus = status;
    return const ApiResult.success(PageResult(items: [
      SupportTicket(uuid: 't1', subject: 'Login issue', status: 'open'),
      SupportTicket(uuid: 't2', subject: 'Payment', status: 'closed'),
    ], hasMore: false));
  }

  @override
  Future<ApiResult<SupportTicket>> createTicket({
    required String subject,
    required String message,
    String? category,
  }) async =>
      const ApiResult.success(SupportTicket(uuid: 't3', subject: 'New', status: 'open'));

  @override
  Future<ApiResult<SupportTicketDetail>> fetchTicket(String uuid) async =>
      const ApiResult.success(SupportTicketDetail(
        ticket: SupportTicket(uuid: 't1', subject: 'Login issue', status: 'open'),
      ));

  @override
  Future<ApiResult<SupportMessage>> replyTicket(String uuid, String message) async =>
      ApiResult.success(SupportMessage(senderType: 'user', message: message));

  @override
  Future<ApiResult<AppVersionInfo>> fetchAppVersion() async =>
      const ApiResult.success(AppVersionInfo(latestVersion: '1.5.0', updateAvailable: true));
}
