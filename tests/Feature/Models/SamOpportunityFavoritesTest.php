<?php

namespace Tests\Feature\Models;

use App\Models\SamOpportunity;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SamOpportunityFavoritesTest extends TestCase
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

    public function test_user_can_favorite_an_opportunity(): void
    {
        $user = User::factory()->create();
        $opportunity = SamOpportunity::create(['title' => 'Test SAM Opportunity']);

        $user->favoriteSamOpportunities()->attach($opportunity->id);

        $this->assertTrue($opportunity->isFavoritedBy($user));
        $this->assertEquals(1, $user->favoriteSamOpportunities()->count());
    }

    public function test_duplicate_favorites_are_prevented(): void
    {
        $this->expectException(QueryException::class);

        $user = User::factory()->create();
        $opportunity = SamOpportunity::create(['title' => 'Duplicate Check']);

        $user->favoriteSamOpportunities()->attach($opportunity->id);
        $user->favoriteSamOpportunities()->attach($opportunity->id);
    }

    public function test_is_favorited_by_returns_false_for_non_favoriting_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $opportunity = SamOpportunity::create(['title' => 'Another Opportunity']);

        $user->favoriteSamOpportunities()->attach($opportunity->id);

        $this->assertTrue($opportunity->isFavoritedBy($user));
        $this->assertFalse($opportunity->isFavoritedBy($otherUser));
    }
}
