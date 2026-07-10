<?php

namespace App\Http\Requests;

use App\Contracts\ValidationTranslatorInterface;
use App\Http\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployeeRequest extends FormRequest
{


    public function __construct(
        protected ValidationTranslatorInterface $translator
    )
    {
        parent::__construct();
    }
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Required vs nullable is driven by the DB schema (see
        // 2026_04_21_195624_create_employees_table):
        //   * email + iqama_number are UNIQUE NOT NULL  → required
        //   * role_id / department_id / phone / logo are nullable → optional
        // Unique rules are scoped to the current employee id so updates don't
        // collide with the row's own values.
        //
        // Seamless creation: user_id and employee_number are now optional on
        // create. When user_id is omitted the controller provisions the login
        // account itself (see EmployeeController@store), and when
        // employee_number is omitted it's auto-generated per company. The old
        // two-step flow (register user, then create employee with user_id)
        // keeps working unchanged.
        $employeeId = $this->route('employee') ?? $this->id;

        $emailRules = ['required', 'email', 'unique:employees,email,' . $employeeId];

        // New-user path (POST without user_id): the controller will also
        // create a users row with this email, so it must be free in the
        // users table too. Scoped to this path only so the legacy flow —
        // where the user already exists with the same email — isn't broken.
        if ($this->isMethod('post') && !$this->filled('user_id')) {
            $emailRules[] = 'unique:users,email';
        }

        return [
            'company_id'      => ['required', 'exists:companies,id'],
            'branch_id'       => ['required', 'exists:company_branches,id'],
            'role_id'         => ['nullable', 'exists:roles,id'],
            'department_id'   => ['nullable', 'exists:departments,id'],

            // 'nullable' only on create — the store() path provisions the user
            // / generates the number when they're absent. On update an explicit
            // empty value would unlink the login account (user_id) or hit the
            // NOT NULL employee_number column with a raw SQL error, so updates
            // use sometimes+required: omit the field to keep the current value,
            // but an empty value is rejected with a 422.
            'user_id'         => $this->isMethod('post')
                ? ['nullable', 'exists:users,id']
                : ['sometimes', 'required', 'exists:users,id'],

            'employee_number' => $this->isMethod('post')
                ? ['nullable', 'string', 'unique:employees,employee_number,' . $employeeId]
                : ['sometimes', 'required', 'string', 'unique:employees,employee_number,' . $employeeId],
            'iqama_number'    => ['required', 'string', 'unique:employees,iqama_number,' . $employeeId],

            'name'            => ['required', 'string', 'max:255'],
            'email'           => $emailRules,
            'phone'           => ['nullable', 'string', 'unique:employees,phone,' . $employeeId],
            'status'          => ['required', 'in:active,inactive'],
            'position'          => ['required'],

            // Optional explicit password for the auto-created login account;
            // when omitted a temporary one is generated (Str::password(8)).
            'password'        => ['nullable', 'string', 'min:8'],

            'logo'            => ['nullable', 'file', 'mimes:jpg,jpeg,png'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::success(null,
                $this->translator->transform($validator),
                422
            )
        );
    }
}
