<?php

namespace App\Modules\PhotoRepository\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MediaCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    public function photos(): HasMany
    {
        return $this->hasMany(MediaPhoto::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = trim($value);
    }

    public function setSlugAttribute(?string $value): void
    {
        $slugSource = filled($value) ? $value : ($this->attributes['name'] ?? '');
        $this->attributes['slug'] = Str::slug($slugSource);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
