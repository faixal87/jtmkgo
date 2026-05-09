<?php

namespace App\Modules\PhotoRepository\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\PhotoRepository\Models\MediaDownloadLog;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('view-photo-repository');

        $monthlyDownloads = $this->monthlyDownloads();
        $storageUsageBytes = $this->storageUsageBytes();

        return view('photo-repository.admin.analytics', [
            'totalDownloads' => MediaDownloadLog::query()->count(),
            'downloadsThisMonth' => MediaDownloadLog::query()
                ->where('downloaded_at', '>=', now()->startOfMonth())
                ->count(),
            'totalViews' => MediaPhoto::query()->sum('view_count'),
            'storageUsageBytes' => $storageUsageBytes,
            'storageUsage' => $this->formatBytes($storageUsageBytes),
            'monthlyDownloads' => $monthlyDownloads,
            'maxMonthlyDownloads' => max(1, max(array_column($monthlyDownloads, 'total'))),
            'topDownloads' => MediaPhoto::query()
                ->with(['profile', 'category'])
                ->where('download_count', '>', 0)
                ->orderByDesc('download_count')
                ->latest()
                ->limit(8)
                ->get(),
            'mostAccessedPhotos' => MediaPhoto::query()
                ->with(['profile', 'category'])
                ->where(function ($query): void {
                    $query
                        ->where('view_count', '>', 0)
                        ->orWhere('download_count', '>', 0);
                })
                ->orderByRaw('(COALESCE(view_count, 0) + COALESCE(download_count, 0)) desc')
                ->latest()
                ->limit(8)
                ->get(),
            'categoryDownloads' => MediaPhoto::query()
                ->select('media_category_id')
                ->selectRaw('SUM(download_count) as total_downloads')
                ->with('category')
                ->groupBy('media_category_id')
                ->orderByDesc('total_downloads')
                ->limit(6)
                ->get(),
        ]);
    }

    /**
     * @return list<array{month: string, label: string, total: int}>
     */
    private function monthlyDownloads(): array
    {
        $start = now()->subMonths(11)->startOfMonth();

        $downloads = MediaDownloadLog::query()
            ->selectRaw("DATE_FORMAT(COALESCE(downloaded_at, created_at), '%Y-%m') as month")
            ->selectRaw('COUNT(*) as total')
            ->where(function ($query) use ($start): void {
                $query
                    ->where('downloaded_at', '>=', $start)
                    ->orWhere(function ($query) use ($start): void {
                        $query->whereNull('downloaded_at')->where('created_at', '>=', $start);
                    });
            })
            ->groupBy(DB::raw("DATE_FORMAT(COALESCE(downloaded_at, created_at), '%Y-%m')"))
            ->pluck('total', 'month');

        return collect(range(0, 11))
            ->map(function (int $offset) use ($start, $downloads): array {
                $month = $start->copy()->addMonths($offset);
                $key = $month->format('Y-m');

                return [
                    'month' => $key,
                    'label' => $month->format('M y'),
                    'total' => (int) ($downloads[$key] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function storageUsageBytes(): int
    {
        return (int) Cache::remember('photo-repository.analytics.storage-usage', now()->addMinutes(10), function (): int {
            try {
                return collect(Storage::disk('public')->allFiles('photo-repository'))
                    ->sum(fn (string $path): int => Storage::disk('public')->size($path));
            } catch (\Throwable) {
                return (int) MediaPhoto::query()->sum('file_size');
            }
        });
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 2).' '.$units[$power];
    }
}
