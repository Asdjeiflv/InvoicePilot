<?php

namespace Tests\Feature;

use App\Actions\Reminders\SendReminderAction;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReminderDuplicatePreventionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Client $client;
    private Invoice $invoice;
    private SendReminderAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);

        $this->client = Client::factory()->create([
            'email' => 'client@example.com',
        ]);

        $this->invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => 'issued',
            'balance_due' => 10000,
        ]);

        $this->action = new SendReminderAction();
    }

    #[Test]
    public function it_prevents_sending_reminder_within_7_days(): void
    {
        // Send first reminder
        $this->action->execute($this->invoice, 'normal');

        // Attempt to send another reminder immediately
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A reminder was already sent');

        $this->action->execute($this->invoice, 'normal');
    }

    #[Test]
    public function it_allows_sending_reminder_after_7_days(): void
    {
        // Send first reminder
        $firstReminder = $this->action->execute($this->invoice, 'normal');

        // Manually update sent_at to 8 days ago
        $firstReminder->update(['sent_at' => now()->subDays(8)]);

        // Should be able to send another reminder now
        $secondReminder = $this->action->execute($this->invoice, 'normal');

        $this->assertNotNull($secondReminder);
        $this->assertEquals(2, $this->invoice->reminders()->count());
    }

    #[Test]
    public function it_prevents_sending_reminder_exactly_at_7_day_boundary(): void
    {
        // Send first reminder
        $firstReminder = $this->action->execute($this->invoice, 'normal');

        // Update sent_at to exactly 7 days ago
        $firstReminder->update(['sent_at' => now()->subDays(7)]);

        // Should still be blocked (within 7 days means <= 7 days)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A reminder was already sent');

        $this->action->execute($this->invoice, 'normal');
    }

    #[Test]
    public function it_shows_when_last_reminder_was_sent_in_error_message(): void
    {
        // Send first reminder
        $reminder = $this->action->execute($this->invoice, 'normal');

        try {
            $this->action->execute($this->invoice, 'normal');
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString(
                $reminder->sent_at->format('Y-m-d H:i:s'),
                $e->getMessage()
            );
            $this->assertStringContainsString('wait at least 7 days', $e->getMessage());
        }
    }

    #[Test]
    public function it_only_checks_recent_reminders_not_old_ones(): void
    {
        // Create old reminder (30 days ago)
        Reminder::factory()->create([
            'invoice_id' => $this->invoice->id,
            'sent_at' => now()->subDays(30),
            'sent_by' => $this->user->id,
        ]);

        // Should be able to send a new reminder
        $newReminder = $this->action->execute($this->invoice, 'normal');

        $this->assertNotNull($newReminder);
        $this->assertEquals(2, $this->invoice->reminders()->count());
    }

    #[Test]
    public function it_checks_most_recent_reminder_only(): void
    {
        // Create multiple old reminders
        Reminder::factory()->create([
            'invoice_id' => $this->invoice->id,
            'sent_at' => now()->subDays(20),
            'sent_by' => $this->user->id,
        ]);

        Reminder::factory()->create([
            'invoice_id' => $this->invoice->id,
            'sent_at' => now()->subDays(15),
            'sent_by' => $this->user->id,
        ]);

        // Send a new reminder (should work since all are older than 7 days)
        $newReminder = $this->action->execute($this->invoice, 'normal');
        $this->assertNotNull($newReminder);

        // Try to send another immediately (should fail)
        $this->expectException(\RuntimeException::class);
        $this->action->execute($this->invoice, 'normal');
    }

    #[Test]
    public function it_allows_sending_different_reminder_types_but_still_enforces_7_day_rule(): void
    {
        // Send a 'soft' reminder
        $this->action->execute($this->invoice, 'soft');

        // Try to send a 'normal' reminder immediately (should still fail)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A reminder was already sent');

        $this->action->execute($this->invoice, 'normal');
    }
}
