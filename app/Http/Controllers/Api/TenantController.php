<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTenantRequest;
use App\Http\Requests\UploadTenantLogoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantController extends Controller
{
    /**
     * Update tenant/business information.
     */
    public function update(UpdateTenantRequest $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $tenant->update($request->validated());

        return response()->json([
            'message' => 'Business details updated successfully',
            'tenant' => [
                'id' => $tenant->id,
                'business_name' => $tenant->business_name,
                'logo_url' => $tenant->getFirstMediaUrl('logo'),
                'logo_thumb_url' => $tenant->getFirstMediaUrl('logo', 'thumb'),
                'phone' => $tenant->phone,
                'email' => $tenant->email,
                'address' => $tenant->address,
                'city' => $tenant->city,
                'country' => $tenant->country,
                'currency' => $tenant->currency,
                'updated_at' => $tenant->updated_at,
            ],
        ]);
    }

    /**
     * Upload or replace business/tenant logo.
     */
    public function uploadLogo(UploadTenantLogoRequest $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $imageData = $request->input('logo');

        // Extract base64 from data URI (data:image/png;base64,xxxxx)
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            $extension = $matches[1];
            $base64Data = substr($imageData, strpos($imageData, ',') + 1);
            $decodedImage = base64_decode($base64Data);

            Log::info('TenantController@uploadLogo called', [
                'tenant_id' => $tenant->id,
                'user_id' => $request->user()->id,
                'extension' => $extension,
                'decoded_size' => strlen($decodedImage),
                'base64_length' => strlen($base64Data),
            ]);

            // Create temporary file
            $tempPath = sys_get_temp_dir().'/'.uniqid().'.'.$extension;
            file_put_contents($tempPath, $decodedImage);

            try {
                // Add to media collection (will replace existing)
                $tenant->addMedia($tempPath)
                    ->toMediaCollection('logo');

                Log::info('Business logo uploaded successfully', [
                    'tenant_id' => $tenant->id,
                    'logo_url' => $tenant->getFirstMediaUrl('logo'),
                ]);

                return response()->json([
                    'message' => 'Business logo uploaded successfully',
                    'logo_url' => $tenant->getFirstMediaUrl('logo'),
                    'logo_thumb_url' => $tenant->getFirstMediaUrl('logo', 'thumb'),
                    'logo_medium_url' => $tenant->getFirstMediaUrl('logo', 'medium'),
                ]);
            } finally {
                // Clean up temp file
                @unlink($tempPath);
            }
        }

        return response()->json(['error' => 'Invalid image format'], 400);
    }

    /**
     * Remove business/tenant logo.
     */
    public function deleteLogo(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $tenant->clearMediaCollection('logo');

        return response()->json([
            'message' => 'Business logo removed successfully',
        ]);
    }
}
