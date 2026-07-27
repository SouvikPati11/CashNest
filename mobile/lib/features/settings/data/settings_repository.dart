import '../../../core/network/api_result.dart';
import '../../offerwall/models/page_result.dart';
import '../models/app_version_info.dart';
import '../models/cms_page.dart';
import '../models/faq.dart';
import '../models/settings_preferences.dart';
import '../models/support_ticket.dart';
import '../models/support_ticket_detail.dart';
import '../models/user_profile.dart';

/// Contract for the settings feature: profile, preferences, CMS pages, FAQ,
/// support tickets, and app version.
abstract interface class SettingsRepository {
  Future<ApiResult<UserProfile>> fetchProfile();

  Future<ApiResult<UserProfile>> updateProfile({
    String? name,
    String? countryCode,
    String? locale,
  });

  Future<ApiResult<SettingsPreferences>> fetchPreferences();

  Future<ApiResult<SettingsPreferences>> updatePreferences({
    String? language,
    String? themeMode,
  });

  Future<ApiResult<CmsPage>> fetchCmsPage(String slug);

  Future<ApiResult<List<FaqCategory>>> fetchFaqs();

  Future<ApiResult<PageResult<SupportTicket>>> fetchTickets({
    String? status,
    String? cursor,
    int limit = 20,
  });

  Future<ApiResult<SupportTicket>> createTicket({
    required String subject,
    required String message,
    String? category,
  });

  Future<ApiResult<SupportTicketDetail>> fetchTicket(String uuid);

  Future<ApiResult<SupportMessage>> replyTicket(String uuid, String message);

  Future<ApiResult<AppVersionInfo>> fetchAppVersion();
}
