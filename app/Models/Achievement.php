<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'category',
        'rarity',
        'criteria',
        'points',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the users who have earned this achievement
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
                    ->withPivot('earned_at', 'progress_data', 'is_featured')
                    ->withTimestamps();
    }

    /**
     * Scope for active achievements
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for specific rarity
     */
    public function scopeRarity($query, $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    /**
     * Get achievements ordered by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Get rarity color class
     */
    public function getRarityColorAttribute()
    {
        return match($this->rarity) {
            'common' => 'text-gray-600',
            'rare' => 'text-blue-600',
            'epic' => 'text-purple-600',
            'legendary' => 'text-yellow-600',
            default => 'text-gray-600',
        };
    }

    /**
     * Get rarity border class
     */
    public function getRarityBorderAttribute()
    {
        return match($this->rarity) {
            'common' => 'border-gray-300',
            'rare' => 'border-blue-300',
            'epic' => 'border-purple-300',
            'legendary' => 'border-yellow-300',
            default => 'border-gray-300',
        };
    }
}
