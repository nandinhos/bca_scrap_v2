<?php
use App\Jobs\EnviarEmailNotificacaoJob;
use App\Mail\NotificacaoBcaMail;
use App\Models\Bca;
use App\Models\BcaOcorrencia;
use App\Models\Efetivo;
use Illuminate\Support\Facades\Mail;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('sends email to efetivo and marks enviado_em', function () {
    Mail::fake();

    $efetivo = Efetivo::factory()->create([
        'email' => 'test@fab.mil.br',
        'oculto' => false,
    ]);
    $bca = Bca::factory()->create();
    $ocorrencia = BcaOcorrencia::create([
        'bca_id' => $bca->id,
        'efetivo_id' => $efetivo->id,
        'snippet' => 'test snippet',
    ]);

    EnviarEmailNotificacaoJob::dispatch($ocorrencia->id);

    Mail::assertSent(NotificacaoBcaMail::class, fn ($mail) => $mail->hasTo('test@fab.mil.br'));
    expect($ocorrencia->fresh()->enviado_em)->not->toBeNull();
});

it('skips email for oculto efetivo', function () {
    Mail::fake();

    $efetivo = Efetivo::factory()->create([
        'email' => 'test@fab.mil.br',
        'oculto' => true,
    ]);
    $bca = Bca::factory()->create();
    $ocorrencia = BcaOcorrencia::create([
        'bca_id' => $bca->id,
        'efetivo_id' => $efetivo->id,
        'snippet' => 'test snippet',
    ]);

    EnviarEmailNotificacaoJob::dispatch($ocorrencia->id);

    Mail::assertNothingSent();
});
