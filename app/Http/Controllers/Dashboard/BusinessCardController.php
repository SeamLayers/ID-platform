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
use App\Services\NotificationService;
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

        // approve / reject / request-changes are NOT gated on the fine-grained
        // permissions. Under the current flow the COMPANY OWNER is the
        // reviewer, and the seeder deliberately withholds business_card.approve
        // from owners — gating here would 403 them until someone re-ran the
        // seeder on the server, exactly the fragile deploy dependency we hit
        // with deletes. The route groups already restrict these to
        // superadmin|owner (dashboard) or superadmin|employee (mobile), and
        // scopeToViewer() enforces tenancy inside each method.
        //////

        $this->middleware('permission:business_card.publish')->only(['publish']);
        $this->middleware('permission:business_card.deactivate')->only(['deactivate']);
    }




    public function downloadVCard(BusinessCard $card)
    {
        // This route is public and used to be bound on the primary key with no
        // gate at all, so it handed out a full contact card — name, company,
        // title, both phones, email — for ANY sequential id, whether that card
        // was a draft, deactivated or expired. Two things close that: the route
        // now binds on public_url so the directory can't be walked, and this
        // gate makes the download agree with the page, which 404s in all three
        // cases. Without it the publish switch would mean nothing.
        if (! $card->isPubliclyVisible()) {
            abort(404);
        }

        $employee = $card->employee;

        // The row is whatever the employee is editing right now, so the .vcf
        // must carry the version the owner approved — the same one the public
        // page renders — not an unreviewed draft.
        if ($card->status !== BusinessCard::STATUS_PUBLISHED) {
            $card->applyPublishedSnapshot();
        }

        $publicUrl = url('/api/v1/card/' . $card->public_url);

        // Omitted entirely rather than emitted empty: a bare TEL line makes some
        // address books create a blank second number.
        $secondaryTel = filled($card->secondary_phone)
            ? "\nTEL;TYPE=CELL:{$card->secondary_phone}"
            : '';

        $vcard = <<<VCF
BEGIN:VCARD
VERSION:3.0
N:{$employee->name}
FN:{$employee->name}
ORG:{$employee->company->name}
TITLE:{$employee->position}
TEL;TYPE=WORK,CELL:{$employee->phone}{$secondaryTel}
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
     * Restrict a card query to what the authenticated viewer may see.
     *
     * - superadmin  → every card on the platform.
     * - owner       → cards of employees in the companies they own.
     * - employee    → their own card only.
     *
     * The old scope was owner-only (`employee.company.user_id = auth()->id()`),
     * which silently returned an EMPTY list to both employees and superadmins:
     * companies.user_id is always the owner's id, so an employee could never
     * match it and the mobile "pending approvals" screen was always empty.
     */
    private function scopeToViewer($query)
    {
        $user = auth()->user();

        if ($user?->hasRole('superadmin')) {
            return $query;
        }

        if ($user?->hasRole('owner')) {
            return $query->whereHas('employee.company', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query->whereHas('employee', function ($q) use ($user) {
            $q->where('user_id', $user?->id);
        });
    }

    /**
     * List business cards
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 200) : 10;

        $cards = $this->scopeToViewer(BusinessCard::with([
            'employee',
            'template',
            'reviewer',

        ]))
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                $q->whereHas('employee', function ($e) use ($request) {
                    $e->where('company_id', $request->company_id);
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
                    // Was missing here while CardProvisioningService included
                    // it, so a card issued from the dashboard showed a blank
                    // Job Title on the employee's phone and on the public page.
                    'position' => $employee->position,

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
        $card = $this->scopeToViewer(BusinessCard::with([
            'employee',
            'template',
            'reviewer'
        ]))
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

        // The public page must obey the same gate as the JSON endpoint: a card
        // only goes live once it is PUBLISHED and still in date. Previously any
        // draft/submitted/rejected card was already publicly readable the
        // moment it was created, so "publish makes it live" wasn't actually
        // true. A card that has been published before stays reachable while its
        // employee edits the next version — the frozen snapshot is what gets
        // served, never the work in progress.
        if (! $card->isPubliclyVisible()) {
            abort(404);
        }

        if ($card->status !== BusinessCard::STATUS_PUBLISHED) {
            $card->applyPublishedSnapshot();
        }

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
        // Scoped: a bare findOrFail let an owner act on another company's
        // card by guessing its id.
        $card = $this->scopeToViewer(BusinessCard::query())->findOrFail($id);

        $data = $request->validated();

        // expiry_days isn't a column — map it onto expiry_public_url so an
        // existing card's lifetime can actually be extended (e.g. reviving
        // cards created under the old 2-day default).
        if (isset($data['expiry_days'])) {
            $data['expiry_public_url'] = now()->addDays((int) $data['expiry_days']);
            unset($data['expiry_days']);
        }

        // The owner can set the same presentation fields the employee edits in
        // the app — so they can prepare a card up front, and the employee sees
        // the owner's version on their next refresh (it rides along on
        // GET /auth/profile).
        if (array_key_exists('theme', $data)) {
            $data['theme_json'] = $data['theme'] ?: null;
            unset($data['theme']);
        }

        // employee_ids belongs to create only; it is not a column.
        unset($data['employee_ids']);

        $card->update($data);

        if ($request->boolean('remove_photo')) {
            $card->clearMediaCollection(BusinessCard::PHOTO_COLLECTION);
        } elseif ($request->hasFile('photo')) {
            $card->addMediaFromRequest('photo')
                ->toMediaCollection(BusinessCard::PHOTO_COLLECTION);
        }

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
        // Scoped: a bare findOrFail let an owner act on another company's
        // card by guessing its id.
        $card = $this->scopeToViewer(BusinessCard::query())->findOrFail($id);

        $card->delete();

        return ResponseHelper::success(
            null,
            __('messages.data_deleted')
        );
    }
    public function submit($id)
    {
        // Scoped: a bare findOrFail let an owner act on another company's
        // card by guessing its id.
        $card = $this->scopeToViewer(BusinessCard::query())->findOrFail($id);

        $card->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        // Tell the employee their card is waiting for their approval.
        $card->load('employee.user');
        (new NotificationService())->notifyUser(
            $card->employee?->user,
            __('messages.notif_card_submitted_title'),
            __('messages.notif_card_submitted_body'),
            ['type' => 'card_submitted', 'card_id' => $card->id]
        );

        return ResponseHelper::success(
            new BusinessCardResource($card),
            __('messages.business_card_submitted')
        );
    }

    /**
     * Send an employee's submitted card back for changes.
     *
     * The owner's half of the review: instead of a flat rejection, they say
     * what to fix and the employee gets the note in the app, edits, resubmits.
     */
    public function requestChanges(Request $request, $id)
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $card = $this->scopeToViewer(BusinessCard::query())->findOrFail($id);

        if ($card->status !== BusinessCard::STATUS_SUBMITTED) {
            return ResponseHelper::error(
                __('messages.card_not_awaiting_review'),
                null,
                422
            );
        }

        $card->update([
            'status'         => BusinessCard::STATUS_CHANGES_REQUESTED,
            'review_comment' => $validated['comment'],
            'reviewed_at'    => now(),
            'reviewed_by'    => auth()->user()?->employee?->id,
        ]);

        $card->load('employee.user');

        (new NotificationService())->notifyUser(
            $card->employee?->user,
            __('messages.notif_card_changes_title'),
            __('messages.notif_card_changes_body', ['comment' => $validated['comment']]),
            [
                'type'    => 'card_changes_requested',
                'card_id' => $card->id,
                // The raw comment travels alongside the rendered body so a
                // client can present it as its own block rather than parsing
                // it back out of a translated sentence.
                'comment' => $validated['comment'],
            ]
        );

        return ResponseHelper::success(
            new BusinessCardResource($card->fresh(['employee', 'template', 'reviewer'])),
            __('messages.business_card_changes_requested')
        );
    }

    /**
     * Approve card
     */
    public function approve($id)
    {
        // Scoped, not a bare findOrFail: without this any employee could
        // approve any other employee's card by guessing an id.
        $card = $this->scopeToViewer(BusinessCard::query())->findOrFail($id);

        // No self-approval. The employee personalises their card and the OWNER
        // signs it off; letting the employee approve their own submission would
        // put them one Publish away from bypassing review entirely. (The legacy
        // mobile reviews screen still points here, hence the guard rather than
        // relying on the client not to offer the button.)
        $actor = auth()->user();
        if (! $actor?->hasAnyRole(['owner', 'superadmin'])
            && $actor?->employee?->id === $card->employee_id
        ) {
            return ResponseHelper::error(__('messages.cannot_approve_own_card'), null, 403);
        }

        // Only a card actually awaiting a decision can be approved. Without
        // this, approving an already-published card silently demoted it back to
        // `approved` — and since every public surface gates on `published`, a
        // live card would go dark with no warning.
        if (! in_array($card->status, [
            BusinessCard::STATUS_SUBMITTED,
            BusinessCard::STATUS_CHANGES_REQUESTED,
        ], true)) {
            return ResponseHelper::error(__('messages.card_not_awaiting_review'), null, 422);
        }

        $card->update([
            'status'      => 'approved',
            'reviewed_at' => now(),
            // reviewed_by is a FOREIGN KEY to employees, not users. Storing
            // auth()->id() (a users id) violates the constraint — MySQL 500s on
            // approve — and made the `reviewer` relation resolve to whichever
            // unrelated employee happened to share that id.
            'reviewed_by' => auth()->user()?->employee?->id,
        ]);

        // Notify the OTHER party, whoever that is. In the current flow the
        // owner approves what the employee personalised, so the employee hears
        // about it; the legacy path (employee approves the owner's card) still
        // notifies the owner.
        $card->load(['employee.company.owner', 'employee.user']);
        $actorIsOwner = auth()->user()?->hasAnyRole(['owner', 'superadmin']) ?? false;

        $notifications = new NotificationService();

        if ($actorIsOwner) {
            $notifications->notifyUser(
                $card->employee?->user,
                __('messages.notif_card_owner_approved_title'),
                __('messages.notif_card_owner_approved_body'),
                ['type' => 'card_approved', 'card_id' => $card->id]
            );
        } else {
            $notifications->notifyUser(
                $card->employee?->company?->owner,
                __('messages.notif_card_approved_title'),
                __('messages.notif_card_approved_body', ['name' => $card->employee?->name ?? '']),
                // A DIFFERENT type from the employee-facing one above: both used
                // to be 'card_approved', so a client had no way to tell whether
                // to open the review queue or the employee's own card. The
                // employee-facing string is kept as-is for older app builds.
                [
                    'type'    => 'card_approved_by_employee',
                    'card_id' => $card->id,
                    'name'    => $card->employee?->name ?? '',
                ]
            );
        }

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

        // Same ownership scope as approve().
        $card = $this->scopeToViewer(BusinessCard::query())->findOrFail($id);

        // And the same self-review guard: without it an employee could reject
        // their OWN submitted card, which flips it back to an editable state
        // and lets them keep editing while the owner is still reviewing.
        $actor = auth()->user();
        if (! $actor?->hasAnyRole(['owner', 'superadmin'])
            && $actor?->employee?->id === $card->employee_id
        ) {
            return ResponseHelper::error(__('messages.cannot_approve_own_card'), null, 403);
        }

        $card->update([
            'status'            => 'rejected',
            'reviewed_at'       => now(),
            // FK to employees, not users — see approve().
            'reviewed_by'       => auth()->user()?->employee?->id,
            'rejection_reason'  => $data['rejection_reason'],
        ]);

        // Tell whoever DIDN'T press the button. Rejecting is the one review
        // outcome that used to notify the company owner in every case — so when
        // the owner rejected, they told themselves and the employee was never
        // informed, even though `rejected` puts the card back in their hands to
        // fix. Mirrors the branch in approve().
        $card->load(['employee.company.owner', 'employee.user']);
        $actorIsOwner = auth()->user()?->hasAnyRole(['owner', 'superadmin']) ?? false;

        (new NotificationService())->notifyUser(
            $actorIsOwner ? $card->employee?->user : $card->employee?->company?->owner,
            $actorIsOwner
                ? __('messages.notif_card_owner_rejected_title')
                : __('messages.notif_card_rejected_title'),
            $actorIsOwner
                ? __('messages.notif_card_owner_rejected_body', ['reason' => $data['rejection_reason']])
                : __('messages.notif_card_rejected_body', [
                    'name'   => $card->employee?->name ?? '',
                    'reason' => $data['rejection_reason'],
                ]),
            [
                'type'    => $actorIsOwner ? 'card_rejected' : 'card_rejected_by_employee',
                'card_id' => $card->id,
                'reason'  => $data['rejection_reason'],
                'name'    => $card->employee?->name ?? '',
            ]
        );

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
        // Scoped: a bare findOrFail let an owner act on another company's
        // card by guessing its id.
        $card = $this->scopeToViewer(BusinessCard::query())->findOrFail($id);

        if ($card->status !== 'approved') {

            return ResponseHelper::error(
                __('messages.business_card_must_be_approved'),
                422
            );
        }

        $card->update([
            'status'    => 'published',
            'is_active' => true,
            // Freeze what is going live BEFORE anyone can edit it again, so the
            // public URL keeps serving this exact version through the next
            // round of employee edits.
            'published_snapshot' => $card->capturePublishedSnapshot(),
            'published_at'       => now(),
        ]);

        // Tell the employee their card is live and shareable.
        $card->load('employee.user');
        (new NotificationService())->notifyUser(
            $card->employee?->user,
            __('messages.notif_card_published_title'),
            __('messages.notif_card_published_body'),
            ['type' => 'card_published', 'card_id' => $card->id]
        );

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
        // Scoped: a bare findOrFail let an owner act on another company's
        // card by guessing its id.
        $card = $this->scopeToViewer(BusinessCard::query())->findOrFail($id);

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
        // Scoped: a bare findOrFail let an owner act on another company's
        // card by guessing its id.
        $card = $this->scopeToViewer(BusinessCard::query())->findOrFail($id);

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
        // Scoped like every other action here. This was the one that still used
        // a bare findOrFail, and the route only checks the `owner` role — so
        // any owner could walk sequential ids and read another company's
        // interaction totals and its ten most recent scans.
        $card = $this->scopeToViewer(BusinessCard::with('interactions'))
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
