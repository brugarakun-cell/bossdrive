<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccountProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_province_city_and_barangay_fields(): void
    {
        $user = User::factory()->create([
            'province' => 'Cavite',
            'city' => 'Silang',
            'barangay' => 'Biga I',
        ]);

        $this->actingAs($user)->get(route('user.account'))
            ->assertOk()
            ->assertSee('name="province"', false)
            ->assertSee('name="city"', false)
            ->assertSee('name="barangay"', false)
            ->assertSee('Province')
            ->assertSee('City / Municipality')
            ->assertSee('Barangay');
    }

    public function test_user_can_update_province_city_and_barangay_in_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('user.account.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'contact_number' => '09123456789',
            'province' => 'Cavite',
            'city' => 'Silang',
            'barangay' => 'Biga I',
            'address' => 'Block 1 Lot 1',
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'province' => 'Cavite',
            'city' => 'Silang',
            'barangay' => 'Biga I',
            'address' => 'Block 1 Lot 1',
        ]);
    }
}
