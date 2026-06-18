<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------
    // REGISTER
    // -----------------------------------------------

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Sam Test',
            'email'                 => 'sam@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    public function test_register_fails_with_duplicate_email()
    {
        User::factory()->create(['email' => 'sam@test.com']);

        $response = $this->postJson('/api/register', [
            'name'                  => 'Sam Test',
            'email'                 => 'sam@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_fails_with_short_password()
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Sam Test',
            'email'                 => 'sam@test.com',
            'password'              => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422);
    }

    // -----------------------------------------------
    // LOGIN
    // -----------------------------------------------

    public function test_user_can_login()
    {
        User::factory()->create([
            'email'    => 'sam@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'sam@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_fails_with_wrong_password()
    {
        User::factory()->create([
            'email'    => 'sam@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'sam@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_with_unknown_email()
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'nobody@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    // -----------------------------------------------
    // PROTECTED ROUTES
    // -----------------------------------------------

    public function test_unauthenticated_user_cannot_access_questions()
    {
        $response = $this->getJson('/api/questions');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_me()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/me');

        $response->assertStatus(200)
                 ->assertJsonFragment(['email' => $user->email]);
    }

    // -----------------------------------------------
    // LOGOUT
    // -----------------------------------------------

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Logged out successfully.']);
    }
}