<?php

namespace App\Http\Controllers;

use App\Contracts\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class AdminImpersonationController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Display the impersonation page
     */
    public function index()
    {
        $users = $this->userRepository->all();

        return Inertia::render('Admin/Impersonation', [
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'shopify_id' => $user->shopify_id,
                ];
            })
        ]);
    }
}
