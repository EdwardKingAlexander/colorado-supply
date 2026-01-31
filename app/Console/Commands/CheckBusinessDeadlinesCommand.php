<?php

namespace App\Console\Commands;

use App\Models\BusinessDeadline;
use App\Models\BusinessDocument;
use App\Notifications\DeadlineApproachingNotification;
use App\Notifications\DocumentExpiringNotification;
use App\Notifications\OverdueAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckBusinessDeadlinesCommand extends Command
{
    protected $signature = 'business:check-deadlines
                            {--dry-run : Show what would be sent without actually sending}
                            {--force : Send notifications even if already sent today}';

    protected $description = 'Check for upcoming deadlines and expiring documents, send reminder notifications';

    protected int $notificationsSent = 0;

    protected int $overdueCount = 0;

    protected int $upcomingCount = 0;

    protected int $expiringCount = 0;

    public function handle(): int
    {
        $recipientEmail = config('business-hub.notification_email');

        if (! $recipientEmail) {
            $this->error('No notification email configured. Set BUSINESS_HUB_NOTIFICATION_EMAIL in .env');

            return self::FAILURE;
        }

        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Checking business deadlines and documents...');
        $this->newLine();

        // Check for overdue deadlines
        $this->checkOverdueDeadlines($recipientEmail, $isDryRun, $force);

        // Check for upcoming deadlines that need reminders
        $this->checkUpcomingDeadlines($recipientEmail, $isDryRun, $force);

        // Check for expiring documents
        $this->checkExpiringDocuments($recipientEmail, $isDryRun, $force);

        // Display summary
        $this->displaySummary($isDryRun);

        return self::SUCCESS;
    }

    protected function checkOverdueDeadlines(string $email, bool $isDryRun, bool $force): void
    {
        $overdueDeadlines = BusinessDeadline::overdue()
            ->when(! $force, function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('last_reminder_sent_at')
                        ->orWhere('last_reminder_sent_at', '<', now()->subDay());
                });
            })
            ->get();

        if ($overdueDeadlines->isEmpty()) {
            $this->line('No overdue deadlines found.');

            return;
        }

        $this->overdueCount = $overdueDeadlines->count();
        $this->warn("Found {$this->overdueCount} overdue deadline(s)");

        foreach ($overdueDeadlines as $deadline) {
            $this->line("  - {$deadline->title} (due: {$deadline->due_date->format('M j, Y')})");

            if (! $isDryRun) {
                Notification::route('mail', $email)
                    ->notify(new OverdueAlertNotification($deadline));

                $deadline->recordReminderSent();
                $this->notificationsSent++;
            }
        }

        $this->newLine();
    }

    protected function checkUpcomingDeadlines(string $email, bool $isDryRun, bool $force): void
    {
        $upcomingDeadlines = BusinessDeadline::upcoming(60)
            ->whereNotNull('reminder_days')
            ->get()
            ->filter(function ($deadline) use ($force) {
                if ($force) {
                    return true;
                }

                // Check if we should send a reminder today
                if (! $deadline->shouldSendReminder()) {
                    return false;
                }

                // Don't send if already sent today
                if ($deadline->last_reminder_sent_at && $deadline->last_reminder_sent_at->isToday()) {
                    return false;
                }

                return true;
            });

        if ($upcomingDeadlines->isEmpty()) {
            $this->line('No upcoming deadline reminders needed.');

            return;
        }

        $this->upcomingCount = $upcomingDeadlines->count();
        $this->info("Sending {$this->upcomingCount} deadline reminder(s)");

        foreach ($upcomingDeadlines as $deadline) {
            $daysUntil = $deadline->daysUntilDue();
            $this->line("  - {$deadline->title} (due in {$daysUntil} days)");

            if (! $isDryRun) {
                Notification::route('mail', $email)
                    ->notify(new DeadlineApproachingNotification($deadline));

                $deadline->recordReminderSent();
                $this->notificationsSent++;
            }
        }

        $this->newLine();
    }

    protected function checkExpiringDocuments(string $email, bool $isDryRun, bool $force): void
    {
        $expiringDocuments = BusinessDocument::expiringSoon(60)
            ->get()
            ->filter(function ($document) use ($force) {
                if ($force) {
                    return true;
                }

                $daysUntil = $document->daysUntilExpiration();

                // Send reminders at 60, 30, 14, 7, 1 days
                return in_array($daysUntil, [60, 30, 14, 7, 1]);
            });

        if ($expiringDocuments->isEmpty()) {
            $this->line('No document expiration reminders needed.');

            return;
        }

        $this->expiringCount = $expiringDocuments->count();
        $this->info("Sending {$this->expiringCount} document expiration reminder(s)");

        foreach ($expiringDocuments as $document) {
            $daysUntil = $document->daysUntilExpiration();
            $this->line("  - {$document->name} (expires in {$daysUntil} days)");

            if (! $isDryRun) {
                Notification::route('mail', $email)
                    ->notify(new DocumentExpiringNotification($document));

                $this->notificationsSent++;
            }
        }

        $this->newLine();
    }

    protected function displaySummary(bool $isDryRun): void
    {
        $this->info('═══════════════════════════════════════');
        $this->info('           SUMMARY                     ');
        $this->info('═══════════════════════════════════════');

        $this->table(
            ['Type', 'Count'],
            [
                ['Overdue Deadlines', $this->overdueCount],
                ['Upcoming Reminders', $this->upcomingCount],
                ['Expiring Documents', $this->expiringCount],
                ['Notifications Sent', $isDryRun ? '0 (dry run)' : $this->notificationsSent],
            ]
        );

        if ($isDryRun) {
            $this->warn('Dry run mode - no notifications were actually sent.');
        }
    }
}
