<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackActivity
{
    /**
     * How long a recorded moment counts as current. Without it every
     * authenticated request would write a row.
     */
    private const int FRESH_FOR_MINUTES = 5;

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $this->isStale($user)) {
            // Quietly and without timestamps: this is a heartbeat, not an
            // edit of the account.
            User::withoutTimestamps(
                fn(): bool => $user->forceFill(['last_active_at' => now()])->saveQuietly(),
            );
        }

        return $next($request);
    }

    private function isStale(User $user): bool
    {
        return $user->last_active_at === null
            || $user->last_active_at->addMinutes(self::FRESH_FOR_MINUTES)->isPast();
    }
}
