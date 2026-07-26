<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;

class ImageUploadController extends Controller
{
    // ⚠️ TEMPORARY DEBUG BUILD — this controller currently logs verbose
    // file/request details and returns raw exception/validation messages
    // in the JSON response instead of generic ones. Revert to the plain
    // version (no \Log:: calls, no 'errors' key, generic catch message)
    // once the intermittent upload failure is found.

    /**
     * POST /api/admin/upload-image
     * Accepts multipart form-data with a `file` field, uploads it to
     * Cloudinary, and returns the real hosted secure_url. This is what
     * ImageUploader.tsx should call the moment an image is picked —
     * instead of storing the local blob:/file: URI, it should store
     * whatever URL this endpoint returns, since that's the only URL that
     * still works once saved to driver_profiles or any other table.
     */
    public function upload(Request $request)
    {
        // --- TEMPORARY DEBUG LOGGING — remove once the intermittent
        // upload failure is diagnosed.
        \Log::info('[ImageUpload] incoming request', [
            'has_file' => $request->hasFile('file'),
            'all_files' => array_keys($request->allFiles()),
            'content_type' => $request->header('Content-Type'),
        ]);

        if ($request->hasFile('file')) {
            $f = $request->file('file');
            \Log::info('[ImageUpload] file details', [
                'original_name' => $f->getClientOriginalName(),
                'mime_type' => $f->getMimeType(),
                'client_mime_type' => $f->getClientMimeType(),
                'extension' => $f->getClientOriginalExtension(),
                'size_kb' => round($f->getSize() / 1024, 1),
                'is_valid' => $f->isValid(),
                'error_code' => $f->getError(),
            ]);
        }
        // --- end temporary debug logging

        try {
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'mimes:jpg,jpeg,png,gif,webp,bmp,svg,tif,tiff,heic,heif,avif,ico,apng',
                    'max:5120', // 5MB
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // TEMP: return the real validation errors instead of a generic one
            \Log::warning('[ImageUpload] validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed (debug mode)',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $upload = Cloudinary::uploadApi()->upload($request->file('file')->getRealPath(), [
                'folder' => 'kayora/admin_uploads',
            ]);

            return response()->json([
                'success' => true,
                'url' => $upload['secure_url'],
            ]);
        } catch (\Exception $e) {
            // TEMP: log full exception + return its message for debugging
            \Log::error('[ImageUpload] Cloudinary upload failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Could not upload image (debug): ' . $e->getMessage(),
            ], 502);
        }
    }
}