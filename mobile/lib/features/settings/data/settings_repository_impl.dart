import 'package:dio/dio.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/dio_error_mapper.dart';
import '../../offerwall/models/page_result.dart';
import '../models/app_version_info.dart';
import '../models/cms_page.dart';
import '../models/faq.dart';
import '../models/settings_preferences.dart';
import '../models/support_ticket.dart';
import '../models/support_ticket_detail.dart';
import '../models/user_profile.dart';
import 'settings_repository.dart';

/// [SettingsRepository] over the foundation [ApiClient]. The paginated ticket
/// list reads the full envelope via [ApiClient.raw] for `meta.pagination` and
/// reuses [DioErrorMapper]. The foundation is not modified.
class SettingsRepositoryImpl implements SettingsRepository {
  SettingsRepositoryImpl(this._client);

  final ApiClient _client;

  static Map<String, dynamic> _map(dynamic data) =>
      data is Map<String, dynamic> ? data : const <String, dynamic>{};

  static List<T> _list<T>(dynamic data, T Function(Map<String, dynamic>) fromJson) {
    if (data is! List) {
      return <T>[];
    }
    return data.whereType<Map<String, dynamic>>().map(fromJson).toList(growable: false);
  }

  @override
  Future<ApiResult<UserProfile>> fetchProfile() {
    return _client.get<UserProfile>(
      '/profile',
      decoder: (data) => UserProfile.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<UserProfile>> updateProfile({String? name, String? countryCode, String? locale}) {
    return _client.put<UserProfile>(
      '/profile',
      body: {
        if (name != null) 'name': name,
        if (countryCode != null) 'country_code': countryCode,
        if (locale != null) 'locale': locale,
      },
      decoder: (data) => UserProfile.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<SettingsPreferences>> fetchPreferences() {
    return _client.get<SettingsPreferences>(
      '/settings',
      decoder: (data) => SettingsPreferences.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<SettingsPreferences>> updatePreferences({String? language, String? themeMode}) {
    return _client.put<SettingsPreferences>(
      '/settings',
      body: {
        if (language != null) 'language': language,
        if (themeMode != null) 'theme_mode': themeMode,
      },
      decoder: (data) => SettingsPreferences.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<CmsPage>> fetchCmsPage(String slug) {
    return _client.get<CmsPage>(
      '/cms/$slug',
      decoder: (data) => CmsPage.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<List<FaqCategory>>> fetchFaqs() {
    // FAQ is served by the CMS endpoint (GET /v1/cms/faq); the response shape
    // ({category, slug, items:[{question, answer}]}) matches FaqCategory.
    return _client.get<List<FaqCategory>>(
      '/cms/faq',
      decoder: (data) => _list(data, FaqCategory.fromJson),
    );
  }

  @override
  Future<ApiResult<PageResult<SupportTicket>>> fetchTickets({
    String? status,
    String? cursor,
    int limit = 20,
  }) async {
    final query = <String, dynamic>{
      'limit': limit,
      if (cursor != null) 'cursor': cursor,
      if (status != null) 'filter[status]': status,
    };
    try {
      final response = await _client.raw.get<dynamic>('/support/tickets', queryParameters: query);
      final envelope = _map(response.data);
      final items = _list(envelope['data'], SupportTicket.fromJson);
      final pagination = _map(_map(envelope['meta'])['pagination']);
      final nextCursor = pagination['next_cursor'] as String?;
      final hasMore = pagination['has_more'] as bool? ?? (nextCursor != null);
      return ApiResult<PageResult<SupportTicket>>.success(
        PageResult<SupportTicket>(items: items, nextCursor: nextCursor, hasMore: hasMore),
      );
    } on DioException catch (error) {
      return ApiResult<PageResult<SupportTicket>>.failure(DioErrorMapper.map(error));
    } catch (error, stack) {
      return ApiResult<PageResult<SupportTicket>>.failure(
        UnknownException('Unexpected error.', cause: error, stackTrace: stack),
      );
    }
  }

  @override
  Future<ApiResult<SupportTicket>> createTicket({
    required String subject,
    required String message,
    String? category,
  }) {
    return _client.post<SupportTicket>(
      '/support/tickets',
      body: {
        'subject': subject,
        'message': message,
        if (category != null) 'category': category,
      },
      decoder: (data) => SupportTicket.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<SupportTicketDetail>> fetchTicket(String uuid) {
    return _client.get<SupportTicketDetail>(
      '/support/tickets/$uuid',
      decoder: (data) => SupportTicketDetail.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<SupportMessage>> replyTicket(String uuid, String message) {
    return _client.post<SupportMessage>(
      '/support/tickets/$uuid/reply',
      body: {'message': message},
      decoder: (data) => SupportMessage.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<AppVersionInfo>> fetchAppVersion() {
    return _client.get<AppVersionInfo>(
      '/app/version',
      decoder: (data) => AppVersionInfo.fromJson(_map(data)),
    );
  }
}
