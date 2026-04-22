<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Http\Requests\CompanyRequest;

class CompanyController extends Controller
{
    /**
     * List all companies
     */

    public function index()
    {
        return response()->json(
            Company::latest()->paginate(10)
        );
    }

    /**
     * Store new company
     */
    public function store(CompanyRequest $request)
    {
        $company = Company::create($request->validated());

        // Upload logo
        if ($request->hasFile('logo')) {
            $company->addMediaFromRequest('logo')
                ->toMediaCollection('company_logo');
        }

        return response()->json([
            'message' => 'Company created successfully',
            'data' => $company
        ], 201);
    }

    /**
     * Show single company
     */
    public function show($id)
    {
        $company = Company::findOrFail($id);

        return response()->json($company);
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

        return response()->json([
            'message' => 'Company updated successfully',
            'data' => $company
        ]);
    }

    /**
     * Soft delete
     */
    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json([
            'message' => 'Company deleted successfully'
        ]);
    }
}
