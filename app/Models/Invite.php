<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invite extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'email',
        'note',
        'created_by',
        'expires_at',
        'used_at',
        'used_by',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Invite $invite) {
            if (empty($invite->token)) {
                $invite->token = Str::random(48);
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function isUsable(): bool
    {
        return $this->used_at === null
            && $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function url(): string
    {
        return route('register.invite', $this->token, absolute: true);
    }

    public function status(): string
    {
        if ($this->used_at) {
            return 'used';
        }

        if ($this->revoked_at) {
            return 'revoked';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'expired';
        }

        return 'active';
    }
}
