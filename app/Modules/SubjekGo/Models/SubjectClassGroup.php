<?php

namespace App\Modules\SubjekGo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectClassGroup extends Model
{
    protected $table = 'subjek_go_subject_class_groups';

    protected $fillable = [
        'offered_subject_id',
        'class_group_id',
    ];

    public function offeredSubject(): BelongsTo
    {
        return $this->belongsTo(OfferedSubject::class);
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

}
