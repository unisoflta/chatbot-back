<?php

namespace App\Domains\Messages\DTOs;

use App\Domains\Messages\Models\Message;
use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="MessageDTO",
 *     title="Mensaje",
 *     description="DTO que representa un mensaje dentro de un chat.",
 *     required={"chat_id", "sender_type", "content", "created_at"},
 *     @OA\Property(property="id", type="integer", example=101, description="ID del mensaje (solo lectura)"),
 *     @OA\Property(property="chat_id", type="integer", example=10, description="ID del chat al que pertenece el mensaje"),
 *     @OA\Property(property="sender_type", type="string", example="user", description="Tipo de remitente: user o bot"),
 *     @OA\Property(property="content", type="string", example="Hola, ¿cómo estás?", description="Contenido del mensaje"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-25T12:34:56Z", description="Fecha y hora de creación del mensaje")
 * )
 */
class MessageDTO
{
    public const SENDER_TYPE_USER = 'user';
    public const SENDER_TYPE_BOT = 'bot';

    /**
     * @param int|null $id
     * @param int $chat_id
     * @param string $sender_type
     * @param string $content
     * @param Carbon $created_at
     */
    public function __construct(
        public readonly int $chat_id,
        public readonly string $sender_type,
        public readonly string $content,
        public readonly Carbon $created_at,
        public readonly ?int $id = null
    ) {}

    /**
     * Crear MessageDTO desde array (sin ID)
     */
    public static function fromArray(array $data): MessageDTO
    {
        return new self(
            chat_id: $data['chat_id'] ?? 0,
            sender_type: $data['sender_type'] ?? self::SENDER_TYPE_USER,
            content: $data['content'] ?? '',
            created_at: isset($data['created_at'])
                ? Carbon::parse($data['created_at'])
                : now()
        );
    }

    /**
     * Crear MessageDTO desde modelo (con ID)
     */
    public static function fromModel(Message $message): MessageDTO
    {
        return new self(
            id: $message->id,
            chat_id: $message->chat_id,
            sender_type: $message->sender_type,
            content: $message->content,
            created_at: $message->created_at
        );
    }

    /**
     * Convertir a array excluyendo valores null
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'chat_id' => $this->chat_id,
            'sender_type' => $this->sender_type,
            'content' => $this->content,
            'created_at' => $this->created_at->toISOString(),
        ], fn($value) => $value !== null);
    }

    /**
     * Datos para persistencia
     */
    public function getFillableData(): array
    {
        return [
            'chat_id' => $this->chat_id,
            'sender_type' => $this->sender_type,
            'content' => $this->content,
            'created_at' => $this->created_at,
        ];
    }

    public function isFromUser(): bool
    {
        return $this->sender_type === self::SENDER_TYPE_USER;
    }

    public function isFromBot(): bool
    {
        return $this->sender_type === self::SENDER_TYPE_BOT;
    }

    public function getFormattedCreatedAt(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->created_at->format($format);
    }

    public function getRelativeTime(): string
    {
        return $this->created_at->diffForHumans();
    }
}
