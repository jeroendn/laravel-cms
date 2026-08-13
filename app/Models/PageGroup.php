<?php

namespace App\Models;

use Override;
use Database\Factories\PageGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $show_in_menu
 * @property int $priority
 * @property int|null $parent_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PageGroup|null $parent
 * @property-read Collection<int, PageGroup> $children
 */
#[Fillable(['name', 'slug', 'show_in_menu', 'priority', 'parent_id'])]
class PageGroup extends Model
{
    /** @use HasFactory<PageGroupFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'show_in_menu' => 'boolean',
            'priority' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PageGroup, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    /**
     * @return HasMany<PageGroup, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }
}
