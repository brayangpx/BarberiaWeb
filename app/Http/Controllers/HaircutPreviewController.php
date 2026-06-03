<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

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

        $resultado = $this->generarImagen($rutaOriginal, $prompt);

        return response()->json([
            'ok' => true,
            'original_image_temp_path' => $rutaOriginal,
            'generated_image_temp_path' => $resultado['path'],
            'generated_image_url' => asset('storage/' . $resultado['path']),
            'preview_prompt' => $prompt,
            'error' => null,
        ]);
    }

    private function generarImagen(string $rutaOriginal, string $prompt): array
    {
        $token = config('services.huggingface.token');
        $provider = config('services.huggingface.provider', 'fal-ai');
        $providerModel = config('services.huggingface.provider_model', 'fal-ai/flux-2/edit');

        if (! $token || ! $provider || ! $providerModel) {
            return [
                'ok' => false,
                'path' => $rutaOriginal,
                'error' => 'No se configuró correctamente Hugging Face.',
            ];
        }

        try {
            $contenido = Storage::disk('public')->get($rutaOriginal);

            $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $contenido) ?: 'image/jpeg';
            $imagenBase64 = 'data:' . $mime . ';base64,' . base64_encode($contenido);

            $endpoint = "https://router.huggingface.co/{$provider}/{$providerModel}?_subdomain=queue";

            $respuesta = Http::timeout(120)
                ->withToken($token)
                ->acceptJson()
                ->post($endpoint, [
                    'prompt' => $prompt,
                    'image_url' => $imagenBase64,
                    'image_urls' => [$imagenBase64],
                    'output_format' => 'png',
                ]);

            if (! $respuesta->successful()) {
                return $this->respuestaError($respuesta, $rutaOriginal);
            }

            $datos = $respuesta->json();

            if (! isset($datos['response_url'])) {
                return [
                    'ok' => false,
                    'path' => $rutaOriginal,
                    'error' => 'Hugging Face no devolvió una URL de respuesta.',
                ];
            }

            $rutaRespuesta = parse_url($datos['response_url'], PHP_URL_PATH);

            if (! $rutaRespuesta) {
                return [
                    'ok' => false,
                    'path' => $rutaOriginal,
                    'error' => 'No se pudo leer la ruta de respuesta de Hugging Face.',
                ];
            }

            $baseRouter = "https://router.huggingface.co/{$provider}";
            $statusUrl = $baseRouter . $rutaRespuesta . '/status?_subdomain=queue';
            $resultUrl = $baseRouter . $rutaRespuesta . '?_subdomain=queue';

            $estado = $datos['status'] ?? 'IN_QUEUE';
            $intentos = 0;

            while ($estado !== 'COMPLETED' && $intentos < 60) {
                sleep(2);

                $statusResponse = Http::timeout(60)
                    ->withToken($token)
                    ->acceptJson()
                    ->get($statusUrl);

                if (! $statusResponse->successful()) {
                    return $this->respuestaError($statusResponse, $rutaOriginal);
                }

                $estado = $statusResponse->json('status') ?? $estado;

                if ($estado === 'FAILED') {
                    return [
                        'ok' => false,
                        'path' => $rutaOriginal,
                        'error' => 'La generación falló en Hugging Face.',
                    ];
                }

                $intentos++;
            }

            if ($estado !== 'COMPLETED') {
                return [
                    'ok' => false,
                    'path' => $rutaOriginal,
                    'error' => 'La generación tardó demasiado tiempo.',
                ];
            }

            $resultado = Http::timeout(120)
                ->withToken($token)
                ->acceptJson()
                ->get($resultUrl);

            if (! $resultado->successful()) {
                return $this->respuestaError($resultado, $rutaOriginal);
            }

            $imagenUrl = $resultado->json('images.0.url');

            if (! $imagenUrl) {
                return [
                    'ok' => false,
                    'path' => $rutaOriginal,
                    'error' => 'Hugging Face no devolvió una imagen generada.',
                ];
            }

            $imagenGenerada = Http::timeout(120)->get($imagenUrl);

            if (! $imagenGenerada->successful()) {
                return [
                    'ok' => false,
                    'path' => $rutaOriginal,
                    'error' => 'No se pudo descargar la imagen generada.',
                ];
            }

            $rutaGenerada = 'previews/generated/' . uniqid('preview_', true) . '.png';

            Storage::disk('public')->put($rutaGenerada, $imagenGenerada->body());

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

    private function respuestaError($respuesta, string $rutaOriginal): array
    {
        $mensajeApi = $respuesta->json('error')
            ?? $respuesta->json('detail')
            ?? $respuesta->json('message')
            ?? $respuesta->body();

        return [
            'ok' => false,
            'path' => $rutaOriginal,
            'error' => 'Hugging Face respondió HTTP ' . $respuesta->status() . ': ' . Str::limit($mensajeApi, 300),
        ];
    }
}
