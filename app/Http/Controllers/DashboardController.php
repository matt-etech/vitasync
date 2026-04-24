<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Home;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'userCount' => User::count(),
            'carerCount' => User::whereHas('roles', fn ($query) => $query->where('name', 'Carer'))->count(),
            'roleCount' => Role::count(),
            'permissionCount' => Permission::count(),
            'homeCount' => Home::count(),
            'clientCount' => Client::count(),
        ]);
    }
}
