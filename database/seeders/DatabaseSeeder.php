<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Order;
use App\Models\Partner;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Admin principal
        $admin = User::factory()->create([
            'name'  => 'Administrateur',
            'email' => 'admin@app.com',
            'role'  => Role::Admin,
        ]);

        // Partenaire de test avec token Sanctum
        $partnerUser = User::factory()->create([
            'name'  => 'Partenaire Test',
            'email' => 'partner@app.com',
            'role'  => Role::Partner,
        ]);

        $partner = Partner::create([
            'user_id' => $partnerUser->id,
            'name'    => 'Partenaire Test',
            'slug'    => 'partenaire-test',
        ]);

        // Token Sanctum affiché en sortie pour les tests manuels
        $token = $partnerUser->createToken('dev-token')->plainTextToken;
        $this->command->info("Token partenaire : {$token}");

        // Commandes avec QR Codes dans différents états
        Order::factory(10)->create()->each(function (Order $order) use ($partner) {
            QrCode::factory()->active()->create(['order_id' => $order->id]);
        });

        Order::factory(3)->create()->each(function (Order $order) use ($partner) {
            QrCode::factory()->used()->create([
                'order_id'   => $order->id,
                'partner_id' => $partner->id,
            ]);
        });

        Order::factory(2)->create()->each(function (Order $order) {
            QrCode::factory()->expired()->create(['order_id' => $order->id]);
        });
    }
}
