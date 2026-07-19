<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Models\User;
use App\Http\Requests\CompanyRequest;
use App\Http\Requests\OwnerCompanyUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    /**
     * List all companies
     */

    public function __construct()
    {
        $this->middleware('permission:company.view')->only(['index', 'show']);
        $this->middleware('permission:company.create')->only(['store']);
        $this->middleware('permission:company.update')->only(['update', 'updateOwn']);
        $this->middleware('permission:company.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 200) : 10;

        $companies = Company::notDeleted()->with([
            'owner',
            'employees',
            'branches'
        ])->latest()->paginate($perPage);

        return ResponseHelper::success(
            CompanyResource::collection($companies),
            __('messages.data_retrieved')
        );
    }

    /**
     * Store new company
     */
    public function store(CompanyRequest $request)
    {
        $data = $request->validated();

        $tempPassword    = null;
        $credentialsMail = null;

        // Provision the owner LOGIN account + the company in one transaction
        // (mirrors EmployeeController@store). The owner gets a temporary
        // password, a 48h expiry, and must_reset_password so the dashboard
        // forces a reset on first login.
        $company = DB::transaction(function () use ($request, $data, &$tempPassword, &$credentialsMail) {

            $plainPassword = Str::password(8);

            $owner = User::create([
                'name'                => $data['owner_name'],
                'email'               => $data['owner_email'],
                'password'            => Hash::make($plainPassword),
                'ip_address'          => $request->ip(),
                'user_type'           => User::TYPE_OWNER,
                'phone'               => $data['owner_phone'] ?? null,
                'expire_password'     => now()->addHours(48),
                'must_reset_password' => true,
            ]);

            $owner->assignRole(User::TYPE_OWNER);

            $tempPassword = $plainPassword;

            $credentialsMail = [
                'to'   => $owner->email,
                'body' => "Your ID Plus company owner account has been created.

Login (dashboard): {$owner->email}
Temporary Password: {$plainPassword}

Please sign in to the dashboard and set your own password within 48 hours.",
            ];

            $company = Company::create([
                'user_id'             => $owner->id,
                'name'                => $data['name'],
                'email'               => $data['email'],
                'phone'               => $data['phone'],
                'commercial_register' => $data['commercial_register'] ?? null,
            ]);

            // Upload logo
            if ($request->hasFile('logo')) {
                $company->addMediaFromRequest('logo')
                    ->toMediaCollection('company_logo');
            }

            return $company;
        });

        // Send credentials AFTER commit so a rollback never emails a password
        // for an owner/company that didn't get created.
        if ($credentialsMail !== null) {
            try {
                Mail::raw($credentialsMail['body'], function ($mail) use ($credentialsMail) {
                    $mail->to($credentialsMail['to'])
                         ->subject('Your ID Plus owner account credentials');
                });
            } catch (\Throwable $mailException) {
                Log::error('Owner Credentials Email Sending Failed', [
                    'email' => $credentialsMail['to'],
                    'error' => $mailException->getMessage(),
                ]);
            }
        }

        return ResponseHelper::success(
            [
                'company'       => new CompanyResource($company->load(['owner', 'employees', 'branches'])),
                'temp_password' => $tempPassword,
            ],
            __('messages.data_saved'),
            201
        );
    }

    /**
     * Show single company.
     *
     * Routed two ways:
     *   - Superadmin: GET /dashboard/company/{id}    (apiResource — $id is the company id)
     *   - Owner:      GET /dashboard/owner/company   (no parameter — resolve by auth user)
     *
     * Returns 404 if the owner isn't linked to a company yet so the
     * dashboard's "My Company" page can render its empty state instead of
     * 500-ing on `null->load()`.
     */
    public function show($id = null)
    {
        $query = Company::with(['owner', 'employees', 'branches']);

        $company = $id !== null ? $query->find($id) : $query->where('user_id', auth()->id())->first();
        if (! $company) {
            return ResponseHelper::error(
                __('messages.company_not_found'),
                null,
                404
            );
        }

        return ResponseHelper::success(
            new CompanyResource($company),
            __('messages.data_retrieved')
        );
    }

    /**
     * Update company
     */
    public function update(CompanyRequest $request, $id)
    {
        $company = Company::findOrFail($id);

        $company->update($request->validated());

        // Update logo (replace old)
        if ($request->hasFile('logo')) {
            $company->clearMediaCollection('company_logo');

            $company->addMediaFromRequest('logo')
                ->toMediaCollection('company_logo');
        }
        return ResponseHelper::success($company,__('messages.data_updated'));
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();
        return ResponseHelper::success($company,__('messages.data_deleted'));

    }

    /**
     * Owner self-service update of THEIR OWN company.
     *
     * Tenancy-safe: the company is resolved from the authenticated user
     * (user_id === auth id), never from a client-supplied id, so an owner can
     * only ever edit the company they own. The owner login account is left
     * untouched (owners maintain public company details only).
     */
    public function updateOwn(OwnerCompanyUpdateRequest $request)
    {
        $company = Company::where('user_id', auth()->id())->first();

        if (! $company) {
            return ResponseHelper::error(
                __('messages.company_not_found'),
                null,
                404
            );
        }

        $company->update($request->validated());

        // Replace the logo only when a new file is uploaded.
        if ($request->hasFile('logo')) {
            $company->clearMediaCollection('company_logo');
            $company->addMediaFromRequest('logo')
                ->toMediaCollection('company_logo');
        }

        return ResponseHelper::success(
            new CompanyResource($company->fresh()->load(['owner', 'employees', 'branches'])),
            __('messages.data_updated')
        );
    }
}
