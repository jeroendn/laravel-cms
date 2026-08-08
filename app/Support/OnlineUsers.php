<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class OnlineUsers
{
    private const int WINDOW_MINUTES = 5;

    /**
     * The ids of everyone whose session was touched in the last few
     * minutes, read straight from the database session driver's table —
     * this costs no writes of our own. It only knows the present: those
     * rows are garbage collected a session lifetime after the last
     * request, which is what `users.last_active_at` is for.
     *
     * @return list<int>
     */
    public static function ids(): array
    {
        $rows = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(self::WINDOW_MINUTES)->getTimestamp())
            ->pluck('user_id');

        $ids = [];

        foreach ($rows as $id) {
            // Untyped column: the driver may hand the id back as a string.
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }
}
