<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Requests\BusinessCardTemplateRequest;
use App\Http\Resources\BusinessCardTemplateResource;
use App\Models\BusinessCardTemplate;
use Illuminate\Http\Request;

class BusinessCardTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:business_card_template.view')
            ->only(['index', 'show']);

        $this->middleware('permission:business_card_template.create')
            ->only(['store']);

        $this->middleware('permission:business_card_template.update')
            ->only(['update']);

        $this->middleware('permission:business_card_template.delete')
            ->only(['destroy']);
    }

    /**
     * List templates
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 200) : 10;

        $templates = BusinessCardTemplate::with('company')
            ->when($request->filled('company_id'), function ($q) use ($request) {
                $q->where('company_id', $request->input('company_id'));
            })
            ->latest()
            ->paginate($perPage);

        return ResponseHelper::success(
            BusinessCardTemplateResource::collection($templates),
            __('messages.data_retrieved')
        );
    }

    /**
     * Store template
     */
    public function store(BusinessCardTemplateRequest $request)
    {
        $template = BusinessCardTemplate::create(
            $request->validated()
        );

        return ResponseHelper::success(
            new BusinessCardTemplateResource(
                $template->load('company')
            ),
            __('messages.data_saved'),
            201
        );
    }

    /**
     * Show template
     */
    public function show($id)
    {
        $template = BusinessCardTemplate::with('company')
            ->findOrFail($id);

        return ResponseHelper::success(
            new BusinessCardTemplateResource($template),
            __('messages.data_retrieved')
        );
    }

    /**
     * Update template
     */
    public function update(
        BusinessCardTemplateRequest $request,
                                    $id
    ) {
        $template = BusinessCardTemplate::findOrFail($id);

        $template->update(
            $request->validated()
        );

        return ResponseHelper::success(
            new BusinessCardTemplateResource(
                $template->load('company')
            ),
            __('messages.data_updated')
        );
    }

    /**
     * Delete template
     */
    public function destroy($id)
    {
        $template = BusinessCardTemplate::findOrFail($id);

        $template->delete();

        return ResponseHelper::success(
            null,
            __('messages.data_deleted')
        );
    }
}
