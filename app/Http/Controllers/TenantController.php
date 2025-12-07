<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class TenantController extends Controller
{
    /**
     * Get current tenant information
     */
    public function show()
    {
        $tenant = Auth::user()->tenant;
        
        return response()->json([
            'success' => true,
            'data' => $tenant
        ]);
    }

    /**
     * Update tenant logo
     */
    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $tenant = Auth::user()->tenant;

        // Delete old logo if exists
        if ($tenant->logo && Storage::disk('public')->exists($tenant->logo)) {
            Storage::disk('public')->delete($tenant->logo);
        }

        // Upload new logo
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'logo_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $logoPath = $file->storeAs('logos', $filename, 'public');

            $tenant->update([
                'logo' => $logoPath
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logo updated successfully',
            'data' => $tenant->fresh()
        ]);
    }

    /**
     * Update tenant information
     */
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'pincode' => 'sometimes|string|max:10',
            'website' => 'sometimes|url|max:255',
        ]);

        $tenant = Auth::user()->tenant;
        $tenant->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'School information updated successfully',
            'data' => $tenant
        ]);
    }

    /**
     * Delete tenant logo
     */
    public function deleteLogo()
    {
        $tenant = Auth::user()->tenant;

        if ($tenant->logo && Storage::disk('public')->exists($tenant->logo)) {
            Storage::disk('public')->delete($tenant->logo);
            $tenant->update(['logo' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logo deleted successfully'
        ]);
    }
}
