import 'package:cashnest/features/referral/models/leaderboard_entry.dart';
import 'package:cashnest/features/referral/models/referral_earning.dart';
import 'package:cashnest/features/referral/models/referral_entry.dart';
import 'package:cashnest/features/referral/models/referral_info.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('ReferralInfo parses code/link/stats', () {
    final i = ReferralInfo.fromJson(const {
      'referral_code': 'ASHA12',
      'referral_link': 'https://cashnest.app/r/ASHA12',
      'total_referrals': 8,
      'qualified': 5,
      'total_earned_coins': 1200,
      'commission_percent': '0.1000',
    });
    expect(i.referralCode, 'ASHA12');
    expect(i.referralLink, endsWith('ASHA12'));
    expect(i.totalReferrals, 8);
    expect(i.qualified, 5);
    expect(i.totalEarnedCoins, 1200);
    expect(i.commissionPercent, '0.1000');
  });

  test('ReferralEntry parses status + qualified helper', () {
    final e = ReferralEntry.fromJson(const {
      'referee_name': 'Ravi',
      'status': 'qualified',
      'joined_at': '2026-07-20T10:00:00Z',
    });
    expect(e.refereeName, 'Ravi');
    expect(e.isQualified, isTrue);
    expect(e.joinedAt, isNotNull);
  });

  test('ReferralEarning parses commission + source', () {
    final e = ReferralEarning.fromJson(const {
      'commission_coins': 120,
      'source': 'offerwall',
      'created_at': '2026-07-22T10:00:00Z',
    });
    expect(e.commissionCoins, 120);
    expect(e.source, 'offerwall');
    expect(e.createdAt, isNotNull);
  });

  test('LeaderboardEntry parses nested user', () {
    final e = LeaderboardEntry.fromJson(const {
      'rank': 1,
      'user': {'name': 'Asha', 'avatar_url': 'a'},
      'score': 12000,
    });
    expect(e.rank, 1);
    expect(e.name, 'Asha');
    expect(e.avatarUrl, 'a');
    expect(e.score, 12000);
  });

  test('LeaderboardMe parses rank/score/period', () {
    final me = LeaderboardMe.fromJson(const {'rank': 42, 'score': 3400, 'period': 'weekly'});
    expect(me.rank, 42);
    expect(me.score, 3400);
    expect(me.period, 'weekly');
  });
}
