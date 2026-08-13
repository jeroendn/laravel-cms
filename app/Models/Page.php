<?php

namespace App\Models;

use Override;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $body
 * @property string $body_html
 * @property bool $is_draft
 * @property bool $show_in_menu
 * @property int $priority
 * @property int|null $page_group_id
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PageGroup|null $group
 */
#[Fillable(['title', 'slug', 'body', 'is_draft', 'show_in_menu', 'priority', 'page_group_id', 'published_at'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
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
            'is_draft' => 'boolean',
            'show_in_menu' => 'boolean',
            'priority' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PageGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(PageGroup::class, 'page_group_id');
    }

    /**
     * Query only pages that are visible to the public: published, and for
     * grouped pages past their publication date. Ungrouped pages ignore
     * the date entirely.
     *
     * @return Builder<static>
     */
    public static function visible(): Builder
    {
        return static::query()
            ->where('is_draft', false)
            ->where(function ($query): void {
                $query->whereNull('page_group_id')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function isVisible(): bool
    {
        if ($this->is_draft) {
            return false;
        }

        if ($this->page_group_id === null) {
            return true;
        }

        return $this->published_at !== null && $this->published_at->lte(now());
    }

    public function isScheduled(): bool
    {
        return !$this->is_draft
            && $this->page_group_id !== null
            && $this->published_at !== null
            && $this->published_at->gt(now());
    }

    /**
     * The body (HTML from the WYSIWYG editor) sanitized by HTMLPurifier,
     * so the result is safe to output unescaped.
     *
     * @return Attribute<string, never>
     */
    protected function bodyHtml(): Attribute
    {
        return Attribute::get(function (): string {
            $clean = Purify::clean($this->body);

            return is_string($clean) ? $clean : '';
        });
    }

    /**
     * A plain-text teaser of the body, for the page list.
     */
    public function excerpt(int $length = 200): string
    {
        return Str::limit(Str::squish(strip_tags($this->body_html)), $length);
    }
}
