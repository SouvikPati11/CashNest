import 'package:flutter/foundation.dart';

import '../../auth/models/auth_user.dart';
import 'home_announcement.dart';
import 'home_banner.dart';
import 'home_section.dart';
import 'home_transaction.dart';
import 'wallet_balance.dart';

/// Aggregated data backing the home dashboard.
///
/// `balance` and `sections` are the required core; banners/announcements/recent
/// transactions degrade gracefully to empty on ancillary failures.
@immutable
class HomeData {
  const HomeData({
    required this.balance,
    required this.sections,
    this.user,
    this.banners = const [],
    this.announcements = const [],
    this.recentTransactions = const [],
  });

  final WalletBalance balance;
  final List<HomeSection> sections;
  final AuthUser? user;
  final List<HomeBanner> banners;
  final List<HomeAnnouncement> announcements;
  final List<HomeTransaction> recentTransactions;

  /// Highest-priority announcement to surface as a popup, if any.
  HomeAnnouncement? get topAnnouncement =>
      announcements.isEmpty ? null : announcements.first;

  HomeData copyWith({
    WalletBalance? balance,
    List<HomeSection>? sections,
    AuthUser? user,
    List<HomeBanner>? banners,
    List<HomeAnnouncement>? announcements,
    List<HomeTransaction>? recentTransactions,
  }) {
    return HomeData(
      balance: balance ?? this.balance,
      sections: sections ?? this.sections,
      user: user ?? this.user,
      banners: banners ?? this.banners,
      announcements: announcements ?? this.announcements,
      recentTransactions: recentTransactions ?? this.recentTransactions,
    );
  }
}
