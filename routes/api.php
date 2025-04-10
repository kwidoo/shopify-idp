<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get(
    '/user', function (Request $request) {
        return $request->user();
    }
)->middleware('auth:api');

Route::middleware('auth:api')->post('/impersonate', [ImpersonationController::class, 'impersonate']);
Route::middleware('auth:api')->get(
    '/api/userinfo', function (Request $request) {
        return response()->json(
            [
            'sub' => $request->user()->id,
            'email' => $request->user()->email,
            'name' => $request->user()->name,
            ]
        );
    }
);
