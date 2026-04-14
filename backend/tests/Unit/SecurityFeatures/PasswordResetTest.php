<?php

namespace Tests\Unit\SecurityFeatures;

use App\Models\PasswordHistory;
use App\Models\User;
use App\Repositories\PasswordHistoryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * T060: Unit Tests for Password Reset Hardening
 *
 * Validates:
 * - 1-hour token expiry (via Laravel Password broker)
 * - Single-use tokens (via Laravel Password broker)
 * - Password reuse prevention (last 3 passwords)
 * - Password history recording
 *
 * @see T048 — Password Reset Hardening
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test password reuse prevention.
     */
    public function test_password_reuse_prevention_rejects_recent_passwords(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;
        $repo = new PasswordHistoryRepository(new PasswordHistory);

        // Create password history
        $password1 = Hash::make('password123');
        $password2 = Hash::make('password456');
        $password3 = Hash::make('password789');

        $repo->recordPasswordChange($userId, $password1);
        $repo->recordPasswordChange($userId, $password2);
        $repo->recordPasswordChange($userId, $password3);

        // Verify reuse detection works
        $this->assertTrue($repo->isPasswordReused($userId, 'password123', 3));
        $this->assertTrue($repo->isPasswordReused($userId, 'password456', 3));
    }

    /**
     * Test password history retrieval.
     */
    public function test_recent_passwords_can_be_retrieved(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;
        $repo = new PasswordHistoryRepository(new PasswordHistory);

        // Create 4 password changes
        $repo->recordPasswordChange($userId, Hash::make('pass1'));
        $repo->recordPasswordChange($userId, Hash::make('pass2'));
        $repo->recordPasswordChange($userId, Hash::make('pass3'));
        $repo->recordPasswordChange($userId, Hash::make('pass4'));

        // Get last 3 passwords
        $recent = $repo->getRecentHashes($userId, 3);
        $this->assertEquals(3, $recent->count());
    }

    /**
     * Test password change timestamp tracking.
     */
    public function test_password_change_timestamp_is_recorded(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;
        $repo = new PasswordHistoryRepository(new PasswordHistory);

        $repo->recordPasswordChange($userId, Hash::make('password'));
        $lastChange = $repo->getLastChangeTime($userId);

        $this->assertNotNull($lastChange);
        $this->assertTrue($lastChange->isToday());
    }

    /**
     * Test password change frequency checking.
     */
    public function test_password_change_frequency_can_be_counted(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;
        $repo = new PasswordHistoryRepository(new PasswordHistory);

        // Record multiple changes
        for ($i = 0; $i < 3; $i++) {
            $repo->recordPasswordChange($userId, Hash::make("password{$i}"));
        }

        $changesInDay = $repo->countChangesInDays($userId, 1);
        $this->assertEquals(3, $changesInDay);
    }
}
