import 'package:cashnest/features/settings/models/app_version_info.dart';
import 'package:cashnest/features/settings/models/cms_page.dart';
import 'package:cashnest/features/settings/models/faq.dart';
import 'package:cashnest/features/settings/models/settings_preferences.dart';
import 'package:cashnest/features/settings/models/support_ticket.dart';
import 'package:cashnest/features/settings/models/support_ticket_detail.dart';
import 'package:cashnest/features/settings/models/user_profile.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('UserProfile parses profile fields', () {
    final p = UserProfile.fromJson(const {
      'uuid': 'u_9f',
      'name': 'Asha',
      'email': 'asha@example.com',
      'referral_code': 'ASHA12',
      'country_code': 'IN',
      'status': 'active',
      'kyc_status': 'approved',
      'created_at': '2026-01-02T09:00:00Z',
    });
    expect(p.uuid, 'u_9f');
    expect(p.name, 'Asha');
    expect(p.countryCode, 'IN');
    expect(p.kycStatus, 'approved');
    expect(p.createdAt, isNotNull);
  });

  test('SettingsPreferences parses from the preferences wrapper', () {
    final prefs = SettingsPreferences.fromJson(const {
      'preferences': {'language': 'bn', 'theme_mode': 'dark'},
      'app_config': {},
    });
    expect(prefs.language, 'bn');
    expect(prefs.themeMode, 'dark');
  });

  test('SettingsPreferences defaults when missing', () {
    final prefs = SettingsPreferences.fromJson(const {});
    expect(prefs.language, 'en');
    expect(prefs.themeMode, 'system');
  });

  test('CmsPage parses slug/title/body', () {
    final page = CmsPage.fromJson(const {
      'slug': 'privacy-policy',
      'title': 'Privacy Policy',
      'body': 'We respect your privacy.',
      'locale': 'en',
      'version': 3,
    });
    expect(page.slug, 'privacy-policy');
    expect(page.title, 'Privacy Policy');
    expect(page.version, 3);
  });

  test('FaqCategory parses nested items', () {
    final c = FaqCategory.fromJson(const {
      'category': 'Withdrawals',
      'items': [
        {'question': 'How long?', 'answer': '2-3 days.'},
      ],
    });
    expect(c.category, 'Withdrawals');
    expect(c.items, hasLength(1));
    expect(c.items.first.question, 'How long?');
  });

  test('SupportTicket exposes isOpen', () {
    final open = SupportTicket.fromJson(const {'uuid': 't1', 'subject': 'Help', 'status': 'open'});
    final closed = SupportTicket.fromJson(const {'uuid': 't2', 'subject': 'Done', 'status': 'closed'});
    expect(open.isOpen, isTrue);
    expect(closed.isOpen, isFalse);
  });

  test('SupportTicketDetail parses thread messages', () {
    final d = SupportTicketDetail.fromJson(const {
      'uuid': 't1',
      'subject': 'Help',
      'status': 'answered',
      'messages': [
        {'sender_type': 'user', 'message': 'Hi'},
        {'sender_type': 'agent', 'message': 'Hello!'},
      ],
    });
    expect(d.ticket.subject, 'Help');
    expect(d.messages, hasLength(2));
    expect(d.messages.first.isFromUser, isTrue);
    expect(d.messages[1].isFromUser, isFalse);
  });

  test('AppVersionInfo parses update flags', () {
    final info = AppVersionInfo.fromJson(const {
      'platform': 'android',
      'latest_version': '1.5.0',
      'latest_version_code': 150,
      'min_supported_code': 120,
      'force_update': false,
      'update_available': true,
      'store_url': 'https://play.google.com/x',
      'changelog': 'Bug fixes',
    });
    expect(info.latestVersion, '1.5.0');
    expect(info.updateAvailable, isTrue);
    expect(info.forceUpdate, isFalse);
    expect(info.storeUrl, 'https://play.google.com/x');
  });
}
