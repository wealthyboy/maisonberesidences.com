<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function image(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:8192'],
        ]);

        $folder = Str::of($request->query('folder', 'uploads'))
            ->replace(['..', '/', '\\'], '')
            ->slug()
            ->value() ?: 'uploads';

        $file = $request->file('file');
        $filename = $folder . '/' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        Storage::disk('spaces')->put($filename, file_get_contents($file->getRealPath()), 'public');

        return response()->json([
            'path' => Storage::disk('spaces')->url($filename),
        ]);
    }
}
