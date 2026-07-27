import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../core/theme/app_radius.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/error_view.dart';
import '../../../shared/widgets/loading_widget.dart';
import '../l10n/settings_strings.dart';
import '../models/support_ticket_detail.dart';
import '../providers/settings_providers.dart';
import '../resources/settings_formatters.dart';

/// A support ticket thread with the ability to reply.
class SupportTicketScreen extends ConsumerStatefulWidget {
  const SupportTicketScreen({required this.uuid, super.key});

  final String uuid;

  @override
  ConsumerState<SupportTicketScreen> createState() => _SupportTicketScreenState();
}

class _SupportTicketScreenState extends ConsumerState<SupportTicketScreen> {
  final TextEditingController _reply = TextEditingController();
  bool _sending = false;

  @override
  void dispose() {
    _reply.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final text = _reply.text.trim();
    if (text.isEmpty || _sending) {
      return;
    }
    setState(() => _sending = true);
    final result = await ref.read(settingsRepositoryProvider).replyTicket(widget.uuid, text);
    if (!mounted) {
      return;
    }
    setState(() => _sending = false);
    switch (result) {
      case ApiSuccess():
        _reply.clear();
        ref.invalidate(ticketDetailProvider(widget.uuid));
      case ApiFailure(:final error):
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(error.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = SettingsStrings.of(context);
    final detail = ref.watch(ticketDetailProvider(widget.uuid));

    return AppScaffold(
      appBar: AppBar(title: Text(detail.valueOrNull?.ticket.subject ?? s.supportTitle)),
      body: detail.when(
        loading: () => const LoadingWidget(),
        error: (error, _) => ErrorView(
          message: error is AppException ? error.message : s.errorTitle,
          onRetry: () => ref.invalidate(ticketDetailProvider(widget.uuid)),
        ),
        data: (data) => _Thread(
          detail: data,
          replyController: _reply,
          sending: _sending,
          onSend: _send,
        ),
      ),
    );
  }
}

class _Thread extends StatelessWidget {
  const _Thread({
    required this.detail,
    required this.replyController,
    required this.sending,
    required this.onSend,
  });

  final SupportTicketDetail detail;
  final TextEditingController replyController;
  final bool sending;
  final VoidCallback onSend;

  @override
  Widget build(BuildContext context) {
    final s = SettingsStrings.of(context);
    final closed = !detail.ticket.isOpen;

    return Column(
      children: [
        Expanded(
          child: ListView.separated(
            padding: const EdgeInsets.all(AppSpacing.screen),
            itemCount: detail.messages.length,
            separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.sm),
            itemBuilder: (context, index) => _MessageBubble(message: detail.messages[index]),
          ),
        ),
        if (closed)
          Padding(
            padding: const EdgeInsets.all(AppSpacing.lg),
            child: Text(
              s.ticketClosed,
              style: context.textTheme.bodyMedium?.copyWith(color: context.colors.onSurfaceVariant),
            ),
          )
        else
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(AppSpacing.md, AppSpacing.sm, AppSpacing.md, AppSpacing.md),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: replyController,
                      decoration: InputDecoration(hintText: s.typeReply, isDense: true),
                      textCapitalization: TextCapitalization.sentences,
                      minLines: 1,
                      maxLines: 4,
                    ),
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  IconButton.filled(
                    onPressed: sending ? null : onSend,
                    icon: sending
                        ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                        : const Icon(Icons.send),
                  ),
                ],
              ),
            ),
          ),
      ],
    );
  }
}

class _MessageBubble extends StatelessWidget {
  const _MessageBubble({required this.message});

  final SupportMessage message;

  @override
  Widget build(BuildContext context) {
    final isUser = message.isFromUser;
    final scheme = context.colors;
    final bg = isUser ? scheme.primaryContainer : scheme.surfaceContainerHighest;
    final fg = isUser ? scheme.onPrimaryContainer : scheme.onSurface;

    return Align(
      alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        constraints: BoxConstraints(maxWidth: MediaQuery.sizeOf(context).width * 0.78),
        padding: const EdgeInsets.all(AppSpacing.md),
        decoration: BoxDecoration(color: bg, borderRadius: AppRadius.lgAll),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(message.message, style: context.textTheme.bodyMedium?.copyWith(color: fg)),
            if (message.createdAt != null) ...[
              const SizedBox(height: AppSpacing.xs),
              Text(
                SettingsFormatters.dateTime(message.createdAt!),
                style: context.textTheme.labelSmall?.copyWith(color: fg.withValues(alpha: 0.7)),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
