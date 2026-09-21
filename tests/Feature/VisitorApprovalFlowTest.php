<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Tenant\DeviceToken;
use App\Models\Tenant\ResidentNotification;
use App\Models\Tenant\User;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorStatusHistory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request as HttpRequest;
use Tests\TestCase;

/**
 * End-to-end tests of the visitor approval + push notification flow against
 * a real (throwaway) tenant database, with Firebase faked at the HTTP layer.
 *
 * Needs the local MySQL server. It creates/migrates a scratch database
 * (`flatcare_test_visitors`) once per run and wraps every test in a
 * transaction that is rolled back, so nothing persists between tests.
 * Authentication is bypassed by pointing the 'society' guard at a fixture
 * user - the point here is the authorization *inside* the controllers/
 * workflow, which is what runs after AuthenticateApiToken in production.
 *
 * FCM outcomes are driven by the device token's prefix: `bad-...` -> FCM
 * answers UNREGISTERED, `flaky-...` -> HTTP 503, anything else -> success.
 */
class VisitorApprovalFlowTest extends TestCase
{
    private const DB = 'flatcare_test_visitors';

    private static bool $migrated = false;
    private static ?string $credentialsPath = null;

    private string $originalDefault;
    private int $blockId;
    private int $flatA;
    private int $flatB;
    private User $residentA;
    private User $residentA2; // second resident of the same flat
    private User $residentB;
    private User $guard;

    /** @var array<int, array{token: string}> */
    private array $fcmCalls = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.society.database' => self::DB]);
        DB::purge('society');

        if (!self::$migrated) {
            DB::connection()->statement('CREATE DATABASE IF NOT EXISTS `'.self::DB.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            Artisan::call('migrate:fresh', ['--database' => 'society', '--path' => 'database/migrations/tenant', '--force' => true]);
            self::$migrated = true;
        }

        // What TenantService::switchConnection() does for a real request.
        $this->originalDefault = DB::getDefaultConnection();
        DB::setDefaultConnection('society');
        DB::connection('society')->beginTransaction();

        $this->withoutMiddleware([AuthenticateApiToken::class, 'throttle:api-auth']);

        config([
            'services.fcm.credentials' => $this->fakeServiceAccount(),
            'services.fcm.project_id' => 'flatcare-test',
        ]);
        Cache::forget('fcm.access_token');
        $this->fakeFcm();

        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        DB::connection('society')->rollBack();
        DB::setDefaultConnection($this->originalDefault);
        parent::tearDown();
    }

    // ---------------------------------------------------------------- 1

    public function test_guard_request_is_stored_pending_and_notifies_the_flats_resident(): void
    {
        $this->registerDevice($this->residentA, 'good-A-phone');
        $this->registerDevice($this->residentB, 'good-B-phone');

        $response = $this->raiseRequest($this->flatA)->assertCreated();

        $response->assertJsonPath('data.status', 'pending')->assertJsonPath('data.status_label', 'PENDING');
        $visitor = Visitor::findOrFail($response->json('data.id'));
        $this->assertSame($this->blockId, $visitor->block_id);
        $this->assertSame($this->guard->id, $visitor->gate_keeper_id);

        $note = ResidentNotification::where('user_id', $this->residentA->id)->firstOrFail();
        $this->assertSame('Visitor Approval Request', $note->title);
        $this->assertSame('Ravi is waiting at the gate. Please approve or reject the visitor request.', $note->body);
        $this->assertSame('visitor_request', $note->type);
        $this->assertSame($visitor->id, $note->visitor_id);
        $this->assertNull($note->read_at);
        $this->assertSame('sent', $note->push_status);

        // Only flat A's residents were told - flat B's resident got nothing.
        $this->assertSame(0, ResidentNotification::where('user_id', $this->residentB->id)->count());
        $this->assertSame(['good-A-phone'], $this->fcmTokens());
        $this->assertHistory($visitor, [[null, 'pending', 'requested']]);
    }

