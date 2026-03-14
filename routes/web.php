<?php
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
});

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', \App\Livewire\BuscaBca::class)->name('dashboard');
    Route::get('/historico', \App\Livewire\HistoricoOcorrencias::class)->name('historico');
    Route::get('/palavras-chave', \App\Livewire\GestorPalavras::class)->name('palavras-chave');

    Route::middleware('role:admin')->group(function () {
        Route::get('/efetivo', \App\Livewire\ListagemEfetivo::class)->name('efetivo');
        Route::get('/usuarios', \App\Livewire\GestorUsuarios::class)->name('usuarios');
        Route::get('/execucoes', \App\Livewire\LogExecucoes::class)->name('execucoes');
    });
});
