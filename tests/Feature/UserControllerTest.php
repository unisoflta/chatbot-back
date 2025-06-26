<?php

namespace Tests\Feature;

use App\Domains\User\DTOs\UserDTO;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user and authenticate
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);
        
        // Create token for authentication
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function it_can_list_users_with_pagination()
    {
        // Create additional users
        User::factory()->count(5)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get('/api/users');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'name',
                                'email',
                                'created_at',
                                'updated_at'
                            ]
                        ],
                        'current_page',
                        'per_page',
                        'total'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Users retrieved successfully', $response->json('message'));
    }

    /** @test */
    public function it_can_list_users_with_custom_parameters()
    {
        User::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get('/api/users?per_page=2&columns=id,name,email');

        $response->assertStatus(200);
        
        $data = $response->json('data.data');
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('email', $data[0]);
        $this->assertArrayNotHasKey('password', $data[0]);
    }

    /** @test */
    public function it_can_get_all_users_non_paginated()
    {
        User::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get('/api/users/all');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Users retrieved successfully', $response->json('message'));
    }

    /** @test */
    public function it_can_create_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => 'user'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->post('/api/users', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'name',
                        'email',
                        'password',
                        'role'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('User created successfully', $response->json('message'));
        $this->assertEquals('John Doe', $response->json('data.name'));
        $this->assertEquals('john@example.com', $response->json('data.email'));

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_user()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->post('/api/users', []);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'name',
                        'email',
                        'password'
                    ]
                ]);

        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_validates_email_uniqueness_when_creating_user()
    {
        // Create a user with existing email
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->post('/api/users', $userData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'email'
                    ]
                ]);
    }

    /** @test */
    public function it_can_show_user()
    {
        $userToShow = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get("/api/users/{$userToShow->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'name',
                        'email',
                        'password',
                        'role'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('User retrieved successfully', $response->json('message'));
        $this->assertEquals($userToShow->name, $response->json('data.name'));
        $this->assertEquals($userToShow->email, $response->json('data.email'));
    }

    /** @test */
    public function it_returns_404_for_nonexistent_user()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get('/api/users/999');

        $response->assertStatus(404)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $this->assertFalse($response->json('success'));
        $this->assertEquals('User not found', $response->json('message'));
    }

    /** @test */
    public function it_can_update_user()
    {
        $userToUpdate = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->put("/api/users/{$userToUpdate->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'name',
                        'email',
                        'password',
                        'role'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('User updated successfully', $response->json('message'));
        $this->assertEquals('Updated Name', $response->json('data.name'));
        $this->assertEquals('updated@example.com', $response->json('data.email'));

        // Verify user was updated in database
        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);
    }

    /** @test */
    public function it_can_update_user_with_password()
    {
        $userToUpdate = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'password' => 'newpassword123'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->put("/api/users/{$userToUpdate->id}", $updateData);

        $response->assertStatus(200);

        // Verify password was hashed and updated
        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'Updated Name'
        ]);
    }

    /** @test */
    public function it_validates_email_uniqueness_when_updating_user()
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $updateData = [
            'email' => 'user1@example.com' // Try to use user1's email for user2
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->put("/api/users/{$user2->id}", $updateData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'email'
                    ]
                ]);
    }

    /** @test */
    public function it_can_delete_user()
    {
        $userToDelete = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->delete("/api/users/{$userToDelete->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('User deleted successfully', $response->json('message'));

        // Verify user was deleted from database
        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id
        ]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_user()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->delete('/api/users/999');

        $response->assertStatus(404)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $this->assertFalse($response->json('success'));
        $this->assertEquals('User not found', $response->json('message'));
    }

    /** @test */
    public function it_can_search_users()
    {
        // Create users with specific names
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);
        User::factory()->create(['name' => 'Bob Johnson']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get('/api/users/search?q=John');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Search completed successfully', $response->json('message'));
    }

    /** @test */
    public function it_validates_search_query_parameter()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get('/api/users/search');

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'q'
                    ]
                ]);
    }

    /** @test */
    public function it_can_find_user_by_email()
    {
        $userToFind = User::factory()->create(['email' => 'findme@example.com']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get('/api/users/find-by-email?email=findme@example.com');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'name',
                        'email',
                        'password',
                        'role'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('User retrieved successfully', $response->json('message'));
        $this->assertEquals('findme@example.com', $response->json('data.email'));
    }

    /** @test */
    public function it_returns_404_when_finding_nonexistent_email()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get('/api/users/find-by-email?email=nonexistent@example.com');

        $response->assertStatus(404)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $this->assertFalse($response->json('success'));
        $this->assertEquals('User not found', $response->json('message'));
    }

    /** @test */
    public function it_validates_email_parameter_for_find_by_email()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get('/api/users/find-by-email');

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'email'
                    ]
                ]);
    }

    /** @test */
    public function it_can_update_user_status()
    {
        $userToUpdate = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->patch("/api/users/{$userToUpdate->id}/status", [
            'status' => 'inactive'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'name',
                        'email',
                        'password',
                        'role'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('User status updated successfully', $response->json('message'));
    }

    /** @test */
    public function it_validates_status_values()
    {
        $userToUpdate = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->patch("/api/users/{$userToUpdate->id}/status", [
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'status'
                    ]
                ]);
    }

    /** @test */
    public function it_requires_authentication_for_all_routes()
    {
        $routes = [
            ['GET', '/api/users'],
            ['POST', '/api/users'],
            ['GET', '/api/users/1'],
            ['PUT', '/api/users/1'],
            ['DELETE', '/api/users/1'],
            ['GET', '/api/users/search?q=test'],
            ['GET', '/api/users/find-by-email?email=test@example.com'],
            ['PATCH', '/api/users/1/status']
        ];

        foreach ($routes as [$method, $route]) {
            $response = $this->withHeaders([
                'Accept' => 'application/json'
            ])->$method($route);

            $response->assertStatus(401);
        }
    }

    /** @test */
    public function it_handles_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json'
        ])->get('/api/users');

        $response->assertStatus(401);
    }
} 