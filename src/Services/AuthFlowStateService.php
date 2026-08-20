<?php

namespace Iquesters\UserManagement\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthFlowStateService
{
    protected const SESSION_ROOT = 'user_management.auth_flows';

    /**
     * @param array<string, mixed> $state
     */
    public function createFlow(Request $request, string $scope, array $state): string
    {
        $flowToken = Str::random(48);
        $this->putFlowState($request, $scope, $flowToken, $state);

        Log::info('Created auth flow state token.', [
            'auth_method' => 'auth_flow_state',
            'operation' => 'create_flow',
            'scope' => $scope,
            'flow_token' => substr($flowToken, 0, 12) . '...',
        ]);

        return $flowToken;
    }

    /**
     * @param array<string, mixed> $state
     */
    public function putFlowState(Request $request, string $scope, string $flowToken, array $state): void
    {
        $flows = $request->session()->get($this->sessionKey($scope), []);
        $flows[$flowToken] = $state;
        $request->session()->put($this->sessionKey($scope), $flows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFlowState(Request $request, string $scope, string $flowToken): ?array
    {
        $flows = $request->session()->get($this->sessionKey($scope), []);

        return isset($flows[$flowToken]) && is_array($flows[$flowToken]) ? $flows[$flowToken] : null;
    }

    public function forgetFlowState(Request $request, string $scope, string $flowToken): void
    {
        $flows = $request->session()->get($this->sessionKey($scope), []);
        unset($flows[$flowToken]);
        $request->session()->put($this->sessionKey($scope), $flows);
    }

    protected function sessionKey(string $scope): string
    {
        return self::SESSION_ROOT . '.' . $scope;
    }
}