    /**
     * Requirement 11 (app closed/background): the Approve/Reject buttons
     * can only be drawn by the app's own background handler, so Android gets
     * a high-priority data-only message; iOS gets a regular alert.
     */
    public function test_visitor_request_push_is_data_only_on_android_and_an_alert_on_ios(): void
    {
        $this->registerDevice($this->residentA, 'good-A-android');
        $this->registerDevice($this->residentA2, 'good-A2-iphone', 'ios');

        $this->raiseRequest($this->flatA)->assertCreated();

        $byToken = collect($this->fcmCalls)->keyBy('token');

        $android = $byToken['good-A-android']['payload'];
        $this->assertArrayNotHasKey('notification', $android['android']);
        $this->assertSame('HIGH', $android['android']['priority']);
        $this->assertSame('Visitor Approval Request', $android['data']['title']);
        $this->assertSame('approve,reject', $android['data']['actions']);
        $this->assertSame('visitor_request', $android['data']['type']);
        $this->assertArrayHasKey('visitor_id', $android['data']);
        $this->assertArrayNotHasKey('notification', $android);

        $ios = $byToken['good-A2-iphone']['payload'];
        $this->assertSame('Visitor Approval Request', $ios['apns']['payload']['aps']['alert']['title']);
    }

    public function test_every_authorised_resident_of_the_flat_is_notified(): void
    {
        $this->registerDevice($this->residentA, 'good-A-phone');
        $this->registerDevice($this->residentA2, 'good-A2-phone');

        $this->raiseRequest($this->flatA)->assertCreated();

        $this->assertEqualsCanonicalizing(['good-A-phone', 'good-A2-phone'], $this->fcmTokens());
    }

    // ---------------------------------------------------------------- 2, 3

    public function test_resident_approves_and_guard_is_notified_immediately(): void
    {
        $this->registerDevice($this->guard, 'good-guard-phone');
        $id = $this->raiseRequest($this->flatA)->json('data.id');
        $this->fcmCalls = [];

        $this->as($this->residentA)->postJson("/api/v1/visitors/{$id}/approve")
            ->assertOk()->assertJsonPath('data.status', 'approved')->assertJsonPath('data.status_label', 'APPROVED');

        $visitor = Visitor::findOrFail($id);
        $this->assertSame($this->residentA->id, $visitor->approved_by);
        $this->assertNotNull($visitor->approved_at);
        $this->assertNull($visitor->rejected_by);

        $note = ResidentNotification::where('user_id', $this->guard->id)->where('type', 'visitor_request_approved')->firstOrFail();
        $this->assertSame('Visitor Approved', $note->title);
        $this->assertSame('Ravi has been approved by the resident. You can allow the visitor to enter.', $note->body);
        $this->assertSame(['good-guard-phone'], $this->fcmTokens());
        $this->assertHistory($visitor, [[null, 'pending', 'requested'], ['pending', 'approved', 'approved']]);
    }

    public function test_resident_rejects_and_guard_is_notified_immediately(): void
    {
        $this->registerDevice($this->guard, 'good-guard-phone');
        $id = $this->raiseRequest($this->flatA)->json('data.id');
        $this->fcmCalls = [];

        $this->as($this->residentA)->postJson("/api/v1/visitors/{$id}/reject")
            ->assertOk()->assertJsonPath('data.status', 'denied')->assertJsonPath('data.status_label', 'REJECTED');

        $visitor = Visitor::findOrFail($id);
        $this->assertSame($this->residentA->id, $visitor->rejected_by);
        $this->assertNotNull($visitor->rejected_at);
        $this->assertNull($visitor->approved_by);

        $note = ResidentNotification::where('user_id', $this->guard->id)->where('type', 'visitor_request_rejected')->firstOrFail();
        $this->assertSame('Visitor Rejected', $note->title);
        $this->assertSame('Ravi has been rejected by the resident. Do not allow the visitor to enter.', $note->body);
        $this->assertSame(['good-guard-phone'], $this->fcmTokens());
    }

    // ---------------------------------------------------------------- 4, 5

    public function test_another_flats_resident_cannot_approve_or_reject_or_see_the_request(): void
    {
        $id = $this->raiseRequest($this->flatA)->json('data.id');

        $this->as($this->residentB)->postJson("/api/v1/visitors/{$id}/approve")->assertNotFound();
        $this->as($this->residentB)->postJson("/api/v1/visitors/{$id}/reject")->assertNotFound();
        $this->as($this->residentB)->getJson("/api/v1/visitors/{$id}")->assertNotFound();
        $this->assertSame([], $this->as($this->residentB)->getJson('/api/v1/visitors')->json('data'));

        $visitor = Visitor::findOrFail($id);
        $this->assertSame('pending', $visitor->status);
        $this->assertNull($visitor->approved_by);
    }

