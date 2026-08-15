<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'api_year',
        'starts_on',
        'ends_on',
        'is_current',
    ];

    protected $casts = [
        'api_year' => 'integer',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_current' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Season $season) {
            if (empty($season->slug)) {
                $season->slug = Str::slug($season->name);
            }
        });

        static::saving(function (Season $season) {
            if ($season->is_current) {
                static::where('id', '!=', $season->id ?? 0)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }
        });
    }

    public function gameWeeks(): HasMany
    {
        return $this->hasMany(GameWeek::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withTimestamps();
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public static function current(): ?self
    {
        return static::query()->current()->first();
    }

    /**
     * Resolve a season from a slug, id, or api_year. Falls back to current.
     */
    public static function resolveFromRequest(?string $value = null): ?self
    {
        if ($value === null || $value === '') {
            return static::current();
        }

        $season = static::query()
            ->where(function ($query) use ($value) {
                $query->where('slug', $value)
                    ->orWhere('name', $value);

                // Only compare numeric columns when the value is a plain integer
                // (Postgres rejects "2025-26" against bigint id/api_year).
                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value)
                        ->orWhere('api_year', (int) $value);
                }
            })
            ->first();

        return $season ?? static::current();
    }

    /**
     * Display label like "2025/26".
     */
    public function displayName(): string
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $this->name, $matches)) {
            return $matches[1] . '/' . $matches[2];
        }

        return $this->name;
    }

    public static function nameFromApiYear(int $apiYear): string
    {
        $end = substr((string) ($apiYear + 1), -2);

        return "{$apiYear}-{$end}";
    }

    /**
     * Find or create a season row for an API year. Optionally mark it current.
     */
    public static function ensureForApiYear(int $apiYear, bool $makeCurrent = false): self
    {
        $name = static::nameFromApiYear($apiYear);

        $season = static::updateOrCreate(
            ['api_year' => $apiYear],
            [
                'name' => $name,
                'slug' => $name,
            ]
        );

        if ($makeCurrent && !$season->is_current) {
            static::where('id', '!=', $season->id)->update(['is_current' => false]);
            $season->update(['is_current' => true]);
        }

        return $season->fresh();
    }
}
