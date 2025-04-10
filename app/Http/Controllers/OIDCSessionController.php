<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OIDCClientService;

class OIDCSessionController extends Controller
{
    public function __construct(protected OIDCClientService $oidc)
    {
    }

    public function handleCallback(Request $request)
    {
        $idToken = $request->input('id_token');

        if (!$idToken) {
            return response('Missing id_token', 400);
        }

        $claims = $this->oidc->validateIdToken($idToken);

        if (!$claims) {
            return response('Invalid ID token', 401);
        }

        // Optional: Authenticate locally
        // $user = User::where('email', $claims['email'])->first();
        // Auth::login($user);

        return response()->json(
            [
            'message' => 'Logged in via OIDC',
            'claims' => $claims
            ]
        );
    }
}
