<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;

class RegisterController extends Controller
{
    public function store(Request $request){
        $request->validate([
            'name'=>'required',
            'email'=>'required|email|unique:users',
            'password'=>'required|min:6|confirmed'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email'=>$request->email,
            'password'=>bcrypt($request->password)
        ]);

        $token = JWTAuth::fromUser($user);//creamos el token usando el facade JWTAuth

        //return response()->json($user,201);
        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }
}
