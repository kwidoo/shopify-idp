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
        // Verify state parameter to prevent CSRF attacks
        if ($request->state !== session('oidc_state')) {
            return redirect()->route('login')
                ->withErrors(['message' => 'Invalid state parameter. Authentication failed.']);
        }

        try {
            // Exchange authorization code for tokens
            $tokens = $this->oidcClientService->getTokensFromAuthorizationCode($request->code);

            // Validate the ID token
            $idTokenPayload = $this->oidcClientService->validateIdToken($tokens['id_token']);

            // Process user login or registration
            $user = $this->userProvisioningService->findOrCreateUser($idTokenPayload);

            // Log the user in
            Auth::login($user);

            return redirect()->intended('/dashboard');
        } catch (OIDCException $e) {
            return redirect()->route('login')
                ->withErrors(['message' => $e->getMessage()]);
        }
    }
}
