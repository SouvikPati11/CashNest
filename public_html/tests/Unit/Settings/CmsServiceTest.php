<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use App\Exceptions\NotFoundException;
use App\Services\CmsService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryCmsPageRepository;
use Tests\Support\InMemoryFaqRepository;

final class CmsServiceTest extends TestCase
{
    private InMemoryCmsPageRepository $pages;

    private InMemoryFaqRepository $faqs;

    private CmsService $service;

    protected function setUp(): void
    {
        $this->pages   = new InMemoryCmsPageRepository();
        $this->faqs    = new InMemoryFaqRepository();
        $this->service = new CmsService($this->pages, $this->faqs);
    }

    public function testReturnsLatestPublishedVersion(): void
    {
        $this->pages->seed(['slug' => 'terms', 'title' => 'Terms v1', 'version' => 1]);
        $this->pages->seed(['slug' => 'terms', 'title' => 'Terms v3', 'version' => 3]);

        $page = $this->service->page('terms', 'en');

        self::assertSame('Terms v3', $page['title']);
        self::assertSame(3, $page['version']);
    }

    public function testFallsBackToEnglish(): void
    {
        $this->pages->seed(['slug' => 'about', 'title' => 'About', 'locale' => 'en']);

        $page = $this->service->page('about', 'hi');

        self::assertSame('About', $page['title']);
    }

    public function testUnknownSlugThrows(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->page('missing', 'en');
    }

    public function testFaqGroupedByCategory(): void
    {
        $this->faqs->seedCategory(1, 'Withdrawals', 'withdrawals', 0);
        $this->faqs->seedCategory(2, 'Rewards', 'rewards', 1);
        $this->faqs->seedFaq(['category_id' => 1, 'question' => 'How to withdraw?']);
        $this->faqs->seedFaq(['category_id' => 2, 'question' => 'How to earn?']);

        $groups = $this->service->faq('en', null);

        self::assertCount(2, $groups);
        self::assertSame('Withdrawals', $groups[0]['category']);
        self::assertSame('How to withdraw?', $groups[0]['items'][0]['question']);
    }

    public function testFaqFilteredByCategory(): void
    {
        $this->faqs->seedCategory(1, 'Withdrawals', 'withdrawals', 0);
        $this->faqs->seedCategory(2, 'Rewards', 'rewards', 1);
        $this->faqs->seedFaq(['category_id' => 1, 'question' => 'How to withdraw?']);
        $this->faqs->seedFaq(['category_id' => 2, 'question' => 'How to earn?']);

        $groups = $this->service->faq('en', 'rewards');

        self::assertCount(1, $groups);
        self::assertSame('Rewards', $groups[0]['category']);
    }
}
