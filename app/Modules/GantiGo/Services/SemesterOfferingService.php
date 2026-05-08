<?php

namespace App\Modules\GantiGo\Services;

use App\Models\User;
use App\Modules\GantiGo\Models\ClassGroup;
use App\Modules\GantiGo\Models\Course;
use App\Modules\GantiGo\Models\MasterClassGroup;
use App\Modules\GantiGo\Models\MasterCourse;
use App\Modules\GantiGo\Models\Semester;
use App\Modules\GantiGo\Models\SemesterClassGroup;
use App\Modules\GantiGo\Models\SemesterCourse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SemesterOfferingService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createCourseOffering(array $data, User $user): Course
    {
        $masterCourse = $this->masterCourseFromData($data);

        return $this->syncCourseRecord(
            Semester::findOrFail((int) $data['semester_id']),
            $masterCourse,
            (bool) ($data['is_active'] ?? true),
            $user,
            $data['class_name'] ?? null
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCourseOffering(Course $course, array $data, User $user): Course
    {
        $masterCourse = $this->masterCourseFromData($data, $course->masterCourse);

        return $this->syncCourseRecord(
            Semester::findOrFail((int) $data['semester_id']),
            $masterCourse,
            (bool) ($data['is_active'] ?? false),
            $user,
            $data['class_name'] ?? null,
            $course
        );
    }

    public function toggleCourseOffering(Course $course): Course
    {
        $isOffered = ! $course->is_active;
        $course->forceFill(['is_active' => $isOffered])->save();

        if ($course->master_course_id) {
            SemesterCourse::updateOrCreate(
                [
                    'semester_id' => $course->semester_id,
                    'master_course_id' => $course->master_course_id,
                ],
                ['is_offered' => $isOffered]
            );
        }

        return $course;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createClassGroupOffering(array $data): ClassGroup
    {
        $masterClassGroup = $this->masterClassGroupFromData($data);

        return $this->syncClassGroupRecord(
            Semester::findOrFail((int) $data['semester_id']),
            $masterClassGroup,
            (bool) ($data['is_active'] ?? true)
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateClassGroupOffering(ClassGroup $classGroup, array $data): ClassGroup
    {
        $masterClassGroup = $this->masterClassGroupFromData($data, $classGroup->masterClassGroup);

        return $this->syncClassGroupRecord(
            Semester::findOrFail((int) $data['semester_id']),
            $masterClassGroup,
            (bool) ($data['is_active'] ?? false),
            $classGroup
        );
    }

    public function toggleClassGroupOffering(ClassGroup $classGroup): ClassGroup
    {
        $isOffered = ! $classGroup->is_active;
        $classGroup->forceFill(['is_active' => $isOffered])->save();

        if ($classGroup->master_class_group_id) {
            SemesterClassGroup::updateOrCreate(
                [
                    'semester_id' => $classGroup->semester_id,
                    'master_class_group_id' => $classGroup->master_class_group_id,
                ],
                ['is_offered' => $isOffered]
            );
        }

        return $classGroup;
    }

    /**
     * @param  array<int, int|string>  $masterCourseIds
     * @param  array<int, int|string>  $masterClassGroupIds
     */
    public function syncSemesterOfferings(Semester $semester, array $masterCourseIds, array $masterClassGroupIds, User $user): void
    {
        $courseIds = collect($masterCourseIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $classGroupIds = collect($masterClassGroupIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        SemesterCourse::query()
            ->where('semester_id', $semester->id)
            ->when($courseIds->isNotEmpty(), fn ($query) => $query->whereNotIn('master_course_id', $courseIds))
            ->update(['is_offered' => false, 'updated_at' => now()]);

        Course::query()
            ->where('semester_id', $semester->id)
            ->when($courseIds->isNotEmpty(), fn ($query) => $query->whereNotIn('master_course_id', $courseIds))
            ->update(['is_active' => false, 'updated_at' => now()]);

        MasterCourse::query()
            ->whereIn('id', $courseIds)
            ->where('is_active', true)
            ->get()
            ->each(fn (MasterCourse $masterCourse) => $this->syncCourseRecord($semester, $masterCourse, true, $user));

        SemesterClassGroup::query()
            ->where('semester_id', $semester->id)
            ->when($classGroupIds->isNotEmpty(), fn ($query) => $query->whereNotIn('master_class_group_id', $classGroupIds))
            ->update(['is_offered' => false, 'updated_at' => now()]);

        ClassGroup::query()
            ->where('semester_id', $semester->id)
            ->when($classGroupIds->isNotEmpty(), fn ($query) => $query->whereNotIn('master_class_group_id', $classGroupIds))
            ->update(['is_active' => false, 'updated_at' => now()]);

        MasterClassGroup::query()
            ->whereIn('id', $classGroupIds)
            ->where('is_active', true)
            ->get()
            ->each(fn (MasterClassGroup $masterClassGroup) => $this->syncClassGroupRecord($semester, $masterClassGroup, true));
    }

    public function previousSemesterFor(Semester $semester): ?Semester
    {
        return Semester::query()
            ->where('start_date', '<', $semester->start_date)
            ->orderByDesc('start_date')
            ->first();
    }

    /**
     * @return Collection<int, int>
     */
    public function previousOfferedCourseIds(Semester $semester): Collection
    {
        $previousSemester = $this->previousSemesterFor($semester);

        if (! $previousSemester) {
            return collect();
        }

        return SemesterCourse::query()
            ->where('semester_id', $previousSemester->id)
            ->where('is_offered', true)
            ->pluck('master_course_id');
    }

    /**
     * @return Collection<int, int>
     */
    public function previousOfferedClassGroupIds(Semester $semester): Collection
    {
        $previousSemester = $this->previousSemesterFor($semester);

        if (! $previousSemester) {
            return collect();
        }

        return SemesterClassGroup::query()
            ->where('semester_id', $previousSemester->id)
            ->where('is_offered', true)
            ->pluck('master_class_group_id');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function masterCourseFromData(array $data, ?MasterCourse $fallback = null): MasterCourse
    {
        $courseCode = strtoupper(trim((string) $data['course_code']));
        $programmeId = Arr::get($data, 'programme_id') ?: null;

        $masterCourse = MasterCourse::query()->firstOrNew([
            'course_code' => $courseCode,
            'programme_id' => $programmeId,
        ]);

        if (! $masterCourse->exists && $fallback && $fallback->course_code === $courseCode && (int) $fallback->programme_id === (int) $programmeId) {
            $masterCourse = $fallback;
        }

        $masterCourse->fill([
            'course_code' => $courseCode,
            'course_name' => $data['course_name'],
            'programme_id' => $programmeId,
            'is_active' => true,
        ])->save();

        return $masterCourse;
    }

    private function syncCourseRecord(Semester $semester, MasterCourse $masterCourse, bool $isOffered, User $user, ?string $className = null, ?Course $course = null): Course
    {
        SemesterCourse::updateOrCreate(
            [
                'semester_id' => $semester->id,
                'master_course_id' => $masterCourse->id,
            ],
            ['is_offered' => $isOffered]
        );

        $course ??= Course::query()->firstOrNew([
            'semester_id' => $semester->id,
            'master_course_id' => $masterCourse->id,
        ]);

        $course->fill([
            'master_course_id' => $masterCourse->id,
            'semester_id' => $semester->id,
            'programme_id' => $masterCourse->programme_id,
            'course_code' => $masterCourse->course_code,
            'course_name' => $masterCourse->course_name,
            'class_name' => $className,
            'is_active' => $isOffered,
            'created_by' => $course->created_by ?: $user->id,
        ])->save();

        return $course;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function masterClassGroupFromData(array $data, ?MasterClassGroup $fallback = null): MasterClassGroup
    {
        $classGroupName = strtoupper(trim((string) $data['class_name']));
        $programmeId = (int) $data['programme_id'];

        $masterClassGroup = MasterClassGroup::query()->firstOrNew([
            'programme_id' => $programmeId,
            'class_group_name' => $classGroupName,
        ]);

        if (! $masterClassGroup->exists && $fallback && $fallback->class_group_name === $classGroupName && (int) $fallback->programme_id === $programmeId) {
            $masterClassGroup = $fallback;
        }

        $masterClassGroup->fill([
            'programme_id' => $programmeId,
            'class_group_name' => $classGroupName,
            'is_active' => true,
        ])->save();

        return $masterClassGroup;
    }

    private function syncClassGroupRecord(Semester $semester, MasterClassGroup $masterClassGroup, bool $isOffered, ?ClassGroup $classGroup = null): ClassGroup
    {
        SemesterClassGroup::updateOrCreate(
            [
                'semester_id' => $semester->id,
                'master_class_group_id' => $masterClassGroup->id,
            ],
            ['is_offered' => $isOffered]
        );

        $classGroup ??= ClassGroup::query()->firstOrNew([
            'semester_id' => $semester->id,
            'master_class_group_id' => $masterClassGroup->id,
        ]);

        $classGroup->fill([
            'master_class_group_id' => $masterClassGroup->id,
            'programme_id' => $masterClassGroup->programme_id,
            'semester_id' => $semester->id,
            'class_name' => $masterClassGroup->class_group_name,
            'is_active' => $isOffered,
        ])->save();

        return $classGroup;
    }
}
