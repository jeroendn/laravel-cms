<?php

namespace App\Models;

use Override;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $body
 * @property string $body_html
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'slug', 'body', 'published_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
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
            'published_at' => 'datetime',
        ];
    }

    /**
     * Query only posts that are visible to the public.
     *
     * @return Builder<static>
     */
    public static function published(): Builder
    {
        return static::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
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
     * A plain-text teaser of the body, for the post list.
     */
    public function excerpt(int $length = 200): string
    {
        return Str::limit(Str::squish(strip_tags($this->body_html)), $length);
    }
}
