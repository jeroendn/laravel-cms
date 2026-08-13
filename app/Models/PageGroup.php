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
 * @property-read Collection<int, Page> $pages
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

    /**
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * The name prefixed with the parent's, to tell subgroups apart in
     * flat listings (the group select, the admin index).
     */
    public function fullName(): string
    {
        return $this->parent === null ? $this->name : $this->parent->name . ' / ' . $this->name;
    }

    /**
     * The URL path, without a leading slash: "group" or "group/subgroup".
     */
    public function path(): string
    {
        return $this->parent === null ? $this->slug : $this->parent->slug . '/' . $this->slug;
    }

    public function url(): string
    {
        return url('/' . $this->path());
    }
}
