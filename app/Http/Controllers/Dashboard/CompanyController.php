<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Http\Requests\CompanyRequest;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * List all companies
     */

    public function __construct()
    {
        $this->middleware('permission:company.view')->only(['index', 'show']);
        $this->middleware('permission:company.create')->only(['store']);
        $this->middleware('permission:company.update')->only(['update']);
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

        unset($data['logo']); // remove file from DB insert
        $data['user_id'] = $request->user_id;
        $company = Company::create($data);

        // Upload logo
        if ($request->hasFile('logo')) {
            $company->addMediaFromRequest('logo')
                ->toMediaCollection('company_logo');
        }
        return ResponseHelper::success(
            $company->load(['owner', 'employees', 'branches']),
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

        $company = $id !== null
            ? $query->find($id)
            : $query->where('user_id', auth()->id())->first();

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
}
