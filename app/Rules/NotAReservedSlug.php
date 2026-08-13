<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Route;

/**
 * Rejects slugs that would shadow a URL the application itself defines
 * (/admin, /login, ...). Only root-level slugs can collide — deeper
 * segments always sit behind a group slug — so the rule is only applied
 * to ungrouped pages and root groups. The list is derived from the route
 * table, so new application routes are covered automatically.
 */
readonly class NotAReservedSlug implements ValidationRule
{
    /**
     * @param list<string> $except
     */
    public function __construct(private array $except = []) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || in_array($value, $this->except, true)) {
            return;
        }

        if (in_array($value, self::reserved(), true)) {
            $fail(__('This slug is reserved by the application.'));
        }
    }

    /**
     * The literal first path segments of all registered routes; parameter
     * segments ({slug} etc.) are no reservation.
     *
     * @return list<string>
     */
    private static function reserved(): array
    {
        $segments = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $segment = explode('/', $route->uri())[0];

            if ($segment !== '' && !str_starts_with($segment, '{')) {
                $segments[] = $segment;
            }
        }

        return array_values(array_unique($segments));
    }
}
