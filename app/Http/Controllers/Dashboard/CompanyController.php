<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Http\Requests\CompanyRequest;

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

    public function index()
    {
        $companies = Company::notDeleted()->with([
            'owner',
            'employees',
            'branches'
        ])->latest()->paginate(10);

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
     * Show single company
     */
    public function show()
    {
        $company = Company::with([
            'owner',
            'employees',
            'branches'
        ])->where('user_id',auth()->id())->first();

        return ResponseHelper::success($company->load(['owner', 'employees', 'branches']), __('messages.data_retrieved'), 201);
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
