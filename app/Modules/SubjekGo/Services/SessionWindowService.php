<?php

namespace App\Modules\SubjekGo\Services;

use App\Modules\SubjekGo\Models\Session;

class SessionWindowService
{
    public function current(): ?Session
    {
        return Session::query()
            ->orderByRaw("FIELD(status, 'open', 'draft', 'closed', 'archived')")
            ->orderByDesc('open_at')
            ->orderByDesc('created_at')
            ->first();
    }

    public function openForSelection(): ?Session
    {
        return Session::query()
            ->open()
            ->where(function ($query): void {
                $query->whereNull('open_at')->orWhere('open_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('close_at')->orWhere('close_at', '>=', now());
            })
            ->orderByDesc('open_at')
            ->orderByDesc('created_at')
            ->first();
    }
}
