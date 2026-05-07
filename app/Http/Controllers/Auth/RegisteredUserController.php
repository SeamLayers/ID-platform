<?php

namespace App\Http\Controllers\Auth;

use App\Http\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\AdvertisementContracts;
use App\Models\Employee;
use App\Models\OurWork;
use App\Models\PaymentTransaction;
use App\Models\Rating;
use App\Models\User;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    /**
     * Register a new user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'user_type' => ['required','in:owner,employee,superadmin'],
            ]);

            DB::beginTransaction();

            $user = User::create([
                'name'       => $validated['name'],
                'email'      => $validated['email'],
                'password'   => Hash::make($validated['password']),
                'ip_address' => $request->ip(),
                'user_type'  => $validated['user_type'], // default
            ]);



            $user->assignRole($validated['user_type']);

            /*
            |----------------------------------------
            | Token
            |----------------------------------------
            */
            $token = $user->createToken('api-token')->plainTextToken;

            event(new Registered($user));

            DB::commit();

            return ResponseHelper::success([
                'user'  => $user,
                'token' => $token,
            ], __('messages.register_success'));

        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Send OTP to phone
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country_code' => 'required|string|max:5',
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                implode(', ', $validator->errors()->all()),
                422
            );
        }

        $countryCode = $request->country_code;
        $phoneNumber = $request->phone;

        // Prevent resending OTP before expiry
        $existingOtp = VerificationCode::where('phone_number', $phoneNumber)
            ->where('country_code', $countryCode)
            ->where('is_used', false)
            ->where('expiration_date', '>', now())
            ->latest()
            ->first();

        if ($existingOtp) {
            $remaining = $existingOtp->expiration_date->diffInSeconds(now());
            return ResponseHelper::error(
                __('messages.otp_wait', ['seconds' => $remaining]),
                429
            );
        }

        $otp = rand(1000, 9999);

        VerificationCode::create([
            'phone_number' => $phoneNumber,
            'country_code' => $countryCode,
            'code' => $otp,
            'expiration_date' => now()->addMinutes(5),
            'is_used' => false,
        ]);

        // TODO: Integrate SMS provider here
        // Example: SmsService::send($countryCode.$phoneNumber, $otp);

        return ResponseHelper::success([
            'phone' => "{$countryCode}{$phoneNumber}",
            'otp' => $otp // remove in production
        ], __('messages.otp_sent'));
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country_code' => 'required|string|max:5',
            'phone' => 'required|string|max:20',
            'otp' => 'required|digits:4',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                implode(', ', $validator->errors()->all()),
                422
            );
        }

        $countryCode = $request->country_code;
        $phoneNumber = $request->phone;

        $record = VerificationCode::where('phone_number', $phoneNumber)
            ->where('country_code', $countryCode)
            ->where('code', $request->otp)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$record) {
            return ResponseHelper::error(__('messages.invalid_otp'), 422);
        }

        if ($record->expiration_date->isPast()) {
            return ResponseHelper::error(__('messages.otp_expired'), 422);
        }

        $record->update(['is_used' => true]);

        return ResponseHelper::success(null, __('messages.otp_verified'));
    }

    /**
     * Get current authenticated user profile
     */
    public function profileData(): JsonResponse
    {
        return ResponseHelper::success(auth()->user(), __('messages.profile_retrieved'));
    }

    /**
     * Update current user profile data
     */
    public function updateData(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return ResponseHelper::error(__('messages.user_not_found'), 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sex' => 'required|in:male,female',
            'material_status' => 'required|in:married,single',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                implode(', ', $validator->errors()->all()),
                422
            );
        }

        // Prevent updating sensitive fields
        $blockedFields = ['email', 'phone', 'password', 'password_confirmation'];
        $data = $request->except($blockedFields);

        $user->update($data);

        return ResponseHelper::success($user->fresh(), __('messages.profile_updated'));
    }

    /**
     * Get freelancer by ID with advertisements and works
     */
    public function getFreelancer(int $photographer_id): JsonResponse
    {
        $validator = Validator::make(
            ['photographer_id' => $photographer_id],
            ['photographer_id' => 'required|integer|exists:users,id']
        );

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors()->first(), 422, $validator->errors());
        }

        $freelancer = User::with([
            'advertisements.contracts',
            'our_works.media',
            'ratings'
        ])->find($photographer_id);





        if (!$freelancer) {
            return ResponseHelper::error(__('messages.freelancer_not_found'), 404);
        }

        return ResponseHelper::success($freelancer, __('messages.user_retrieved'));
    }

    /**
     * Retrieve best freelancers based on ratings
     */
    public function bestFreelancers(): JsonResponse
    {
        $freelancers = Rating::whereHas('user', fn($q) => $q->where('user_type', '!=', 'admin'))
            ->select('user_id', 'advertisement_id')
            ->groupBy('user_id', 'advertisement_id')
            ->with(['user', 'advertisement'])
            ->get();

        return ResponseHelper::success($freelancers, __('messages.best_freelancers'));
    }

    /**
     * Search across freelancers, services, works, or all
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string|min:2',
            'type' => 'required|in:freelancer,service,work,all',
        ]);

        return match ($request->type) {
            'freelancer' => $this->searchFreelancer($request->key),
            'service'    => $this->searchService($request->key),
            'work'       => $this->searchOurWorks($request->key),
            'all'        => $this->searchAll($request->key),
        };
    }

    private function searchFreelancer(string $query): JsonResponse
    {
        $freelancers = User::where('name', 'like', "%{$query}%")
            ->withAvg('ratings', 'rate')
            ->orderByDesc('ratings_avg_rate')
            ->get();

        return ResponseHelper::success($freelancers, __('messages.freelancer_retrieved'));
    }

    private function searchService(string $query): JsonResponse
    {
        $services = Advertisement::where(fn($q) => $q->where('title', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%"))
            ->with('user')
            ->get();

        return ResponseHelper::success($services, __('messages.services_retrieved'));
    }

    private function searchOurWorks(string $query): JsonResponse
    {
        $works = OurWork::where(fn($q) => $q->where('title', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%"))
            ->with('user')
            ->latest()
            ->get();

        return ResponseHelper::success($works, __('messages.works_retrieved'));
    }

    /**
     * Retrieve statistics for a freelancer
     */
    public function statistics(int $id): JsonResponse
    {
        $freelancer = User::find($id);

        if (!$freelancer) {
            return ResponseHelper::error(__('messages.freelancer_not_found'), 404);
        }

        $stats = [
            'freelancer' => $freelancer,
            'advertisements_count' => Advertisement::where('user_id', $id)->count(),
            'contracts_count' => AdvertisementContracts::where('publisher_id', $id)->count(),
            'ourworks_count' => OurWork::where('user_id', $id)->count(),
            'ratings_count' => Rating::where('user_id', $id)->count(),
            'rating_avg' => round(Rating::where('user_id', $id)->avg('rate'), 1),
            'total_earnings' => AdvertisementContracts::where('publisher_id', $id)
                ->where('contract_status', 'completed')
                ->sum('actual_amount'),
        ];

        return ResponseHelper::success($stats, __('messages.statistics_retrieved'));
    }


    /**
     * Customer: Create transaction + upload attachment
     * POST /create-transaction
     */
    public function CreateTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'adv_id' => 'required|exists:advertisements,id',
            'images' => 'required|file',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                __('messages.validation_failed'),
                $validator->errors(),
                422
            );
        }

        $advActualAmount = AdvertisementContracts::where('advertisement_id', $request->adv_id)
            ->value('actual_amount'); // cleaner than first()->

        $appPercentage = config('settings.APP_AMOUNT'); // e.g. 10 for 10%

        $appAmount = $advActualAmount * ($appPercentage / 100);

        $transaction = PaymentTransaction::create([
            'adv_id'           => $request->adv_id,
            'customer_id'      => auth()->id(),
            'transaction_date' => now(),
            'status'           => 'pending',
            'note'             => $request->note,
            'created_by'       => auth()->id(),
            'app_amount'       => $appAmount,
        ]);


        return ResponseHelper::success($transaction, __('messages.submit_transaction_success'));
    }
}
