import 'package:cashnest/features/home/models/home_announcement.dart';
import 'package:cashnest/features/home/models/home_banner.dart';
import 'package:cashnest/features/home/models/home_section.dart';
import 'package:cashnest/features/home/models/home_transaction.dart';
import 'package:cashnest/features/home/models/wallet_balance.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('WalletBalance.fromJson', () {
    test('parses backend shape (cash_balance is a string)', () {
      final balance = WalletBalance.fromJson(const {
        'coin_balance': 4200,
        'coin_reserved': 100,
        'available': 4100,
        'cash_balance': '4.2000',
        'currency': 'INR',
        'lifetime_earned': 9000,
        'lifetime_spent': 4800,
      });

      expect(balance.coinBalance, 4200);
      expect(balance.coinReserved, 100);
      expect(balance.available, 4100);
      expect(balance.cashBalance, '4.2000');
      expect(balance.currency, 'INR');
      expect(balance.lifetimeEarned, 9000);
      expect(balance.lifetimeSpent, 4800);
    });

    test('falls back to safe defaults on missing keys', () {
      final balance = WalletBalance.fromJson(const {});
      expect(balance.coinBalance, 0);
      expect(balance.cashBalance, '0.0000');
      expect(balance.currency, 'INR');
    });
  });

  group('HomeSection.fromJson', () {
    test('keeps unknown type as a raw string and parses config lists', () {
      final section = HomeSection.fromJson(const {
        'type': 'quick_actions',
        'sort_order': 2,
        'title': 'Quick actions',
        'config': {
          'actions': ['earn', 'spin', 'refer'],
        },
      });

      expect(section.type, 'quick_actions');
      expect(section.sortOrder, 2);
      expect(section.title, 'Quick actions');
      expect(section.stringList('actions'), ['earn', 'spin', 'refer']);
      expect(section.stringList('missing'), isEmpty);
    });

    test('tolerates a non-map config', () {
      final section = HomeSection.fromJson(const {'type': 'banner_carousel', 'config': 'nope'});
      expect(section.config, isEmpty);
      expect(section.sortOrder, 0);
    });
  });

  group('HomeBanner.fromJson', () {
    test('parses image and action fields', () {
      final banner = HomeBanner.fromJson(const {
        'id': 7,
        'image_url': 'https://cdn/x.png',
        'title': 'Promo',
        'action_type': 'route',
        'action_value': '/offers',
        'sort_order': 3,
      });
      expect(banner.id, 7);
      expect(banner.imageUrl, 'https://cdn/x.png');
      expect(banner.actionType, 'route');
      expect(banner.actionValue, '/offers');
      expect(banner.sortOrder, 3);
    });
  });

  group('HomeAnnouncement.fromJson', () {
    test('parses display and dismissible flags', () {
      final a = HomeAnnouncement.fromJson(const {
        'id': 11,
        'title': 'Welcome',
        'body': 'Enjoy CashNest',
        'display_type': 'popup',
        'priority': 10,
        'is_dismissible': false,
        'action_type': 'route',
        'action_value': '/rewards',
      });
      expect(a.id, 11);
      expect(a.displayType, 'popup');
      expect(a.isDismissible, isFalse);
      expect(a.actionType, 'route');
      expect(a.actionValue, '/rewards');
    });
  });

  group('HomeTransaction.fromJson', () {
    test('exposes isCredit from direction', () {
      final credit = HomeTransaction.fromJson(const {
        'uuid': 't1',
        'direction': 'credit',
        'amount': 50,
        'type': 'daily_checkin',
        'description': 'Daily bonus',
        'created_at': '2026-07-26T10:00:00Z',
      });
      final debit = HomeTransaction.fromJson(const {
        'uuid': 't2',
        'direction': 'debit',
        'amount': 20,
        'type': 'withdraw',
      });

      expect(credit.isCredit, isTrue);
      expect(credit.description, 'Daily bonus');
      expect(debit.isCredit, isFalse);
      expect(debit.description, isNull);
    });
  });
}
