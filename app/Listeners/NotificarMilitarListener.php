<?php
namespace App\Listeners;

use App\Events\MilitarEncontradoEvent;
use App\Jobs\EnviarEmailNotificacaoJob;

class NotificarMilitarListener
{
    public function handle(MilitarEncontradoEvent $event): void
    {
        EnviarEmailNotificacaoJob::dispatch($event->ocorrencia->id);
    }
}
