<?php

namespace Iquesters\UserManagement\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Iquesters\UserManagement\Services\OrderOtpService;

/**
 * Internal-only order ownership OTP endpoints.
 * The caller must first fetch the actual Woo order and supply its billing
 * phone. These routes never return a destination, order existence or code.
 */
final class OrderOtpController extends Controller
{
    public function issue(Request $request, OrderOtpService $service): JsonResponse
    {
        $v = $request->validate($this->fields());
        $result = $service->issue(
            (int) $v['organisation_id'], $v['channel_uid'], $v['chatbot_uid'],
            (int) $v['store_id'], $v['provider_order_id'],
            $v['requester_phone'], $v['order_phone'],
        );

        return response()->json($result)->header('Cache-Control', 'no-store');
    }

    public function verify(Request $request, OrderOtpService $service): JsonResponse
    {
        $v = $request->validate(array_merge($this->fields(), [
            'challenge_id' => ['required', 'uuid'],
            'code' => ['required', 'regex:/^[0-9]{6}$/D'],
        ]));

        $verified = $service->verify(
            $v['challenge_id'], $v['code'], (int) $v['organisation_id'],
            $v['channel_uid'], $v['chatbot_uid'], (int) $v['store_id'],
            $v['provider_order_id'], $v['requester_phone'], $v['order_phone'],
        );

        return response()->json(['verified' => $verified])->header('Cache-Control', 'no-store');
    }

    private function fields(): array
    {
        return [
            'organisation_id' => ['required', 'integer', 'min:1'],
            'channel_uid' => ['required', 'string', 'max:64'],
            'chatbot_uid' => ['required', 'string', 'max:64'],
            'store_id' => ['required', 'integer', 'min:1'],
            'provider_order_id' => ['required', 'string', 'max:128'],
            'requester_phone' => ['required', 'string', 'regex:/^\\+[1-9][0-9]{7,14}$/D'],
            'order_phone' => ['required', 'string', 'regex:/^\\+[1-9][0-9]{7,14}$/D'],
        ];
    }
}
