<?php

declare(strict_types=1);

namespace Tests\Unit\Reward;

use App\Exceptions\HttpException;
use App\Services\LedgerService;
use App\Services\TaskService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTransactionRunner;
use Tests\Support\InMemoryTaskRepository;
use Tests\Support\InMemoryWalletRepository;
use Tests\Support\InMemoryWalletTransactionRepository;
use Tests\Support\NullLogger;

final class TaskServiceTest extends TestCase
{
    private const USER = 20;

    private InMemoryTaskRepository $tasks;

    private InMemoryWalletRepository $wallets;

    private InMemoryWalletTransactionRepository $ledgerRepo;

    private TaskService $service;

    protected function setUp(): void
    {
        $this->tasks      = new InMemoryTaskRepository();
        $this->wallets    = new InMemoryWalletRepository();
        $this->ledgerRepo = new InMemoryWalletTransactionRepository();

        $ledger = new LedgerService(new FakeTransactionRunner(), $this->wallets, $this->ledgerRepo, new NullLogger());
        $this->service = new TaskService($this->tasks, $ledger, new NullLogger());
    }

    public function testAutoTaskStartThenCompleteCreditsThroughLedger(): void
    {
        $taskId = $this->tasks->seedTask(['reward_coins' => 200, 'verification_type' => 'auto', 'task_type' => 'social']);

        $start = $this->service->start(self::USER, $taskId);
        self::assertSame('started', $start['status']);

        $result = $this->service->complete(self::USER, $taskId, []);

        self::assertSame('credited', $result['status']);
        self::assertSame(200, $result['coins_awarded']);
        self::assertSame(200, $result['new_balance']);
        self::assertCount(1, $this->ledgerRepo->rows);
        self::assertSame('task', $this->ledgerRepo->rows[1]['type']);
    }

    public function testManualTaskGoesPendingWithoutCredit(): void
    {
        $taskId = $this->tasks->seedTask(['reward_coins' => 100, 'verification_type' => 'manual']);
        $this->service->start(self::USER, $taskId);

        $result = $this->service->complete(self::USER, $taskId, ['verification_ref' => 'proof-1']);

        self::assertSame('pending', $result['status']);
        self::assertCount(0, $this->ledgerRepo->rows);
    }

    public function testCompleteRequiresStart(): void
    {
        $taskId = $this->tasks->seedTask(['reward_coins' => 100, 'verification_type' => 'auto']);

        try {
            $this->service->complete(self::USER, $taskId, []);
            self::fail('Expected RESOURCE_CONFLICT.');
        } catch (HttpException $e) {
            self::assertSame('RESOURCE_CONFLICT', $e->getErrorCode());
        }
    }

    public function testPerUserLimitEnforced(): void
    {
        $taskId = $this->tasks->seedTask(['reward_coins' => 10, 'verification_type' => 'auto', 'per_user_limit' => 1]);

        $this->service->start(self::USER, $taskId);
        $this->service->complete(self::USER, $taskId, []); // 1 completion recorded

        try {
            $this->service->start(self::USER, $taskId);
            self::fail('Expected LIMIT_REACHED.');
        } catch (HttpException $e) {
            self::assertSame(429, $e->getStatusCode());
            self::assertSame('LIMIT_REACHED', $e->getErrorCode());
        }
    }

    public function testListReportsMyCompletions(): void
    {
        $taskId = $this->tasks->seedTask(['reward_coins' => 10, 'verification_type' => 'auto', 'title' => 'T']);
        $this->service->start(self::USER, $taskId);
        $this->service->complete(self::USER, $taskId, []);

        $list = $this->service->listTasks(self::USER, []);

        self::assertCount(1, $list['items']);
        self::assertSame(1, $list['items'][0]['my_completions']);
    }

    public function testUnknownTaskNotFound(): void
    {
        $this->expectException(\App\Exceptions\NotFoundException::class);
        $this->service->detail(self::USER, 999);
    }
}
