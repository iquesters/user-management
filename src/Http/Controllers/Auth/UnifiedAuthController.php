<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserManagement\Helpers\BaseAuthHelper;
use Iquesters\UserManagement\Helpers\LoginHelper;
use Iquesters\UserManagement\Helpers\RegistrationHelper;
use Iquesters\UserManagement\Http\Requests\Auth\CompleteIdentifierRegistrationRequest;
use Iquesters\UserManagement\Http\Requests\Auth\IdentifyAuthIdentifierRequest;
use Iquesters\UserManagement\Http\Requests\Auth\SendIdentifierOtpRequest;
use Iquesters\UserManagement\Http\Requests\Auth\VerifyIdentifierOtpRequest;
use Iquesters\UserManagement\Services\AuthFlowStateService;
use Iquesters\UserManagement\Services\IdentifierResolverService;
use Iquesters\UserManagement\Services\OtpService;
use Iquesters\UserManagement\Services\RegistrationFieldService;

class UnifiedAuthController extends Controller
{
    protected const FLOW_SCOPE = 'unified_auth';

    public function __construct(
        protected AuthFlowStateService $authFlowStateService,
        protected IdentifierResolverService $identifierResolverService,
        protected OtpService $otpService,
        protected RegistrationFieldService $registrationFieldService
    ) {
    }

    public function show(): View|RedirectResponse
    {
        if (!$this->isUnifiedFlowEnabled()) {
            return redirect()->route('login');
        }

        return view('usermanagement::auth.unified-login', [
            'countryDialCodes' => $this->identifierResolverService->getCountryDialCodes(),
        ]);
    }

