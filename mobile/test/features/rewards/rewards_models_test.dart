import 'package:cashnest/features/rewards/models/checkin_calendar.dart';
import 'package:cashnest/features/rewards/models/checkin_status.dart';
import 'package:cashnest/features/rewards/models/claim_result.dart';
import 'package:cashnest/features/rewards/models/reward_history_entry.dart';
import 'package:cashnest/features/rewards/models/reward_task.dart';
import 'package:cashnest/features/rewards/models/scratch_card.dart';
import 'package:cashnest/features/rewards/models/spin_result.dart';
import 'package:cashnest/features/rewards/models/spin_status.dart';
import 'package:cashnest/features/rewards/models/task_action_result.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('CheckinStatus parses claimable + streak', () {
    final s = CheckinStatus.fromJson(const {
      'can_claim_today': true,
      'current_streak': 3,
      'next_reward_coins': 30,
      'last_checkin_date': '2026-07-24',
    });
    expect(s.canClaimToday, isTrue);
    expect(s.currentStreak, 3);
    expect(s.nextRewardCoins, 30);
    expect(s.lastCheckinDate, '2026-07-24');
  });

  test('CheckinCalendar parses ladder', () {
    final c = CheckinCalendar.fromJson(const {
      'ladder': [
        {'day': 1, 'coins': 10, 'is_milestone': false},
        {'day': 7, 'coins': 100, 'is_milestone': true},
      ],
      'current_streak': 3,
    });
    expect(c.ladder, hasLength(2));
    expect(c.ladder[1].isMilestone, isTrue);
    expect(c.currentStreak, 3);
  });

  test('ClaimResult parses reward fields', () {
    final r = ClaimResult.fromJson(const {
      'coins_awarded': 30,
      'streak_day': 3,
      'new_balance': 4230,
      'transaction_uuid': 'wt_x9',
    });
    expect(r.coinsAwarded, 30);
    expect(r.streakDay, 3);
    expect(r.newBalance, 4230);
    expect(r.transactionUuid, 'wt_x9');
  });

  test('ScratchCard status helpers', () {
    final issued = ScratchCard.fromJson(const {'uuid': 'sc_1', 'status': 'issued'});
    final revealed = ScratchCard.fromJson(const {'uuid': 'sc_2', 'status': 'revealed', 'reward_coins': 50});
    expect(issued.isIssued, isTrue);
    expect(revealed.isRevealed, isTrue);
    expect(revealed.rewardCoins, 50);
  });

  test('SpinStatus sorts segments by position and computes canSpin', () {
    final status = SpinStatus.fromJson(const {
      'spins_remaining': 2,
      'daily_limit': 3,
      'segments': [
        {'id': 'seg_2', 'label': '20', 'color_hex': '#00FF00', 'position': 1},
        {'id': 'seg_1', 'label': '50', 'color_hex': '#FFCC00', 'position': 0},
      ],
    });
    expect(status.segments.first.id, 'seg_1');
    expect(status.canSpin, isTrue);
    expect(const SpinStatus(spinsRemaining: 0, dailyLimit: 3, segments: []).canSpin, isFalse);
  });

  test('SpinResult parses outcome', () {
    final r = SpinResult.fromJson(const {
      'segment_id': 'seg_3',
      'reward_type': 'coins',
      'reward_coins': 50,
      'new_balance': 4330,
      'spins_remaining': 1,
    });
    expect(r.segmentId, 'seg_3');
    expect(r.rewardCoins, 50);
    expect(r.spinsRemaining, 1);
  });

  test('RewardTask availability from completions vs limit', () {
    final task = RewardTask.fromJson(const {
      'uuid': 't_1',
      'title': 'Watch a video',
      'reward_coins': 100,
      'per_user_limit': 2,
      'my_completions': 1,
    });
    expect(task.isAvailable, isTrue);
    expect(task.copyWith(myCompletions: 2).isAvailable, isFalse);
  });

  test('TaskActionResult credited/pending', () {
    final credited = TaskActionResult.fromJson(const {
      'status': 'credited',
      'coins_awarded': 100,
      'new_balance': 4430,
    });
    final pending = TaskActionResult.fromJson(const {'status': 'pending'});
    expect(credited.isCredited, isTrue);
    expect(credited.coinsAwarded, 100);
    expect(pending.isPending, isTrue);
    expect(pending.coinsAwarded, isNull);
  });

  test('RewardHistoryEntry factories set kind and coins', () {
    final scratch = RewardHistoryEntry.scratch(const {
      'uuid': 'sc_1',
      'reward_coins': 50,
      'status': 'claimed',
      'created_at': '2026-07-24T10:00:00Z',
    });
    final spin = RewardHistoryEntry.spin(const {'uuid': 'sp_1', 'reward_coins': 20});
    final task = RewardHistoryEntry.task(const {'uuid': 'tc_1', 'coins_awarded': 100});
    expect(scratch.kind, RewardKind.scratch);
    expect(scratch.coins, 50);
    expect(scratch.createdAt, isNotNull);
    expect(spin.kind, RewardKind.spin);
    expect(task.kind, RewardKind.task);
    expect(task.coins, 100);
  });
}
