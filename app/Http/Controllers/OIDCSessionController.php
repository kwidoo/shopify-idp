<?php

namespace App\Http\Controllers;

use App\Contracts\OIDCClientServiceInterface;
use App\Contracts\UserProvisioningServiceInterface;
use App\Exceptions\OIDCException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OIDCSessionController extends Controller
{
    public function __construct(
        protected OIDCClientServiceInterface $oidcService,
        protected UserProvisioningServiceInterface $userProvisioningService
    ) {}

    public function redirectToShopify(Request $request)
    {
        // Generate a random state and store it in the session
        $state = Str::random(40);
        $request->session()->put('oidc_state', $state);

        $authUrl = $this->oidcService->createAuthorizationUrl([
            'state' => $state,
        ]);

        return redirect()->away($authUrl);
    }

    public function handleCallback(Request $request)
    {
        // Verify state parameter to prevent CSRF
        if ($request->input('state') !== session('oidc_state')) {
            return redirect()->route('login')
                ->withErrors(['message' => 'Invalid state parameter. Authentication failed.']);
        }

        try {
            // Exchange authorization code for tokens
            $tokens = $this->oidcService->getTokensFromAuthorizationCode($request->input('code'));

            // Validate ID token
            $claims = $this->oidcService->validateIdToken($tokens['id_token']);

            if (!$claims) {
                return redirect()->route('login')
                    ->withErrors(['message' => 'Invalid ID token. Authentication failed.']);
            }

            // Find or create user
            $user = $this->userProvisioningService->findOrCreateUser($claims);

            // Log the user in
            Auth::login($user);

            return redirect()->intended(route('dashboard'));
        } catch (OIDCException $e) {
            return redirect()->route('login')
                ->withErrors(['message' => $e->getOIDCErrorDescription() ?? $e->getOIDCError()]);
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('login')
                ->withErrors(['message' => 'Authentication failed. Please try again later.']);
        }
    }
}
