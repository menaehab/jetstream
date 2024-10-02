<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class loginUser
{
    public function __invoke(Request $request)
    {
        $user = User::where('name', $request->name)->first();
            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }
    }
}
