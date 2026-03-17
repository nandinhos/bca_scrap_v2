<?php

namespace App\Livewire;

use App\Models\BcaExecucao;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Log de Execuções')]
class LogExecucoes extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.log-execucoes', [
            'execucoes' => BcaExecucao::orderByDesc('data_execucao')->paginate(20),
        ]);
    }
}