    public function test_a_resident_who_moved_out_can_no_longer_answer(): void
    {
        $id = $this->raiseRequest($this->flatA)->json('data.id');
        DB::connection('society')->table('flat_residents')->where('user_id', $this->residentA->id)->update(['status' => 'inactive']);

        $this->as($this->residentA)->postJson("/api/v1/visitors/{$id}/approve")->assertNotFound();
    }

    public function test_guard_endpoints_reject_non_guards_and_a_client_cannot_choose_the_gate_keeper(): void
    {
        $this->as($this->residentA)->getJson('/api/v1/guard/visitors')->assertForbidden();
        $this->as($this->residentA)->postJson('/api/v1/guard/visitors', $this->payload($this->flatA))->assertForbidden();

        // gate_keeper_id / status / approved_by in the body are ignored - they are set server-side.
        $id = $this->as($this->guard)->postJson('/api/v1/guard/visitors', $this->payload($this->flatA) + [
            'gate_keeper_id' => $this->residentB->id,
            'status' => 'approved',
            'approved_by' => $this->residentB->id,
        ])->assertCreated()->json('data.id');

        $visitor = Visitor::findOrFail($id);
        $this->assertSame($this->guard->id, $visitor->gate_keeper_id);
        $this->assertSame('pending', $visitor->status);
        $this->assertNull($visitor->approved_by);
    }

    public function test_guard_cannot_use_a_flat_that_does_not_exist_or_a_mismatched_block(): void
    {
        $this->as($this->guard)->postJson('/api/v1/guard/visitors', $this->payload(999999))->assertNotFound();

        $otherBlock = DB::connection('society')->table('blocks')->insertGetId(['name' => 'Block Z', 'block_number' => 'Z', 'created_at' => now(), 'updated_at' => now()]);
        $this->as($this->guard)->postJson('/api/v1/guard/visitors', $this->payload($this->flatA) + ['block_id' => $otherBlock])
            ->assertStatus(422);

        $this->assertSame(0, Visitor::count());
    }

    /**
     * Each society has its own database and the API token pins the request
     * to it, so another society's visitor simply is not in the tenant
     * database a guard's queries run against. Modelled here by an id that
     * doesn't exist in this society's database.
     */
    public function test_guard_cannot_reach_a_visitor_outside_their_society(): void
    {
        $this->as($this->guard);

        $this->getJson('/api/v1/guard/visitors/424242')->assertNotFound();
        $this->postJson('/api/v1/guard/visitors/424242/entry')->assertNotFound();
        $this->postJson('/api/v1/guard/visitors/424242/exit')->assertNotFound();
    }

    // ---------------------------------------------------------------- 6, 7

    public function test_all_active_devices_receive_the_push_and_an_invalid_token_is_deactivated(): void
    {
        $this->registerDevice($this->residentA, 'good-A-phone');
        $this->registerDevice($this->residentA, 'good-A-tablet');
        $this->registerDevice($this->residentA, 'bad-A-old-phone');

        $this->raiseRequest($this->flatA)->assertCreated();

        $this->assertEqualsCanonicalizing(['good-A-phone', 'good-A-tablet', 'bad-A-old-phone'], $this->fcmTokens());

        $dead = DeviceToken::where('token_hash', DeviceToken::hash('bad-A-old-phone'))->firstOrFail();
        $this->assertFalse($dead->is_active);
        $this->assertNotNull($dead->deactivated_at);
        $this->assertSame(2, DeviceToken::active()->where('user_id', $this->residentA->id)->count());
        $this->assertSame('sent', ResidentNotification::where('user_id', $this->residentA->id)->firstOrFail()->push_status);

        // The dead token is not tried again on the next request.
        $this->fcmCalls = [];
        $this->raiseRequest($this->flatA)->assertCreated();
        $this->assertNotContains('bad-A-old-phone', $this->fcmTokens());
    }

