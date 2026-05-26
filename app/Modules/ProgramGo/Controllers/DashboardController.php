<?php

namespace App\Modules\ProgramGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ProgramGo\Models\ProgramActivity;
use App\Modules\ProgramGo\Services\ProgramGoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ProgramGoService $programGo): View
    {
        $user = $request->user();
        $canViewInsights = $programGo->canViewAdminInsights($user);

        $ownQuery = ProgramActivity::query()
            ->where('user_id', $user->id);

        $adminQuery = ProgramActivity::query()
            ->approved();

        return view('program-go.dashboard', [
            'canViewInsights' => $canViewInsights,
            'userStats' => [
                'total' => (clone $ownQuery)->count(),
                'pending' => (clone $ownQuery)->where('status', ProgramActivity::STATUS_PENDING)->count(),
                'approved' => (clone $ownQuery)->where('status', ProgramActivity::STATUS_APPROVED)->count(),
                'needsAction' => (clone $ownQuery)->whereIn('status', [ProgramActivity::STATUS_REJECTED, ProgramActivity::STATUS_RETURNED])->count(),
            ],
            'adminStats' => $canViewInsights ? [
                'pendingBudgetVerification' => ProgramActivity::query()->whereIn('status', ProgramActivity::verificationPendingStatuses())->count(),
                'inProgress' => ProgramActivity::query()->where('status', ProgramActivity::STATUS_IN_PROGRESS)->count(),
                'completedAwaitingVerification' => ProgramActivity::query()->where('status', ProgramActivity::STATUS_COMPLETED)->count(),
                'yearBudget' => (clone $adminQuery)->whereYear('approved_at', now()->year)->sum('total_budget'),
            ] : null,
        ]);
    }
}
