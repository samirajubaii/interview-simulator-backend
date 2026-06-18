<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterviewResultTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email): array
    {
        $user  = User::factory()->create(['email' => $email]);
        $token = $user->createToken('test')->plainTextToken;
        return [$user, $token];
    }

    public function test_authenticated_user_can_store_result()
    {
        [$user, $token] = $this->makeUser('a@test.com');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/results', [
                'score'           => 85,
                'total_questions' => 5,
                'skipped'         => 0,
                'category_id'     => 'Backend',
                'answers'         => [
                    ['question' => 'What is REST?', 'answer' => 'An architectural style', 'score' => 80]
                ],
            ]);

        $response->assertStatus(201);
    }

    public function test_unauthenticated_user_cannot_store_result()
    {
        $response = $this->postJson('/api/results', [
            'score'           => 85,
            'total_questions' => 5,
            'skipped'         => 0,
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_results()
    {
        [$user, $token] = $this->makeUser('b@test.com');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/results');

        $response->assertStatus(200)->assertJsonIsArray();
    }

    public function test_unauthenticated_user_cannot_get_results()
    {
        $response = $this->getJson('/api/results');
        $response->assertStatus(401);
    }

    public function test_user_cannot_access_another_users_result()
    {
        $userA = User::factory()->create(['id' => 1, 'email' => 'usera@test.com']);
        $userB = User::factory()->create(['id' => 2, 'email' => 'userb@test.com']);
    
        $result = \App\Models\InterviewResult::create([
            'user_id'         => 1,
            'score'           => 75,
            'total_questions' => 5,
            'skipped'         => 1,
            'category_id'     => 'Frontend',
            'answers'         => [],
        ]);
    
        // UserA CAN access their own result
        $this->actingAs($userA)
            ->getJson('/api/results/' . $result->id)
            ->assertStatus(200);
    
        // UserB CANNOT access userA's result
        $this->actingAs($userB)
            ->getJson('/api/results/' . $result->id)
            ->assertStatus(403);
    }
}