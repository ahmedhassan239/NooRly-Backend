<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Users\SavedItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SavedItemsTest extends TestCase
{
    use RefreshDatabase;

    private AppUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = AppUser::factory()->create();
    }

    /** @test */
    public function it_requires_authentication_to_list_saved_items()
    {
        $response = $this->getJson('/api/v1/saved');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_lists_saved_items_for_authenticated_user()
    {
        Sanctum::actingAs($this->user);

        // Create some saved items
        SavedItem::create([
            'user_id' => $this->user->id,
            'item_type' => 'dua',
            'item_id' => '1',
        ]);

        SavedItem::create([
            'user_id' => $this->user->id,
            'item_type' => 'hadith',
            'item_id' => '2',
        ]);

        $response = $this->getJson('/api/v1/saved');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);

        $this->assertEquals(2, $response->json('meta.total'));
    }

    /** @test */
    public function it_filters_saved_items_by_type()
    {
        Sanctum::actingAs($this->user);

        SavedItem::create([
            'user_id' => $this->user->id,
            'item_type' => 'dua',
            'item_id' => '1',
        ]);

        SavedItem::create([
            'user_id' => $this->user->id,
            'item_type' => 'hadith',
            'item_id' => '2',
        ]);

        $response = $this->getJson('/api/v1/saved?type=dua');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    /** @test */
    public function it_saves_an_item()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/saved/dua/123');

        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseHas('saved_items', [
            'user_id' => $this->user->id,
            'item_type' => 'dua',
            'item_id' => '123',
        ]);
    }

    /** @test */
    public function it_saves_verse_type()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/saved/verse/456');

        $response->assertStatus(201);

        $this->assertDatabaseHas('saved_items', [
            'user_id' => $this->user->id,
            'item_type' => 'verse',
            'item_id' => '456',
        ]);
    }

    /** @test */
    public function it_saves_adhkar_type()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/saved/adhkar/789');

        $response->assertStatus(201);

        $this->assertDatabaseHas('saved_items', [
            'user_id' => $this->user->id,
            'item_type' => 'adhkar',
            'item_id' => '789',
        ]);
    }

    /** @test */
    public function it_rejects_invalid_item_type()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/saved/invalid_type/123');

        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
            ]);
    }

    /** @test */
    public function it_removes_saved_item()
    {
        Sanctum::actingAs($this->user);

        SavedItem::create([
            'user_id' => $this->user->id,
            'item_type' => 'dua',
            'item_id' => '123',
        ]);

        $response = $this->deleteJson('/api/v1/saved/dua/123');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseMissing('saved_items', [
            'user_id' => $this->user->id,
            'item_type' => 'dua',
            'item_id' => '123',
        ]);
    }

    /** @test */
    public function it_returns_404_when_removing_nonexistent_item()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/v1/saved/dua/nonexistent');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_does_not_duplicate_saved_items()
    {
        Sanctum::actingAs($this->user);

        // Save the same item twice
        $this->postJson('/api/v1/saved/dua/123');
        $this->postJson('/api/v1/saved/dua/123');

        // Should only have one record
        $count = SavedItem::where('user_id', $this->user->id)
            ->where('item_type', 'dua')
            ->where('item_id', '123')
            ->count();

        $this->assertEquals(1, $count);
    }
}
