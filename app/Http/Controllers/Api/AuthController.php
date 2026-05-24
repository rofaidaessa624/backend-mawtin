<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // ============ Admin & Staff Login ============
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'بيانات الدخول غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'البريد الإلكتروني أو كلمة السر غير صحيحة'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'الحساب غير نشط. يرجى التواصل مع الدعم'
            ], 403);
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ],
            'token' => $token
        ]);
    }

    // ============ Register (Admin creating new staff) ============
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,staff',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'بيانات التسجيل غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ]);

        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'تم إنشاء المستخدم بنجاح',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    // ============ Logout ============
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح'
        ]);
    }

    // ============ Get current user ============
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    // ============ Client Login (using national_id) ============
    public function clientLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'national_id' => 'required|string|exists:clients,national_id',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'بيانات الدخول غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $client = Client::where('national_id', $request->national_id)->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
            return response()->json([
                'message' => 'الرقم القومي أو كلمة السر غير صحيحة'
            ], 401);
        }

        if (!$client->is_active) {
            return response()->json([
                'message' => 'الحساب غير نشط. يرجى التواصل مع الدعم'
            ], 403);
        }

        $token = $client->createToken('client-token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'client' => [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'national_id' => $client->national_id,
                'is_active' => $client->is_active,
            ],
            'token' => $token
        ]);
    }

    // ============ Client Logout ============
    public function clientLogout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح'
        ]);
    }

    // ============ Client Register (new client signup) ============
    public function clientRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'required|string',
            'national_id' => 'required|string|unique:clients,national_id|size:14',
            'password' => 'required|string|min:6',
            'address' => 'nullable|string',
            'gender' => 'nullable|in:male,female',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'بيانات التسجيل غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $client = Client::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'national_id' => $request->national_id,
        'password' => Hash::make($request->password), // ← التشفير هنا
            'address' => $request->address,
            'gender' => $request->gender,
            'is_active' => true,
        ]);

        $token = $client->createToken('client-token')->plainTextToken;

        return response()->json([
            'message' => 'تم إنشاء الحساب بنجاح',
            'client' => $client,
            'token' => $token
        ], 201);
    }
}