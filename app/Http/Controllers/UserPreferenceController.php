<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserPreferenceController extends Controller
{
    /**
     * Get all preferences for the authenticated user
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id();
        $preferences = UserPreference::getAllForUser($userId);

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    /**
     * Get a specific preference for the authenticated user
     */
    public function show(string $key): JsonResponse
    {
        $userId = Auth::id();
        $value = UserPreference::getValue($userId, $key);

        if ($key === 'theme') {
            return response()->json([
                'theme' => $value,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $key,
                'value' => $value,
            ],
        ]);
    }

    /**
     * Set/update preferences for the authenticated user
     * Accepts single preference or multiple preferences
     */
    public function store(Request $request): JsonResponse
    {
        $userId = Auth::id();

        // Validate request
        $request->validate([
            'key' => 'required_without:preferences|string',
            'value' => 'required_without:preferences',
            'preferences' => 'required_without:key|array',
            'preferences.*.key' => 'required|string',
            'preferences.*.value' => 'required',
        ]);

        try {
            if ($request->has('preferences')) {
                // Bulk update
                foreach ($request->preferences as $preference) {
                    UserPreference::setValue($userId, $preference['key'], $preference['value']);
                }
            } else {
                // Single preference update
                UserPreference::setValue($userId, $request->key, $request->value);
            }

            return response()->json([
                'success' => true,
                'message' => 'Preferences updated successfully',
            ]);

        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'preferences' => ['Failed to update preferences: ' . $e->getMessage()],
            ]);
        }
    }

    /**
     * Update a specific preference
     */
    public function update(Request $request, string $key): JsonResponse
    {
        if ($key === 'theme') {
            $value = $request->input('theme', $request->input('value', 'light'));
            // Validate theme value
            if (!in_array($value, ['light', 'dark', 'auto'])) {
                throw ValidationException::withMessages([
                    'theme' => ['Theme must be light, dark, or auto'],
                ]);
            }
        } else {
            $request->validate([
                'value' => 'required',
            ]);
            $value = $request->value;
        }

        $userId = Auth::id();
        UserPreference::setValue($userId, $key, $value);

        if ($key === 'theme') {
            return response()->json([
                'theme' => $value,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Preference updated successfully',
            'data' => [
                'key' => $key,
                'value' => $value,
            ],
        ]);
    }

    /**
     * Delete a specific preference
     */
    public function destroy(string $key): JsonResponse
    {
        $userId = Auth::id();

        $deleted = UserPreference::where('user_id', $userId)
            ->where('key', $key)
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Preference deleted successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Preference not found',
        ], 404);
    }
}
