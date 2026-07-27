import 'package:cashnest/features/offerwall/application/paginated_list_state.dart';
import 'package:cashnest/features/settings/application/support_tickets_controller.dart';
import 'package:cashnest/features/settings/models/support_ticket.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_settings_repository.dart';

void main() {
  late FakeSettingsRepository repo;

  setUp(() => repo = FakeSettingsRepository());

  test('load populates the ticket list', () async {
    final c = SupportTicketsController(repo);
    await c.load();
    expect(c.debugState.status, ListStatus.ready);
    expect(c.debugState.items, hasLength(2));
    c.dispose();
  });

  test('applyStatus reloads with the status filter', () async {
    final c = SupportTicketsController(repo);
    await c.load();
    await c.applyStatus('open');
    expect(c.status, 'open');
    expect(repo.lastStatus, 'open');
    c.dispose();
  });

  test('prepend adds a new ticket to the top', () async {
    final c = SupportTicketsController(repo);
    await c.load();
    c.prepend(const SupportTicket(uuid: 't9', subject: 'Fresh', status: 'open'));
    expect(c.debugState.items.first.uuid, 't9');
    expect(c.debugState.items, hasLength(3));
    c.dispose();
  });

  test('search filters tickets by subject', () async {
    final c = SupportTicketsController(repo);
    await c.load();
    c.setSearch('payment');
    expect(c.visibleItems.single.uuid, 't2');
    c.dispose();
  });
}
