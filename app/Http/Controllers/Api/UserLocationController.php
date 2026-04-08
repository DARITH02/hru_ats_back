<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserLocation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserLocationController extends Controller
{
    /**
     * Store location data from frontend.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy'  => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Capture additional context
        $userId    = Auth::id(); // null if not logged in
        $ipAddress = $request->ip();
        $userAgent = $request->header('User-Agent');

        // 3. Store in database
        try {
            $location = UserLocation::create([
                'user_id'    => $userId,
                'latitude'   => $request->latitude,
                'longitude'  => $request->longitude,
                'accuracy'   => $request->accuracy,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Location saved successfully',
                'data'    => $location
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to save location',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
