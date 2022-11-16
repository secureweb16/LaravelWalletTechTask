<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Validator, Hash, Auth;

class UserController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required'
            ]);
            if ($validator->fails()) {
                return response(['status' => false, 'errors' => $validator->errors()->first()], 422);
            }
            $user = User::where('email', $request->email)->first();

            if (!$user)
                return response(["message" => 'Unauthenticated User: Invalid Email'], 403);

            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('testjob')->accessToken;
                $data = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'wallet' => $user->wallet,
                    "token" => $token
                ];
                return response()->json(['status' => true, 'message' => 'User logged in successfully', 'data' => $data], 200);
            } else {
                return response()->json(['success' => false, 'message' => 'Unauthenticated User: Invalid Password'], 403);
            }
        } catch (Exception $e) {
            $response = ["message" => 'Bad request'];
            return response($response, 400);
        }
    }

    public function addMoney(Request $request)
    {
        try {
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:3|max:100',
            ]);
            if ($validator->fails()) {
                return response(['status' => false, 'errors' => $validator->errors()->first()], 422);
            }
            $user->wallet = $user->wallet + $request->amount;
            $user->save();
            return response()->json(['status' => true, 'message' => "$$request->amount added successfully to the wallet", 'balance' => $user->wallet], 200);
        } catch (Exception $e) {
            $msg = "Bad Request";
            Log::channel('wallet')->error($msg);
            return response(["message" => $msg], 400);
        }
    }

    public function buyCookie(Request $request)
    {
        try {
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|numeric|min:1',
            ]);
            if ($request->quantity == 0) {
                return response(['status' => false, 'errors' => 'Items must be greater than 0'], 422);
            }
            $deductamount = $request->quantity * 1;
            if ($deductamount > $user->wallet) // $1 price per item
            {
                return response(['status' => false, 'errors' => 'Insufficient balance in the wallet'], 422);
            }
            if ($validator->fails()) {
                return response(['status' => false, 'errors' => $validator->errors()->first()], 422);
            }
            $user->wallet = $user->wallet - $deductamount;
            $user->save();
            return response()->json(['status' => true, 'message' => "$$deductamount deducted successfully to the wallet", 'balance' => $user->wallet], 200);
        } catch (Exception $e) {
            $msg = "Bad request";
            Log::channel('wallet')->error($msg);
            return response(["message" => $msg], 400);
        }
    }
}