    public function test_device_registration_is_idempotent_reassigns_shared_phones_and_unregisters(): void
    {
        $token = 'good-shared-phone-token-0001';

        $this->as($this->residentA)->postJson('/api/v1/devices', ['token' => $token, 'platform' => 'android'])->assertCreated();
        $this->as($this->residentA)->postJson('/api/v1/devices', ['token' => $token])->assertCreated();
        $this->assertSame(1, DeviceToken::count());

        // Phone handed to another resident: the token now belongs to them alone.
        $this->as($this->residentB)->postJson('/api/v1/devices', ['token' => $token])->assertCreated();
        $this->assertSame(1, DeviceToken::count());
        $this->assertSame($this->residentB->id, DeviceToken::firstOrFail()->user_id);

        // The token is stored encrypted, never in clear.
        $this->assertNotSame($token, DB::connection('society')->table('device_tokens')->value('token'));

        // Someone else cannot unregister a device that isn't theirs...
        $this->as($this->residentA)->deleteJson('/api/v1/devices', ['token' => $token])->assertOk();
        $this->assertTrue(DeviceToken::firstOrFail()->is_active);
        // ...but its owner can.
        $this->as($this->residentB)->deleteJson('/api/v1/devices', ['token' => $token])->assertOk();
        $this->assertFalse(DeviceToken::firstOrFail()->is_active);

        $this->as($this->residentA)->postJson('/api/v1/devices', ['token' => 'short'])->assertStatus(422);
    }

    // ---------------------------------------------------------------- 8, 9, 10

    public function test_duplicate_approve_is_processed_once(): void
    {
        $this->registerDevice($this->guard, 'good-guard-phone');
        $id = $this->raiseRequest($this->flatA)->json('data.id');

        // Two devices / two residents of the same flat answering the same request.
        $this->as($this->residentA)->postJson("/api/v1/visitors/{$id}/approve")->assertOk();
        $second = $this->as($this->residentA2)->postJson("/api/v1/visitors/{$id}/approve");

        $second->assertStatus(409);
        $this->assertStringContainsString('already been approved', $second->json('message'));

        $visitor = Visitor::findOrFail($id);
        $this->assertSame($this->residentA->id, $visitor->approved_by); // first writer kept
        $this->assertSame(1, ResidentNotification::where('type', 'visitor_request_approved')->count());
        $this->assertSame(1, VisitorStatusHistory::where('visitor_id', $id)->where('action', 'approved')->count());
    }

    public function test_approve_after_reject_is_refused(): void
    {
        $id = $this->raiseRequest($this->flatA)->json('data.id');

        $this->as($this->residentA)->postJson("/api/v1/visitors/{$id}/reject")->assertOk();
        $this->as($this->residentA2)->postJson("/api/v1/visitors/{$id}/approve")->assertStatus(409);

        $visitor = Visitor::findOrFail($id);
        $this->assertSame('denied', $visitor->status);
        $this->assertNull($visitor->approved_by);
        $this->assertNull($visitor->approved_at);
    }

    public function test_reject_after_approve_is_refused(): void
    {
        $id = $this->raiseRequest($this->flatA)->json('data.id');

        $this->as($this->residentA)->postJson("/api/v1/visitors/{$id}/approve")->assertOk();
        $this->as($this->residentA2)->postJson("/api/v1/visitors/{$id}/reject")->assertStatus(409);

        $visitor = Visitor::findOrFail($id);
        $this->assertSame('approved', $visitor->status);
        $this->assertNull($visitor->rejected_by);
    }

    /**
     * A second device that loaded the request while it was still PENDING and
     * answers after the other one already did: the workflow re-reads the row
     * under a lock, so the stale device loses even though its own copy said PENDING.
     */
    public function test_a_stale_second_device_loses_the_race(): void
    {
        $id = $this->raiseRequest($this->flatA)->json('data.id');
        $stale = Visitor::findOrFail($id); // device 2's view: still pending
        $this->assertTrue($stale->isPending());

        $this->as($this->residentA)->postJson("/api/v1/visitors/{$id}/reject")->assertOk();
        $this->as($this->residentA2)->postJson("/api/v1/visitors/{$id}/approve")->assertStatus(409);

        $this->assertSame('denied', Visitor::findOrFail($id)->status);
    }

    // ---------------------------------------------------------------- 13

