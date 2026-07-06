<?php

namespace App\Livewire;

use App\Models\Unidade;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Unidades')]
class GestorUnidades extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $nome = '';
    public string $sigla = '';
    public string $codigo = '';
    public bool $ativo = true;

    protected function rules(): array
    {
        $uniqueSigla = 'unique:unidades,sigla';
        $uniqueCodigo = 'unique:unidades,codigo';

        if ($this->editingId) {
            $uniqueSigla .= ',' . $this->editingId;
            $uniqueCodigo .= ',' . $this->editingId;
        }

        return [
            'nome' => 'required|string|max:255',
            'sigla' => ['required', 'string', 'max:50', $uniqueSigla],
            'codigo' => ['required', 'string', 'max:50', $uniqueCodigo],
            'ativo' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'nome.required' => 'O nome da unidade é obrigatório.',
            'sigla.required' => 'A sigla é obrigatória.',
            'sigla.unique' => 'Esta sigla já está cadastrada em outra unidade.',
            'codigo.required' => 'O código é obrigatório.',
            'codigo.unique' => 'Este código já está cadastrado em outra unidade.',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'nome', 'sigla', 'codigo']);
        $this->ativo = true;
        $this->showModal = true;
        $this->resetValidation();
    }

    public function openEdit(int $id): void
    {
        $u = Unidade::findOrFail($id);
        $this->editingId = $id;
        $this->nome = $u->nome;
        $this->sigla = $u->sigla;
        $this->codigo = $u->codigo;
        $this->ativo = (bool) $u->ativo;
        $this->showModal = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'nome' => $this->nome,
            'sigla' => $this->sigla,
            'codigo' => $this->codigo,
            'ativo' => $this->ativo,
        ];

        if ($this->editingId) {
            Unidade::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Unidade atualizada com sucesso.');
        } else {
            Unidade::create($data);
            session()->flash('message', 'Unidade criada com sucesso.');
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function toggleAtivo(int $id): void
    {
        $u = Unidade::findOrFail($id);
        $u->update(['ativo' => ! $u->ativo]);
        session()->flash('message', $u->ativo ? 'Unidade ativada.' : 'Unidade desativada.');
    }

    public function render()
    {
        return view('livewire.gestor-unidades', [
            'unidades' => Unidade::orderBy('nome')->paginate(10),
        ]);
    }
}
