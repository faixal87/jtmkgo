<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use SplFileObject;
use Throwable;

class UserCsvImportService
{
    private const EXPECTED_COLUMNS = [
        'name',
        'ic_number',
        'email',
        'phone',
        'date_of_birth',
        'profile_photo',
        'department',
        'position',
        'grade',
        'mbot_membership',
        'bem_membership',
        'account_status',
        'is_super_admin',
        'password',
    ];

    /**
     * @return array{created: int, updated: int, skipped: int, errors: array<int, string>}
     */
    public function import(UploadedFile $file, ?int $adminId): array
    {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $csv = new SplFileObject($file->getRealPath());
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $headers = null;

        foreach ($csv as $lineNumber => $columns) {
            if ($this->isEmptyRow($columns)) {
                continue;
            }

            if ($headers === null) {
                $headers = $this->normalizeHeaders($columns);

                if (! in_array('ic_number', $headers, true)) {
                    $summary['errors'][] = 'CSV header must include ic_number.';

                    return $summary;
                }

                continue;
            }

            $rowNumber = $lineNumber + 1;
            $row = $this->combineRow($headers, $columns);

            $result = $this->importRow($row, $rowNumber, $adminId);

            if ($result === 'created') {
                $summary['created']++;
            } elseif ($result === 'updated') {
                $summary['updated']++;
            } else {
                $summary['skipped']++;
                $summary['errors'][] = $result;
            }
        }

        if ($headers === null) {
            $summary['errors'][] = 'CSV file is empty.';
        }

        return $summary;
    }

    /**
     * @param array<string, string|null> $row
     */
    private function importRow(array $row, int $rowNumber, ?int $adminId): string
    {
        $rawIcNumber = trim((string) ($row['ic_number'] ?? ''));
        $icNumber = $this->normalizeIcNumber($row['ic_number'] ?? null);

        if (! $icNumber) {
            return "Row {$rowNumber}: IC number is required.";
        }

        $user = User::query()
            ->where('ic_number', $icNumber)
            ->when($rawIcNumber !== $icNumber, fn ($query) => $query->orWhere('ic_number', $rawIcNumber))
            ->first();
        $isNewUser = $user === null;
        $statusProvided = filled($row['account_status'] ?? null);

        $dateOfBirth = $this->parseDate($row['date_of_birth'] ?? null);

        if ($dateOfBirth === false) {
            return "Row {$rowNumber}: Date of birth must be a valid date.";
        }

        $accountStatus = $this->resolveAccountStatus(
            $row['account_status'] ?? null,
            $isNewUser ? 'approved' : $user->account_status
        );

        if ($accountStatus === null) {
            return "Row {$rowNumber}: Account status must be pending, approved, rejected, or inactive.";
        }

        $isSuperAdmin = $this->resolveBoolean(
            $row['is_super_admin'] ?? null,
            $user?->is_super_admin ?? false
        );

        if ($user?->is_super_admin) {
            $isSuperAdmin = true;
        }

        if ($isSuperAdmin) {
            $accountStatus = 'approved';
        }

        $attributes = [
            'name' => $row['name'] ?: $user?->name,
            'ic_number' => $icNumber,
            'email' => strtolower((string) ($row['email'] ?: $user?->email)),
            'phone' => $row['phone'] ?? $user?->phone,
            'date_of_birth' => $dateOfBirth ?? $user?->date_of_birth,
            'department' => $row['department'] ?? $user?->department,
            'position' => $row['position'] ?? $user?->position,
            'grade' => $row['grade'] ?? $user?->grade,
            'mbot_membership' => $row['mbot_membership'] ?? $user?->mbot_membership,
            'bem_membership' => $row['bem_membership'] ?? $user?->bem_membership,
            'is_super_admin' => $isSuperAdmin,
        ];

        if ($row['profile_photo'] ?? null) {
            $attributes['profile_photo'] = $row['profile_photo'];
        } elseif ($isNewUser) {
            $attributes['profile_photo'] = null;
        }

        if ($isNewUser || $statusProvided || $isSuperAdmin || $user?->is_super_admin) {
            $attributes['account_status'] = $accountStatus;

            if ($accountStatus === 'approved') {
                $attributes['approved_at'] = $user?->approved_at ?? now();
                $attributes['approved_by'] = $user?->approved_by ?? $adminId;
            } else {
                $attributes['approved_at'] = null;
                $attributes['approved_by'] = null;
            }
        }

        $validator = Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'ic_number' => ['required', 'string', 'max:20'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'profile_photo' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'mbot_membership' => ['nullable', 'string', 'max:255'],
            'bem_membership' => ['nullable', 'string', 'max:255'],
            'account_status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected', 'inactive'])],
            'is_super_admin' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return "Row {$rowNumber}: ".$validator->errors()->first();
        }

        try {
            if ($isNewUser) {
                User::query()->create(array_merge($attributes, [
                    'password' => Hash::make($row['password'] ?: $icNumber),
                    'force_password_change' => ! filled($row['password']),
                ]));

                return 'created';
            }

            $user->forceFill($attributes)->save();

            return 'updated';
        } catch (Throwable $exception) {
            return "Row {$rowNumber}: {$exception->getMessage()}";
        }
    }

    /**
     * @param array<int, mixed> $columns
     * @return array<int, string>
     */
    private function normalizeHeaders(array $columns): array
    {
        return array_map(function (mixed $column): string {
            $header = strtolower(trim((string) $column));
            $header = str_replace([' ', '-'], '_', $header);

            return preg_replace('/^\xEF\xBB\xBF/', '', $header) ?: '';
        }, $columns);
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, mixed> $columns
     * @return array<string, string|null>
     */
    private function combineRow(array $headers, array $columns): array
    {
        $row = array_fill_keys(self::EXPECTED_COLUMNS, null);

        foreach ($headers as $index => $header) {
            if (! in_array($header, self::EXPECTED_COLUMNS, true)) {
                continue;
            }

            $value = $columns[$index] ?? null;
            $value = is_string($value) ? trim($value) : $value;

            $row[$header] = $value === '' ? null : $value;
        }

        return $row;
    }

    /**
     * @param array<int, mixed>|false $columns
     */
    private function isEmptyRow(array|false $columns): bool
    {
        if ($columns === false || $columns === [null]) {
            return true;
        }

        return collect($columns)
            ->filter(fn (mixed $value) => trim((string) $value) !== '')
            ->isEmpty();
    }

    private function normalizeIcNumber(?string $icNumber): ?string
    {
        if (! $icNumber) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $icNumber);

        return $normalized ?: null;
    }

    private function parseDate(?string $date): string|null|false
    {
        if (! $date) {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $date);
            } catch (Throwable) {
                $parsed = false;
            }

            if ($parsed && $parsed->format($format) === $date) {
                return $parsed->toDateString();
            }
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (Throwable) {
            return false;
        }
    }

    private function resolveAccountStatus(?string $status, string $default): ?string
    {
        if (! $status) {
            return $default;
        }

        $status = strtolower(trim($status));

        return in_array($status, ['pending', 'approved', 'rejected', 'inactive'], true)
            ? $status
            : null;
    }

    private function resolveBoolean(?string $value, bool $default): bool
    {
        if ($value === null || trim($value) === '') {
            return $default;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'y'], true);
    }
}
