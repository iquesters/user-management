<?php
 
namespace Iquesters\UserManagement\Http\Controllers\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use App\Models\User;
 
class OtpController extends Controller
{
    /*
        Send otp to the specific phone number
    */
 
    public function sendOtp(Request $request): JsonResponse
    {
        Log::debug("sendOtp hit");
        try {
 
            // Validate phone
            $rules = [
                'phone' => ['required', 'digits:10'],
            ];
 
            $validator = Validator::make($request->all(), $rules);
 
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
 
            $phone = $request->phone;
 
            // Check phone number in database
            $user = User::where('phone', $phone)->first();
 
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone number not found in our records'
                ], 404);
            }
 
            // If found, send OTP
            $otp = rand(1000, 9999);
 
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'response' => [
                    'otp' => $otp
                ]
            ], 200);
 
        } catch (\Exception $e) {
 
            Log::error("sendOtp error: " . $e->getMessage());
 
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage()
            ], 500);
        }
    }
 
 
}