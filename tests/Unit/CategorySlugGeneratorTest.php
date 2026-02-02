<?php

namespace Tests\Unit;

use App\Services\Categories\CategorySlugGenerator;
use PHPUnit\Framework\TestCase;

class CategorySlugGeneratorTest extends TestCase
{
    private CategorySlugGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new CategorySlugGenerator();
    }

    public function test_generates_english_slug()
    {
        // Use reflection to access protected method
        $method = new \ReflectionMethod(CategorySlugGenerator::class, 'slugify');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, 'My Test Category', 'en');

        $this->assertEquals('my-test-category', $result);
    }

    public function test_generates_arabic_slug_preserving_arabic_chars()
    {
        $method = new \ReflectionMethod(CategorySlugGenerator::class, 'slugify');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, 'الصبر والتوكل', 'ar');

        $this->assertEquals('الصبر-والتوكل', $result);
    }

    public function test_arabic_slug_removes_invalid_characters()
    {
        $method = new \ReflectionMethod(CategorySlugGenerator::class, 'slugifyArabic');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, 'الصبر! @#$ والتوكل');

        // Should remove special chars but keep Arabic and hyphens
        $this->assertStringNotContainsString('!', $result);
        $this->assertStringNotContainsString('@', $result);
        $this->assertStringNotContainsString('#', $result);
        $this->assertStringNotContainsString('$', $result);
        $this->assertStringContainsString('الصبر', $result);
        $this->assertStringContainsString('والتوكل', $result);
    }

    public function test_arabic_slug_handles_multiple_spaces()
    {
        $method = new \ReflectionMethod(CategorySlugGenerator::class, 'slugifyArabic');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, 'الصبر    والتوكل');

        // Multiple spaces should become single hyphen
        $this->assertStringNotContainsString('--', $result);
        $this->assertEquals('الصبر-والتوكل', $result);
    }

    public function test_arabic_slug_trims_hyphens()
    {
        $method = new \ReflectionMethod(CategorySlugGenerator::class, 'slugifyArabic');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, ' الصبر ');

        $this->assertFalse(str_starts_with($result, '-'), 'Slug should not start with hyphen');
        $this->assertFalse(str_ends_with($result, '-'), 'Slug should not end with hyphen');
    }

    public function test_handles_mixed_arabic_english()
    {
        $method = new \ReflectionMethod(CategorySlugGenerator::class, 'slugifyArabic');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, 'الصبر Patience');

        // Should preserve both Arabic and English
        $this->assertStringContainsString('الصبر', $result);
        $this->assertStringContainsString('Patience', $result);
    }

    public function test_handles_empty_string()
    {
        $method = new \ReflectionMethod(CategorySlugGenerator::class, 'slugify');
        $method->setAccessible(true);

        $resultEn = $method->invoke($this->generator, '', 'en');
        $resultAr = $method->invoke($this->generator, '', 'ar');

        $this->assertEquals('', $resultEn);
        $this->assertEquals('', $resultAr);
    }

    public function test_farsi_uses_arabic_slug_method()
    {
        $method = new \ReflectionMethod(CategorySlugGenerator::class, 'slugify');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, 'صبر و توکل', 'fa');

        // Farsi should use Arabic slugify (preserves Persian characters)
        $this->assertStringContainsString('صبر', $result);
    }

    public function test_urdu_uses_arabic_slug_method()
    {
        $method = new \ReflectionMethod(CategorySlugGenerator::class, 'slugify');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, 'صبر اور توکل', 'ur');

        // Urdu should use Arabic slugify
        $this->assertStringContainsString('صبر', $result);
    }
}
