<?php

namespace App\Jobs;

use App\Models\AutomationStep;
use App\Services\Automations\AutomationActionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteAutomationStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $stepId,
        public string $recipientPhone,
        public array $data = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AutomationActionService $actionService): void
    {
        $step = AutomationStep::find($this->stepId);

        if (! $step) {
            return;
        }

        $actionService->execute($step, $this->recipientPhone, $this->data);
    }
}
