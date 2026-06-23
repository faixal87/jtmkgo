<?php

namespace App\Modules\ProgramGo\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramActivityCollaborator extends Model
{
    protected $table = 'program_go_activity_collaborators';

    protected $fillable = [
        'program_activity_id',
        'user_id',
        'role',
        'can_edit',
        'can_submit',
        'added_by',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProgramActivity::class, 'program_activity_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    protected function casts(): array
    {
        return [
            'can_edit' => 'boolean',
            'can_submit' => 'boolean',
        ];
    }
}
