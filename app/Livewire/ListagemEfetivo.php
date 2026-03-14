<?php
namespace App\Livewire;

use App\Models\Efetivo;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Efetivo')]
class ListagemEfetivo extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $saram = '';
    public string $nomeGuerra = '';
    public string $nomeCompleto = '';
    public string $posto = '';
    public string $especialidade = '';
    public string $email = '';
    public string $omOrigem = 'GAC-PAC';
    public bool $ativo = true;
    public bool $oculto = false;

    public function updatedSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->reset(['editingId','saram','nomeGuerra','nomeCompleto','posto','especialidade','email','omOrigem','ativo','oculto']);
        $this->omOrigem = 'GAC-PAC';
        $this->ativo = true;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $e = Efetivo::findOrFail($id);
        $this->editingId = $id;
        $this->saram = $e->saram;
        $this->nomeGuerra = $e->nome_guerra;
        $this->nomeCompleto = $e->nome_completo;
        $this->posto = $e->posto;
        $this->especialidade = $e->especialidade ?? '';
        $this->email = $e->email ?? '';
        $this->omOrigem = $e->om_origem;
        $this->ativo = $e->ativo;
        $this->oculto = $e->oculto;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'saram' => 'required|string|max:8',
            'nomeGuerra' => 'required|string|max:50',
            'nomeCompleto' => 'required|string|max:200',
            'posto' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $data = [
            'saram' => $this->saram,
            'nome_guerra' => strtoupper($this->nomeGuerra),
            'nome_completo' => strtoupper($this->nomeCompleto),
            'posto' => $this->posto,
            'especialidade' => $this->especialidade ?: null,
            'email' => $this->email ?: null,
            'om_origem' => $this->omOrigem,
            'ativo' => $this->ativo,
            'oculto' => $this->oculto,
        ];

        if ($this->editingId) {
            Efetivo::findOrFail($this->editingId)->update($data);
        } else {
            Efetivo::create($data);
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.listagem-efetivo', [
            'efetivos' => Efetivo::query()
                ->when($this->search, fn($q) =>
                    $q->where('nome_guerra','ilike',"%{$this->search}%")
                      ->orWhere('nome_completo','ilike',"%{$this->search}%")
                      ->orWhere('saram','like',"%{$this->search}%"))
                ->orderBy('nome_guerra')
                ->paginate(20),
        ]);
    }
}
