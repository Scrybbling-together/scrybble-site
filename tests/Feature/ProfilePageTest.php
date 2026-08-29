<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_visit_their_profile_page()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->actingAs($user)
            ->get('/profile');
        $response->assertStatus(200);

        $response->assertSee($user->name);
        $response->assertSee($user->email);
        $response->assertSee('No license');
    }

    public function test_unauthenticated_user_cannot_access_profile_page()
    {
        $this->get('/profile')->assertRedirect('/login');
    }
}
