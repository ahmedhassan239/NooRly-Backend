<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Hadith\HadithCollection;
use App\Domain\Verses\VerseCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * GET /api/v1/daily-inspiration (public) — from library collections only.
 * Hadith from HadithCollection pivots, ayah from VerseCollection pivots.
 */
class DailyInspirationFromCollectionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->overrideExternalConnectionsToDefault();
    }

    /** Point hadith and quran_all_lang connections to default so we can seed on one DB. */
    private function overrideExternalConnectionsToDefault(): void
    {
        $default = config('database.default');
        config(['database.connections.mysql_hadith' => config("database.connections.{$default}")]);
        config(['database.connections.mysql_quran_all_lang' => config("database.connections.{$default}")]);
        config(['content_sources.hadith.table' => 'hadiths']);
        config(['content_sources.hadith.connection' => 'mysql_hadith']);
    }

    /** Create minimal external hadith table and one row. */
    private function seedHadithExternal(int $hadithId = 1): void
    {
        if (! Schema::connection('mysql_hadith')->hasTable('hadiths')) {
            Schema::connection('mysql_hadith')->create('hadiths', function ($table) {
                $table->id();
                $table->string('source', 64)->nullable();
                $table->unsignedInteger('chapter_no')->nullable();
                $table->unsignedInteger('hadith_no')->nullable();
                $table->text('text_ar')->nullable();
                $table->text('text_en')->nullable();
            });
        }
        if (DB::connection('mysql_hadith')->table('hadiths')->where('id', $hadithId)->doesntExist()) {
            DB::connection('mysql_hadith')->table('hadiths')->insert([
                'id' => $hadithId,
                'source' => 'bukhari',
                'chapter_no' => 1,
                'hadith_no' => 1,
                'text_ar' => 'حديث عربي',
                'text_en' => 'Hadith in English',
            ]);
        }
    }

    /** Create minimal quran_all_lang tables and one verse. */
    private function seedQuranExternal(int $verseId = 1): void
    {
        $conn = 'mysql_quran_all_lang';
        if (! Schema::connection($conn)->hasTable('languages')) {
            Schema::connection($conn)->create('languages', function ($table) {
                $table->id();
                $table->string('code', 10);
                $table->string('name');
                $table->string('native_name')->nullable();
                $table->string('direction', 3)->default('ltr');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }
        if (! Schema::connection($conn)->hasTable('translations')) {
            Schema::connection($conn)->create('translations', function ($table) {
                $table->id();
                $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
                $table->string('source_name')->nullable();
                $table->string('file_name')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::connection($conn)->hasTable('quran_verses')) {
            Schema::connection($conn)->create('quran_verses', function ($table) {
                $table->id();
                $table->unsignedInteger('surah_number');
                $table->unsignedInteger('ayah_number');
                $table->string('ayah_key', 32)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
        if (! Schema::connection($conn)->hasTable('verse_texts')) {
            Schema::connection($conn)->create('verse_texts', function ($table) {
                $table->id();
                $table->unsignedBigInteger('verse_id');
                $table->unsignedBigInteger('translation_id');
                $table->longText('text')->nullable();
                $table->timestamps();
            });
        }

        if (DB::connection($conn)->table('languages')->where('code', 'en')->doesntExist()) {
            DB::connection($conn)->table('languages')->insert([
                'id' => 1,
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'direction' => 'ltr',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if (DB::connection($conn)->table('languages')->where('code', 'ar')->doesntExist()) {
            DB::connection($conn)->table('languages')->insert([
                'id' => 2,
                'code' => 'ar',
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'direction' => 'rtl',
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if (DB::connection($conn)->table('translations')->where('id', 1)->doesntExist()) {
            DB::connection($conn)->table('translations')->insert([
                'id' => 1,
                'language_id' => 1,
                'source_name' => 'Test',
                'file_name' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if (DB::connection($conn)->table('translations')->where('id', 2)->doesntExist()) {
            DB::connection($conn)->table('translations')->insert([
                'id' => 2,
                'language_id' => 2,
                'source_name' => 'Test AR',
                'file_name' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if (DB::connection($conn)->table('quran_verses')->where('id', $verseId)->doesntExist()) {
            DB::connection($conn)->table('quran_verses')->insert([
                'id' => $verseId,
                'surah_number' => 1,
                'ayah_number' => 1,
                'ayah_key' => '1:1',
                'created_at' => now(),
            ]);
        }
        if (DB::connection($conn)->table('verse_texts')->where('verse_id', $verseId)->doesntExist()) {
            DB::connection($conn)->table('verse_texts')->insert([
                'verse_id' => $verseId,
                'translation_id' => 1,
                'text' => 'In the name of Allah',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::connection($conn)->table('verse_texts')->insert([
                'verse_id' => $verseId,
                'translation_id' => 2,
                'text' => 'بسم الله',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function test_invalid_type_returns_422(): void
    {
        $response = $this->getJson('/api/v1/daily-inspiration?type=invalid');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_returns_404_when_no_collections_available(): void
    {
        $response = $this->getJson('/api/v1/daily-inspiration');
        $response->assertStatus(404)
            ->assertJsonPath('status', false);
    }

    public function test_force_hadith_returns_hadith_only(): void
    {
        $this->seedHadithExternal(1);
        $coll = HadithCollection::create([
            'title' => 'Test Hadith',
            'slug' => 'test-hadith',
            'display_order' => 0,
        ]);
        DB::table('lib_hadith_collection_item')->insert([
            'hadith_collection_id' => $coll->id,
            'hadith_item_id' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/daily-inspiration?type=hadith');
        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.type', 'hadith')
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.collection_id', $coll->id)
            ->assertJsonPath('data.arabic', 'حديث عربي')
            ->assertJsonPath('data.translation', 'Hadith in English');
    }

    public function test_force_ayah_returns_ayah_only(): void
    {
        $this->seedQuranExternal(1);
        $coll = VerseCollection::create([
            'title' => 'Test Verses',
            'slug' => 'test-verses',
            'display_order' => 0,
        ]);
        DB::table('verse_collection_ayah')->insert([
            'verse_collection_id' => $coll->id,
            'quran_ayah_id' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/daily-inspiration?type=ayah');
        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.type', 'ayah')
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.collection_id', $coll->id);
    }

    public function test_returned_id_in_collection_pivot(): void
    {
        $this->seedHadithExternal(1);
        $this->seedQuranExternal(1);
        $hadithColl = HadithCollection::create([
            'title' => 'Hadith Coll',
            'slug' => 'hadith-coll',
            'display_order' => 0,
        ]);
        DB::table('lib_hadith_collection_item')->insert([
            'hadith_collection_id' => $hadithColl->id,
            'hadith_item_id' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $verseColl = VerseCollection::create([
            'title' => 'Verse Coll',
            'slug' => 'verse-coll',
            'display_order' => 0,
        ]);
        DB::table('verse_collection_ayah')->insert([
            'verse_collection_id' => $verseColl->id,
            'quran_ayah_id' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['hadith', 'ayah'] as $type) {
            $response = $this->getJson("/api/v1/daily-inspiration?type={$type}&refresh=1");
            $response->assertOk();
            $data = $response->json('data');
            $this->assertNotEmpty($data['id']);
            $this->assertNotEmpty($data['collection_id']);
            if ($data['type'] === 'hadith') {
                $collection = HadithCollection::find($data['collection_id']);
                $this->assertNotNull($collection);
                $ids = $collection->getHadithItemIds();
                $this->assertContains($data['id'], $ids, 'Returned hadith id must be in collection pivot.');
            } else {
                $collection = VerseCollection::find($data['collection_id']);
                $this->assertNotNull($collection);
                $ids = $collection->getQuranAyahIds();
                $this->assertContains($data['id'], $ids, 'Returned ayah id must be in collection pivot.');
            }
        }
    }

    public function test_cached_response_same_without_refresh(): void
    {
        $this->seedHadithExternal(1);
        $coll = HadithCollection::create([
            'title' => 'Test',
            'slug' => 'test',
            'display_order' => 0,
        ]);
        DB::table('lib_hadith_collection_item')->insert([
            'hadith_collection_id' => $coll->id,
            'hadith_item_id' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $first = $this->getJson('/api/v1/daily-inspiration?type=hadith');
        $first->assertOk();
        $second = $this->getJson('/api/v1/daily-inspiration?type=hadith');
        $second->assertOk();
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame($first->json('data.collection_id'), $second->json('data.collection_id'));
    }

    public function test_refresh_bypasses_cache(): void
    {
        $this->seedHadithExternal(1);
        $this->seedHadithExternal(2);
        $coll = HadithCollection::create([
            'title' => 'Test',
            'slug' => 'test',
            'display_order' => 0,
        ]);
        DB::table('lib_hadith_collection_item')->insert([
            'hadith_collection_id' => $coll->id,
            'hadith_item_id' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('lib_hadith_collection_item')->insert([
            'hadith_collection_id' => $coll->id,
            'hadith_item_id' => 2,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $first = $this->getJson('/api/v1/daily-inspiration?type=hadith');
        $first->assertOk();
        $second = $this->getJson('/api/v1/daily-inspiration?type=hadith&refresh=1');
        $second->assertOk();
        // With refresh we may get same or different (random); at least response is valid
        $this->assertContains($second->json('data.id'), [1, 2]);
        $this->assertSame($coll->id, $second->json('data.collection_id'));
    }

    public function test_debug_included_when_debug_param(): void
    {
        $this->seedHadithExternal(1);
        $coll = HadithCollection::create([
            'title' => 'Test',
            'slug' => 'test',
            'display_order' => 0,
        ]);
        DB::table('lib_hadith_collection_item')->insert([
            'hadith_collection_id' => $coll->id,
            'hadith_item_id' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/daily-inspiration?type=hadith&debug=1');
        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => ['type', 'id', 'collection_id', 'title', 'arabic', 'translation', 'source'],
                'debug' => [
                    'picked_type',
                    'picked_collection_id',
                    'picked_item_id',
                    'counts' => ['hadith_collections', 'verse_collections'],
                    'strategy',
                    'cache_key',
                    'forced_type',
                ],
            ]);
        $this->assertSame('hadith', $response->json('debug.picked_type'));
        $this->assertSame('collection_pivot_random_id + external_db_fetch', $response->json('debug.strategy'));
    }

    public function test_public_endpoint_ok_without_auth(): void
    {
        $this->seedHadithExternal(1);
        $coll = HadithCollection::create([
            'title' => 'Guest Test',
            'slug' => 'guest-test',
            'display_order' => 0,
        ]);
        DB::table('lib_hadith_collection_item')->insert([
            'hadith_collection_id' => $coll->id,
            'hadith_item_id' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/daily-inspiration?type=hadith');
        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.type', 'hadith');
    }

    public function test_cache_key_includes_guest_or_user(): void
    {
        $this->seedHadithExternal(1);
        $coll = HadithCollection::create([
            'title' => 'Cache Test',
            'slug' => 'cache-test',
            'display_order' => 0,
        ]);
        DB::table('lib_hadith_collection_item')->insert([
            'hadith_collection_id' => $coll->id,
            'hadith_item_id' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $guestResponse = $this->getJson('/api/v1/daily-inspiration?type=hadith&debug=1');
        $guestResponse->assertOk();
        $cacheKeyGuest = $guestResponse->json('debug.cache_key');
        $this->assertStringContainsString('guest', $cacheKeyGuest);

        $user = AppUser::factory()->create();
        $userResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/daily-inspiration?type=hadith&debug=1&refresh=1');
        $userResponse->assertOk();
        $cacheKeyUser = $userResponse->json('debug.cache_key');
        $this->assertStringContainsString((string) $user->id, $cacheKeyUser);
    }
}
