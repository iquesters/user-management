<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserManagement\Helpers\LoginHelper;
use Iquesters\UserManagement\Helpers\RegistrationHelper;
use Iquesters\UserManagement\Http\Requests\Auth\CompleteWhatsappRegistrationRequest;
use Iquesters\UserManagement\Http\Requests\Auth\ResendWhatsappOtpRequest;
use Iquesters\UserManagement\Http\Requests\Auth\SendWhatsappOtpRequest;
use Iquesters\UserManagement\Http\Requests\Auth\VerifyWhatsappOtpRequest;
use Iquesters\UserManagement\Services\AuthFlowStateService;
use Iquesters\UserManagement\Services\OtpVerificationService;
use Iquesters\UserManagement\Services\PhoneNumberService;
use Iquesters\UserManagement\Services\WhatsAppOtpService;

class OtpController extends Controller
{
    protected const PENDING_REGISTRATION_SCOPE = 'pending_registration';
    protected const LEGACY_WHATSAPP_FLOW_TOKEN = 'legacy_whatsapp_registration';

    public function __construct(
        protected AuthFlowStateService $authFlowStateService,
        protected WhatsAppOtpService $whatsAppOtpService,
        protected OtpVerificationService $otpVerificationService,
        protected PhoneNumberService $phoneNumberService
    ) {
    }

    public function sendOtp(SendWhatsappOtpRequest $request): JsonResponse
    {
        return $this->executeOtpAction($request, 'send_otp', function () use ($request) {
            $response = $this->whatsAppOtpService->sendOtp(
                $request->string('phone')->toString(),
                $request->ip(),
                $request->userAgent()
            );

            return response()->json($response, $response['success'] ? 200 : 422);
        });
    }

    public function sendRegistrationOtp(SendWhatsappOtpRequest $request): JsonResponse
    {
        return $this->executeOtpAction($request, 'send_register_otp', function () use ($request) {
            $response = $this->whatsAppOtpService->sendRegistrationOtp(
                $request->string('phone')->toString(),
                $request->ip(),
                $request->userAgent()
            );

            return response()->json($response, $response['success'] ? 200 : 422);
        });
    }

    public function resendOtp(ResendWhatsappOtpRequest $request): JsonResponse
    {
        return $this->executeOtpAction($request, 'resend_otp', function () use ($request) {
            $response = $this->whatsAppOtpService->resendOtp(
                $request->string('phone')->toString(),
                $request->ip(),
                $request->userAgent()
            );

            return response()->json($response, $response['success'] ? 200 : 422);
        });
    }

    public function verifyOtp(VerifyWhatsappOtpRequest $request): JsonResponse
    {
        return $this->executeOtpAction($request, 'verify_otp', function () use ($request) {
            $maskedPhone = $this->phoneNumberService->mask($request->input('phone'));
            $verificationResult = $this->otpVerificationService->verifyOtp(
                $request->string('phone')->toString(),
                $request->string('otp')->toString(),
                $request->ip()
            );

            if (!$verificationResult['success']) {
                return response()->json($verificationResult, 422);
            }

            $user = $verificationResult['user'];

            if (!$user || !LoginHelper::process_login($user)) {
                Log::error('WhatsApp OTP login failed after successful verification because session issuance could not complete.', [
                    'auth_method' => 'whatsapp_otp',
                    'operation' => 'verify_otp',
                    'masked_phone' => $maskedPhone,
                    'user_id' => $user?->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to complete login right now. Please try again.',
                ], 500);
            }

            $request->session()->regenerate();

            Log::info('WhatsApp OTP login session issued successfully.', [
                'auth_method' => 'whatsapp_otp',
                'operation' => 'verify_otp',
                'masked_phone' => $maskedPhone,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully.',
                'redirect_url' => route(ConfProvider::from(Module::USER_MGMT)->default_auth_route ?? 'dashboard'),
            ]);
        });
    }

    public function verifyRegistrationOtp(VerifyWhatsappOtpRequest $request): JsonResponse
    {
        return $this->executeOtpAction($request, 'verify_register_otp', function () use ($request) {
            $verificationResult = $this->otpVerificationService->verifyPhoneOtpChallenge(
                $request->string('phone')->toString(),
                $request->string('otp')->toString(),
                $request->ip()
            );

            if (!$verificationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $verificationResult['message'],
                ], 422);
            }

            $verifiedPhone = $verificationResult['normalized_phone'];

            if ($this->whatsAppOtpService->findUserByPhone($verifiedPhone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This WhatsApp number is already registered.',
                ], 422);
            }

            $this->authFlowStateService->putFlowState(
                $request,
                self::PENDING_REGISTRATION_SCOPE,
                self::LEGACY_WHATSAPP_FLOW_TOKEN,
                [
                    'identifier_type' => 'phone',
                    'identifier' => $verifiedPhone,
                    'verified_at' => now()->toDateTimeString(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp number verified. Continue with your name to create the account.',
            ]);
        });
    }

    public function completeRegistration(CompleteWhatsappRegistrationRequest $request): JsonResponse
    {
        return $this->executeOtpAction($request, 'complete_registration', function () use ($request) {
            $pendingRegistration = $this->authFlowStateService->getFlowState(
                $request,
                self::PENDING_REGISTRATION_SCOPE,
                self::LEGACY_WHATSAPP_FLOW_TOKEN
            );
            $verifiedPhone = $pendingRegistration['identifier'] ?? null;

            if (empty($verifiedPhone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your WhatsApp number first.',
                ], 422);
            }

            if ($this->whatsAppOtpService->findUserByPhone($verifiedPhone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This WhatsApp number is already registered.',
                ], 422);
            }

            $user = RegistrationHelper::register_user(
                name: $request->string('name')->toString(),
                identifierType: 'phone',
                identifierValue: $verifiedPhone,
                password: null,
                email_verified: false,
                meta: [],
                extraAttributes: [
                    'email' => $request->string('email')->toString(),
                ]
            );

            if (empty($user->phone_verified_at)) {
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
            $this->authFlowStateService->forgetFlowState($request, self::PENDING_REGISTRATION_SCOPE, self::LEGACY_WHATSAPP_FLOW_TOKEN);

            return response()->json([
                'success' => true,
                'message' => 'Account created successfully.',
                'redirect_url' => route(ConfProvider::from(Module::USER_MGMT)->default_auth_route ?? 'dashboard'),
            ]);
        });
    }

    protected function executeOtpAction(Request $request, string $operation, callable $callback): JsonResponse
    {
        $maskedPhone = $request->has('phone') ? $this->phoneNumberService->mask((string) $request->input('phone')) : null;

        Log::info('Received WhatsApp OTP controller request.', [
            'auth_method' => 'whatsapp_otp',
            'operation' => $operation,
            'masked_phone' => $maskedPhone,
            'request_ip' => $request->ip(),
        ]);

        try {
            return $callback();
        } catch (\Throwable $throwable) {
            Log::error('WhatsApp OTP controller action failed unexpectedly.', [
                'auth_method' => 'whatsapp_otp',
                'operation' => $operation,
                'masked_phone' => $maskedPhone,
                'request_ip' => $request->ip(),
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process your request right now. Please try again.',
            ], 500);
        }
    }
}
