<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiswaModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiswaFaceController extends Controller
{
    public function store(Request $request, SiswaModel $siswa)
    {
        $descriptor = $request->input('face_descriptor');
        $faceImageB64 = $request->input('face_image_b64');

        if (is_string($descriptor)) {
            $descriptor = json_decode($descriptor, true);
        }

        if (! $this->isValidDescriptor($descriptor)) {
            return response()->json([
                'message' => 'Descriptor wajah tidak valid. Silakan scan ulang.',
            ], 422);
        }

        if (! $this->isValidBase64Image($faceImageB64)) {
            return response()->json([
                'message' => 'Foto wajah tidak valid. Silakan scan ulang.',
            ], 422);
        }

        $imagePath = $this->storeFaceImage($siswa, $faceImageB64);

        $siswa->update([
            'face_descriptor' => array_map('floatval', $descriptor),
            'face_image_path' => $imagePath,
            'is_face_registered' => true,
            'face_registered_by' => Auth::id(),
            'face_registered_at' => now(),
        ]);

        return response()->json([
            'message' => 'Data wajah berhasil disimpan.',
            'face_image_url' => asset('storage/' . $imagePath),
        ]);
    }

    private function isValidDescriptor(mixed $descriptor): bool
    {
        if (! is_array($descriptor) || count($descriptor) !== 128) {
            return false;
        }

        foreach ($descriptor as $value) {
            if (! is_numeric($value)) {
                return false;
            }

            $number = (float) $value;

            if (! is_finite($number) || abs($number) > 10) {
                return false;
            }
        }

        return true;
    }

    private function isValidBase64Image(?string $image): bool
    {
        if (! $image || ! Str::startsWith($image, 'data:image/')) {
            return false;
        }

        if (! preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $image)) {
            return false;
        }

        $raw = preg_replace('/^data:image\/(jpeg|jpg|png);base64,/', '', $image);
        $decoded = base64_decode($raw, true);

        if ($decoded === false) {
            return false;
        }

        return strlen($decoded) <= 2 * 1024 * 1024;
    }

    private function storeFaceImage(SiswaModel $siswa, string $image): string
    {
        $raw = preg_replace('/^data:image\/(jpeg|jpg|png);base64,/', '', $image);
        $decoded = base64_decode($raw, true);

        $filename = 'face-enrollment/' . $siswa->getKey() . '_' . now()->timestamp . '.jpg';

        if (
            $siswa->face_image_path &&
            Storage::disk('public')->exists($siswa->face_image_path)
        ) {
            Storage::disk('public')->delete($siswa->face_image_path);
        }

        Storage::disk('public')->put($filename, $decoded);

        return $filename;
    }
}
