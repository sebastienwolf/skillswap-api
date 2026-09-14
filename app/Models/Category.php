<?php

namespace App\Models;

use App\Enums\CategoryType;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @use HasFactory<CategoryFactory>
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
        ];
    }

    protected static function booted(): void
    {
        // Le slug est dérivé du nom : on évite de faire porter cette
        // responsabilité aux contrôleurs qui créent une catégorie.
        static::creating(function (self $category): void {
            $category->slug ??= Str::slug($category->name);
        });
    }

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * @return HasMany<Skill, $this>
     */
    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }
}
