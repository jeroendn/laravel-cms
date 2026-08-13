<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Support\OnlineUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function testAnAuthenticatedRequestRecordsActivity(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('home'))->assertOk();

        $user->refresh();
        $this->assertNotNull($user->last_active_at);
        $this->assertTrue($user->last_active_at->equalTo(now()));
    }

    public function testActivityOnThePublicSiteCountsToo(): void
    {
        $user = User::factory()->create();
        $page = Page::factory()->visible()->create();

        $this->actingAs($user)->get($page->url());

        $this->assertNotNull($user->refresh()->last_active_at);
    }

    public function testAFreshTimestampIsNotRewrittenOnEveryRequest(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create();
        $recorded = now()->subMinutes(2);
        $user->forceFill(['last_active_at' => $recorded])->save();

        $this->actingAs($user)->get(route('home'));

        $user->refresh();
        $this->assertNotNull($user->last_active_at);
        $this->assertTrue($user->last_active_at->equalTo($recorded));
    }

    public function testAStaleTimestampIsRefreshed(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create();
        $user->forceFill(['last_active_at' => now()->subHour()])->save();

        $this->actingAs($user)->get(route('home'));

        $user->refresh();
        $this->assertNotNull($user->last_active_at);
        $this->assertTrue($user->last_active_at->equalTo(now()));
    }

    public function testTheHeartbeatDoesNotCountAsEditingTheAccount(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create();
        $updatedAt = $user->updated_at;
        $this->assertNotNull($updatedAt);

        $this->travel(1)->hours();
        $this->actingAs($user)->get(route('home'));

        $user->refresh();
        $this->assertNotNull($user->updated_at);
        $this->assertTrue($user->updated_at->equalTo($updatedAt));
        $this->assertNotNull($user->last_active_at);
    }

    public function testTheOverviewMarksWhoIsOnlineRightNow(): void
    {
        $online = User::factory()->create();
        $this->sessionRow($online, minutesAgo: 1);

        $away = User::factory()->create();
        $this->sessionRow($away, minutesAgo: 30);

        $response = $this->actingAs(User::factory()->create())->get(route('admin.users.index'));

        $response->assertSee(__('Online now'));
        $this->assertSame([$online->id], OnlineUsers::ids());
    }

    public function testTheOverviewShowsWhoHasNeverSignedIn(): void
    {
        User::factory()->create(['name' => 'Invited editor']);

        $response = $this->actingAs(User::factory()->create())->get(route('admin.users.index'));

        $response->assertSee(__('Last active'));
        $response->assertSee(__('Never'));
    }

    /**
     * A row like the database session driver would leave behind. The tests
     * themselves run on the array driver, so nothing writes one for us.
     */
    private function sessionRow(User $user, int $minutesAgo): void
    {
        DB::table('sessions')->insert([
            'id' => Str::random(40),
            'user_id' => $user->id,
            'payload' => '',
            'last_activity' => now()->subMinutes($minutesAgo)->getTimestamp(),
        ]);
    }
}
