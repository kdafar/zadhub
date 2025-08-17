<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowRunStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_run_id',
        'screen_id',
        'input',          // user submission JSON
        'output',         // screen payload JSON
        'next_screen_id',
        'error_message',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];

    /** Relationships */
    public function run()
    {
        return $this->belongsTo(FlowRun::class, 'flow_run_id');
    }
}
