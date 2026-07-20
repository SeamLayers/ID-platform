<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Models\BusinessCard;
use App\Models\CardInteraction;
use App\Models\Company;
use App\Models\CompanyBranch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeProject;
use App\Models\Project;
use Illuminate\Http\Request;

/**
 * Real dashboard analytics.
 *
 * The web dashboard's home screen used to show hardcoded charts (fake card
 * scans, pipeline, regions, activity). This endpoint replaces every one of
 * those with real aggregates computed from business_cards + card_interactions,
 * tenancy-scoped so an owner only ever sees their own company's numbers.
 *
 * Entity counts (companies/branches/employees/...) are returned here too, under
 * `entities`. The dashboard used to derive them by calling six list endpoints
 * with per_page=1 and counting the rows that came back — which reported "1" for
 * every populated table, because the paginator's real `total` never survives
 * the response envelope.
 */
class OverviewController extends Controller
{
    public function __construct()
    {
        // Any dashboard user who can see cards can see the aggregate figures.
        $this->middleware('permission:business_card.view');
    }

    public function overview(Request $request)
    {
        $user = $request->user();
        $isSuperadmin = $user->hasRole('superadmin');

        // Owner tenancy scope: only their own company/companies. Superadmin is
        // unrestricted (null scope = no whereIn filter at all).
        $companyIds = $isSuperadmin
            ? null
            : Company::where('user_id', $user->id)->pluck('id');

        $employeeIds = $isSuperadmin
            ? null
            : Employee::whereIn('company_id', $companyIds ?? [])->pluck('id');

        // --- Entity counts ---------------------------------------------------
        // Real totals for the six count tiles. Owners are scoped to the
        // companies they own; a superadmin gets platform-wide numbers.
        //
        // These MUST match what the corresponding list page shows, so they use
        // the same unconditional whereHas('company') the list endpoints use: it
        // carries the parent's soft-delete scope, excluding rows orphaned by a
        // deleted company (delete doesn't cascade). Counting those would make a
        // superadmin's "Branches" tile disagree with /branches.
        //
        // NOTE: projects and employee_projects have NO deleted_at column, so
        // their notDeleted() scopes would produce invalid SQL — don't use them.
        $ownedOnly = function ($q) use ($isSuperadmin, $user) {
            $q->when(! $isSuperadmin, fn ($c) => $c->where('user_id', $user->id));
        };

        $entities = [
            'companies'   => $isSuperadmin
                ? Company::count()
                : Company::where('user_id', $user->id)->count(),
            'branches'    => CompanyBranch::whereHas('company', $ownedOnly)->count(),
            'departments' => Department::whereHas('company', $ownedOnly)->count(),
            'employees'   => Employee::whereHas('company', $ownedOnly)->count(),
            'projects'    => Project::whereHas('company', $ownedOnly)->count(),
            'assignments' => EmployeeProject::whereHas(
                'employee',
                fn ($e) => $e->whereHas('company', $ownedOnly)
            )->count(),
        ];

        // --- Cards -----------------------------------------------------------
        $cards = BusinessCard::query()
            ->when($employeeIds !== null, fn ($q) => $q->whereIn('employee_id', $employeeIds))
            ->with(['employee:id,name,branch_id', 'employee.branch:id,name'])
            ->get(['id', 'employee_id', 'status', 'is_active']);

        $cardIds = $cards->pluck('id');

        $statusKeys = ['draft', 'submitted', 'approved', 'published', 'rejected'];
        $cardStatus = array_fill_keys($statusKeys, 0);
        foreach ($cards as $card) {
            $s = $card->status ?: 'draft';
            $cardStatus[$s] = ($cardStatus[$s] ?? 0) + 1;
        }

        // --- Regions (live cards grouped by branch) ---------------------------
        // Live, not merely status='published'. Since employees can reopen a
        // published card to work on a new version, "published" is now narrower
        // than "reachable by the public": a reopened card sits in draft while
        // its snapshot keeps serving. Counting on status made the chart drop a
        // person every time one of them started an edit.
        $regions = $cards
            ->filter(fn ($c) => $c->isPubliclyVisible())
            ->groupBy(fn ($c) => optional(optional($c->employee)->branch)->name ?: '—')
            ->map(fn ($group, $name) => ['name' => $name, 'value' => $group->count()])
            ->values()
            ->sortByDesc('value')
            ->take(6)
            ->values();

        // --- Interactions ----------------------------------------------------
        // Pulled as a small collection and aggregated in PHP so the same code
        // works identically on sqlite (local) and MySQL (production) without
        // driver-specific date functions.
        $interactions = CardInteraction::query()
            ->when($employeeIds !== null, fn ($q) => $q->whereIn('business_card_id', $cardIds))
            ->latest()
            ->get(['id', 'business_card_id', 'interaction_type', 'source', 'created_at']);

        $bySource = ['QR' => 0, 'NFC' => 0, 'LINK' => 0];
        $views = 0;
        foreach ($interactions as $it) {
            $src = strtoupper((string) $it->source);
            if (isset($bySource[$src])) {
                $bySource[$src]++;
            }
            if ($it->interaction_type === 'view') {
                $views++;
            }
        }
        $scans = $bySource['QR'] + $bySource['NFC'];

        // --- Monthly time-series (last 7 calendar months) --------------------
        $months = [];
        $cursor = now()->startOfMonth();
        for ($i = 6; $i >= 0; $i--) {
            $m = (clone $cursor)->subMonths($i);
            $months[$m->format('Y-m')] = [
                'month' => $m->format('Y-m'),
                'label' => $m->format('M'),
                'scans' => 0,
                'views' => 0,
            ];
        }
        foreach ($interactions as $it) {
            $key = optional($it->created_at)->format('Y-m');
            if ($key === null || ! isset($months[$key])) {
                continue;
            }
            $src = strtoupper((string) $it->source);
            if ($src === 'QR' || $src === 'NFC') {
                $months[$key]['scans']++;
            }
            if ($it->interaction_type === 'view') {
                $months[$key]['views']++;
            }
        }

        // --- Recent activity (latest 8 real interactions) --------------------
        $cardNameById = $cards->keyBy('id');
        $recent = $interactions->take(8)->map(function ($it) use ($cardNameById) {
            $card = $cardNameById->get($it->business_card_id);
            return [
                'type'   => $it->interaction_type,
                'source' => strtoupper((string) $it->source),
                'name'   => optional(optional($card)->employee)->name ?: '—',
                'at'     => optional($it->created_at)->toIso8601String(),
            ];
        })->values();

        return ResponseHelper::success([
            'entities' => $entities,
            'cards' => [
                'total'     => $cards->count(),
                'published' => $cardStatus['published'],
                // "Active" means live to the public — which is exactly what
                // isPubliclyVisible() decides, including the case of a card
                // that has been reopened for editing but is still serving its
                // published snapshot. is_active alone defaults to true at
                // creation, so the original figure counted untouched drafts.
                'active'    => $cards->filter(fn ($c) => $c->isPubliclyVisible())->count(),
                // Awaiting the employee's decision — the number an owner acts on.
                'pending'   => $cardStatus['submitted'],
                // Rollout coverage: employees who still have no card at all.
                'employees_without_card' => Employee::whereHas('company', $ownedOnly)
                    ->doesntHave('businessCard')
                    ->count(),
            ],
            'interactions' => [
                'total' => $interactions->count(),
                'views' => $views,
                'scans' => $scans,
            ],
            'sources'     => $bySource,
            'monthly'     => array_values($months),
            'regions'     => $regions,
            'card_status' => $cardStatus,
            'recent'      => $recent,
        ], __('messages.data_retrieved'));
    }
}
