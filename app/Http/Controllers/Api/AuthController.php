<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * API Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $loginInput = $request->email;
        $user = User::where('email', $loginInput)
            ->orWhere('phone', $loginInput)
            ->orWhereHas('student', function($q) use ($loginInput) {
                $q->where('student_code', $loginInput);
            })
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->role === 'student') {
            $student = \App\Models\Student::where('user_id', $user->id)->first();
            if (!$student || ($student->student_code !== $request->password && !Hash::check($request->password, $user->password))) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
        } else {
            if (!Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
        }

        $deviceName = $request->device_name ?? 'web-dashboard';
        $token = $user->createToken($deviceName)->plainTextToken;

        $studentData = null;
        if ($user->role === 'student') {
            $student = \App\Models\Student::where('user_id', $user->id)->first();
            if ($student) {
                $studentData = [
                    'student_code' => $student->student_code,
                    'group_id' => $student->group_id,
                    'id' => $student->id
                ];
            }
        }

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'student' => $studentData
            ]
        ]);
    }

    /**
     * API Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * User Profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $studentData = null;
        if ($user->role === 'student') {
            $student = \App\Models\Student::where('user_id', $user->id)->first();
            if ($student) {
                $studentData = [
                    'student_code' => $student->student_code,
                    'group_id' => $student->group_id,
                    'id' => $student->id
                ];
            }
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'student' => $studentData
        ]);
    }

    /**
     * Public Branding Settings
     */
    public function branding()
    {
        return response()->json([
            'success' => true,
            'app_name' => \App\Models\Setting::get('app_name', 'HRU'),
            'app_sub' => \App\Models\Setting::get('app_sub'),
            'system_name' => \App\Models\Setting::get('system_name'),
            'app_logo' => \App\Models\Setting::get('app_logo', 'https://res.cloudinary.com/dnrblpkal/image/upload/q_auto/f_auto/v1775536855/branding/k6obqtagifkszo8pehnd.png'),
            'campus_lat' => \App\Models\Setting::get('campus_lat', '11.524012'),
            'campus_lng' => \App\Models\Setting::get('campus_lng', '104.876273'),
            'campus_radius_meters' => \App\Models\Setting::get('campus_radius_meters', '250'),
            'require_location' => \App\Models\Setting::get('require_location', 'true') === 'true',
        ]);
    }
}
