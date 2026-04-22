<?php

use App\Jobs\EnviarCompiladoSADJob;
use App\Mail\CompiladoSadMail;
use App\Models\Bca;
use App\Models\BcaOcorrencia;
use App\Models\Efetivo;
use App\Models\Unidade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('sends compiled report to SAD when there are occurrences', function () {
    Mail::fake();

    $unidade = Unidade::create([
        'nome' => 'Grupo de Aviação',
        'sigla' => 'GAC-PAC',
        'codigo' => 'GAC-PAC',
        'ativo' => true,
    ]);

    $efetivo = Efetivo::factory()->create([
        'email' => 'militar@fab.mil.br',
        'unidade_id' => $unidade->id,
    ]);

    $bca = Bca::factory()->create();

    $ocorrencia = BcaOcorrencia::create([
        'bca_id' => $bca->id,
        'efetivo_id' => $efetivo->id,
        'snippet' => 'test snippet',
        'enviado_em' => now(),
    ]);

    EnviarCompiladoSADJob::dispatch($bca->id);

    Mail::assertSent(CompiladoSadMail::class, function ($mail) use ($bca) {
        return $mail->hasTo(config('bca.sad_email'))
            && $mail->bca->id === $bca->id;
    });
});

it('does not send email when there are no sent occurrences', function () {
    Mail::fake();

    $bca = Bca::factory()->create();

    EnviarCompiladoSADJob::dispatch($bca->id);

    Mail::assertNothingSent();
});

it('does not send email when sad_email is not configured', function () {
    Mail::fake();

    config(['bca.sad_email' => null]);

    $efetivo = Efetivo::factory()->create(['email' => 'militar@fab.mil.br']);
    $bca = Bca::factory()->create();

    BcaOcorrencia::create([
        'bca_id' => $bca->id,
        'efetivo_id' => $efetivo->id,
        'snippet' => 'test',
        'enviado_em' => now(),
    ]);

    EnviarCompiladoSADJob::dispatch($bca->id);

    Mail::assertNothingSent();
});
