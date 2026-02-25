<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Adhkar\Adhkar;
use App\Domain\Auth\AppUser;
use App\Domain\Categories\Models\Category;
use App\Domain\Categories\Models\CategoryTranslation;
use App\Domain\ContentScopes\ContentScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoriesScopeAndAdhkarValidationTest extends TestCase
{
    use RefreshDatabase;

    private int $scopeAdhkarId;
    private int $scopeDuasId;
    private int $categoryAdhkarId;
    private int $categoryDuasId;
    private AppUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('languages')->insert([
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adhkarScope = ContentScope::create([
            'key' => 'adhkar',
            'label' => 'Adhkar',
            'is_active' => true,
        ]);
        $this->scopeAdhkarId = $adhkarScope->id;

        $duasScope = ContentScope::create([
            'key' => 'duas',
            'label' => 'Duas',
            'is_active' => true,
        ]);
        $this->scopeDuasId = $duasScope->id;

        $catAdhkar = Category::create([
            'scope_id' => $this->scopeAdhkarId,
        ]);
        $this->categoryAdhkarId = $catAdhkar->id;
        CategoryTranslation::create([
            'category_id' => $catAdhkar->id,
            'language_code' => 'en',
            'name' => 'Adhkar Category',
            'slug' => 'adhkar-cat-1',
        ]);

        $catDuas = Category::create([
            'scope_id' => $this->scopeDuasId,
        ]);
        $this->categoryDuasId = $catDuas->id;
        CategoryTranslation::create([
            'category_id' => $catDuas->id,
            'language_code' => 'en',
            'name' => 'Duas Category',
            'slug' => 'duas-cat-1',
        ]);

        $this->user = AppUser::factory()->create();
    }

    /** @test */
    public function get_categories_filtered_by_scope_returns_only_that_scope(): void
    {
        $response = $this->getJson('/api/v1/categories?scope=adhkar');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.0.id', $this->categoryAdhkarId)
            ->assertJsonPath('data.0.scope.key', 'adhkar');
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->categoryAdhkarId, $data[0]['id']);
    }

    /** @test */
    public function get_categories_with_scope_duas_returns_only_duas_categories(): void
    {
        $response = $this->getJson('/api/v1/categories?scope=duas');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->categoryDuasId, $data[0]['id']);
        $this->assertEquals('duas', $data[0]['scope']['key']);
    }

    /** @test */
    public function creating_adhkar_with_valid_category_id_same_scope_returns_success(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/adhkar', [
            'category_id' => $this->categoryAdhkarId,
            'text' => ['ar' => 'ذكر', 'en' => 'Dhikr'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.category_id', $this->categoryAdhkarId)
            ->assertJsonPath('data.type', 'adhkar');
        $this->assertArrayHasKey('category_id', $response->json('data'));
        $this->assertArrayHasKey('category', $response->json('data'));
        $this->assertDatabaseHas('adhkar', [
            'category_id' => $this->categoryAdhkarId,
        ]);
    }

    /** @test */
    public function creating_adhkar_with_category_id_from_different_scope_returns_422(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/adhkar', [
            'category_id' => $this->categoryDuasId,
            'text' => ['ar' => 'ذكر', 'en' => 'Dhikr'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
        $this->assertDatabaseCount('adhkar', 0);
    }

    /** @test */
    public function creating_adhkar_requires_category_id(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/adhkar', [
            'text' => ['ar' => 'ذكر', 'en' => 'Dhikr'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    }

    /** @test */
    public function adhkar_show_response_includes_category_id_and_category_object(): void
    {
        $adhkar = Adhkar::create([
            'category_id' => $this->categoryAdhkarId,
            'text' => ['ar' => 'ذكر', 'en' => 'Dhikr'],
            'count' => 1,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/adhkar/{$adhkar->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $adhkar->id)
            ->assertJsonPath('data.category_id', $this->categoryAdhkarId)
            ->assertJsonPath('data.category.id', $this->categoryAdhkarId)
            ->assertJsonPath('data.category.name', 'Adhkar Category');
    }
}
