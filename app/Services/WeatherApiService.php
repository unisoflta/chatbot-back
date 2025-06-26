<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WeatherApiService
{
    private const WEATHER_API_URL = 'https://api.open-meteo.com/v1/forecast';
    private PendingRequest $httpClient;

    public function __construct()
    {
        $this->httpClient = Http::timeout(30);
        Log::info('🌤️ Weather API Service initialized successfully');
    }

    /**
     * Get weather forecast for a city and date
     *
     * @param string $city
     * @param string $date
     * @return array
     * @throws Exception
     */
    public function getForecast(string $city, string $date): array
    {
        Log::info('🌤️ Weather forecast request started', [
            'city' => $city,
            'date' => $date
        ]);

        try {
            // First, get coordinates for the city
            Log::info('🌍 Getting city coordinates', [
                'city' => $city
            ]);
            $coordinates = $this->getCityCoordinates($city);
            Log::info('✅ City coordinates retrieved', [
                'city' => $coordinates['name'],
                'country' => $coordinates['country'],
                'latitude' => $coordinates['latitude'],
                'longitude' => $coordinates['longitude']
            ]);

            // Then get weather data
            Log::info('🌤️ Getting weather data for coordinates', [
                'latitude' => $coordinates['latitude'],
                'longitude' => $coordinates['longitude'],
                'date' => $date
            ]);
            $weatherData = $this->getWeatherData($coordinates, $date);
            Log::info('✅ Weather data retrieved successfully', [
                'city' => $weatherData['city'],
                'temperature' => $weatherData['temperature'],
                'weather_code' => $weatherData['weathercode']
            ]);

            return $weatherData;

        } catch (Exception $e) {
            Log::error('❌ Weather API Error', [
                'error' => $e->getMessage(),
                'city' => $city,
                'date' => $date,
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception("Error getting weather forecast for {$city}: " . $e->getMessage());
        }
    }

    /**
     * Get city coordinates using geocoding API
     *
     * @param string $city
     * @return array
     * @throws Exception
     */
    private function getCityCoordinates(string $city): array
    {
        Log::info('🌍 Geocoding API request started', [
            'city' => $city,
            'url' => 'https://geocoding-api.open-meteo.com/v1/search'
        ]);

        $response = $this->httpClient->get('https://geocoding-api.open-meteo.com/v1/search', [
            'name' => $city,
            'count' => 1,
            'language' => 'es',
            'format' => 'json'
        ]);

        if (!$response->successful()) {
            Log::error('❌ Geocoding API request failed', [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'city' => $city
            ]);
            throw new Exception('Geocoding API error: ' . $response->body());
        }

        Log::info('✅ Geocoding API request successful', [
            'status_code' => $response->status(),
            'response_size' => strlen($response->body())
        ]);

        $data = $response->json();

        if (empty($data['results'])) {
            Log::error('❌ City not found in geocoding API', [
                'city' => $city,
                'response_data' => $data
            ]);
            throw new Exception("City '{$city}' not found");
        }

        $result = $data['results'][0];
        Log::info('🌍 City coordinates found', [
            'city' => $result['name'],
            'country' => $result['country'],
            'latitude' => $result['latitude'],
            'longitude' => $result['longitude']
        ]);

        return [
            'latitude' => $result['latitude'],
            'longitude' => $result['longitude'],
            'name' => $result['name'],
            'country' => $result['country']
        ];
    }

    /**
     * Get weather data for coordinates and date
     *
     * @param array $coordinates
     * @param string $date
     * @return array
     * @throws Exception
     */
    private function getWeatherData(array $coordinates, string $date): array
    {
        Log::info('🌤️ Weather API request started', [
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'date' => $date,
            'url' => self::WEATHER_API_URL
        ]);

        $response = $this->httpClient->get(self::WEATHER_API_URL, [
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'daily' => 'temperature_2m_max,temperature_2m_min,weathercode',
            'timezone' => 'auto',
            'start_date' => $date,
            'end_date' => $date
        ]);

        if (!$response->successful()) {
            Log::error('❌ Weather API request failed', [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'latitude' => $coordinates['latitude'],
                'longitude' => $coordinates['longitude'],
                'date' => $date
            ]);
            throw new Exception('Weather API error: ' . $response->body());
        }

        Log::info('✅ Weather API request successful', [
            'status_code' => $response->status(),
            'response_size' => strlen($response->body())
        ]);

        $data = $response->json();

        if (empty($data['daily']['time']) || empty($data['daily']['time'][0])) {
            Log::error('❌ No weather data available for date', [
                'date' => $date,
                'daily_time_count' => count($data['daily']['time'] ?? []),
                'response_data' => $data
            ]);
            throw new Exception("No weather data available for date: {$date}");
        }

        // Get the first (and only) day's data
        $index = 0;

        $weatherData = [
            'city' => $coordinates['name'],
            'country' => $coordinates['country'],
            'date' => $data['daily']['time'][$index],
            'temperature_max' => $data['daily']['temperature_2m_max'][$index],
            'temperature_min' => $data['daily']['temperature_2m_min'][$index],
            'temperature' => round(($data['daily']['temperature_2m_max'][$index] + $data['daily']['temperature_2m_min'][$index]) / 2, 1),
            'weathercode' => $data['daily']['weathercode'][$index],
            'coordinates' => [
                'latitude' => $coordinates['latitude'],
                'longitude' => $coordinates['longitude']
            ]
        ];

        Log::info('🌤️ Weather data processed successfully', [
            'city' => $weatherData['city'],
            'date' => $weatherData['date'],
            'temperature_max' => $weatherData['temperature_max'],
            'temperature_min' => $weatherData['temperature_min'],
            'temperature_avg' => $weatherData['temperature'],
            'weather_code' => $weatherData['weathercode']
        ]);

        return $weatherData;
    }

    /**
     * Get current weather for a city
     *
     * @param string $city
     * @return array
     * @throws Exception
     */
    public function getCurrentWeather(string $city): array
    {
        Log::info('🌤️ Current weather request started', [
            'city' => $city,
            'date' => now()->format('Y-m-d')
        ]);

        $result = $this->getForecast($city, now()->format('Y-m-d'));
        
        Log::info('✅ Current weather retrieved successfully', [
            'city' => $result['city'],
            'temperature' => $result['temperature']
        ]);

        return $result;
    }

    /**
     * Get weather forecast for next 7 days
     *
     * @param string $city
     * @return array
     * @throws Exception
     */
    public function getWeeklyForecast(string $city): array
    {
        Log::info('🌤️ Weekly forecast request started', [
            'city' => $city,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(6)->format('Y-m-d')
        ]);

        try {
            Log::info('🌍 Getting city coordinates for weekly forecast', [
                'city' => $city
            ]);
            $coordinates = $this->getCityCoordinates($city);
            Log::info('✅ City coordinates retrieved for weekly forecast', [
                'city' => $coordinates['name'],
                'country' => $coordinates['country']
            ]);
            
            $startDate = now()->format('Y-m-d');
            $endDate = now()->addDays(6)->format('Y-m-d');

            Log::info('🌤️ Weekly weather API request started', [
                'latitude' => $coordinates['latitude'],
                'longitude' => $coordinates['longitude'],
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            $response = $this->httpClient->get(self::WEATHER_API_URL, [
                'latitude' => $coordinates['latitude'],
                'longitude' => $coordinates['longitude'],
                'daily' => 'temperature_2m_max,temperature_2m_min,weathercode',
                'timezone' => 'auto',
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            if (!$response->successful()) {
                Log::error('❌ Weekly weather API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'city' => $city
                ]);
                throw new Exception('Weather API error: ' . $response->body());
            }

            Log::info('✅ Weekly weather API request successful', [
                'status_code' => $response->status(),
                'response_size' => strlen($response->body())
            ]);

            $data = $response->json();

            $forecast = [];
            for ($i = 0; $i < count($data['daily']['time']); $i++) {
                $forecast[] = [
                    'date' => $data['daily']['time'][$i],
                    'temperature_max' => $data['daily']['temperature_2m_max'][$i],
                    'temperature_min' => $data['daily']['temperature_2m_min'][$i],
                    'temperature' => round(($data['daily']['temperature_2m_max'][$i] + $data['daily']['temperature_2m_min'][$i]) / 2, 1),
                    'weathercode' => $data['daily']['weathercode'][$i]
                ];
            }

            $result = [
                'city' => $coordinates['name'],
                'country' => $coordinates['country'],
                'forecast' => $forecast
            ];

            Log::info('✅ Weekly forecast processed successfully', [
                'city' => $result['city'],
                'forecast_days' => count($forecast),
                'date_range' => $forecast[0]['date'] . ' to ' . $forecast[count($forecast)-1]['date']
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('❌ Weekly Weather API Error', [
                'error' => $e->getMessage(),
                'city' => $city,
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception("Error getting weekly forecast for {$city}: " . $e->getMessage());
        }
    }
} 