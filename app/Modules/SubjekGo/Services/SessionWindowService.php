<?php

namespace App\Modules\SubjekGo\Services;

use App\Modules\SubjekGo\Models\Session;
use App\Support\SafeArrayCache;
use Illuminate\Support\Facades\Cache;

class SessionWindowService
{
    private const CURRENT_CACHE_KEY = 'subjek-go.session.current';
    private const OPEN_CACHE_KEY = 'subjek-go.session.open';

    public function current(): ?Session
    {
        $cached = SafeArrayCache::remember(self::CURRENT_CACHE_KEY, now()->addSeconds(30), function (): array {
            return [
                'id' => Session::query()
                    ->orderByRaw("FIELD(status, 'open', 'draft', 'closed', 'archived')")
                    ->orderByDesc('open_at')
                    ->orderByDesc('created_at')
                    ->value('id'),
            ];
        }, ['id']);

        return filled($cached['id'] ?? null) ? Session::query()->find($cached['id']) : null;
    }

    public function openForSelection(): ?Session
    {
        $cached = SafeArrayCache::remember(self::OPEN_CACHE_KEY, now()->addSeconds(30), function (): array {
            return [
                'id' => Session::query()
                    ->open()
                    ->where(function ($query): void {
                        $query->whereNull('open_at')->orWhere('open_at', '<=', now());
                    })
                    ->where(function ($query): void {
                        $query->whereNull('close_at')->orWhere('close_at', '>=', now());
                    })
                    ->orderByDesc('open_at')
                    ->orderByDesc('created_at')
                    ->value('id'),
            ];
        }, ['id']);

        return filled($cached['id'] ?? null) ? Session::query()->find($cached['id']) : null;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CURRENT_CACHE_KEY);
        Cache::forget(self::OPEN_CACHE_KEY);
    }
}
