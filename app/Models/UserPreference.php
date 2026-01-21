<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a preference value for a user
     */
    public static function getValue(int $userId, string $key, $default = null)
    {
        $preference = static::where('user_id', $userId)->where('key', $key)->first();
        return $preference ? $preference->value : $default;
    }

    /**
     * Set a preference value for a user
     */
    public static function setValue(int $userId, string $key, $value): void
    {
        static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get all preferences for a user as key-value array
     */
    public static function getAllForUser(int $userId): array
    {
        return static::where('user_id', $userId)
            ->pluck('value', 'key')
            ->toArray();
    }
}
