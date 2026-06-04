<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateHaircutPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HaircutPreviewController extends Controller
{
    public function generateTemp(Request $request)
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

        $jobId = (string) Str::uuid();

        Storage::disk('local')->put(
            $this->rutaEstado($jobId),
            json_encode([
                'status' => 'pending',
                'original_image_temp_path' => $rutaOriginal,
                'generated_image_temp_path' => null,
                'preview_prompt' => $prompt,
                'error' => null,
            ], JSON_PRETTY_PRINT)
        );

        GenerateHaircutPreview::dispatch($jobId, $rutaOriginal, $prompt);

        return response()->json([
            'ok' => true,
            'job_id' => $jobId,
            'status' => 'pending',
        ]);
    }

    public function status(string $jobId)
    {
        $rutaEstado = $this->rutaEstado($jobId);

        if (! Storage::disk('local')->exists($rutaEstado)) {
            return response()->json([
                'ok' => false,
                'status' => 'missing',
                'error' => 'No se encontro la previsualizacion solicitada.',
            ], 404);
        }

        $datos = json_decode(Storage::disk('local')->get($rutaEstado), true) ?: [];
        $rutaGenerada = $datos['generated_image_temp_path'] ?? null;

        return response()->json([
            'ok' => ($datos['status'] ?? null) !== 'failed',
            'status' => $datos['status'] ?? 'pending',
            'original_image_temp_path' => $datos['original_image_temp_path'] ?? null,
            'generated_image_temp_path' => $rutaGenerada,
            'generated_image_url' => $rutaGenerada ? '/storage/' . $rutaGenerada : null,
            'preview_prompt' => $datos['preview_prompt'] ?? null,
            'error' => $datos['error'] ?? null,
        ]);
    }

    private function rutaEstado(string $jobId): string
    {
        return 'preview-jobs/' . $jobId . '.json';
    }
}
