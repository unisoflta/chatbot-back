<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Domains\Chat\Models\Chat;
use App\Domains\Messages\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Chat $chat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->chat = Chat::create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);
    }

    public function test_store_message_requires_authentication()
    {
        $response = $this->postJson('/api/messages', [
            'chat_id' => 1,
            'message' => 'Hello'
        ]);

        $response->assertStatus(401);
    }

    public function test_store_message_validation()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/messages', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['chat_id', 'message']);
    }

    public function test_store_message_success()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/messages', [
            'chat_id' => $this->chat->id,
            'message' => 'Hello, how are you?'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user_message',
                    'bot_response',
                    'chat_id',
                    'timestamp'
                ]
            ]);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $this->chat->id,
            'sender_type' => 'user',
            'content' => 'Hello, how are you?'
        ]);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $this->chat->id,
            'sender_type' => 'bot'
        ]);
    }

    public function test_store_message_chat_not_found()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/messages', [
            'chat_id' => 999,
            'message' => 'Hello'
        ]);

        $response->assertStatus(404);
    }

    public function test_store_message_chat_not_owned_by_user()
    {
        $otherUser = User::factory()->create();
        $otherChat = Chat::create([
            'user_id' => $otherUser->id,
            'status' => 'active'
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/messages', [
            'chat_id' => $otherChat->id,
            'message' => 'Hello'
        ]);

        $response->assertStatus(404);
    }

    public function test_index_requires_authentication()
    {
        $response = $this->getJson('/api/messages');

        $response->assertStatus(401);
    }

    public function test_index_success()
    {
        Sanctum::actingAs($this->user);

        // Create some messages
        $this->chat->messages()->createMany([
            ['sender_type' => 'user', 'content' => 'Message 1'],
            ['sender_type' => 'bot', 'content' => 'Response 1'],
            ['sender_type' => 'user', 'content' => 'Message 2']
        ]);

        $response = $this->getJson('/api/messages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'messages',
                    'pagination'
                ]
            ]);

        $this->assertEquals(3, $response->json('data.pagination.total'));
    }

    public function test_show_requires_authentication()
    {
        $response = $this->getJson('/api/messages/1');

        $response->assertStatus(401);
    }

    public function test_show_success()
    {
        Sanctum::actingAs($this->user);

        $message = $this->chat->messages()->create([
            'sender_type' => 'user',
            'content' => 'Test message'
        ]);

        $response = $this->getJson("/api/messages/{$message->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'message'
                ]
            ]);
    }

    public function test_show_not_found()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/messages/999');

        $response->assertStatus(404);
    }

    public function test_update_requires_authentication()
    {
        $response = $this->putJson('/api/messages/1', [
            'content' => 'Updated message'
        ]);

        $response->assertStatus(401);
    }

    public function test_update_validation()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/messages/1', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_update_success()
    {
        Sanctum::actingAs($this->user);

        $message = $this->chat->messages()->create([
            'sender_type' => 'user',
            'content' => 'Original message'
        ]);

        $response = $this->putJson("/api/messages/{$message->id}", [
            'content' => 'Updated message'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'content' => 'Updated message'
        ]);
    }

    public function test_update_message_not_owned_by_user()
    {
        $otherUser = User::factory()->create();
        $otherChat = Chat::create([
            'user_id' => $otherUser->id,
            'status' => 'active'
        ]);

        $message = $otherChat->messages()->create([
            'sender_type' => 'user',
            'content' => 'Other message'
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/messages/{$message->id}", [
            'content' => 'Updated message'
        ]);

        $response->assertStatus(404);
    }

    public function test_destroy_requires_authentication()
    {
        $response = $this->deleteJson('/api/messages/1');

        $response->assertStatus(401);
    }

    public function test_destroy_success()
    {
        Sanctum::actingAs($this->user);

        $message = $this->chat->messages()->create([
            'sender_type' => 'user',
            'content' => 'Test message'
        ]);

        $response = $this->deleteJson("/api/messages/{$message->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    public function test_destroy_message_not_owned_by_user()
    {
        $otherUser = User::factory()->create();
        $otherChat = Chat::create([
            'user_id' => $otherUser->id,
            'status' => 'active'
        ]);

        $message = $otherChat->messages()->create([
            'sender_type' => 'user',
            'content' => 'Other message'
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/messages/{$message->id}");

        $response->assertStatus(404);
    }

    public function test_get_by_chat_requires_authentication()
    {
        $response = $this->getJson('/api/messages/chat/1');

        $response->assertStatus(401);
    }

    public function test_get_by_chat_success()
    {
        Sanctum::actingAs($this->user);

        $this->chat->messages()->createMany([
            ['sender_type' => 'user', 'content' => 'Message 1'],
            ['sender_type' => 'bot', 'content' => 'Response 1'],
            ['sender_type' => 'user', 'content' => 'Message 2']
        ]);

        $response = $this->getJson("/api/messages/chat/{$this->chat->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'chat',
                    'messages',
                    'pagination'
                ]
            ]);

        $this->assertEquals(3, $response->json('data.pagination.total'));
    }

    public function test_get_by_chat_not_owned_by_user()
    {
        $otherUser = User::factory()->create();
        $otherChat = Chat::create([
            'user_id' => $otherUser->id,
            'status' => 'active'
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/messages/chat/{$otherChat->id}");

        $response->assertStatus(404);
    }

    public function test_get_by_user_requires_authentication()
    {
        $response = $this->getJson('/api/messages/user');

        $response->assertStatus(401);
    }

    public function test_get_by_user_success()
    {
        Sanctum::actingAs($this->user);

        $this->chat->messages()->createMany([
            ['sender_type' => 'user', 'content' => 'Message 1'],
            ['sender_type' => 'bot', 'content' => 'Response 1']
        ]);

        $response = $this->getJson('/api/messages/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'messages',
                    'pagination'
                ]
            ]);

        $this->assertEquals(2, $response->json('data.pagination.total'));
    }

    public function test_get_by_sender_type_requires_authentication()
    {
        $response = $this->getJson('/api/messages/sender/user');

        $response->assertStatus(401);
    }

    public function test_get_by_sender_type_success()
    {
        Sanctum::actingAs($this->user);

        $this->chat->messages()->createMany([
            ['sender_type' => 'user', 'content' => 'User message'],
            ['sender_type' => 'bot', 'content' => 'Bot response'],
            ['sender_type' => 'user', 'content' => 'Another user message']
        ]);

        $response = $this->getJson('/api/messages/sender/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'messages',
                    'pagination'
                ]
            ]);

        $this->assertEquals(2, $response->json('data.pagination.total'));
    }

    public function test_get_by_sender_type_invalid_type()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/messages/sender/invalid');

        $response->assertStatus(422);
    }

    public function test_search_requires_authentication()
    {
        $response = $this->getJson('/api/messages/search?query=test');

        $response->assertStatus(401);
    }

    public function test_search_validation()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/messages/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    }

    public function test_search_success()
    {
        Sanctum::actingAs($this->user);

        $this->chat->messages()->createMany([
            ['sender_type' => 'user', 'content' => 'Hello world'],
            ['sender_type' => 'bot', 'content' => 'Hi there'],
            ['sender_type' => 'user', 'content' => 'How are you?']
        ]);

        $response = $this->getJson('/api/messages/search?query=hello');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'query',
                    'messages',
                    'pagination'
                ]
            ]);

        $this->assertEquals('hello', $response->json('data.query'));
        $this->assertGreaterThan(0, $response->json('data.pagination.total'));
    }
} 