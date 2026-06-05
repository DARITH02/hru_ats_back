<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * API Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required_without_all:login,phone',
            'login' => 'required_without_all:email,phone',
            'phone' => 'required_without_all:email,login',
            'password' => 'required',
        ]);

        $loginInput = trim((string) ($request->email ?? $request->login ?? $request->phone));
        $phoneCandidates = $this->phoneLoginCandidates($loginInput);

        $user = User::where('email', $loginInput)
            ->orWhere('phone', $loginInput)
            ->when($phoneCandidates !== [], function ($query) use ($phoneCandidates) {
                $query->orWhereIn($this->normalizedPhoneColumn(), $phoneCandidates);
            })
            ->orWhereHas('student', function($q) use ($loginInput) {
                $q->where('student_code', $loginInput);
            })
            ->first();

        if (!$user) {
            return $this->invalidCredentialsResponse();
        }

        if ($user->role === 'student') {
            $student = \App\Models\Student::where('user_id', $user->id)->first();
            $allowsCodeLogin = config('auth.allow_student_code_login')
                && $student
                && strcasecmp($student->student_code, (string) $request->password) === 0;
            if (!$student || (!$allowsCodeLogin && !Hash::check($request->password, $user->password))) {
                return $this->invalidCredentialsResponse();
            }
        } else {
            if (!Hash::check($request->password, $user->password)) {
                return $this->invalidCredentialsResponse();
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

    private function phoneLoginCandidates(string $value): array
    {
        $digits = preg_replace('/\D+/', '', $value);

        if (!$digits) {
            return [];
        }

        $candidates = [$digits];

        if (str_starts_with($digits, '0')) {
            $candidates[] = '855' . substr($digits, 1);
        } elseif (str_starts_with($digits, '855')) {
            $candidates[] = '0' . substr($digits, 3);
        }

        return array_values(array_unique($candidates));
    }

    private function normalizedPhoneColumn(): \Illuminate\Contracts\Database\Query\Expression
    {
        return DB::raw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', ''), '(', ''), ')', '')");
    }

    private function invalidCredentialsResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'The provided credentials are incorrect.',
            'errors' => [
                'email' => ['The provided credentials are incorrect.'],
            ],
        ], 401);
    }
}
