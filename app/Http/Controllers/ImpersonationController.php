<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Routing\Controller;

class ImpersonationController extends Controller
{
    public function __construct(protected TokenService $tokenService)
    {
    }

    public function impersonate(Request $request)
    {
        $this->authorize('impersonate', User::class);

        $actingUser = $request->user();
        $targetUser = User::findOrFail($request->input('user_id'));

        $tokenData = $this->tokenService->createTokenForUser(
            $targetUser, [
            'impersonated_by' => $actingUser->id,
            'expires_in' => 900,
            'scopes' => ['openid', 'email'],
            'client_id' => 'shopify' // or real OIDC client ID
            ]
        );

        return response()->json($tokenData);
    }

}
