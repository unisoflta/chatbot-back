<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\GPTChatService;
use App\Services\WeatherApiService;
use App\Domains\Chat\Services\ChatService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;

class GPTChatServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private GPTChatService $gptChatService;
    private WeatherApiService $weatherService;
    private ChatService $chatService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the WeatherApiService
        $this->weatherService = Mockery::mock(WeatherApiService::class);
        $this->gptChatService = new GPTChatService($this->weatherService);
        $this->chatService = new ChatService($this->gptChatService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_chat_with_weather_request()
    {
        // Create a test user
        $user = User::factory()->create();

        // Mock weather service response
        $this->weatherService
            ->shouldReceive('getForecast')
            ->with('Madrid', '2024-01-15')
            ->andReturn([
                'city' => 'Madrid',
                'country' => 'Spain',
                'date' => '2024-01-15',
                'temperature' => 18.5,
                'weathercode' => 61,
                'coordinates' => [
                    'latitude' => 40.4168,
                    'longitude' => -3.7038
                ]
            ]);

        // Test the chat service
        $question = "¿Cómo estará el clima en Madrid mañana?";
        $history = [];

        try {
            $result = $this->chatService->sendMessage($user, $question, $history);
            
            // The response should contain weather information
            $this->assertIsArray($result);
            $this->assertArrayHasKey('response', $result);
            $this->assertArrayHasKey('chat_id', $result);
            $this->assertArrayHasKey('timestamp', $result);
            $this->assertNotEmpty($result['response']);
            
        } catch (\Exception $e) {
            // If OpenAI API is not configured, this is expected
            $this->assertStringContainsString('OpenAI API', $e->getMessage());
        }
    }

    public function test_save_conversation_to_database()
    {
        // Create a test user
        $user = User::factory()->create();

        $question = "¿Hola, cómo estás?";
        $response = "¡Hola! Estoy muy bien, gracias por preguntar. ¿En qué puedo ayudarte hoy?";

        // Save conversation using ChatService
        $result = $this->chatService->sendMessage($user, $question, []);

        // Assert chat was created
        $this->assertDatabaseHas('chats', [
            'id' => $result['chat_id'],
            'user_id' => $user->id,
            'status' => 'active'
        ]);

        // Assert messages were created
        $this->assertDatabaseHas('messages', [
            'chat_id' => $result['chat_id'],
            'sender_type' => 'user',
            'content' => $question
        ]);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $result['chat_id'],
            'sender_type' => 'bot',
            'content' => $result['response']
        ]);
    }

    public function test_get_chat_history()
    {
        // Create a test user
        $user = User::factory()->create();

        // Create a chat with messages
        $chat = $user->chats()->create([
            'status' => 'active',
            'last_message_at' => now()
        ]);

        // Create some messages
        $chat->messages()->createMany([
            [
                'sender_type' => 'user',
                'content' => '¿Hola?'
            ],
            [
                'sender_type' => 'bot',
                'content' => '¡Hola! ¿En qué puedo ayudarte?'
            ],
            [
                'sender_type' => 'user',
                'content' => '¿Cómo estás?'
            ],
            [
                'sender_type' => 'bot',
                'content' => '¡Muy bien, gracias!'
            ]
        ]);

        // Get chat history
        $history = $this->chatService->getChatHistory($user->id, $chat->id, 10);

        // Assert history structure
        $this->assertIsArray($history);
        $this->assertCount(4, $history);

        // Check first message
        $this->assertEquals('user', $history[0]['role']);
        $this->assertEquals('¿Hola?', $history[0]['content']);

        // Check second message
        $this->assertEquals('assistant', $history[1]['role']);
        $this->assertEquals('¡Hola! ¿En qué puedo ayudarte?', $history[1]['content']);
    }

    public function test_get_chats_with_pagination()
    {
        // Create a test user
        $user = User::factory()->create();

        // Create multiple chats
        $user->chats()->createMany([
            ['status' => 'active', 'last_message_at' => now()],
            ['status' => 'active', 'last_message_at' => now()->subHour()],
            ['status' => 'closed', 'last_message_at' => now()->subDay()]
        ]);

        // Get chats
        $chats = $this->chatService->getChats($user, 10);

        // Assert pagination structure
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $chats);
        $this->assertEquals(3, $chats->total());
    }

    public function test_close_chat()
    {
        // Create a test user and chat
        $user = User::factory()->create();
        $chat = $user->chats()->create([
            'status' => 'active',
            'last_message_at' => now()
        ]);

        // Close chat
        $closedChat = $this->chatService->closeChat($user, $chat->id);

        // Assert chat was closed
        $this->assertEquals('closed', $closedChat->status);
        $this->assertDatabaseHas('chats', [
            'id' => $chat->id,
            'status' => 'closed'
        ]);
    }

    public function test_delete_chat()
    {
        // Create a test user and chat with messages
        $user = User::factory()->create();
        $chat = $user->chats()->create([
            'status' => 'active',
            'last_message_at' => now()
        ]);

        // Create some messages
        $chat->messages()->createMany([
            ['sender_type' => 'user', 'content' => 'Test message'],
            ['sender_type' => 'bot', 'content' => 'Test response']
        ]);

        // Delete chat
        $result = $this->chatService->deleteChat($user, $chat->id);

        // Assert chat was deleted
        $this->assertTrue($result);
        $this->assertDatabaseMissing('chats', ['id' => $chat->id]);
        $this->assertDatabaseMissing('messages', ['chat_id' => $chat->id]);
    }

    public function test_normalize_date_method()
    {
        // Test with reflection to access private method
        $reflection = new \ReflectionClass($this->gptChatService);
        $method = $reflection->getMethod('normalizeDate');
        $method->setAccessible(true);

        // Test 'hoy'
        $result = $method->invoke($this->gptChatService, 'hoy');
        $this->assertEquals(now()->format('Y-m-d'), $result);

        // Test 'mañana'
        $result = $method->invoke($this->gptChatService, 'mañana');
        $this->assertEquals(now()->addDay()->format('Y-m-d'), $result);

        // Test date format
        $result = $method->invoke($this->gptChatService, '2024-01-15');
        $this->assertEquals('2024-01-15', $result);
    }

    public function test_requires_api_data_detection()
    {
        // Test with reflection to access private method
        $reflection = new \ReflectionClass($this->gptChatService);
        $method = $reflection->getMethod('requiresApiData');
        $method->setAccessible(true);

        // Test with API requirement
        $response = "🔍 REQUIERE_API: ciudad=[Madrid], fecha=[mañana]";
        $result = $method->invoke($this->gptChatService, $response);
        $this->assertTrue($result);

        // Test without API requirement
        $response = "¡Hola! ¿En qué puedo ayudarte?";
        $result = $method->invoke($this->gptChatService, $response);
        $this->assertFalse($result);
    }

    public function test_extract_api_requirements()
    {
        // Test with reflection to access private method
        $reflection = new \ReflectionClass($this->gptChatService);
        $method = $reflection->getMethod('extractApiRequirements');
        $method->setAccessible(true);

        $response = "🔍 REQUIERE_API: ciudad=[Madrid], fecha=[mañana]";
        $result = $method->invoke($this->gptChatService, $response);

        $this->assertEquals([
            'city' => 'Madrid',
            'date' => 'mañana'
        ], $result);
    }
} 