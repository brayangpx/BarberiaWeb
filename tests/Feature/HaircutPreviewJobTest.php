<?php

namespace Tests\Feature;

use App\Jobs\GenerateHaircutPreview;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HaircutPreviewJobTest extends TestCase
{
    public function test_el_job_guarda_la_imagen_generada_y_el_estado_final(): void
    {
        config([
            'services.huggingface.token' => 'token-demo',
            'services.huggingface.provider' => 'fal-ai',
            'services.huggingface.provider_model' => 'fal-ai/flux-2/edit',
        ]);

        Storage::fake('public');
        Storage::fake('local');

        Storage::disk('public')->put('previews/original/foto.jpg', 'imagen-original');

        Http::fakeSequence()
            ->push([
                'response_url' => 'https://router.huggingface.co/fal-ai/respuesta-demo',
                'status' => 'IN_QUEUE',
            ], 200)
            ->push(['status' => 'COMPLETED'], 200)
            ->push(['images' => [['url' => 'https://imagenes.test/generada.png']]], 200)
            ->push('imagen-generada', 200);

        $job = new GenerateHaircutPreview(
            'job-demo',
            'previews/original/foto.jpg',
            'Prompt demo'
        );

        $job->handle();

        Storage::disk('public')->assertExists(
            Storage::disk('public')->files('previews/generated')[0]
        );

        Storage::disk('local')->assertExists('preview-jobs/job-demo.json');

        $estado = json_decode(
            Storage::disk('local')->get('preview-jobs/job-demo.json'),
            true
        );

        $this->assertSame('completed', $estado['status']);
        $this->assertSame('Prompt demo', $estado['preview_prompt']);
        $this->assertNull($estado['error']);
    }
}
