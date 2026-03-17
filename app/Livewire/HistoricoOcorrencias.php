<?php

namespace App\Livewire;

use App\Models\BcaOcorrencia;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Histórico')]
class HistoricoOcorrencias extends Component
{
    use WithPagination;

    public string $filtroData = '';

    public string $filtroMilitar = '';

    public function updatedFiltroData(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroMilitar(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = BcaOcorrencia::with(['efetivo', 'bca'])->orderByDesc('created_at');
        if ($this->filtroData) {
            $query->whereHas('bca', fn ($q) => $q->whereDate('data', $this->filtroData));
        }
        if ($this->filtroMilitar) {
            $query->whereHas('efetivo', fn ($q) => $q->where('nome_guerra', 'ilike', "%{$this->filtroMilitar}%")->orWhere('nome_completo', 'ilike', "%{$this->filtroMilitar}%"));
        }

        return view('livewire.historico-ocorrencias', ['ocorrencias' => $query->paginate(15)]);
    }
}