    public function test_request_stays_pending_and_is_retried_when_the_push_service_fails(): void
    {
        $this->registerDevice($this->residentA, 'flaky-A-phone');

        $response = $this->raiseRequest($this->flatA)->assertCreated();

        $visitor = Visitor::findOrFail($response->json('data.id'));
        $this->assertSame('pending', $visitor->status);

        $note = ResidentNotification::where('user_id', $this->residentA->id)->firstOrFail();
        $this->assertSame('failed', $note->push_status);
        $this->assertSame(1, $note->push_attempts);
        $this->assertNotEmpty($note->push_error);
        $this->assertTrue(DeviceToken::firstOrFail()->is_active); // transient error - token kept

        // Firebase recovers; the retry delivers it and records success.
        $this->flakyFails = false;
        app(\App\Services\NotificationService::class)->push($note->fresh());

        $note->refresh();
        $this->assertSame('sent', $note->push_status);
        $this->assertSame(2, $note->push_attempts);
        $this->assertNull($note->push_error);
    }

    public function test_request_is_saved_even_if_firebase_cannot_be_reached_at_all(): void
    {
        $this->registerDevice($this->residentA, 'good-A-phone');
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: timed out'));
        Cache::forget('fcm.access_token');

        $response = $this->raiseRequest($this->flatA)->assertCreated();

        $this->assertSame('pending', Visitor::findOrFail($response->json('data.id'))->status);
        $this->assertSame('failed', ResidentNotification::where('user_id', $this->residentA->id)->firstOrFail()->push_status);
    }

    public function test_no_registered_device_still_creates_the_in_app_notification(): void
    {
        $this->raiseRequest($this->flatA)->assertCreated();

        $note = ResidentNotification::where('user_id', $this->residentA->id)->firstOrFail();
        $this->assertSame('skipped', $note->push_status);
        $this->assertSame([], $this->fcmTokens());

        $this->as($this->residentA)->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.notification_type', 'visitor_request')
            ->assertJsonPath('data.0.visitor_request_id', $note->visitor_id)
            ->assertJsonPath('data.0.is_read', false);
    }

    // ---------------------------------------------------------------- 14, 15

    public function test_guard_marks_an_approved_visitor_entered_then_exited(): void
    {
        $id = $this->raiseRequest($this->flatA)->json('data.id');
        $this->as($this->residentA)->postJson("/api/v1/visitors/{$id}/approve")->assertOk();

        $this->as($this->guard)->postJson("/api/v1/guard/visitors/{$id}/entry")
            ->assertOk()->assertJsonPath('data.status_label', 'ENTERED')->assertJsonPath('data.can_exit', true);
        $this->assertNotNull(Visitor::findOrFail($id)->check_in_at);

        $this->postJson("/api/v1/guard/visitors/{$id}/exit")
            ->assertOk()->assertJsonPath('data.status_label', 'EXITED');
        $this->assertNotNull(Visitor::findOrFail($id)->check_out_at);

        $this->assertHistory(Visitor::findOrFail($id), [
            [null, 'pending', 'requested'],
            ['pending', 'approved', 'approved'],
            ['approved', 'checked_in', 'entered'],
            ['checked_in', 'checked_out', 'exited'],
        ]);

        // Terminal: nothing further is possible.
        $this->postJson("/api/v1/guard/visitors/{$id}/exit")->assertStatus(409);
        $this->postJson("/api/v1/guard/visitors/{$id}/entry")->assertStatus(409);
    }

    public function test_guard_cannot_let_in_a_pending_or_rejected_visitor_or_exit_one_who_never_entered(): void
    {
        $pending = $this->raiseRequest($this->flatA)->json('data.id');
        $rejected = $this->raiseRequest($this->flatA)->json('data.id');
        $this->as($this->residentA)->postJson("/api/v1/visitors/{$rejected}/reject")->assertOk();
        $approved = $this->raiseRequest($this->flatA)->json('data.id');
        $this->as($this->residentA)->postJson("/api/v1/visitors/{$approved}/approve")->assertOk();

        $this->as($this->guard);
        $this->postJson("/api/v1/guard/visitors/{$pending}/entry")->assertStatus(409);
        $this->postJson("/api/v1/guard/visitors/{$pending}/check-in")->assertStatus(409); // legacy alias is guarded too
        $this->postJson("/api/v1/guard/visitors/{$rejected}/entry")->assertStatus(409);
        $this->postJson("/api/v1/guard/visitors/{$approved}/exit")->assertStatus(409);

        $this->assertSame('pending', Visitor::findOrFail($pending)->status);
        $this->assertSame('denied', Visitor::findOrFail($rejected)->status);
        $this->assertSame('approved', Visitor::findOrFail($approved)->status);
    }

