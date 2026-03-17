<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correct_credentials()
    {
        // User Admin
        $user = User::factory()->create([
            'email' => 'admin@energeek.id',
            'password' => bcrypt('tes123'),
            'role' => 'admin',
        ]);

        // Lakukan request POST ke endpoint login
        $response = $this->postJson('/api/login', [
            'email' => 'admin@energeek.id',
            'password' => 'tes123',
        ]);

        // Response
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'access_token',
                     'token_type',
                     'user' => [
                         'id',
                         'name',
                         'email',
                         'role'
                     ]
                 ]);
    }
}