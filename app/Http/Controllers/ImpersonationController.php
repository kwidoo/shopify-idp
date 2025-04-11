<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Contracts\UserRepository;
use App\Contracts\TokenServiceInterface;
use App\Models\User;

class ImpersonationController extends Controller
{
    public function __construct(
        protected TokenServiceInterface $tokenService,
        protected UserRepository $userRepository
    ) {
    }

    public function impersonate(Request $request)
    {
        $this->authorize('impersonate', User::class);

        $actingUser = $request->user();
        $targetUser = $this->userRepository->findById($request->input('user_id'));

        if (!$targetUser) {
            abort(404, 'User not found.');
        }

        $tokenData = $this->tokenService->createTokenForUser(
            $targetUser, [
            'impersonated_by' => $actingUser->id,
            'expires_in' => 900,
            'scopes' => ['openid', 'email'],
            'client_id' => 'shopify'
            ]
        );

        return response()->json($tokenData);
    }
}
