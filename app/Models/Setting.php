<?php

namespace App\Models;

use Override;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $site_name
 * @property string $primary_color
 * @property bool $under_construction
 * @property bool $show_login_link
 * @property list<string> $locales
 * @property string $default_locale
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['site_name', 'primary_color', 'under_construction', 'show_login_link', 'locales', 'default_locale'])]
class Setting extends Model
{
    /** The value app.css ships with; see Theme::primaryStyle(). */
    public const string DEFAULT_PRIMARY_COLOR = '#750f2e';

    /**
     * Mirrors the migration's column defaults, for the unsaved instance
     * current() falls back to when the row is missing.
     *
     * @var array<string, mixed>
     */
    #[Override]
    protected $attributes = [
        'primary_color' => self::DEFAULT_PRIMARY_COLOR,
        'under_construction' => true,
        'show_login_link' => false,
        'locales' => '["en"]',
        'default_locale' => 'en',
    ];

    /** Resolved once per request; see the scoped binding in AppServiceProvider. */
    public static function current(): self
    {
        return app(self::class);
    }

    /** Falls back to APP_NAME while the field is empty. */
    public function name(): string
    {
        return $this->site_name ?? config()->string('app.name');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'under_construction' => 'boolean',
            'show_login_link' => 'boolean',
            'locales' => 'array',
        ];
    }

    /**
     * Saving drops the per-request memo — a feature test serves several
     * requests from one container, where it would otherwise go stale.
     */
    #[Override]
    protected static function booted(): void
    {
        static::saved(static function (): void {
            app()->forgetInstance(self::class);
        });
    }
}
