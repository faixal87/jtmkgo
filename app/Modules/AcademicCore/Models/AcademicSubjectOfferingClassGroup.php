<?php

namespace App\Modules\AcademicCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicSubjectOfferingClassGroup extends Model
{
    protected $table = 'academic_subject_offering_class_groups';

    protected $fillable = [
        'academic_subject_offering_id',
        'academic_class_group_id',
    ];

    public function offering(): BelongsTo
    {
        return $this->belongsTo(AcademicSubjectOffering::class, 'academic_subject_offering_id');
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(AcademicClassGroup::class, 'academic_class_group_id');
    }
}
