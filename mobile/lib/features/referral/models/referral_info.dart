import 'package:flutter/foundation.dart';

/// The user's referral code, link, and aggregate stats (`GET /v1/referral`).
@immutable
class ReferralInfo {
  const ReferralInfo({
    required this.referralCode,
    required this.referralLink,
    required this.totalReferrals,
    required this.qualified,
    required this.totalEarnedCoins,
    this.commissionPercent,
  });

  final String referralCode;
  final String referralLink;
  final int totalReferrals;
  final int qualified;
  final int totalEarnedCoins;
  final String? commissionPercent;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  factory ReferralInfo.fromJson(Map<String, dynamic> json) {
    return ReferralInfo(
      referralCode: json['referral_code'] as String? ?? '',
      referralLink: json['referral_link'] as String? ?? '',
      totalReferrals: _int(json['total_referrals']),
      qualified: _int(json['qualified']),
      totalEarnedCoins: _int(json['total_earned_coins']),
      commissionPercent: json['commission_percent']?.toString(),
    );
  }

  static const ReferralInfo empty = ReferralInfo(
    referralCode: '',
    referralLink: '',
    totalReferrals: 0,
    qualified: 0,
    totalEarnedCoins: 0,
  );
}
