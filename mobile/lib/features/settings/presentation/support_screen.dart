import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_result.dart';
import '../../../core/theme/app_radius.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../offerwall/presentation/widgets/paginated_list_view.dart';
import '../l10n/settings_strings.dart';
import '../models/support_ticket.dart';
import '../providers/settings_providers.dart';
import '../resources/settings_formatters.dart';
import 'support_ticket_screen.dart';

/// Help & support: the user's tickets with a status filter and ticket creation.
class SupportScreen extends ConsumerStatefulWidget {
  const SupportScreen({super.key});

  @override
  ConsumerState<SupportScreen> createState() => _SupportScreenState();
}

class _SupportScreenState extends ConsumerState<SupportScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(supportTicketsControllerProvider.notifier).load();
    });
  }

  Future<void> _createTicket() async {
    final created = await showModalBottomSheet<SupportTicket>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (_) => const _CreateTicketSheet(),
    );
    if (created != null && mounted) {
      ref.read(supportTicketsControllerProvider.notifier).prepend(created);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = SettingsStrings.of(context);
    final state = ref.watch(supportTicketsControllerProvider);
    final controller = ref.read(supportTicketsControllerProvider.notifier);

    return AppScaffold(
      appBar: AppBar(title: Text(s.supportTitle)),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _createTicket,
        icon: const Icon(Icons.add),
        label: Text(s.newTicket),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(
              AppSpacing.screen,
              AppSpacing.sm,
              AppSpacing.screen,
              AppSpacing.sm,
            ),
            child: Row(
              children: [
                _StatusChip(label: s.all, selected: controller.status == null, onTap: () => controller.applyStatus(null)),
                const SizedBox(width: AppSpacing.sm),
                _StatusChip(label: s.open, selected: controller.status == 'open', onTap: () => controller.applyStatus('open')),
                const SizedBox(width: AppSpacing.sm),
                _StatusChip(label: s.closed, selected: controller.status == 'closed', onTap: () => controller.applyStatus('closed')),
              ],
            ),
          ),
          Expanded(
            child: PaginatedListView<SupportTicket>(
              state: state,
              visibleItems: controller.visibleItems,
              isFiltering: controller.status != null,
              onRefresh: controller.refresh,
              onLoadMore: controller.loadMore,
              onRetryLoadMore: controller.retryLoadMore,
              onRetry: controller.load,
              emptyIcon: Icons.support_agent_outlined,
              emptyTitle: s.noTickets,
              emptyBody: s.noTicketsBody,
              noResultsTitle: s.noResults,
              noResultsBody: s.noResultsBody,
              loadMoreErrorText: s.loadMoreError,
              skeletonItemHeight: 76,
              itemBuilder: (context, ticket) => _TicketTile(ticket: ticket),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.label, required this.selected, required this.onTap});

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ChoiceChip(label: Text(label), selected: selected, onSelected: (_) => onTap());
  }
}

class _TicketTile extends StatelessWidget {
  const _TicketTile({required this.ticket});

  final SupportTicket ticket;

  @override
  Widget build(BuildContext context) {
    final s = SettingsStrings.of(context);
    return Card(
      child: ListTile(
        shape: const RoundedRectangleBorder(borderRadius: AppRadius.mdAll),
        leading: CircleAvatar(
          backgroundColor: context.colors.secondaryContainer,
          child: Icon(Icons.confirmation_number_outlined, color: context.colors.onSecondaryContainer, size: 20),
        ),
        title: Text(ticket.subject, maxLines: 1, overflow: TextOverflow.ellipsis),
        subtitle: Text(
          ticket.createdAt != null ? SettingsFormatters.date(ticket.createdAt!) : (ticket.category ?? ''),
        ),
        trailing: Text(
          ticket.isOpen ? s.open : s.closed,
          style: context.textTheme.labelMedium?.copyWith(
            color: ticket.isOpen ? context.colors.primary : context.colors.onSurfaceVariant,
          ),
        ),
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute<void>(builder: (_) => SupportTicketScreen(uuid: ticket.uuid)),
        ),
      ),
    );
  }
}

class _CreateTicketSheet extends ConsumerStatefulWidget {
  const _CreateTicketSheet();

  @override
  ConsumerState<_CreateTicketSheet> createState() => _CreateTicketSheetState();
}

class _CreateTicketSheetState extends ConsumerState<_CreateTicketSheet> {
  final TextEditingController _subject = TextEditingController();
  final TextEditingController _message = TextEditingController();
  bool _sending = false;
  String? _error;

  @override
  void dispose() {
    _subject.dispose();
    _message.dispose();
    super.dispose();
  }

  bool get _canSend =>
      _subject.text.trim().isNotEmpty && _message.text.trim().isNotEmpty && !_sending;

  Future<void> _send() async {
    setState(() {
      _sending = true;
      _error = null;
    });
    final result = await ref.read(settingsRepositoryProvider).createTicket(
          subject: _subject.text.trim(),
          message: _message.text.trim(),
        );
    if (!mounted) {
      return;
    }
    switch (result) {
      case ApiSuccess(:final data):
        Navigator.of(context).pop(data);
      case ApiFailure(:final error):
        setState(() {
          _sending = false;
          _error = error.message;
        });
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = SettingsStrings.of(context);
    return SafeArea(
      child: Padding(
        padding: EdgeInsets.only(
          left: AppSpacing.lg,
          right: AppSpacing.lg,
          bottom: MediaQuery.viewInsetsOf(context).bottom + AppSpacing.lg,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(s.newTicket, style: context.textTheme.titleLarge),
            const SizedBox(height: AppSpacing.lg),
            TextField(
              controller: _subject,
              decoration: InputDecoration(labelText: s.subject),
              textCapitalization: TextCapitalization.sentences,
              onChanged: (_) => setState(() {}),
            ),
            const SizedBox(height: AppSpacing.md),
            TextField(
              controller: _message,
              decoration: InputDecoration(labelText: s.message),
              maxLines: 4,
              textCapitalization: TextCapitalization.sentences,
              onChanged: (_) => setState(() {}),
            ),
            if (_error != null) ...[
              const SizedBox(height: AppSpacing.md),
              Text(_error!, style: context.textTheme.bodyMedium?.copyWith(color: context.colors.error)),
            ],
            const SizedBox(height: AppSpacing.lg),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: _canSend ? _send : null,
                child: Text(_sending ? s.sending : s.send),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
