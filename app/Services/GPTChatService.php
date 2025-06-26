<?php

namespace App\Services;

use App\Domains\Chat\Models\Chat;
use App\Domains\Messages\Models\Message;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GPTChatService
{
    private const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';
    private const GPT_MODEL = 'gpt-3.5-turbo';
    private const MAX_TOKENS = 1000;
    private const TEMPERATURE = 0.7;

    private PendingRequest $httpClient;
    private WeatherApiService $weatherService;

    public function __construct(WeatherApiService $weatherService)
    {
        $this->weatherService = $weatherService;
        $this->httpClient = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            'Content-Type' => 'application/json',
        ])->timeout(30);
        
        Log::info('🤖 GPT Service initialized successfully');
    }

    /**
     * Handle chat conversation with GPT
     *
     * @param string $question
     * @param array $history
     * @return string
     * @throws Exception
     */
    public function handleChat(string $question, array $history = []): string
    {
        Log::info('🤖 GPT Chat started', [
            'question_length' => strlen($question),
            'history_count' => count($history),
            'question_preview' => substr($question, 0, 100) . (strlen($question) > 100 ? '...' : '')
        ]);

        try {
            // First, send the question to GPT
            Log::info('🤖 Sending initial request to GPT API');
            $initialResponse = $this->sendToGPT($question, $history);
            Log::info('🤖 Initial GPT response received', [
                'response_length' => strlen($initialResponse),
                'response_preview' => substr($initialResponse, 0, 100) . (strlen($initialResponse) > 100 ? '...' : '')
            ]);

            // Check if GPT requires external API data
            if ($this->requiresApiData($initialResponse)) {
                Log::info('🔍 GPT requires external API data - extracting requirements');
                $apiData = $this->extractApiRequirements($initialResponse);
                Log::info('🔍 API requirements extracted', [
                    'city' => $apiData['city'],
                    'date' => $apiData['date']
                ]);
                
                Log::info('🌤️ Requesting weather data from external API');
                $weatherData = $this->getWeatherData($apiData);
                Log::info('🌤️ Weather data received successfully', [
                    'city' => $weatherData['city'] ?? 'unknown',
                    'temperature' => $weatherData['temperature'] ?? 'unknown'
                ]);
                
                // Send follow-up with weather data
                Log::info('🤖 Sending follow-up request to GPT with weather data');
                $finalResponse = $this->sendFollowUpWithData($question, $history, $weatherData, $apiData);
                Log::info('🤖 Final GPT response received with weather data', [
                    'response_length' => strlen($finalResponse),
                    'response_preview' => substr($finalResponse, 0, 100) . (strlen($finalResponse) > 100 ? '...' : '')
                ]);
                
                return $finalResponse;
            }

            Log::info('🤖 GPT response completed without external API requirements');
            return $initialResponse;

        } catch (Exception $e) {
            Log::error('❌ GPT Chat Service Error', [
                'error' => $e->getMessage(),
                'question' => $question,
                'history_count' => count($history),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception('Error processing chat request: ' . $e->getMessage());
        }
    }

    /**
     * Send message to GPT API
     *
     * @param string $question
     * @param array $history
     * @return string
     * @throws Exception
     */
    private function sendToGPT(string $question, array $history = []): string
    {
        Log::info('🤖 Preparing GPT API request', [
            'model' => self::GPT_MODEL,
            'max_tokens' => self::MAX_TOKENS,
            'temperature' => self::TEMPERATURE
        ]);

        $messages = $this->buildMessages($question, $history);
        Log::info('🤖 Messages built for GPT', [
            'total_messages' => count($messages),
            'system_message_length' => strlen($messages[0]['content'])
        ]);

        $response = $this->httpClient->post(self::OPENAI_API_URL, [
            'model' => self::GPT_MODEL,
            'messages' => $messages,
            'max_tokens' => self::MAX_TOKENS,
            'temperature' => self::TEMPERATURE,
        ]);

        if (!$response->successful()) {
            Log::error('❌ GPT API request failed', [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'url' => self::OPENAI_API_URL
            ]);
            throw new Exception('OpenAI API error: ' . $response->body());
        }

        Log::info('✅ GPT API request successful', [
            'status_code' => $response->status(),
            'response_size' => strlen($response->body())
        ]);

        $data = $response->json();
        
        if (!isset($data['choices'][0]['message']['content'])) {
            Log::error('❌ Invalid GPT API response format', [
                'response_keys' => array_keys($data),
                'choices_count' => count($data['choices'] ?? []),
                'response_data' => $data
            ]);
            throw new Exception('Invalid response format from OpenAI API');
        }

        $content = trim($data['choices'][0]['message']['content']);
        Log::info('🤖 GPT response content extracted', [
            'content_length' => strlen($content),
            'usage_tokens' => $data['usage']['total_tokens'] ?? 'unknown'
        ]);

        return $content;
    }

    /**
     * Build messages array for GPT API
     *
     * @param string $question
     * @param array $history
     * @return array
     */
    private function buildMessages(string $question, array $history = []): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt()
            ]
        ];

        // Add conversation history
        foreach ($history as $message) {
            $messages[] = [
                'role' => $message['role'] ?? 'user',
                'content' => $message['content'] ?? ''
            ];
        }

        // Add current question
        $messages[] = [
            'role' => 'user',
            'content' => $question
        ];

        Log::info('🤖 Messages array built', [
            'system_message' => true,
            'history_messages' => count($history),
            'current_question' => true,
            'total_messages' => count($messages)
        ]);

        return $messages;
    }

    /**
     * Get system prompt for GPT
     *
     * @return string
     */
    private function getSystemPrompt(): string
    {
        return "Eres un experto meteorólogo con acceso a datos externos. " .
               "Responde en español y usa markdown legible. " .
               "Cuando necesites datos, responde en el siguiente formato: " .
               "🔍 REQUIERE_API: ciudad=[...], fecha=[...]. " .
               "No inventes datos si no los tienes. Espera a que el sistema te los proporcione antes de generar la respuesta final.";
    }

    /**
     * Check if GPT response requires API data
     *
     * @param string $response
     * @return bool
     */
    private function requiresApiData(string $response): bool
    {
        $requires = str_contains($response, 'REQUIERE_API:');
        Log::info('🔍 Checking if GPT response requires API data', [
            'requires_api' => $requires,
            'response_contains_keyword' => str_contains($response, 'REQUIERE_API:')
        ]);
        return $requires;
    }

    /**
     * Extract API requirements from GPT response
     *
     * @param string $response
     * @return array
     * @throws Exception
     */
    private function extractApiRequirements(string $response): array
    {
        Log::info('🔍 Extracting API requirements from GPT response');
        
        // Extract city and date from the format: 🔍 REQUIERE_API: ciudad=[...], fecha=[...]
        if (!preg_match('/🔍 REQUIERE_API: ciudad=\[([^\]]+)\], fecha=\[([^\]]+)\]/', $response, $matches)) {
            Log::error('❌ Invalid API requirement format in GPT response', [
                'response_preview' => substr($response, 0, 200),
                'pattern_not_found' => true
            ]);
            throw new Exception('Invalid API requirement format in GPT response');
        }

        $apiData = [
            'city' => trim($matches[1]),
            'date' => trim($matches[2])
        ];

        Log::info('✅ API requirements extracted successfully', [
            'city' => $apiData['city'],
            'date' => $apiData['date']
        ]);

        return $apiData;
    }

    /**
     * Get weather data from external API
     *
     * @param array $apiData
     * @return array
     * @throws Exception
     */
    private function getWeatherData(array $apiData): array
    {
        $city = $apiData['city'];
        $date = $apiData['date'];

        Log::info('🌤️ Requesting weather data', [
            'city' => $city,
            'date' => $date
        ]);

        // Normalize date
        $normalizedDate = $this->normalizeDate($date);
        Log::info('📅 Date normalized', [
            'original_date' => $date,
            'normalized_date' => $normalizedDate
        ]);

        try {
            $weatherData = $this->weatherService->getForecast($city, $normalizedDate);
            Log::info('✅ Weather data retrieved successfully', [
                'city' => $weatherData['city'] ?? 'unknown',
                'temperature' => $weatherData['temperature'] ?? 'unknown',
                'weather_code' => $weatherData['weathercode'] ?? 'unknown'
            ]);
            return $weatherData;
        } catch (Exception $e) {
            Log::error('❌ Weather API Error in GPT service', [
                'city' => $city,
                'date' => $date,
                'normalized_date' => $normalizedDate,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Normalize date input
     *
     * @param string $date
     * @return string
     */
    private function normalizeDate(string $date): string
    {
        Log::info('📅 Normalizing date input', [
            'original_date' => $date
        ]);

        $date = strtolower(trim($date));

        if ($date === 'hoy') {
            $normalized = now()->format('Y-m-d');
            Log::info('📅 Date normalized: "hoy" -> today', [
                'normalized_date' => $normalized
            ]);
            return $normalized;
        }

        if ($date === 'mañana') {
            $normalized = now()->addDay()->format('Y-m-d');
            Log::info('📅 Date normalized: "mañana" -> tomorrow', [
                'normalized_date' => $normalized
            ]);
            return $normalized;
        }

        // If it's already a date format, return as is
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            Log::info('📅 Date already in correct format', [
                'normalized_date' => $date
            ]);
            return $date;
        }

        // Try to parse other date formats
        try {
            $normalized = \Carbon\Carbon::parse($date)->format('Y-m-d');
            Log::info('📅 Date parsed successfully', [
                'original_date' => $date,
                'normalized_date' => $normalized
            ]);
            return $normalized;
        } catch (Exception $e) {
            // Default to tomorrow if parsing fails
            $normalized = now()->addDay()->format('Y-m-d');
            Log::warning('⚠️ Date parsing failed, defaulting to tomorrow', [
                'original_date' => $date,
                'normalized_date' => $normalized,
                'error' => $e->getMessage()
            ]);
            return $normalized;
        }
    }

    /**
     * Send follow-up message to GPT with weather data
     *
     * @param string $originalQuestion
     * @param array $history
     * @param array $weatherData
     * @param array $apiData
     * @return string
     * @throws Exception
     */
    private function sendFollowUpWithData(string $originalQuestion, array $history, array $weatherData, array $apiData): string
    {
        Log::info('🤖 Preparing follow-up request with weather data', [
            'city' => $apiData['city'],
            'date' => $apiData['date'],
            'weather_data_keys' => array_keys($weatherData)
        ]);

        $weatherContext = $this->formatWeatherData($weatherData, $apiData);
        Log::info('🌤️ Weather context formatted', [
            'context_length' => strlen($weatherContext),
            'context_preview' => substr($weatherContext, 0, 100)
        ]);

        $messages = $this->buildMessages($originalQuestion, $history);

        // Add the weather data as a system message
        $weatherMessage = "Datos del clima para {$apiData['city']} {$apiData['date']}: {$weatherContext}";
        $messages[] = [
            'role' => 'system',
            'content' => $weatherMessage
        ];

        Log::info('🤖 Follow-up messages prepared', [
            'total_messages' => count($messages),
            'weather_message_length' => strlen($weatherMessage)
        ]);

        $response = $this->httpClient->post(self::OPENAI_API_URL, [
            'model' => self::GPT_MODEL,
            'messages' => $messages,
            'max_tokens' => self::MAX_TOKENS,
            'temperature' => self::TEMPERATURE,
        ]);

        if (!$response->successful()) {
            Log::error('❌ GPT follow-up API request failed', [
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);
            throw new Exception('OpenAI API error in follow-up: ' . $response->body());
        }

        Log::info('✅ GPT follow-up API request successful', [
            'status_code' => $response->status()
        ]);

        $data = $response->json();
        
        if (!isset($data['choices'][0]['message']['content'])) {
            Log::error('❌ Invalid GPT follow-up response format', [
                'response_keys' => array_keys($data),
                'choices_count' => count($data['choices'] ?? [])
            ]);
            throw new Exception('Invalid response format from OpenAI API in follow-up');
        }

        $content = trim($data['choices'][0]['message']['content']);
        Log::info('🤖 GPT follow-up response content extracted', [
            'content_length' => strlen($content),
            'usage_tokens' => $data['usage']['total_tokens'] ?? 'unknown'
        ]);

        return $content;
    }

    /**
     * Format weather data for GPT context
     *
     * @param array $weatherData
     * @param array $apiData
     * @return string
     */
    private function formatWeatherData(array $weatherData, array $apiData): string
    {
        Log::info('🌤️ Formatting weather data for GPT context', [
            'city' => $apiData['city'],
            'date' => $apiData['date'],
            'weather_data_keys' => array_keys($weatherData)
        ]);

        $city = $apiData['city'];
        $date = $apiData['date'];

        // Extract relevant weather information
        $temperature = $weatherData['temperature'] ?? 'N/A';
        $weatherCode = $weatherData['weathercode'] ?? 'N/A';
        $description = $this->getWeatherDescription($weatherCode);

        $formatted = "temperatura {$temperature}°C, weathercode {$weatherCode} ({$description})";
        
        Log::info('🌤️ Weather data formatted successfully', [
            'formatted_length' => strlen($formatted),
            'temperature' => $temperature,
            'weather_code' => $weatherCode,
            'description' => $description
        ]);

        return $formatted;
    }

    /**
     * Get weather description from weather code
     *
     * @param int $weatherCode
     * @return string
     */
    private function getWeatherDescription(int $weatherCode): string
    {
        Log::info('🌤️ Getting weather description for code', [
            'weather_code' => $weatherCode
        ]);

        $descriptions = [
            0 => 'cielo despejado',
            1 => 'mayormente despejado',
            2 => 'parcialmente nublado',
            3 => 'nublado',
            45 => 'niebla',
            48 => 'niebla con escarcha',
            51 => 'llovizna ligera',
            53 => 'llovizna moderada',
            55 => 'llovizna intensa',
            56 => 'llovizna helada ligera',
            57 => 'llovizna helada intensa',
            61 => 'lluvia leve',
            63 => 'lluvia moderada',
            65 => 'lluvia intensa',
            66 => 'lluvia helada leve',
            67 => 'lluvia helada intensa',
            71 => 'nieve leve',
            73 => 'nieve moderada',
            75 => 'nieve intensa',
            77 => 'granizo',
            80 => 'lluvia ligera',
            81 => 'lluvia moderada',
            82 => 'lluvia intensa',
            85 => 'nieve ligera',
            86 => 'nieve intensa',
            95 => 'tormenta',
            96 => 'tormenta con granizo',
            99 => 'tormenta intensa con granizo',
        ];

        $description = $descriptions[$weatherCode] ?? 'condición desconocida';
        
        Log::info('🌤️ Weather description retrieved', [
            'weather_code' => $weatherCode,
            'description' => $description,
            'is_known_code' => isset($descriptions[$weatherCode])
        ]);

        return $description;
    }

} 