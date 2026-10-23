<?php

use App\Jobs\EnviarEmailNotificacaoJob;
use App\Mail\NotificacaoBcaMail;
use App\Models\Bca;
use App\Models\BcaOcorrencia;
use App\Models\Efetivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('job de notificacao nao reenvia ocorrencia ja enviada (sem adendo)', function () {
    Mail::fake();

    $efetivo = Efetivo::factory()->create(['email' => 'a@exemplo.test', 'oculto' => false]);
    $ocorrencia = BcaOcorrencia::create([
        'bca_id' => Bca::factory()->create()->id,
        'efetivo_id' => $efetivo->id,
        'snippet' => 'x',
        'enviado_em' => now(),
    ]);

    (new EnviarEmailNotificacaoJob($ocorrencia->id))->handle();

    Mail::assertNothingSent();
});

it('reenvio intencional com adendo continua permitido', function () {
    Mail::fake();

    $efetivo = Efetivo::factory()->create(['email' => 'a@exemplo.test', 'oculto' => false]);
    $ocorrencia = BcaOcorrencia::create([
        'bca_id' => Bca::factory()->create()->id,
        'efetivo_id' => $efetivo->id,
        'snippet' => 'x',
        'enviado_em' => now(),
    ]);

    (new EnviarEmailNotificacaoJob($ocorrencia->id, 'Nota excepcional.'))->handle();

    Mail::assertSent(NotificacaoBcaMail::class);
});

it('jobs de e-mail sao unicos por ocorrencia e por BCA', function () {
    expect((new EnviarEmailNotificacaoJob(42))->uniqueId())->toBe('notificacao-42')
        ->and((new \App\Jobs\EnviarCompiladoSADJob(7))->uniqueId())->toBe('compilado-sad-7');
});
