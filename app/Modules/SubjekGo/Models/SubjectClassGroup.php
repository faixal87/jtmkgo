<?php

namespace App\Modules\SubjekGo\Models;

use App\Models\User;
use App\Modules\GantiGo\Models\ClassGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectClassGroup extends Model
{
    protected $table = 'subjek_go_subject_class_groups';

    protected $fillable = [
        'offered_subject_id',
        'class_group_id',
        'academic_advisor_user_id',
    ];

    public function offeredSubject(): BelongsTo
    {
        return $this->belongsTo(OfferedSubject::class);
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function academicAdvisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'academic_advisor_user_id');
    }
}
