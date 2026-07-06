<?php

namespace App\Providers;

use App\Events\MilitarEncontradoEvent;
use App\Listeners\NotificarMilitarListener;
use App\Repositories\BcaRepository;
use App\Repositories\Contracts\BcaRepositoryInterface;
use App\Repositories\Contracts\EfetivoRepositoryInterface;
use App\Repositories\EfetivoRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EfetivoRepositoryInterface::class, EfetivoRepository::class);
        $this->app->bind(BcaRepositoryInterface::class, BcaRepository::class);
    }

    public function boot(): void
    {
        Event::listen(MilitarEncontradoEvent::class, NotificarMilitarListener::class);
    }
}