    public function test_a_residents_own_pre_approved_pass_can_be_entered_but_not_answered(): void
    {
        $id = $this->as($this->residentA)->postJson('/api/v1/visitors', [
            'flat_id' => $this->flatA, 'visitor_name' => 'Cousin', 'visitor_phone' => '9000000001', 'purpose' => 'guest',
        ])->assertCreated()->json('data.id');

        $this->as($this->residentA2)->postJson("/api/v1/visitors/{$id}/approve")->assertStatus(409);
        $this->as($this->guard)->postJson("/api/v1/guard/visitors/{$id}/entry")->assertOk()->assertJsonPath('data.status', 'checked_in');
    }

    // ---------------------------------------------------------------- lists / notifications

    public function test_guard_list_defaults_to_active_requests_and_filters_by_status(): void
    {
        $pending = $this->raiseRequest($this->flatA)->json('data.id');
        $done = $this->raiseRequest($this->flatB, 'Done')->json('data.id');
        $this->as($this->residentB)->postJson("/api/v1/visitors/{$done}/reject")->assertOk();

        $ids = fn ($r) => collect($r->json('data'))->pluck('id')->all();

        $this->as($this->guard);
        $this->assertSame([$pending], $ids($this->getJson('/api/v1/guard/visitors')));
        $this->assertSame([$done], $ids($this->getJson('/api/v1/guard/visitors?status=denied')));
        $this->assertEqualsCanonicalizing([$pending, $done], $ids($this->getJson('/api/v1/guard/visitors?status=all')));
        $this->getJson('/api/v1/guard/visitors?status=bogus')->assertStatus(422);
    }

    public function test_notifications_can_be_marked_read_one_by_one_and_all_at_once(): void
    {
        $this->raiseRequest($this->flatA);
        $this->raiseRequest($this->flatA, 'Second');
        $this->as($this->residentA);

        $ids = collect($this->getJson('/api/v1/notifications')->json('data'))->pluck('id');
        $this->assertCount(2, $ids);

        $this->postJson("/api/v1/notifications/{$ids[0]}/read")->assertOk()->assertJsonPath('data.is_read', true);
        $this->assertSame(1, $this->getJson('/api/v1/notifications/unread-count')->json('data.count'));

        // A resident cannot read another user's notification.
        $other = ResidentNotification::create(['user_id' => $this->residentB->id, 'type' => 'new_notice', 'title' => 'x']);
        $this->postJson("/api/v1/notifications/{$other->id}/read")->assertNotFound();

        $this->postJson('/api/v1/notifications/read-all')->assertOk();
        $this->assertSame(0, $this->getJson('/api/v1/notifications/unread-count')->json('data.count'));
        $this->assertNotNull(ResidentNotification::findOrFail($ids[0])->read_at);
    }

    // ================================================================ helpers

    private function raiseRequest(int $flatId, string $name = 'Ravi')
    {
        return $this->as($this->guard)->postJson('/api/v1/guard/visitors', $this->payload($flatId, $name));
    }

    /** @return array<string, mixed> */
    private function payload(int $flatId, string $name = 'Ravi'): array
    {
        return [
            'flat_id' => $flatId,
            'visitor_name' => $name,
            'visitor_phone' => '9876543210',
            'purpose' => 'guest',
            'notes' => 'Family visit',
        ];
    }

    private function as(User $user): static
    {
        Auth::guard('society')->setUser($user);

        return $this;
    }

