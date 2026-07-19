<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee.view')->only(['index', 'show']);
        $this->middleware('permission:employee.create')->only(['store']);
        $this->middleware('permission:employee.update')->only(['update']);
        // NOTE: destroy is intentionally NOT gated on the fine-grained
        // `employee.delete` permission. The route group already restricts it to
        // role:superadmin|owner, and destroy() enforces tenancy (an owner may
        // only delete their own company's employees). Requiring the extra
        // permission meant owners couldn't delete until the roles seeder was
        // re-run on the server — a fragile deploy dependency. Role + tenancy is
        // sufficient and works immediately. (The seeder still grants owners
        // employee.delete for any future permission-based checks.)
    }

    /**
     * List employees
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 200) : 10;

        $employees = Employee::notDeleted()
            ->with([
                'company',
                'branch',
                'role',
                'department',
                'user',
                'projects',
                'businessCard',
            ])
            // whereHas stays UNCONDITIONAL: besides tenancy it also enforces the
            // parent's soft-delete scope, so rows orphaned by a deleted company
            // (delete doesn't cascade) never reach a Resource that dereferences
            // ->company and 500s. Only the ownership predicate is role-dependent.
            ->whereHas('company', function ($q) {
                $q->when(
                    ! auth()->user()?->hasRole('superadmin'),
                    fn ($c) => $c->where('user_id', auth()->id())
                );
            })
            ->when($request->company_id, function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            ->when($request->branch_id, function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            })
            ->latest()
            ->paginate($perPage);

        return ResponseHelper::success(
            EmployeeResource::collection($employees),
            __('messages.data_retrieved')
        );
    }

    /**
     * Create employee
     *
     * Two supported flows:
     *   1. Legacy two-step: POST /dashboard/register first, then call this
     *      with the resulting user_id (+ explicit employee_number).
     *   2. Seamless one-step: omit user_id — the login account is created
     *      here (mirroring RegisteredUserController@store) and, when
     *      employee_number is also omitted, the number is auto-generated
     *      per company (EMP-{companyId}-{NNNN}).
     */
    public function store(EmployeeRequest $request)
    {
        $data = $request->validated();

        // Tenancy scoping: owners may only create employees inside their own
        // company (superadmin is unrestricted). Mirrors the company-resolution
        // pattern used by CompanyController@show / DepartmentController@destroy.
        $authUser = $request->user();

        if (!$authUser->hasRole('superadmin')) {
            $ownCompanyIds = \App\Models\Company::where('user_id', $authUser->id)->pluck('id');

            if (!$ownCompanyIds->contains((int) $data['company_id'])) {
                return ResponseHelper::error(
                    __('messages.employee_company_forbidden'),
                    null,
                    403
                );
            }
        }

        // When linking an EXISTING user, never allow attaching a privileged
        // account (owner/superadmin) or a user who is already an employee —
        // otherwise an owner could bind another tenant's login to their
        // company and expose its email on the public card page.
        if (!empty($data['user_id'])) {
            $linkedUser = User::findOrFail($data['user_id']);

            $alreadyEmployed = Employee::withTrashed()
                ->where('user_id', $linkedUser->id)
                ->exists();

            if ($linkedUser->user_type !== User::TYPE_EMPLOYEE || $alreadyEmployed) {
                return ResponseHelper::error(
                    __('messages.employee_user_invalid'),
                    null,
                    422
                );
            }
        }

        $tempPassword = null;
        $credentialsMail = null;
        $credentialsSms = null;

        $employee = DB::transaction(function () use ($request, $data, &$tempPassword, &$credentialsMail, &$credentialsSms) {

            // Seamless path: provision the login account when none is linked.
            if (empty($data['user_id'])) {

                $plainPassword = $data['password'] ?? Str::password(8);

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($plainPassword),
                    'ip_address' => $request->ip(),
                    'user_type' => User::TYPE_EMPLOYEE,
                    'phone' => $data['phone'] ?? null,
                    'expire_password' => now()->addHours(48),
                    'must_reset_password' => true,
                ]);

                $user->assignRole(User::TYPE_EMPLOYEE);

                $data['user_id'] = $user->id;

                // Only surface the password in the response when we
                // generated it ourselves.
                if (empty($data['password'])) {
                    $tempPassword = $plainPassword;
                }

                // Queue the credentials email for AFTER the transaction
                // commits — sending inside it means a rollback (unique-index
                // race, media failure) emails a password for an account that
                // never got created.
                // Prepare email
                $credentialsMail = [
                    'to'   => $user->email,
                    'body' => "Your account has been created successfully.

Email: {$user->email}
Temporary Password: {$plainPassword}

Please log in to the application and change your password within 48 hours.

Download ID Plus App:
Google Play / Apple Store",
                ];

                // SMS goes out after the commit too, for the same reason.
                $credentialsSms = [
                    'to'   => $user->phone,
                    'body' => "Your ID Plus account has been created. Email: {$user->email}, Temporary Password: {$plainPassword}. Please change your password within 48 hours.",
                ];
            }

            // Never mass-assign the login password onto the employee row.
            unset($data['password']);

            if (empty($data['employee_number'])) {
                $data['employee_number'] = Employee::nextNumberForCompany((int) $data['company_id']);
            }

            $employee = Employee::create($data);

            // Upload employee logo
            if ($request->hasFile('logo')) {
                $employee->addMediaFromRequest('logo')
                    ->toMediaCollection('employee_logo');
            }

            return $employee;
        });

        if ($credentialsMail !== null) {
            try {
                Mail::raw($credentialsMail['body'], function ($mail) use ($credentialsMail) {
                    $mail->to($credentialsMail['to'])
                         ->subject('Your ID Plus account credentials');
                });
            } catch (\Throwable $mailException) {
                Log::error('Credentials Email Sending Failed', [
                    'email' => $credentialsMail['to'],
                    'error' => $mailException->getMessage(),
                ]);
            }
        }

        if ($credentialsSms !== null && ! empty($credentialsSms['to'])) {
            try {
                SmsService::sendSMS($credentialsSms['to'], $credentialsSms['body']);
            } catch (\Throwable $smsException) {
                Log::error('Credentials SMS Sending Failed', [
                    'phone' => $credentialsSms['to'],
                    'error' => $smsException->getMessage(),
                ]);
            }
        }


        $employee->load([
            'company',
            'branch',
            'role',
            'department',
            'user'
        ]);

        // Same envelope as before; the auto-generated credentials are only
        // appended on the seamless path (user info rides in data.user).
        $responseData = $employee->toArray();

        if ($tempPassword !== null) {
            $responseData['temp_password'] = $tempPassword;
        }

        return ResponseHelper::success(
            $responseData,
            __('messages.data_saved'),
            201
        );
    }

    /**
     * Show single employee
     */
    public function show($id)
    {
        $employee = Employee::with([
            'company',
            'branch',
            'role',
            'department',
            'user',
            'projects',
            'businessCard'
        ])
            ->findOrFail($id);

        return ResponseHelper::success(
            $employee,
            __('messages.data_retrieved')
        );
    }

    /**
     * Update employee
     */
    public function update(EmployeeRequest $request, $id)
    {
        $employee = Employee::findOrFail($id);

        // Tenancy scoping: owners may only edit their own company's employees.
        $authUser = auth()->user();
        if (! $authUser->hasRole('superadmin')) {
            $ownCompanyIds = \App\Models\Company::where('user_id', $authUser->id)->pluck('id');
            if (! $ownCompanyIds->contains((int) $employee->company_id)) {
                return ResponseHelper::error(__('messages.employee_company_forbidden'), null, 403);
            }
        }

        $employee->update($request->validated());

        // Replace logo if uploaded
        if ($request->hasFile('logo')) {
            $employee->clearMediaCollection('employee_logo');

            $employee->addMediaFromRequest('logo')
                ->toMediaCollection('employee_logo');
        }

        return ResponseHelper::success(
            $employee->load([
                'company',
                'branch',
                'role',
                'department',
                'user'
            ]),
            __('messages.data_updated')
        );
    }

    /**
     * Delete employee
     */
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);

        // Tenancy scoping: owners may only delete employees inside their own
        // company (superadmin is unrestricted). Without this, an owner granted
        // employee.delete could delete another tenant's employee by guessing id.
        $authUser = auth()->user();
        if (! $authUser->hasRole('superadmin')) {
            $ownCompanyIds = \App\Models\Company::where('user_id', $authUser->id)->pluck('id');
            if (! $ownCompanyIds->contains((int) $employee->company_id)) {
                return ResponseHelper::error(
                    __('messages.employee_company_forbidden'),
                    null,
                    403
                );
            }
        }

        $employee->delete();

        return ResponseHelper::success(
            $employee,
            __('messages.data_deleted')
        );
    }
}
