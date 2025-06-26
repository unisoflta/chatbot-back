<?php

namespace App\Domains\Messages\Models;

use App\Domains\Chat\Models\Chat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'chat_id',
        'sender_type',
        'content',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Valid sender types
     */
    public const SENDER_TYPE_USER = 'user';
    public const SENDER_TYPE_BOT = 'bot';

    /**
     * Get the chat that owns the message.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Check if the message is from a user.
     */
    public function isFromUser(): bool
    {
        return $this->sender_type === self::SENDER_TYPE_USER;
    }

    /**
     * Check if the message is from a bot.
     */
    public function isFromBot(): bool
    {
        return $this->sender_type === self::SENDER_TYPE_BOT;
    }
} 