<?php

namespace App\Modules\ProgramGo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\ProgramGo\Models\ProgramActivity;
use App\Modules\ProgramGo\Services\ProgramGoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BudgetMonitoringController extends Controller
{
    public function __invoke(Request $request, ProgramGoService $programGo): View
    {
        abort_unless($programGo->canViewAdminInsights($request->user()), 403);

        $yearOptions = $this->yearOptions();
        $requestedYear = (int) $request->query('year', now()->year);
        $year = $yearOptions->contains($requestedYear) ? $requestedYear : (int) $yearOptions->first();
        $month = $this->monthFilter($request);
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', ProgramActivity::STATUS_APPROVED);

        if (! array_key_exists($status, ProgramActivity::statuses()) && $status !== 'all') {
            $status = ProgramActivity::STATUS_APPROVED;
        }

        [$sort, $direction] = $this->sortState($request);
        $sortMap = [
            'reference_no' => 'reference_no',
            'activity_name' => 'activity_name',
            'activity_date' => 'activity_date',
            'venue' => 'venue',
            'activity_code' => 'activity_code',
            'total_budget' => 'total_budget',
            'status' => 'status',
        ];

        $listQuery = ProgramActivity::query()
            ->with('lecturer:id,name')
            ->whereYear('activity_date', $year)
            ->when($month !== 'all', fn (Builder $query) => $query->whereMonth('activity_date', (int) $month))
            ->search($search)
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status));

        if ($sort === 'lecturer') {
            $listQuery
                ->leftJoin('users', 'program_go_activities.user_id', '=', 'users.id')
                ->select('program_go_activities.*')
                ->orderBy('users.name', $direction);
        } else {
            $listQuery->orderBy($sortMap[$sort] ?? 'activity_date', $direction);
        }

        $activities = $listQuery
            ->orderByDesc('program_go_activities.id')
            ->paginate(20)
            ->withQueryString();

        $approvedYearQuery = ProgramActivity::query()
            ->approved()
            ->whereYear('activity_date', $year);

        $filteredApprovedQuery = (clone $approvedYearQuery)
            ->when($month !== 'all', fn (Builder $query) => $query->whereMonth('activity_date', (int) $month));

        $monthForCard = $month !== 'all' ? (int) $month : (int) now()->month;
        $quarterForCard = (int) ceil($monthForCard / 3);
        $quarterMonths = $this->quarterMonths($quarterForCard);
        $yearBudgetUsage = (float) (clone $approvedYearQuery)->sum('total_budget');
        $monthBudgetUsage = (float) (clone $approvedYearQuery)
            ->whereMonth('activity_date', $monthForCard)
            ->sum('total_budget');
        $quarterBudgetUsage = (float) (clone $approvedYearQuery)
            ->whereIn(DB::raw('MONTH(activity_date)'), $quarterMonths)
            ->sum('total_budget');

        return view('program-go.admin.budget-monitoring', [
            'activities' => $activities,
            'kpis' => [
                'yearBudgetUsage' => $yearBudgetUsage,
                'monthBudgetUsage' => $monthBudgetUsage,
                'quarterBudgetUsage' => $quarterBudgetUsage,
                'approvedActivities' => (clone $filteredApprovedQuery)->count(),
            ],
            'monthlyBudget' => (clone $approvedYearQuery)
                ->selectRaw('MONTH(activity_date) as month_number, SUM(total_budget) as total')
                ->whereNotNull('activity_date')
                ->groupByRaw('MONTH(activity_date)')
                ->orderByRaw('MONTH(activity_date)')
                ->pluck('total', 'month_number'),
            'monthlyActivityCounts' => (clone $approvedYearQuery)
                ->selectRaw('MONTH(activity_date) as month_number, COUNT(*) as total')
                ->whereNotNull('activity_date')
                ->groupByRaw('MONTH(activity_date)')
                ->orderByRaw('MONTH(activity_date)')
                ->pluck('total', 'month_number'),
            'quarterlyBudget' => (clone $approvedYearQuery)
                ->selectRaw('QUARTER(activity_date) as quarter_number, SUM(total_budget) as total')
                ->whereNotNull('activity_date')
                ->groupByRaw('QUARTER(activity_date)')
                ->orderByRaw('QUARTER(activity_date)')
                ->pluck('total', 'quarter_number'),
            'filters' => compact('year', 'month', 'search', 'status', 'sort', 'direction', 'monthForCard', 'quarterForCard'),
            'yearOptions' => $yearOptions,
            'statuses' => ProgramActivity::statuses(),
        ]);
    }

    /**
     * @return Collection<int, int>
     */
    private function yearOptions(): Collection
    {
        $years = ProgramActivity::query()
            ->whereNotNull('activity_date')
            ->selectRaw('DISTINCT YEAR(activity_date) as activity_year')
            ->orderByDesc('activity_year')
            ->pluck('activity_year')
            ->map(fn ($year) => (int) $year)
            ->values();

        return $years
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values();
    }

    private function monthFilter(Request $request): string
    {
        $month = (string) $request->query('month', 'all');

        if ($month === 'all') {
            return 'all';
        }

        $monthNumber = (int) $month;

        return $monthNumber >= 1 && $monthNumber <= 12 ? (string) $monthNumber : 'all';
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function sortState(Request $request): array
    {
        $allowedSorts = [
            'reference_no',
            'activity_name',
            'activity_date',
            'venue',
            'lecturer',
            'activity_code',
            'total_budget',
            'status',
        ];
        $sort = (string) $request->query('sort', 'activity_date');
        $direction = (string) $request->query('direction', 'desc');

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'activity_date';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        return [$sort, $direction];
    }

    /**
     * @return array<int, int>
     */
    private function quarterMonths(int $quarter): array
    {
        return match ($quarter) {
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            default => [10, 11, 12],
        };
    }
}
