<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Models\BusinessCard;
use App\Models\CardInteraction;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;

/**
 * Real dashboard analytics.
 *
 * The web dashboard's home screen used to show hardcoded charts (fake card
 * scans, pipeline, regions, activity). This endpoint replaces every one of
 * those with real aggregates computed from business_cards + card_interactions,
 * tenancy-scoped so an owner only ever sees their own company's numbers.
 *
 * Entity counts (companies/branches/employees/...) already come from the
 * individual list endpoints, so this focuses on card + interaction analytics.
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

        // --- Regions (published cards grouped by branch) ---------------------
        $regions = $cards
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
            'cards' => [
                'total'     => $cards->count(),
                'published' => $cardStatus['published'],
                'active'    => $cards->where('is_active', true)->count(),
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
