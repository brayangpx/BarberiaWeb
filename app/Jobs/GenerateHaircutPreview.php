<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GenerateHaircutPreview implements ShouldQueue
{
    use Queueable;

    public function __construct(private string $jobId, private string $rutaOriginal,
        private string $prompt) {
    }

    public function handle(): void
    {
        $this->guardarEstado([
            'status' => 'processing',
            'original_image_temp_path' => $this->rutaOriginal,
            'generated_image_temp_path' => null,
            'preview_prompt' => $this->prompt,
            'error' => null,
        ]);

        $resultado = $this->generarImagen();

        $this->guardarEstado([
            'status' => $resultado['ok'] ? 'completed' : 'failed',
            'original_image_temp_path' => $this->rutaOriginal,
            'generated_image_temp_path' => $resultado['path'],
            'preview_prompt' => $this->prompt,
            'error' => $resultado['error'],
        ]);
    }

    private function generarImagen(): array
    {
        $token = config('services.huggingface.token');
        $provider = config('services.huggingface.provider', 'fal-ai');
        $providerModel = config('services.huggingface.provider_model', 'fal-ai/flux-2/edit');

        if (! $token || ! $provider || ! $providerModel) {
            return [
                'ok' => false,
                'path' => $this->rutaOriginal,
                'error' => 'No se configuro correctamente Hugging Face.',
            ];
        }

        try {
            $respuesta = $this->iniciarGeneracion($token, $provider, $providerModel);

            if (! $respuesta->successful()) {
                return $this->respuestaError($respuesta);
            }

            $datos = $respuesta->json();

            if (! isset($datos['response_url'])) {
                return [
                    'ok' => false,
                    'path' => $this->rutaOriginal,
                    'error' => 'Hugging Face no devolvio una URL de respuesta.',
                ];
            }

            $rutaRespuesta = parse_url($datos['response_url'], PHP_URL_PATH);

            if (! $rutaRespuesta) {
                return [
                    'ok' => false,
                    'path' => $this->rutaOriginal,
                    'error' => 'No se pudo leer la ruta de respuesta de Hugging Face.',
                ];
            }

            $resultado = $this->esperarResultado($token, $provider, $rutaRespuesta, $datos['status'] ?? 'IN_QUEUE');

            if (! $resultado['ok']) {
                return $resultado;
            }

            $imagenUrl = $resultado['respuesta']->json('images.0.url');

            if (! $imagenUrl) {
                return [
                    'ok' => false,
                    'path' => $this->rutaOriginal,
                    'error' => 'Hugging Face no devolvio una imagen generada.',
                ];
            }

            $rutaGenerada = $this->guardarImagenGenerada($imagenUrl);

            return [
                'ok' => true,
                'path' => $rutaGenerada,
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'path' => $this->rutaOriginal,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function iniciarGeneracion(string $token, string $provider, string $providerModel)
    {
        $imagenBase64 = $this->imagenOriginalBase64();
        $endpoint = "https://router.huggingface.co/{$provider}/{$providerModel}?_subdomain=queue";

        return Http::timeout(120)->withToken($token)
            ->acceptJson()->post($endpoint, [
                'prompt' => $this->prompt,
                'image_url' => $imagenBase64,
                'image_urls' => [$imagenBase64],
                'output_format' => 'png',
            ]);
    }

    private function imagenOriginalBase64(): string
    {
        $contenido = Storage::disk('public')->get($this->rutaOriginal);
        $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $contenido) ?: 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode($contenido);
    }

    private function esperarResultado(string $token, string $provider, string $rutaRespuesta, string $estado): array
    {
        $baseRouter = "https://router.huggingface.co/{$provider}";
        $statusUrl = $baseRouter . $rutaRespuesta . '/status?_subdomain=queue';
        $resultUrl = $baseRouter . $rutaRespuesta . '?_subdomain=queue';
        $intentos = 0;

        while ($estado !== 'COMPLETED' && $intentos < 60) {
            sleep(2);

            $statusResponse = Http::timeout(60)->withToken($token)
            ->acceptJson()->get($statusUrl);

            if (! $statusResponse->successful()) {
                return $this->respuestaError($statusResponse);
            }

            $estado = $statusResponse->json('status') ?? $estado;

            if ($estado === 'FAILED') {
                return [
                    'ok' => false,
                    'path' => $this->rutaOriginal,
                    'error' => 'La generacion fallo en Hugging Face.',
                ];
            }

            $intentos++;
        }

        if ($estado !== 'COMPLETED') {
            return [
                'ok' => false,
                'path' => $this->rutaOriginal,
                'error' => 'La generacion tardo demasiado tiempo.',
            ];
        }

        $resultado = Http::timeout(120)->withToken($token)
        ->acceptJson()->get($resultUrl);

        if (! $resultado->successful()) {
            return $this->respuestaError($resultado);
        }

        return [
            'ok' => true,
            'respuesta' => $resultado,
        ];
    }

    private function guardarImagenGenerada(string $imagenUrl): string
    {
        $imagenGenerada = Http::timeout(120)->get($imagenUrl);

        if (! $imagenGenerada->successful()) {
            throw new RuntimeException('No se pudo descargar la imagen generada.');
        }

        $rutaGenerada = 'previews/generated/' . uniqid('preview_', true) . '.png';

        Storage::disk('public')->put($rutaGenerada, $imagenGenerada->body());

        return $rutaGenerada;
    }

    private function respuestaError($respuesta): array
    {
        $mensajeApi = $respuesta->json('error') ?? $respuesta->json('detail')
            ?? $respuesta->json('message') ?? $respuesta->body();

        return [
            'ok' => false,
            'path' => $this->rutaOriginal,
            'error' => 'Hugging Face respondio HTTP ' . $respuesta->status() . ': ' . Str::limit($mensajeApi, 300),
        ];
    }

    private function guardarEstado(array $datos): void
    {
        Storage::disk('local')->put(
            $this->rutaEstado(),
            json_encode($datos, JSON_PRETTY_PRINT)
        );
    }

    private function rutaEstado(): string
    {
        return 'preview-jobs/' . $this->jobId . '.json';
    }
}
