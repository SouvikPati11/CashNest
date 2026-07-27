import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/localization/app_localizations.dart';
import '../../features/offerwall/l10n/offerwall_strings.dart';
import '../../features/rewards/l10n/rewards_strings.dart';
import '../../features/settings/l10n/settings_strings.dart';
import '../../features/wallet/l10n/wallet_strings.dart';

/// The bottom-navigation shell hosting the five primary tabs
/// (Home · Rewards · Wallet · Earn · Settings). Each tab keeps its own
/// navigation stack via [StatefulNavigationShell].
class HomeShell extends StatelessWidget {
  const HomeShell({required this.navigationShell, super.key});

  final StatefulNavigationShell navigationShell;

  void _onTap(int index) {
    // Re-tapping the active tab pops it back to its root.
    navigationShell.goBranch(index, initialLocation: index == navigationShell.currentIndex);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    final destinations = <NavigationDestination>[
      NavigationDestination(
        icon: const Icon(Icons.home_outlined),
        selectedIcon: const Icon(Icons.home),
        label: l10n.home,
      ),
      NavigationDestination(
        icon: const Icon(Icons.card_giftcard_outlined),
        selectedIcon: const Icon(Icons.card_giftcard),
        label: RewardsStrings.of(context).title,
      ),
      NavigationDestination(
        icon: const Icon(Icons.account_balance_wallet_outlined),
        selectedIcon: const Icon(Icons.account_balance_wallet),
        label: WalletStrings.of(context).title,
      ),
      NavigationDestination(
        icon: const Icon(Icons.local_offer_outlined),
        selectedIcon: const Icon(Icons.local_offer),
        label: OfferwallStrings.of(context).title,
      ),
      NavigationDestination(
        icon: const Icon(Icons.settings_outlined),
        selectedIcon: const Icon(Icons.settings),
        label: SettingsStrings.of(context).title,
      ),
    ];

    return Scaffold(
      body: navigationShell,
      bottomNavigationBar: NavigationBar(
        selectedIndex: navigationShell.currentIndex,
        onDestinationSelected: _onTap,
        destinations: destinations,
      ),
    );
  }
}
