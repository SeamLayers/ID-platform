<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Requests\BusinessCardRequest;
use App\Http\Requests\RejectBCRequest;
use App\Http\Resources\BusinessCardResource;
use App\Http\Resources\EmployeeResource;
use App\Models\BusinessCard;
use App\Models\Employee;
use App\Services\CardCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BusinessCardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:business_card.view')->only(['index', 'show']);
        $this->middleware('permission:business_card.create')->only(['store']);
        $this->middleware('permission:business_card.update')->only(['update']);
        $this->middleware('permission:business_card.submit')->only(['submit']);

        // for employee
        $this->middleware('permission:business_card.approve')->only(['approve']);
        $this->middleware('permission:business_card.reject')->only(['reject']);
        //////

        $this->middleware('permission:business_card.publish')->only(['publish']);
        $this->middleware('permission:business_card.deactivate')->only(['deactivate']);
    }




    public function downloadVCard(BusinessCard $card)
    {
        $employee = $card->employee;

        $publicUrl = url('/api/v1/card/' . $card->public_url);

        $vcard = <<<VCF
BEGIN:VCARD
VERSION:3.0
N:{$employee->name}
FN:{$employee->name}
ORG:{$employee->company->name}
TITLE:{$employee->position}
TEL;TYPE=WORK,CELL:{$employee->phone}
EMAIL;TYPE=WORK:{$employee->email}
URL:{$publicUrl}
NOTE:Digital Business Card
END:VCARD
VCF;

        return response($vcard)
            ->header('Content-Type', 'text/vcard; charset=UTF-8')
            ->header(
                'Content-Disposition',
                'attachment; filename="'.$employee->employee_number.'.vcf"'
            );
    }
    /**
     * List business cards
     */
    public function index(Request $request)
    {
        // Honour the dashboard's list filters (status tabs, company picker) and
        // its per_page (Issue-Cards dialog dropdowns ask for more than 10).
        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 200) : 10;

        $cards = BusinessCard::with([
            'employee',
            'template',
            'reviewer'
        ])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                $q->whereHas('employee', function ($e) use ($request) {
                    $e->where('company_id', $request->input('company_id'));
                });
            })
            ->latest()
            ->paginate($perPage);

        return ResponseHelper::success(
            BusinessCardResource::collection($cards),
            __('messages.data_retrieved')
        );
    }

    /**
     * Store new business card
     */
    public function store(
        BusinessCardRequest $request,
        CardCodeService $service
    ) {
        $data = $request->validated();

        $employees = Employee::with([
            'company',
            'branch',
            'department',
            'role'
        ])
            ->whereIn('id', $data['employee_ids'])
            ->get();

        $cards = [];

        foreach ($employees as $employee) {

            $codes = $service->generateAll($employee);

            $cards[] = BusinessCard::create([

                'employee_id' => $employee->id,
                'template_id' => $data['template_id'],

                'card_data_json' => [
                    'employee_number' => $employee->employee_number,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'iqama_number' => $employee->iqama_number,
                    'status' => $employee->status,

                    'company' => optional($employee->company)->name,
                    'branch' => optional($employee->branch)->name,
                    'department' => optional($employee->department)->name,
                    'role' => optional($employee->role)->name,
                ],

                'public_url' => $codes['public_url'],
                'qr_code' => $codes['qr_code'],
                'nfc_code' => $codes['nfc_code'],

                // Default to one year — the old 2-day default silently killed
                // every public card link (and NFC/QR tags pointing at it).
                'expiry_public_url' => now()->addDays($data['expiry_days'] ?? 365),
            ]);
        }

        $cards = BusinessCard::with(['employee', 'template', 'reviewer'])
            ->whereIn('id', collect($cards)->pluck('id'))
            ->get();

        return ResponseHelper::success(
            BusinessCardResource::collection($cards),
            __('messages.data_saved'),
            201
        );
    }
    /**
     * Show single business card
     */
    public function show($id)
    {
        $card = BusinessCard::with([
            'employee',
            'template',
            'reviewer'
        ])
            ->findOrFail($id);

        return ResponseHelper::success(
            new BusinessCardResource($card),
            __('messages.data_retrieved')
        );
    }

    public function CardSlug(string $slug)
    {
        $card = BusinessCard::with([
            'employee.company',
            'employee.department',
            'employee.businessCard',
        ])
            ->where('public_url', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('business-card.profile', [
            'employee' => $card->employee,
            'card' => $card,
        ]);
    }
    /**
     * Update business card
     */
    public function update(BusinessCardRequest $request, $id)
    {
        $card = BusinessCard::findOrFail($id);

        $data = $request->validated();

        // expiry_days isn't a column — map it onto expiry_public_url so an
        // existing card's lifetime can actually be extended (e.g. reviving
        // cards created under the old 2-day default).
        if (isset($data['expiry_days'])) {
            $data['expiry_public_url'] = now()->addDays((int) $data['expiry_days']);
            unset($data['expiry_days']);
        }

        $card->update($data);

        return ResponseHelper::success(
            new BusinessCardResource(
                $card->load([
                    'employee',
                    'template',
                    'reviewer'
                ])
            ),
            __('messages.data_updated')
        );
    }

    /**
     * Submit card
     */

    public function destroy($id)
    {
        $card = BusinessCard::findOrFail($id);

        $card->delete();

        return ResponseHelper::success(
            null,
            __('messages.data_deleted')
        );
    }
    public function submit($id)
    {
        $card = BusinessCard::findOrFail($id);

        $card->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        return ResponseHelper::success(
            new BusinessCardResource($card),
            __('messages.business_card_submitted')
        );
    }

    /**
     * Approve card
     */
    public function approve($id)
    {
        $card = BusinessCard::findOrFail($id);

        $card->update([
            'status'      => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return ResponseHelper::success(
            new BusinessCardResource($card),
            __('messages.business_card_approved')
        );
    }

    /**
     * Reject card
     */
    public function reject(RejectBCRequest $request, $id)
    {

        $data = $request->validated();

        $card = BusinessCard::findOrFail($id);

        $card->update([
            'status'            => 'rejected',
            'reviewed_at'       => now(),
            'reviewed_by'       => auth()->id(),
            'rejection_reason'  => $data['rejection_reason'],
        ]);

        return ResponseHelper::success(
            new BusinessCardResource($card),
            __('messages.business_card_rejected')
        );
    }

    /**
     * Publish card
     */
    public function publish($id)
    {
        $card = BusinessCard::findOrFail($id);

        if ($card->status !== 'approved') {

            return ResponseHelper::error(
                __('messages.business_card_must_be_approved'),
                422
            );
        }

        $card->update([
            'status'    => 'published',
            'is_active' => true,
        ]);

        return ResponseHelper::success(
            new BusinessCardResource($card),
            __('messages.business_card_published')
        );
    }

    /**
     * Deactivate card
     */
    public function deactivate($id)
    {
        $card = BusinessCard::findOrFail($id);

        $card->update([
            'is_active' => false,
        ]);

        return ResponseHelper::success(
            new BusinessCardResource($card),
            __('messages.business_card_deactivated')
        );
    }

    public function track(Request $request, $id)
    {
        $card = BusinessCard::findOrFail($id);

        $validated = $request->validate([

            'interaction_type' => [
                'required',
                'string'
            ],

            'source' => [
                'nullable',
                'string'
            ],
        ]);

        // card_interactions.source is NOT NULL in the schema — default it,
        // same as the public track endpoint.
        $validated['source'] = $validated['source'] ?? 'LINK';

        $validated['ip_address'] = $request->ip();

        $validated['user_agent'] = $request->userAgent();

        $card->interactions()->create($validated);

        return ResponseHelper::success(
            null,
            __('messages.data_saved')
        );
    }

    /**
     * Business card analytics
     */
    public function analytics($id)
    {
        $card = BusinessCard::with('interactions')
            ->findOrFail($id);

        $analytics = [

            'total_interactions' => $card->interactions->count(),

            'total_views' => $card->interactions
                ->where('interaction_type', 'view')
                ->count(),

            'qr_scans' => $card->interactions
                ->where('interaction_type', 'qr_scan')
                ->count(),

            'nfc_scans' => $card->interactions
                ->where('interaction_type', 'nfc_scan')
                ->count(),

            'link_views' => $card->interactions
                ->where('source', 'LINK')
                ->count(),

            'qr_views' => $card->interactions
                ->where('source', 'QR')
                ->count(),

            'nfc_views' => $card->interactions
                ->where('source', 'NFC')
                ->count(),
            'latest_interactions' => $card->interactions()
                ->latest()
                ->take(10)
                ->get([
                    'id',
                    'interaction_type',
                    'source'
                ]),
        ];

        return ResponseHelper::success(
            $analytics,
            __('messages.data_retrieved')
        );
    }
}