    public function country(): JsonResponse
    {
        if (!$this->isUnifiedFlowEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Unified sign-in is not enabled.',
            ], 404);
        }

        $countryCode = BaseAuthHelper::getDetectedCountryCode();
        $dialCode = $this->identifierResolverService->getDialCodeForCountry($countryCode);

        Log::info('Resolved default country metadata for unified auth.', [
            'auth_method' => 'unified_auth',
            'operation' => 'detect_country',
            'country_code' => $countryCode,
            'dial_code' => $dialCode,
        ]);

        return response()->json([
            'success' => true,
            'country_code' => $countryCode,
            'dial_code' => $dialCode,
        ]);
    }

    public function identify(IdentifyAuthIdentifierRequest $request): JsonResponse
    {
        if (!$this->isUnifiedFlowEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Unified sign-in is not enabled.',
            ], 404);
        }

        $resolved = $this->identifierResolverService->resolve(
            $request->string('identifier')->toString(),
            $request->input('country_dial_code')
        );

        $user = $resolved['user'];
        $availableLoginMethods = ['otp'];

        if ($user?->email) {
            $availableLoginMethods[] = 'password';
        }

        $sessionState = [
            'identifier_type' => $resolved['identifier_type'],
            'identifier' => $resolved['normalized_identifier'],
            'masked_identifier' => $resolved['masked_identifier'],
            'delivery_channel' => $resolved['delivery_channel'],
            'status' => $user ? 'existing' : 'new',
            'user_id' => $user?->id,
            'password_login_email' => $user?->email,
            'identified_at' => now()->toDateTimeString(),
        ];

        $flowToken = $this->authFlowStateService->createFlow($request, self::FLOW_SCOPE, $sessionState);

        Log::info('Stored unified auth identification state.', [
            'auth_method' => 'unified_auth',
            'operation' => 'identify',
            'identifier_type' => $sessionState['identifier_type'],
            'delivery_channel' => $sessionState['delivery_channel'],
            'masked_identifier' => $sessionState['masked_identifier'],
            'status' => $sessionState['status'],
            'user_id' => $sessionState['user_id'],
            'flow_token' => substr($flowToken, 0, 12) . '...',
        ]);

        return response()->json([
            'success' => true,
            'status' => $sessionState['status'],
            'flow_token' => $flowToken,
            'identifier_type' => $sessionState['identifier_type'],
            'normalized_identifier' => $sessionState['identifier'],
            'delivery_channel' => $sessionState['delivery_channel'],
            'masked_identifier' => $sessionState['masked_identifier'],
            'available_login_methods' => array_values(array_unique($availableLoginMethods)),
            'password_login_email' => $sessionState['password_login_email'],
        ]);
    }

    public function sendOtp(SendIdentifierOtpRequest $request): JsonResponse
    {
        if (!$this->isUnifiedFlowEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Unified sign-in is not enabled.',
            ], 404);
        }

        $state = $this->validatedState(
            $request,
            $request->input('flow_token'),
            $request->input('identifier_type'),
            $request->input('identifier'),
            $request->input('delivery_channel')
        );

        if ($state instanceof JsonResponse) {
            return $state;
        }

        $response = $state['status'] === 'existing'
            ? $this->otpService->sendLoginOtp($state['identifier_type'], $state['identifier'], $state['delivery_channel'], $request->ip(), $request->userAgent())
            : $this->otpService->sendRegistrationOtp($state['identifier_type'], $state['identifier'], $state['delivery_channel'], $request->ip(), $request->userAgent());

        return response()->json($response, $response['success'] ? 200 : 422);
    }

    public function resendOtp(SendIdentifierOtpRequest $request): JsonResponse
    {
        if (!$this->isUnifiedFlowEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Unified sign-in is not enabled.',
            ], 404);
        }

        $state = $this->validatedState(
            $request,
            $request->input('flow_token'),
            $request->input('identifier_type'),
            $request->input('identifier'),
            $request->input('delivery_channel')
        );

        if ($state instanceof JsonResponse) {
            return $state;
        }

        $response = $state['status'] === 'existing'
            ? $this->otpService->resendLoginOtp($state['identifier_type'], $state['identifier'], $state['delivery_channel'], $request->ip(), $request->userAgent())
            : $this->otpService->sendRegistrationOtp($state['identifier_type'], $state['identifier'], $state['delivery_channel'], $request->ip(), $request->userAgent());

        return response()->json($response, $response['success'] ? 200 : 422);
    }

    public function verifyOtp(VerifyIdentifierOtpRequest $request): JsonResponse
    {
        if (!$this->isUnifiedFlowEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Unified sign-in is not enabled.',
            ], 404);
        }

        $state = $this->validatedState(
            $request,
            $request->input('flow_token'),
            $request->input('identifier_type'),
            $request->input('identifier'),
            $request->input('delivery_channel')
        );

        if ($state instanceof JsonResponse) {
            return $state;
        }

        if ($state['status'] === 'existing') {
            $verificationResult = $this->otpService->verifyLoginOtp(
                $state['identifier_type'],
                $state['identifier'],
                $state['delivery_channel'],
                $request->string('otp')->toString(),
                $request->ip()
            );

            if (!$verificationResult['success']) {
                return response()->json($verificationResult, 422);
            }

            $user = $verificationResult['user'];

            if (!$user || !LoginHelper::process_login($user)) {
                Log::error('Unified auth login failed after successful OTP verification.', [
                    'auth_method' => 'unified_auth',
                    'operation' => 'verify_otp',
                    'identifier_type' => $state['identifier_type'],
                    'delivery_channel' => $state['delivery_channel'],
                    'masked_identifier' => $state['masked_identifier'],
                    'user_id' => $user?->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to complete login right now. Please try again.',
                ], 500);
            }

            $request->session()->regenerate();
            $this->authFlowStateService->forgetFlowState($request, self::FLOW_SCOPE, $request->string('flow_token')->toString());

            Log::info('Unified auth login session issued successfully.', [
                'auth_method' => 'unified_auth',
                'operation' => 'verify_otp',
                'identifier_type' => $state['identifier_type'],
                'delivery_channel' => $state['delivery_channel'],
                'masked_identifier' => $state['masked_identifier'],
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully.',
                'redirect_url' => route(ConfProvider::from(Module::USER_MGMT)->default_auth_route ?? 'dashboard'),
            ]);
        }

        $verificationResult = $this->otpService->verifyOtpChallenge(
            $state['identifier_type'],
            $state['identifier'],
            $state['delivery_channel'],
            $request->string('otp')->toString(),
            $request->ip()
        );

        if (!$verificationResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $verificationResult['message'],
            ], 422);
        }

        if ($this->identifierResolverService->findUserByIdentifier($state['identifier_type'], $state['identifier'])) {
            return response()->json([
                'success' => false,
                'message' => 'This identifier is already linked to an account.',
            ], 422);
        }

        $fields = $this->registrationFieldService->presentableFields();
        $state['verified_at'] = now()->toDateTimeString();
        $state['otp_verified'] = true;
        $this->authFlowStateService->putFlowState(
            $request,
            self::FLOW_SCOPE,
            $request->string('flow_token')->toString(),
            $state
        );

        Log::info('Unified auth registration identifier verified.', [
            'auth_method' => 'unified_auth',
            'operation' => 'verify_otp',
            'identifier_type' => $state['identifier_type'],
            'delivery_channel' => $state['delivery_channel'],
            'masked_identifier' => $state['masked_identifier'],
            'requires_additional_fields' => !empty($fields),
        ]);

        if (empty($fields)) {
            return $this->completeRegistrationFromState($request, $state, []);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification successful. Please complete the remaining fields.',
            'flow_token' => $request->string('flow_token')->toString(),
            'requires_registration_fields' => true,
            'fields' => $fields,
        ]);
    }

    public function completeRegistration(CompleteIdentifierRegistrationRequest $request): JsonResponse
    {
        if (!$this->isUnifiedFlowEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Unified sign-in is not enabled.',
            ], 404);
        }

        $state = $this->authFlowStateService->getFlowState(
            $request,
            self::FLOW_SCOPE,
            $request->string('flow_token')->toString()
        ) ?? [];

        if (($state['status'] ?? null) !== 'new' || !($state['otp_verified'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your identifier first.',
            ], 422);
        }

        $validator = Validator::make($request->all(), $this->registrationFieldService->validationRules());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete the required registration fields.',
                'errors' => $validator->errors(),
            ], 422);
        }

        return $this->completeRegistrationFromState($request, $state, $request->input('fields', []));
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $submittedFields
     */
    protected function completeRegistrationFromState(Request $request, array $state, array $submittedFields): JsonResponse
    {
        if ($this->identifierResolverService->findUserByIdentifier($state['identifier_type'], $state['identifier'])) {
            return response()->json([
                'success' => false,
                'message' => 'This identifier is already linked to an account.',
            ], 422);
        }

        $meta = $this->registrationFieldService->metaPayload($submittedFields);
        $resolvedName = trim((string) ($submittedFields['name'] ?? $meta['name'] ?? RegistrationHelper::default_name_for_identifier($state['identifier'])));

        $user = RegistrationHelper::register_user(
            name: $resolvedName,
            identifierType: $state['identifier_type'],
            identifierValue: $state['identifier'],
            password: null,
            email_verified: $state['identifier_type'] === 'email',
            meta: $meta
        );

        if ($state['identifier_type'] === 'phone' && empty($user->phone_verified_at)) {
            $user->forceFill([
                'phone_verified_at' => now(),
            ])->save();
        }

        if (!LoginHelper::process_login($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Account created, but login could not be completed.',
            ], 500);
        }

        $request->session()->regenerate();
        $this->authFlowStateService->forgetFlowState($request, self::FLOW_SCOPE, $request->string('flow_token')->toString());

        Log::info('Unified auth account created and login completed.', [
            'auth_method' => 'unified_auth',
            'operation' => 'complete_registration',
            'identifier_type' => $state['identifier_type'],
            'delivery_channel' => $state['delivery_channel'],
            'masked_identifier' => $state['masked_identifier'],
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully.',
            'redirect_url' => route(ConfProvider::from(Module::USER_MGMT)->default_auth_route ?? 'dashboard'),
        ]);
    }

    protected function isUnifiedFlowEnabled(): bool
    {
        return (ConfProvider::from(Module::USER_MGMT)->signin_flow ?? 'classic') === 'unified';
    }

    protected function validatedState(
        Request $request,
        ?string $flowToken,
        ?string $identifierType,
        ?string $identifier,
        ?string $deliveryChannel
    ): array|JsonResponse
    {
        $state = $flowToken
            ? ($this->authFlowStateService->getFlowState($request, self::FLOW_SCOPE, $flowToken) ?? [])
            : [];

        if (($state['identifier_type'] ?? null) !== $identifierType
            || ($state['identifier'] ?? null) !== $identifier
            || ($state['delivery_channel'] ?? null) !== $deliveryChannel) {
            return response()->json([
                'success' => false,
                'message' => 'Please start again from the identifier step.',
            ], 422);
        }

        return $state;
    }
}
