<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'staff_short_code')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('staff_short_code', 20)->nullable()->after('grade');
            });
        }

        $usedCodes = DB::table('users')
            ->whereNotNull('staff_short_code')
            ->pluck('staff_short_code')
            ->map(fn ($code) => strtoupper((string) $code))
            ->filter()
            ->flip()
            ->all();

        DB::table('users')
            ->select(['id', 'name', 'staff_short_code'])
            ->where(function ($query): void {
                $query->whereNull('staff_short_code')
                    ->orWhere('staff_short_code', '');
            })
            ->chunkById(200, function ($users) use (&$usedCodes): void {
                foreach ($users as $user) {
                    $baseCode = $this->baseShortCode($user->name);
                    $code = $baseCode;
                    $counter = 2;

                    while (isset($usedCodes[$code])) {
                        $code = $baseCode.$counter;
                        $counter++;
                    }

                    $usedCodes[$code] = true;

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'staff_short_code' => $code,
                            'updated_at' => now(),
                        ]);
                }
            });

        Schema::table('users', function (Blueprint $table): void {
            if (! $this->hasIndex('users', 'users_staff_short_code_unique')) {
                $table->unique('staff_short_code', 'users_staff_short_code_unique');
            }

            if (! $this->hasIndex('users', 'users_staff_directory_status_index')) {
                $table->index(['account_status', 'is_super_admin'], 'users_staff_directory_status_index');
            }

            if (! $this->hasIndex('users', 'users_department_index')) {
                $table->index('department', 'users_department_index');
            }

            if (! $this->hasIndex('users', 'users_grade_index')) {
                $table->index('grade', 'users_grade_index');
            }

            if (! $this->hasIndex('users', 'users_phone_index')) {
                $table->index('phone', 'users_phone_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'staff_short_code')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if ($this->hasIndex('users', 'users_staff_short_code_unique')) {
                $table->dropUnique('users_staff_short_code_unique');
            }

            if ($this->hasIndex('users', 'users_staff_directory_status_index')) {
                $table->dropIndex('users_staff_directory_status_index');
            }

            if ($this->hasIndex('users', 'users_department_index')) {
                $table->dropIndex('users_department_index');
            }

            if ($this->hasIndex('users', 'users_grade_index')) {
                $table->dropIndex('users_grade_index');
            }

            if ($this->hasIndex('users', 'users_phone_index')) {
                $table->dropIndex('users_phone_index');
            }

            $table->dropColumn('staff_short_code');
        });
    }

    private function baseShortCode(?string $name): string
    {
        $normalizedName = strtoupper(trim((string) $name));

        if (str_contains($normalizedName, 'MOHD FAIZAL') && str_contains($normalizedName, 'YAHAYA')) {
            return 'FAI';
        }

        $firstName = preg_split('/\s+/', $normalizedName)[0] ?? '';
        $firstName = preg_replace('/[^A-Z]/', '', strtoupper(Str::ascii($firstName))) ?: 'USR';

        return substr($firstName, 0, 3) ?: 'USR';
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$index]))->isNotEmpty();
    }
};
