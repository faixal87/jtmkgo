<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleUserAccess;
use App\Models\User;
use App\Modules\RubricGrading\Models\GradingSession;
use App\Modules\RubricGrading\Models\Rubric;
use App\Modules\RubricGrading\Models\SessionStudent;
use App\Modules\RubricGrading\Models\StudentScore;
use App\Modules\RubricGrading\Services\RubricGradingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RubricGoPw2TestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $owner = User::query()
                ->where('email', 'faizalyahaya@polimas.edu.my')
                ->first()
                ?? User::query()
                    ->where('account_status', 'approved')
                    ->where('is_super_admin', false)
                    ->orderBy('id')
                    ->firstOrFail();

            $module = Module::query()->updateOrCreate(
                ['slug' => 'rubric-grading'],
                [
                    'name' => 'RubricGo',
                    'icon' => 'RG',
                    'route_prefix' => '/rubric-grading',
                    'description' => 'Rubric template builder, lecturer grading sessions, score exports, and official print forms.',
                    'is_active' => true,
                ]
            );

            ModuleUserAccess::query()->updateOrCreate(
                [
                    'user_id' => $owner->id,
                    'module_id' => $module->id,
                ],
                [
                    'granted_by' => 1,
                    'granted_at' => now(),
                    'is_active' => true,
                ]
            );

            $rubric = Rubric::query()->updateOrCreate(
                [
                    'user_id' => $owner->id,
                    'title' => 'Practical Work 2 - Active Directory Administration',
                    'course_code' => 'DFK40063',
                ],
                [
                    'course_name' => 'Server Administration',
                    'assessment_type' => 'Practical Work',
                ]
            );

            app(RubricGradingService::class)->syncRubricStructure($rubric, [
                'title' => 'Practical Work 2 - Active Directory Administration',
                'course_code' => 'DFK40063',
                'course_name' => 'Server Administration',
                'assessment_type' => 'Practical Work',
                'levels' => [
                    ['label' => 'Excellent', 'value' => 4],
                    ['label' => 'Good', 'value' => 3],
                    ['label' => 'Fair', 'value' => 2],
                    ['label' => 'Poor', 'value' => 1],
                ],
                'criteria' => [
                    [
                        'name' => 'Identification of Existing AD Objects - (a) Display and classification of user and group objects in petronas.local',
                        'weight' => 10,
                        'descriptors' => [
                            'Accurately lists at least 5 users and 2 groups; correctly identifies admin vs standard users with clear screenshots.',
                            'Lists required users/groups but with minor errors in classification or missing labels.',
                            'Partial listing with fewer than 5 users or fewer than 2 groups and unclear classification; screenshots incomplete.',
                            'Unable to identify users/groups correctly or no evidence provided.',
                        ],
                    ],
                    [
                        'name' => 'Creation of Organizational Unit (OU) - (b.i) Creation of ICT_Department under correct domain path',
                        'weight' => 10,
                        'descriptors' => [
                            'OU created correctly under petronas.local and named properly; screenshot provided.',
                            'OU created but naming or structure slightly inaccurate.',
                            'OU created in wrong path or missing screenshot.',
                            'OU not created or incorrect procedure followed.',
                        ],
                    ],
                    [
                        'name' => 'User and Group Configuration - (b.ii-v) User accounts, attributes, and security group setup',
                        'weight' => 15,
                        'descriptors' => [
                            'All 3 users created with correct attributes (Description, Department); ICT_Users created as Global Security and includes all members.',
                            'Users and group created but minor attribute errors or missing one member.',
                            'Only some users/groups created; attributes incomplete.',
                            'No valid users or group created; task not completed.',
                        ],
                    ],
                    [
                        'name' => 'Password Policy Configuration (PSO) - (c.i-ii) Implementation of Fine-Grained Password Policy',
                        'weight' => 15,
                        'descriptors' => [
                            'PSO_ICT created correctly; values are accurate; applied to ICT_Users; verification screenshot included.',
                            'PSO created with minor mistakes such as wrong duration or missing verification screenshot.',
                            'PSO partially created or applied incorrectly.',
                            'No PSO created or incorrect concept used.',
                        ],
                    ],
                ],
            ]);

            $rubric = $rubric->fresh(['criteria', 'levels']);

            $session = GradingSession::query()->updateOrCreate(
                [
                    'rubric_id' => $rubric->id,
                    'user_id' => $owner->id,
                    'name' => 'Sesi 1 2025/2026 - Practical Work 2',
                ],
                [
                    'class_group' => 'DFK40063 S1 2025/2026',
                    'assessment_date' => '2025-10-15',
                ]
            );

            $students = [
                ['name' => 'Ahmad Danish Bin Rahim', 'registration_no' => '03DDT24F1001', 'scores' => [4, 4, 4, 4], 'remarks' => 'Complete evidence and clear screenshots.'],
                ['name' => 'Nur Aisyah Binti Kamal', 'registration_no' => '03DDT24F1002', 'scores' => [3, 3, 3, 3], 'remarks' => 'Good attempt with minor configuration evidence gaps.'],
                ['name' => 'Muhammad Haziq Bin Azman', 'registration_no' => '03DDT24F1003', 'scores' => [2, 3, 2, 2], 'remarks' => 'Needs clearer verification and complete screenshots.'],
            ];

            foreach ($students as $index => $studentData) {
                $student = SessionStudent::query()->updateOrCreate(
                    [
                        'grading_session_id' => $session->id,
                        'registration_no' => $studentData['registration_no'],
                    ],
                    [
                        'name' => $studentData['name'],
                        'remarks' => $studentData['remarks'],
                        'sort_order' => $index + 1,
                    ]
                );

                foreach ($rubric->criteria->values() as $criterionIndex => $criterion) {
                    StudentScore::query()->updateOrCreate(
                        [
                            'session_student_id' => $student->id,
                            'criterion_id' => $criterion->id,
                        ],
                        ['level_value' => $studentData['scores'][$criterionIndex]]
                    );
                }
            }
        });
    }
}
