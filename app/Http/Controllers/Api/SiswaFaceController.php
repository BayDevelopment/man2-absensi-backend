<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiswaModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiswaFaceController extends Controller
{
    public function registerFace(Request $request, SiswaModel $siswa)
    {
        $validated = $request->validate([
            'face_descriptor' => ['required', 'array', 'size:128'],
            'face_descriptor.*' => ['required', 'numeric'],
            'face_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $faceImagePath = $siswa->face_image;

        if ($request->hasFile('face_image')) {
            if ($faceImagePath && Storage::disk('public')->exists($faceImagePath)) {
                Storage::disk('public')->delete($faceImagePath);
            }

            $faceImagePath = $request->file('face_image')->store('face-siswa', 'public');
        }

        $siswa->update([
            'face_descriptor' => $validated['face_descriptor'],
            'face_image' => $faceImagePath,
            'is_face_registered' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data wajah siswa berhasil didaftarkan.',
            'data' => [
                'id' => $siswa->id,
                'nama_lengkap' => $siswa->nama_lengkap,
                'is_face_registered' => $siswa->is_face_registered,
                'face_image' => $siswa->face_image,
            ],
        ]);
    }
}
