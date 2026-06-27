<?php

namespace Tests\Feature\Middleware;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsurePartnerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Route de test protégée par Sanctum + EnsurePartner
        $this->app['router']
             ->middleware(['auth:sanctum', 'ensure.partner'])
             ->post('api/test-partner', fn () => response()->json(['ok' => true]));
    }

    // -------------------------------------------------------------------------
    // Cas d'acceptation
    // -------------------------------------------------------------------------

    public function test_autorise_un_partenaire_actif(): void
    {
        $partner = Partner::factory()->active()->withUser()->create();

        $this->actingAs($partner->user, 'sanctum')
             ->postJson('api/test-partner')
             ->assertStatus(200)
             ->assertJson(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // Cas de rejet
    // -------------------------------------------------------------------------

    public function test_rejette_sans_authentification(): void
    {
        $this->postJson('api/test-partner')
             ->assertStatus(401);
    }

    public function test_rejette_un_partenaire_suspendu(): void
    {
        $partner = Partner::factory()->suspended()->withUser()->create();

        $this->actingAs($partner->user, 'sanctum')
             ->postJson('api/test-partner')
             ->assertStatus(403)
             ->assertJsonFragment(['status' => 'suspended']);
    }

    public function test_rejette_un_partenaire_inactif(): void
    {
        $partner = Partner::factory()->inactive()->withUser()->create();

        $this->actingAs($partner->user, 'sanctum')
             ->postJson('api/test-partner')
             ->assertStatus(403);
    }

    public function test_rejette_un_admin_sur_une_route_partenaire(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
             ->postJson('api/test-partner')
             ->assertStatus(403);
    }

    public function test_rejette_un_utilisateur_sans_enregistrement_partner(): void
    {
        // User avec rôle partner mais sans ligne dans la table partners
        $user = User::factory()->partner()->create();

        $this->actingAs($user, 'sanctum')
             ->postJson('api/test-partner')
             ->assertStatus(403);
    }
}
