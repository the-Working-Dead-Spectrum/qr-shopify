<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests pour le nouveau tableau de bord administration.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test d'accès au nouveau tableau de bord.
     */
    public function test_can_access_new_dashboard(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('admin.dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard_new');
    }

    /**
     * Test de l'API de données en temps réel.
     */
    public function test_real_time_data_api(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('admin.api.dashboard-data'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'scansToday',
                'validationRate',
                'recentActivities',
            ],
            'timestamp',
        ]);
    }

    /**
     * Test d'accès aux paramètres.
     */
    public function test_can_access_settings(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('admin.settings'));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.settings');
    }

    /**
     * Test d'accès aux logs.
     */
    public function test_can_access_logs(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('admin.logs'));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.logs');
    }

    /**
     * Test d'accès aux rapports.
     */
    public function test_can_access_reports(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('admin.reports'));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.reports');
    }

    /**
     * Test d'accès au support.
     */
    public function test_can_access_support(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('admin.support'));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.support');
    }

    /**
     * Test d'accès à l'ancien tableau de bord.
     */
    public function test_can_access_old_dashboard(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('admin.dashboard.old'));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }
}