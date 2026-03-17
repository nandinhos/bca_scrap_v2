<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HealthController;
use App\Livewire\BuscaBca;
use App\Livewire\GestorPalavras;
use App\Livewire\GestorUsuarios;
use App\Livewire\HistoricoOcorrencias;
use App\Livewire\ListagemEfetivo;
use App\Livewire\LogExecucoes;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'check'])->name('health');
Route::get('/metrics', [HealthController::class, 'metrics'])->name('metrics');

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', BuscaBca::class)->name('dashboard');
    Route::get('/historico', HistoricoOcorrencias::class)->name('historico');
    Route::get('/palavras-chave', GestorPalavras::class)->name('palavras-chave');

    Route::middleware('role:admin')->group(function () {
        Route::get('/efetivo', ListagemEfetivo::class)->name('efetivo');
        Route::get('/usuarios', GestorUsuarios::class)->name('usuarios');
        Route::get('/execucoes', LogExecucoes::class)->name('execucoes');
    });
});
