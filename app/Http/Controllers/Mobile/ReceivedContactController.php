<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Resources\CardContactShareResource;
use App\Models\CardContactShare;
use App\Models\Employee;
use Illuminate\Http\Request;

/**
 * The employee's inbox for the reverse contact exchange: people who scanned
 * their card and sent their own details back.
 *
 * Every query starts from the authenticated user's employee row — there is no
 * id-addressable path into another employee's contacts.
 */
class ReceivedContactController extends Controller
{
    private function employee(Request $request): ?Employee
    {
        return $request->user()?->employee;
    }

    public function index(Request $request)
    {
        $employee = $this->employee($request);

        if (! $employee) {
            return ResponseHelper::error(__('messages.no_card_yet'), null, 404);
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $contacts = CardContactShare::forEmployee($employee->id)
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->input('search') . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);

        // The paginator itself is returned (not a bare Resource::collection like
        // the dashboard lists) because the inbox is infinite-scrolled: the app
        // needs last_page/total to know when to stop asking for more.
        $contacts->through(fn (CardContactShare $contact) => new CardContactShareResource($contact));

        return ResponseHelper::success($contacts, __('messages.data_retrieved'));
    }

    public function unreadCount(Request $request)
    {
        $employee = $this->employee($request);

        if (! $employee) {
            return ResponseHelper::error(__('messages.no_card_yet'), null, 404);
        }

        return ResponseHelper::success(
            ['count' => CardContactShare::forEmployee($employee->id)
                ->where('is_read', false)
                ->count()],
            __('messages.data_retrieved')
        );
    }

    public function markAsRead(Request $request, $id)
    {
        $employee = $this->employee($request);

        if (! $employee) {
            return ResponseHelper::error(__('messages.no_card_yet'), null, 404);
        }

        $contact = CardContactShare::forEmployee($employee->id)->find($id);

        if (! $contact) {
            return ResponseHelper::error(__('messages.contact_not_found'), null, 404);
        }

        $contact->update(['is_read' => true]);

        return ResponseHelper::success(
            new CardContactShareResource($contact),
            __('messages.data_updated')
        );
    }

    public function destroy(Request $request, $id)
    {
        $employee = $this->employee($request);

        if (! $employee) {
            return ResponseHelper::error(__('messages.no_card_yet'), null, 404);
        }

        $contact = CardContactShare::forEmployee($employee->id)->find($id);

        if (! $contact) {
            return ResponseHelper::error(__('messages.contact_not_found'), null, 404);
        }

        // Soft delete: if the same person shares again the public endpoint
        // revives this row rather than tripping the unique index.
        $contact->delete();

        return ResponseHelper::success(null, __('messages.data_deleted'));
    }
}
