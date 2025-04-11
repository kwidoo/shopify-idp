<?php

namespace App\Http\Controllers;

use App\Contracts\TokenServiceInterface;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ApiTokenController extends Controller
{
    protected $tokenService;

    public function __construct(TokenServiceInterface $tokenService)
    {
        $this->tokenService = $tokenService;
        $this->middleware('auth');
    }

    /**
     * Show the API token management page.
     */
    public function index()
    {
        $user = Auth::user();
        $tokens = $this->tokenService->listUserTokens($user);

        // Define available scopes for token creation
        $availableScopes = [
            'read:profile' => 'View your profile information',
            'read:orders' => 'View your orders',
            'write:orders' => 'Create and modify orders',
            'read:products' => 'View products',
            'write:products' => 'Create and modify products',
        ];

        return Inertia::render('ApiTokens/Index', [
            'tokens' => $tokens,
            'availableScopes' => $availableScopes,
            'token' => session('token'),
            'refresh_token' => session('refresh_token'),
            'status' => session('status'),
        ]);
    }

    /**
     * Create a new personal access token.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'scopes' => 'array',
            'scopes.*' => Rule::in([
                'read:profile',
                'read:orders',
                'write:orders',
                'read:products',
                'write:products'
            ]),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = $request->user();
        $scopes = $request->input('scopes', []);
        $name = $request->input('name');

        $token = $this->tokenService->createPersonalAccessToken($user, $name, $scopes);

        // Show the token once to the user
        return redirect()->route('api-tokens.index')
            ->with('token', $token['access_token'])
            ->with('refresh_token', $token['refresh_token'])
            ->with('status', 'API token created successfully.');
    }

    /**
     * Revoke the specified token.
     */
    public function destroy(Request $request, $id)
    {
        $token = RefreshToken::findOrFail($id);

        // Check if the token belongs to the authenticated user
        if ($token->user_id !== $request->user()->id) {
            abort(403);
        }

        $token->revoke();

        return redirect()->route('api-tokens.index')
            ->with('status', 'API token revoked successfully.');
    }
}
