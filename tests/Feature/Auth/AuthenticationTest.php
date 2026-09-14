<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_visitor_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jeanne Dupont',
            'email' => 'jeanne@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Jeanne Dupont')
            ->assertJsonStructure(['data' => ['id', 'name'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'jeanne@example.com']);
    }

    #[Test]
    public function registration_requires_a_unique_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Doublon',
            'email' => 'existing@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    #[Test]
    public function a_member_can_log_in_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'membre@example.com',
            'password' => Hash::make('Password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'membre@example.com',
            'password' => 'Password123',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()->assertJsonStructure(['data', 'token']);
    }

    #[Test]
    public function login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'membre@example.com']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'membre@example.com',
            'password' => 'wrong-password',
            'device_name' => 'phpunit',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    #[Test]
    public function an_authenticated_member_can_fetch_their_own_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/auth/me');

        $response->assertOk()->assertJsonPath('data.id', $user->id);
    }

    #[Test]
    public function a_guest_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    #[Test]
    public function a_deactivated_account_is_blocked_even_with_a_valid_token(): void
    {
        $member = User::factory()->inactive()->create();

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertForbidden();
    }
}
