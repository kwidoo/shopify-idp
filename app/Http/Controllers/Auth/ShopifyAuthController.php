<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\OIDCClientServiceInterface;
use App\Contracts\UserProvisioningServiceInterface;
use App\Exceptions\OIDCException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ShopifyAuthController extends Controller
{
    protected $oidcClientService;
    protected $userProvisioningService;

    public function __construct(
        OIDCClientServiceInterface $oidcClientService,
        UserProvisioningServiceInterface $userProvisioningService
    ) {
        $this->oidcClientService = $oidcClientService;
        $this->userProvisioningService = $userProvisioningService;
    }

    /**
     * Redirect the user to the Shopify authorization page.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(Request $request)
    {
        // Generate and store a state parameter to prevent CSRF
        $state = Str::random(40);
        session(['oidc_state' => $state]);

        // Create the authorization URL with the state parameter
        $authUrl = $this->oidcClientService->createAuthorizationUrl(['state' => $state]);

        return redirect()->away($authUrl);
    }

    /**
     * Handle the callback from Shopify after authorization.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        try {
            // Verify state parameter to prevent CSRF
            if ($request->query('state') !== $request->session()->pull('oidc_state')) {
                $request->session()->put('auth.failed', true);
                $request->session()->put('auth.error', 'Invalid state parameter');
                return redirect()->route('login')
                    ->withErrors(['message' => 'Invalid state parameter. Authentication failed.']);
            }

            // Exchange authorization code for tokens
            $tokens = $this->oidcClientService->getTokensFromAuthorizationCode($request->query('code'));

            // Validate ID token
            $idTokenPayload = $this->oidcClientService->validateIdToken($tokens['id_token']);

            // Find or provision user from claims
            $user = $this->userProvisioningService->findOrCreateUser($idTokenPayload);

            // Log the user in
            Auth::login($user);

            // Set success flag for logging
            $request->session()->put('auth.success', true);
            $request->session()->put('auth.user', $user->email);

            return redirect()->intended('/dashboard');
        } catch (OIDCException $e) {
            // Set failure flag for logging
            $request->session()->put('auth.failed', true);
            $request->session()->put('auth.error', $e->getMessage());

            return redirect()->route('login')
                ->withErrors(['message' => $e->getMessage()]);
        }
    }
}
