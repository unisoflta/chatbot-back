<?php

namespace App\Domains\Chat\DTOs;

use App\Domains\Chat\Models\Chat;
use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="ChatDTO",
 *     title="Chat",
 *     description="DTO que representa un chat entre usuario y bot.",
 *     required={"user_id", "status"},
 *     @OA\Property(property="id", type="integer", example=10, description="ID del chat (solo lectura)"),
 *     @OA\Property(property="user_id", type="integer", example=1, description="ID del usuario asociado al chat"),
 *     @OA\Property(property="status", type="string", example="active", description="Estado del chat: active, closed, etc."),
 *     @OA\Property(property="last_message_at", type="string", format="date-time", example="2024-06-25T12:34:56Z", description="Fecha y hora del último mensaje en el chat")
 * )
 */
class ChatDTO
{
    /**
     * ChatDTO constructor
     *
     * @param int|null $id
     * @param int $user_id
     * @param string $status
     * @param Carbon|null $last_message_at
     */
    public function __construct(
        public readonly int $user_id,
        public readonly string $status,
        public readonly ?Carbon $last_message_at = null,
        public readonly ?int $id = null
    ) {}

    /**
     * Crear ChatDTO desde un array (para crear/actualizar)
     *
     * @param array $data
     * @return ChatDTO
     */
    public static function fromArray(array $data): ChatDTO
    {
        return new self(
            user_id: $data['user_id'] ?? 0,
            status: $data['status'] ?? '',
            last_message_at: isset($data['last_message_at'])
                ? Carbon::parse($data['last_message_at'])
                : null
        );
    }

    /**
     * Crear ChatDTO desde modelo (lectura)
     *
     * @param Chat $chat
     * @return ChatDTO
     */
    public static function fromModel(Chat $chat): ChatDTO
    {
        return new self(
            id: $chat->id,
            user_id: $chat->user_id,
            status: $chat->status,
            last_message_at: $chat->last_message_at
        );
    }

    /**
     * Convertir DTO a array limpio
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'last_message_at' => $this->last_message_at?->toISOString(),
        ], fn($value) => $value !== null);
    }

    /**
     * Extraer campos para persistencia
     *
     * @return array
     */
    public function getFillableData(): array
    {
        return array_filter([
            'user_id' => $this->user_id,
            'status' => $this->status,
            'last_message_at' => $this->last_message_at,
        ], fn($value) => $value !== null);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function hasLastMessage(): bool
    {
        return $this->last_message_at !== null;
    }

    public function getFormattedLastMessageDate(string $format = 'Y-m-d H:i:s'): ?string
    {
        return $this->last_message_at?->format($format);
    }
}