    private function registerDevice(User $user, string $token, string $platform = 'android'): void
    {
        DeviceToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'token_hash' => DeviceToken::hash($token),
            'platform' => $platform,
            'is_active' => true,
        ]);
    }

    /** @return array<int, string> */
    private function fcmTokens(): array
    {
        return array_column($this->fcmCalls, 'token');
    }

    /**
     * @param  array<int, array{0: ?string, 1: string, 2: string}>  $expected  [from, to, action]
     */
    private function assertHistory(Visitor $visitor, array $expected): void
    {
        $actual = VisitorStatusHistory::where('visitor_id', $visitor->id)->orderBy('id')->get()
            ->map(fn ($h) => [$h->from_status, $h->to_status, $h->action])->all();

        $this->assertSame($expected, $actual);
    }

    private bool $flakyFails = true;

    private function fakeFcm(): void
    {
        $this->fcmCalls = [];

        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->url(), 'oauth2.googleapis.com')) {
                return Http::response(['access_token' => 'fake-access-token', 'expires_in' => 3600]);
            }

            $token = $request['message']['token'];
            $this->fcmCalls[] = ['token' => $token, 'payload' => $request['message']];

            if (str_starts_with($token, 'bad-')) {
                return Http::response(['error' => ['status' => 'UNREGISTERED', 'message' => 'Requested entity was not found.']], 404);
            }
            if ($this->flakyFails && str_starts_with($token, 'flaky-')) {
                return Http::response(['error' => ['status' => 'UNAVAILABLE', 'message' => 'The service is currently unavailable.']], 503);
            }

            return Http::response(['name' => 'projects/flatcare-test/messages/1']);
        });
    }

    private function fakeServiceAccount(): string
    {
        if (self::$credentialsPath === null) {
            $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];

            // PHP on Windows often can't find openssl.cnf on its own.
            foreach ([dirname(PHP_BINARY).'/extras/ssl/openssl.cnf', 'C:/xampp/php/extras/ssl/openssl.cnf'] as $cnf) {
                if (!openssl_pkey_new($options) && is_file($cnf)) {
                    $options['config'] = $cnf;
                    break;
                }
            }

            $key = openssl_pkey_new($options);
            openssl_pkey_export($key, $pem, null, isset($options['config']) ? ['config' => $options['config']] : []);

            self::$credentialsPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'flatcare-test-fcm-'.getmypid().'.json';
            file_put_contents(self::$credentialsPath, json_encode([
                'project_id' => 'flatcare-test',
                'client_email' => 'test@flatcare-test.iam.gserviceaccount.com',
                'private_key' => $pem,
                'token_uri' => 'https://oauth2.googleapis.com/token',
            ]));
        }

        return self::$credentialsPath;
    }

    private function seedFixtures(): void
    {
        $db = DB::connection('society');
        $now = now();

        $this->blockId = $db->table('blocks')->insertGetId(['name' => 'Block A', 'block_number' => 'A', 'created_at' => $now, 'updated_at' => $now]);
        $flat = fn (string $number) => $db->table('flats')->insertGetId([
            'block_id' => $this->blockId, 'flat_number' => $number, 'floor_number' => '1', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->flatA = $flat('A-101');
        $this->flatB = $flat('A-102');

        $user = function (string $name, string $phone, ?string $role = null) use ($db, $now): User {
            $u = User::create(['name' => $name, 'email' => "{$phone}@example.test", 'phone' => $phone, 'password' => 'x', 'status' => 'active']);
            if ($role) {
                $roleId = $db->table('roles')->where('name', $role)->value('id')
                    ?? $db->table('roles')->insertGetId(['name' => $role, 'display_name' => ucfirst($role), 'created_at' => $now, 'updated_at' => $now]);
                $db->table('role_user')->insert(['user_id' => $u->id, 'role_id' => $roleId, 'created_at' => $now, 'updated_at' => $now]);
            }

            return $u;
        };

        $this->residentA = $user('Asha (A-101)', '9100000001', 'resident');
        $this->residentA2 = $user('Arun (A-101)', '9100000002', 'resident');
        $this->residentB = $user('Bina (A-102)', '9100000003', 'resident');
        $this->guard = $user('Gopal (guard)', '9100000009', 'security');

        foreach ([[$this->flatA, $this->residentA], [$this->flatA, $this->residentA2], [$this->flatB, $this->residentB]] as [$flatId, $resident]) {
            $db->table('flat_residents')->insert([
                'flat_id' => $flatId, 'user_id' => $resident->id, 'resident_type' => 'owner',
                'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
}
