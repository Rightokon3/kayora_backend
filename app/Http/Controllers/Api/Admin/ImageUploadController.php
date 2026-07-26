<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;

class ImageUploadController extends Controller
{
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
        $request->validate([
            // Deliberately using `mimes` (not the `image` rule) with the
            // full extension list below. The `image` rule under the hood
            // calls getimagesize()-style checks that don't reliably
            // recognize newer formats like heic/avif on every server
            // (depends on the GD/Imagick build), and it silently excludes
            // formats like tiff/ico outright. `mimes` + `file` validates
            // off the extension/mime the client actually sent, which
            // Cloudinary can accept and transcode regardless of format.
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,gif,webp,bmp,svg,tif,tiff,heic,heif,avif,ico,apng',
                'max:5120', // 5MB
            ],
        ]);

        try {
            $upload = Cloudinary::uploadApi()->upload($request->file('file')->getRealPath(), [
                'folder' => 'kayora/admin_uploads',
            ]);

            return response()->json([
                'success' => true,
                'url' => $upload['secure_url'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not upload image. Please try again.',
            ], 502);
        }
    }
}