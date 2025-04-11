<?php

namespace App\Http\Controllers\API;

use App\Contracts\TokenServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TokenRefreshController extends Controller
{
    protected $tokenService;

    public function __construct(TokenServiceInterface $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Refresh an access token using a refresh token.
     */
    public function refresh(Request $request)
    {
        try {
            $request->validate([
                'refresh_token' => 'required|string',
            ]);

            $refreshToken = $request->input('refresh_token');
            $tokens = $this->tokenService->refreshToken($refreshToken);

            return response()->json($tokens);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Revoke a refresh token.
     */
    public function revoke(Request $request)
    {
        try {
            $request->validate([
                'refresh_token' => 'required|string',
            ]);

            $refreshToken = $request->input('refresh_token');
            $success = $this->tokenService->revokeToken($refreshToken);

            if ($success) {
                return response()->json(['message' => 'Token revoked successfully']);
            } else {
                return response()->json(['error' => 'Token not found or already revoked'], 404);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'validation_error',
                'error_description' => $e->errors(),
            ], 400);
        }
    }
}
