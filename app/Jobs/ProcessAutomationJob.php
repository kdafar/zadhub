<?php

namespace App\Jobs;

use App\Models\Automation;
use App\Services\Automations\ConditionEvaluatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAutomationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $automationId,
        public string $recipientPhone,
        public array $data = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ConditionEvaluatorService $evaluator): void
    {
        $automation = Automation::with('steps')->find($this->automationId);

        if (! $automation) {
            Log::warning('Automation not found in ProcessAutomationJob', ['automation_id' => $this->automationId]);

            return;
        }

        foreach ($automation->steps as $step) {
            if ($evaluator->allConditionsMet($step->conditions, $this->data)) {
                $job = new ExecuteAutomationStepJob(
                    $step->id,
                    $this->recipientPhone,
                    $this->data
                );

                if ($step->delay_minutes > 0) {
                    $job->delay(now()->addMinutes($step->delay_minutes));
                }

                dispatch($job);
            } else {
                Log::info('Automation step skipped due to unmet conditions', ['step_id' => $step->id]);
            }
        }
    }
}
