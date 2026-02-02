<?php

namespace Tests\Unit;

use App\Services\Hadith\HadithSearchService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class HadithSearchServiceTest extends TestCase
{
    private HadithSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HadithSearchService();
    }

    public function test_extract_snippet_around_match_at_start()
    {
        $method = new ReflectionMethod(HadithSearchService::class, 'extractSnippetAroundMatch');
        $method->setAccessible(true);

        $text = 'طعام الواحد يكفي الاثنين وطعام الاثنين يكفي الأربعة';
        $matchPos = 0;
        $searchTerm = 'طعام';

        $result = $method->invoke($this->service, $text, $matchPos, $searchTerm);

        $this->assertStringContainsString('طعام', $result);
        // Should not have leading ellipsis since match is at start
        $this->assertFalse(str_starts_with($result, '…'), 'Should not start with ellipsis when match is at start');
    }

    public function test_extract_snippet_around_match_in_middle()
    {
        $method = new ReflectionMethod(HadithSearchService::class, 'extractSnippetAroundMatch');
        $method->setAccessible(true);

        // Create a longer text where the match is far from the start
        $text = str_repeat('هذا نص طويل جداً ', 10) . 'طعام' . str_repeat(' وهذا نص آخر طويل', 10);
        $matchPos = mb_strpos($text, 'طعام');
        $searchTerm = 'طعام';

        $result = $method->invoke($this->service, $text, $matchPos, $searchTerm);

        $this->assertStringContainsString('طعام', $result);
        // Should have ellipsis since there's lots of text before
        $this->assertTrue(str_starts_with($result, '…'), 'Should start with ellipsis when match is in middle');
    }

    public function test_truncate_text_respects_max_length()
    {
        $method = new ReflectionMethod(HadithSearchService::class, 'truncateText');
        $method->setAccessible(true);

        $text = 'هذا نص طويل جداً يجب اختصاره';
        $maxLength = 10;

        $result = $method->invoke($this->service, $text, $maxLength);

        // Result should be truncated with ellipsis
        $this->assertLessThanOrEqual($maxLength + 1, mb_strlen($result)); // +1 for ellipsis
        $this->assertStringEndsWith('…', $result);
    }

    public function test_truncate_text_preserves_short_text()
    {
        $method = new ReflectionMethod(HadithSearchService::class, 'truncateText');
        $method->setAccessible(true);

        $text = 'نص قصير';
        $maxLength = 50;

        $result = $method->invoke($this->service, $text, $maxLength);

        // Short text should remain unchanged
        $this->assertEquals($text, $result);
    }

    public function test_build_prefix_with_source_and_number()
    {
        $method = new ReflectionMethod(HadithSearchService::class, 'buildPrefix');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Sahih Bukhari', '123');

        $this->assertEquals('Sahih Bukhari #123: ', $result);
    }

    public function test_build_prefix_with_source_only()
    {
        $method = new ReflectionMethod(HadithSearchService::class, 'buildPrefix');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Sahih Bukhari', null);

        $this->assertEquals('Sahih Bukhari: ', $result);
    }

    public function test_build_prefix_with_no_source()
    {
        $method = new ReflectionMethod(HadithSearchService::class, 'buildPrefix');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, null, '123');

        $this->assertEquals('', $result);
    }

    public function test_search_returns_empty_for_short_term()
    {
        // This test requires DB connection, skip if not available
        if (!$this->canConnectToDatabase()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = $this->service->searchArabicHadith('ا', 10);

        $this->assertEmpty($result);
    }

    public function test_search_returns_empty_for_empty_term()
    {
        $result = $this->service->searchArabicHadith('', 10);

        $this->assertEmpty($result);
    }

    public function test_search_returns_empty_for_whitespace_only()
    {
        $result = $this->service->searchArabicHadith('   ', 10);

        $this->assertEmpty($result);
    }

    /**
     * Check if we can connect to the hadith database.
     */
    private function canConnectToDatabase(): bool
    {
        try {
            \Illuminate\Support\Facades\DB::connection('mysql_hadith')->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
