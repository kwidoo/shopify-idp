<?php

namespace App\Http\Controllers;

use App\Contracts\UserRepository;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function __construct(protected UserRepository $userRepository) {}

    public function index(): JsonResponse
    {
        return response()->json($this->userRepository->all()); // Make sure UserRepository has `all()`
    }
}
