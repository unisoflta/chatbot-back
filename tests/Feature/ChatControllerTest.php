<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Domains\Chat\Models\Chat;
use App\Domains\Messages\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_send_message_requires_authentication()
    {
        $response = $this->postJson('/api/chat/send', [
            'message' => 'Hello'
        ]);

        $response->assertStatus(401);
    }

    public function test_send_message_validation()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/chat/send', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_send_message_success()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/chat/send', [
            'message' => 'Hello, how are you?'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'response',
                    'chat_id',
                    'timestamp'
                ]
            ]);

        $this->assertDatabaseHas('chats', [
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $this->assertDatabaseHas('messages', [
            'sender_type' => 'user',
            'content' => 'Hello, how are you?'
        ]);
    }

    public function test_send_message_with_chat_id()
    {
        Sanctum::actingAs($this->user);

        // Create a chat first
        $chat = Chat::create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $response = $this->postJson('/api/chat/send', [
            'message' => 'Hello, how are you?',
            'chat_id' => $chat->id
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'sender_type' => 'user',
            'content' => 'Hello, how are you?'
        ]);
    }

    public function test_get_history_requires_authentication()
    {
        $response = $this->getJson('/api/chat/history');

        $response->assertStatus(401);
    }

    public function test_get_history_success()
    {
        Sanctum::actingAs($this->user);

        // Create a chat with messages
        $chat = Chat::create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $chat->messages()->createMany([
            ['sender_type' => 'user', 'content' => 'Hello'],
            ['sender_type' => 'bot', 'content' => 'Hi there!']
        ]);

        $response = $this->getJson('/api/chat/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'history',
                    'count'
                ]
            ]);

        $this->assertEquals(2, $response->json('data.count'));
    }

    public function test_get_history_with_chat_id()
    {
        Sanctum::actingAs($this->user);

        // Create multiple chats
        $chat1 = Chat::create(['user_id' => $this->user->id, 'status' => 'active']);
        $chat2 = Chat::create(['user_id' => $this->user->id, 'status' => 'active']);

        $chat1->messages()->create(['sender_type' => 'user', 'content' => 'Hello 1']);
        $chat2->messages()->create(['sender_type' => 'user', 'content' => 'Hello 2']);

        $response = $this->getJson('/api/chat/history?chat_id=' . $chat1->id);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.count'));
    }

    public function test_get_chats_requires_authentication()
    {
        $response = $this->getJson('/api/chat/chats');

        $response->assertStatus(401);
    }

    public function test_get_chats_success()
    {
        Sanctum::actingAs($this->user);

        // Create multiple chats
        Chat::create(['user_id' => $this->user->id, 'status' => 'active']);
        Chat::create(['user_id' => $this->user->id, 'status' => 'active']);

        $response = $this->getJson('/api/chat/chats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'chats',
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total'
                    ]
                ]
            ]);

        $this->assertEquals(2, $response->json('data.pagination.total'));
    }

    public function test_get_chat_messages_requires_authentication()
    {
        $response = $this->getJson('/api/chat/chats/1/messages');

        $response->assertStatus(401);
    }

    public function test_get_chat_messages_success()
    {
        Sanctum::actingAs($this->user);

        $chat = Chat::create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $chat->messages()->createMany([
            ['sender_type' => 'user', 'content' => 'Message 1'],
            ['sender_type' => 'bot', 'content' => 'Response 1'],
            ['sender_type' => 'user', 'content' => 'Message 2']
        ]);

        $response = $this->getJson("/api/chat/chats/{$chat->id}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'chat' => [
                        'id',
                        'status',
                        'created_at',
                        'last_message_at'
                    ],
                    'messages',
                    'pagination'
                ]
            ]);

        $this->assertEquals(3, $response->json('data.pagination.total'));
    }

    public function test_get_chat_messages_not_found()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/chat/chats/999/messages');

        $response->assertStatus(404);
    }

    public function test_close_chat_requires_authentication()
    {
        $response = $this->patchJson('/api/chat/chats/1/close');

        $response->assertStatus(401);
    }

    public function test_close_chat_success()
    {
        Sanctum::actingAs($this->user);

        $chat = Chat::create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $response = $this->patchJson("/api/chat/chats/{$chat->id}/close");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'chat_id',
                    'status'
                ]
            ]);

        $this->assertEquals('closed', $response->json('data.status'));
        $this->assertDatabaseHas('chats', [
            'id' => $chat->id,
            'status' => 'closed'
        ]);
    }

    public function test_close_chat_not_found()
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson('/api/chat/chats/999/close');

        $response->assertStatus(404);
    }

    public function test_delete_chat_requires_authentication()
    {
        $response = $this->deleteJson('/api/chat/chats/1');

        $response->assertStatus(401);
    }

    public function test_delete_chat_success()
    {
        Sanctum::actingAs($this->user);

        $chat = Chat::create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $chat->messages()->create([
            'sender_type' => 'user',
            'content' => 'Test message'
        ]);

        $response = $this->deleteJson("/api/chat/chats/{$chat->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('chats', ['id' => $chat->id]);
        $this->assertDatabaseMissing('messages', ['chat_id' => $chat->id]);
    }

    public function test_delete_chat_not_found()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/chat/chats/999');

        $response->assertStatus(404);
    }

    public function test_user_cannot_access_other_user_chat()
    {
        $otherUser = User::factory()->create();
        $chat = Chat::create([
            'user_id' => $otherUser->id,
            'status' => 'active'
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/chat/chats/{$chat->id}/messages");

        $response->assertStatus(404);
    }
} 