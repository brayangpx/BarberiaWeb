<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HuggingFaceImageService
{
    public function generar(string $rutaOriginal, string $prompt): array
    {
        $token = config('services.huggingface.token');
        $endpoint = config('services.huggingface.endpoint');

        if (! $token || ! $endpoint) {
            return [
                'ok' => false,
                'path' => $rutaOriginal,
                'error' => 'No se configuró el token de Hugging Face.',
            ];
        }

        try {
            $contenido = Storage::disk('public')->get($rutaOriginal);

            $respuesta = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->attach('image', $contenido, 'foto.jpg')
                ->post($endpoint, [
                    'inputs' => $prompt,
                ]);

            if (! $respuesta->successful()) {
                return [
                    'ok' => false,
                    'path' => $rutaOriginal,
                    'error' => 'No se pudo generar la previsualización.',
                ];
            }

            $rutaGenerada = 'previews/generated/' . uniqid('preview_', true) . '.png';
            Storage::disk('public')->put($rutaGenerada, $respuesta->body());

            return [
                'ok' => true,
                'path' => $rutaGenerada,
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'path' => $rutaOriginal,
                'error' => $e->getMessage(),
            ];
        }
    }
}
