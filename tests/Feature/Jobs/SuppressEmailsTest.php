<?php

use App\Jobs\AnalisarEfetivoJob;
use App\Jobs\EnviarCompiladoSADJob;
use App\Jobs\EnviarEmailNotificacaoJob;
use App\Models\Bca;
use App\Models\Efetivo;
use App\Services\BcaAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function bcaComMilitar(): Bca
{
    $efetivo = Efetivo::factory()->create([
        'nome_completo' => 'FULANO DE TAL TESTE',
        'saram' => '123456-7',
        'email' => 'fulano@exemplo.test',
        'oculto' => false,
    ]);

    return Bca::factory()->create([
        'texto_completo' => "Publica-se FULANO DE TAL TESTE saram {$efetivo->saram}",
        'processado_em' => now(),
    ]);
}

it('suppressEmails=true serializado no job nao dispara e-mail nem compilado', function () {
    Bus::fake();
    $bca = bcaComMilitar();

    $job = new AnalisarEfetivoJob($bca->id, 'manual', [], true);
    $job->handle(app(BcaAnalysisService::class));

    Bus::assertNotDispatched(EnviarEmailNotificacaoJob::class);
    Bus::assertNotDispatched(EnviarCompiladoSADJob::class);
    expect($bca->ocorrencias()->count())->toBe(1);
});

it('sem suppressEmails o fluxo normal continua disparando (controle)', function () {
    Bus::fake();
    $bca = bcaComMilitar();

    $job = new AnalisarEfetivoJob($bca->id, 'manual', []);
    $job->handle(app(BcaAnalysisService::class));

    Bus::assertDispatched(EnviarCompiladoSADJob::class);
});
