<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Opportunity;
use App\Models\Stage;

class OpportunityObserver
{
    public function creating(Opportunity $opportunity): void
    {
        if (!$opportunity->created_by) {
            $opportunity->created_by = auth()->id();
        }

        if (!$opportunity->updated_by) {
            $opportunity->updated_by = auth()->id();
        }

        if (!$opportunity->status) {
            $opportunity->status = 'open';
        }
    }

    public function updating(Opportunity $opportunity): void
    {
        $opportunity->updated_by = auth()->id();

        // Track stage changes
        if ($opportunity->isDirty('stage_id')) {
            $oldStageId = $opportunity->getOriginal('stage_id');
            $newStageId = $opportunity->stage_id;

            $oldStage = Stage::find($oldStageId);
            $newStage = Stage::find($newStageId);

            if ($oldStage && $newStage) {
                // Log stage change as activity
                Activity::create([
                    'opportunity_id' => $opportunity->id,
                    'type' => 'note',
                    'subject' => 'Stage Changed',
                    'body' => sprintf(
                        'Moved from "%s" to "%s" by %s',
                        $oldStage->name,
                        $newStage->name,
                        auth()->user()->name ?? 'System'
                    ),
                    'owner_id' => auth()->id() ?? $opportunity->owner_id,
                    'done_at' => now(),
                ]);

                // Auto-update status for won/lost stages
                if ($newStage->is_won && $opportunity->status !== 'won') {
                    $opportunity->status = 'won';
                    $opportunity->closed_at = now();
                } elseif ($newStage->is_lost && $opportunity->status !== 'lost') {
                    $opportunity->status = 'lost';
                    $opportunity->closed_at = now();
                }
            }
        }

        // Log status changes
        if ($opportunity->isDirty('status')) {
            $oldStatus = $opportunity->getOriginal('status');
            $newStatus = $opportunity->status;

            Activity::create([
                'opportunity_id' => $opportunity->id,
                'type' => 'note',
                'subject' => 'Status Changed',
                'body' => sprintf(
                    'Status changed from "%s" to "%s" by %s',
                    ucfirst($oldStatus),
                    ucfirst($newStatus),
                    auth()->user()->name ?? 'System'
                ),
                'owner_id' => auth()->id() ?? $opportunity->owner_id,
                'done_at' => now(),
            ]);
        }

        // Log owner changes
        if ($opportunity->isDirty('owner_id')) {
            $oldOwnerId = $opportunity->getOriginal('owner_id');
            $newOwnerId = $opportunity->owner_id;

            $oldOwner = \App\Models\User::find($oldOwnerId);
            $newOwner = \App\Models\User::find($newOwnerId);

            if ($oldOwner && $newOwner) {
                Activity::create([
                    'opportunity_id' => $opportunity->id,
                    'type' => 'note',
                    'subject' => 'Owner Changed',
                    'body' => sprintf(
                        'Reassigned from "%s" to "%s" by %s',
                        $oldOwner->name,
                        $newOwner->name,
                        auth()->user()->name ?? 'System'
                    ),
                    'owner_id' => auth()->id() ?? $newOwnerId,
                    'done_at' => now(),
                ]);
            }
        }

        // Log significant amount changes (> 10% change)
        if ($opportunity->isDirty('amount')) {
            $oldAmount = floatval($opportunity->getOriginal('amount'));
            $newAmount = floatval($opportunity->amount);

            $percentChange = $oldAmount > 0
                ? abs((($newAmount - $oldAmount) / $oldAmount) * 100)
                : 0;

            if ($percentChange >= 10) {
                Activity::create([
                    'opportunity_id' => $opportunity->id,
                    'type' => 'note',
                    'subject' => 'Amount Changed',
                    'body' => sprintf(
                        'Amount changed from $%s to $%s (%.1f%% change) by %s',
                        number_format($oldAmount, 2),
                        number_format($newAmount, 2),
                        $percentChange,
                        auth()->user()->name ?? 'System'
                    ),
                    'owner_id' => auth()->id() ?? $opportunity->owner_id,
                    'done_at' => now(),
                ]);
            }
        }
    }

    public function deleted(Opportunity $opportunity): void
    {
        // Log deletion
        Activity::create([
            'opportunity_id' => $opportunity->id,
            'type' => 'note',
            'subject' => 'Opportunity Deleted',
            'body' => sprintf(
                'Opportunity deleted by %s',
                auth()->user()->name ?? 'System'
            ),
            'owner_id' => auth()->id() ?? $opportunity->owner_id,
            'done_at' => now(),
        ]);
    }
}
