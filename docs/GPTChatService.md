# Chat Service Documentation

## Overview

The Chat system consists of two main services:

1. **`GPTChatService`** - Handles OpenAI integration and weather API delegation
2. **`ChatService`** - Manages chat operations, database storage, and business logic

The system follows a domain-driven design approach with proper separation of concerns.

## Architecture

```
ChatController → ChatService → GPTChatService → WeatherApiService
                ↓
            Database (Chat, Message models)
```

## Services

### GPTChatService

Located in `app/Services/GPTChatService.php`

**Responsibilities:**
- OpenAI API integration
- Weather API delegation
- Natural language processing
- API requirement detection and extraction

**Key Methods:**
- `handleChat(string $question, array $history = []): string`

### ChatService

Located in `app/Domains/Chat/Services/ChatService.php`

**Responsibilities:**
- Chat management operations
- Database storage and retrieval
- User chat history management
- Chat lifecycle management

**Key Methods:**
- `sendMessage(User $user, string $message, array $history = [], ?int $chatId = null): array`
- `getChatHistory(int $userId, ?int $chatId = null, int $limit = 20): array`
- `getChats(User $user, int $perPage = 15): LengthAwarePaginator`
- `getChatMessages(User $user, int $chatId, int $perPage = 50): array`
- `closeChat(User $user, int $chatId): Chat`
- `deleteChat(User $user, int $chatId): bool`

## Configuration

### Environment Variables

Add the following to your `.env` file:

```env
OPENAI_API_KEY=your_openai_api_key_here
```

### Service Configuration

The service is configured in `config/services.php`:

```php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
],
```

## Usage

### Using ChatService (Recommended)

```php
use App\Domains\Chat\Services\ChatService;
use App\Models\User;

// Inject the service
$chatService = app(ChatService::class);

// Send a message
$user = User::find(1);
$result = $chatService->sendMessage($user, "¿Cómo estará el clima en Madrid mañana?");

// Get chat history
$history = $chatService->getChatHistory($user->id, null, 20);

// Get all chats
$chats = $chatService->getChats($user, 15);
```

### Using GPTChatService Directly

```php
use App\Services\GPTChatService;
use App\Services\WeatherApiService;

// Inject the services
$weatherService = new WeatherApiService();
$gptChatService = new GPTChatService($weatherService);

// Handle a chat message
$response = $gptChatService->handleChat("¿Cómo estará el clima en Madrid mañana?");
```

## API Endpoints

### Send Message
```
POST /api/chat/send
```

**Request Body:**
```json
{
    "message": "¿Cómo estará el clima en Madrid mañana?",
    "history": [
        {"role": "user", "content": "Hola"},
        {"role": "assistant", "content": "¡Hola! ¿En qué puedo ayudarte?"}
    ],
    "chat_id": 1
}
```

**Response:**
```json
{
    "success": true,
    "message": "Message processed successfully",
    "data": {
        "response": "☔ Clima en Madrid mañana:\n- Temperatura: 18°C\n- Lluvia ligera\n¡Sí, lleva paraguas!",
        "chat_id": 1,
        "timestamp": "2024-01-15T10:30:00Z"
    }
}
```

### Get Chat History
```
GET /api/chat/history?limit=20&chat_id=1
```

### Get All Chats
```
GET /api/chat/chats?per_page=15
```

### Get Chat Messages
```
GET /api/chat/chats/{chatId}/messages?per_page=50
```

### Close Chat
```
PATCH /api/chat/chats/{chatId}/close
```

### Delete Chat
```
DELETE /api/chat/chats/{chatId}
```

## How It Works

### 1. Message Processing Flow

1. **Controller** receives request and validates input
2. **ChatService** handles the business logic:
   - Gets or creates chat session
   - Retrieves chat history
   - Calls GPTChatService for AI processing
   - Saves conversation to database
3. **GPTChatService** processes with OpenAI:
   - Sends message to GPT with system prompt
   - Detects if external API data is needed
   - Calls WeatherApiService if required
   - Returns final response
4. **Response** is returned to client

### 2. API Requirement Detection

If GPT determines it needs external data, it responds with:
```
🔍 REQUIERE_API: ciudad=[Madrid], fecha=[mañana]
```
The brackets around the values are recommended but optional. The system will
also accept responses like:
```
🔍 REQUIERE_API: ciudad=Madrid, fecha=hoy
```

The system then:
1. Extracts city and date requirements
2. Calls weather API for real data
3. Re-sends to GPT with context
4. Returns final response

### 3. Database Storage

Conversations are automatically saved using:
- **Chat Model**: Manages chat sessions and status
- **Message Model**: Stores individual messages with sender type

## Weather API Integration

The service uses the Open-Meteo API for weather data:

- **Geocoding**: Converts city names to coordinates
- **Weather Data**: Retrieves temperature, weather codes, and descriptions
- **Date Normalization**: Handles "hoy", "mañana", and specific dates

## Error Handling

Both services include comprehensive error handling:

- **Network Errors**: Timeout and connection issues
- **API Errors**: Invalid responses from OpenAI or weather APIs
- **Validation Errors**: Invalid input data
- **Database Errors**: Storage and retrieval issues

All errors are logged with context information for debugging.

## Testing

Run the tests with:

```bash
# Test GPTChatService
php artisan test tests/Feature/GPTChatServiceTest.php

# Test ChatController
php artisan test tests/Feature/ChatControllerTest.php
```

The tests include:
- Weather request handling
- Database storage
- Chat history retrieval
- API requirement detection
- Controller authentication and validation
- Chat lifecycle management

## Dependencies

- **OpenAI API**: For natural language processing
- **Open-Meteo API**: For weather data (free, no API key required)
- **Laravel HTTP Client**: For API requests
- **Laravel Database**: For conversation storage
- **Laravel Sanctum**: For authentication

## Security Considerations

- API keys are stored in environment variables
- All user input is validated
- Database queries use proper relationships and user ownership
- Error messages don't expose sensitive information
- Authentication required for all chat operations

## Performance Considerations

- HTTP requests have 30-second timeouts
- Chat history is limited to prevent memory issues
- Database queries are optimized with proper indexing
- Weather data is cached by the external API
- Pagination implemented for large datasets

## Domain Structure

```
app/
├── Domains/
│   └── Chat/
│       ├── Models/
│       │   └── Chat.php
│       └── Services/
│           └── ChatService.php
├── Services/
│   ├── GPTChatService.php
│   └── WeatherApiService.php
└── Http/
    └── Controllers/
        └── Api/
            └── ChatController.php
``` 