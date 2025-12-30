<?php

namespace Tests\Feature\Api;

use App\Models\SamOpportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SamOpportunityFavoritesApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('sam_opportunities')) {
            Schema::create('sam_opportunities', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_guest_cannot_access_favorites(): void
    {
        $response = $this->getJson('/api/v1/sam-opportunities/favorites');
        $response->assertUnauthorized();
    }

    public function test_user_can_favorite_and_unfavorite(): void
    {
        $user = User::factory()->create();
        $opp = SamOpportunity::create(['title' => 'API Favorite']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sam-opportunities/{$opp->id}/favorite")
            ->assertCreated()
            ->assertJson([
                'sam_opportunity_id' => $opp->id,
                'is_favorite' => true,
            ]);

        $this->assertDatabaseHas('sam_opportunity_favorites', [
            'user_id' => $user->id,
            'sam_opportunity_id' => $opp->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/sam-opportunities/{$opp->id}/favorite")
            ->assertOk()
            ->assertJson([
                'sam_opportunity_id' => $opp->id,
                'is_favorite' => false,
            ]);

        $this->assertDatabaseMissing('sam_opportunity_favorites', [
            'user_id' => $user->id,
            'sam_opportunity_id' => $opp->id,
        ]);
    }

    public function test_listing_favorites_shows_only_current_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $opp1 = SamOpportunity::create(['title' => 'Mine']);
        $opp2 = SamOpportunity::create(['title' => 'Theirs']);

        $user->favoriteSamOpportunities()->attach($opp1->id);
        $other->favoriteSamOpportunities()->attach($opp2->id);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sam-opportunities/favorites')
            ->assertOk()
            ->json('data');

        $ids = collect($response)->pluck('id')->all();
        $this->assertContains($opp1->id, $ids);
        $this->assertNotContains($opp2->id, $ids);
    }
}
