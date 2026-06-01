<?php

namespace App\Http\Controllers;

use App\Services\HuggingFaceImageService;
use Illuminate\Http\Request;

class HaircutPreviewController extends Controller
{
    public function generateTemp(Request $request, HuggingFaceImageService $huggingFace)
    {
        $request->validate([
            'preview_image' => ['required', 'image'],
            'haircut_name' => ['required', 'string'],
        ]);

        $rutaOriginal = $request->file('preview_image')
            ->store('previews/original', 'public');

        $prompt = 'Modify the hairstyle of the person to look like '
            . $request->input('haircut_name')
            . ', keeping the face as similar as possible.';

        $resultado = $huggingFace->generar($rutaOriginal, $prompt);

        return response()->json([
            'original_image_temp_path' => $rutaOriginal,
            'generated_image_temp_path' => $resultado['path'],
            'preview_prompt' => $prompt,
            'ok' => $resultado['ok'],
            'error' => $resultado['error'],
        ]);
    }
}
