<?php

namespace App\Livewire;

use App\Models\PalavraChave;
use App\Models\Unidade;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Palavras-chave')]
class GestorPalavras extends Component
{
    public array $palavras = [];

    public array $unidades = [];

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $palavra = '';

    public string $cor = 'FFFFFF';

    public ?int $unidadeId = null;

    public function mount(): void
    {
        $this->reload();
        $this->unidades = Unidade::orderBy('nome')->get()->toArray();
        // Operador tem unidade fixa; admin pode escolher no form
        if (! auth()->user()?->isAdmin()) {
            $this->unidadeId = auth()->user()?->unidade_id;
        }
    }

    private function reload(): void
    {
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $userUnidadeId = $user?->unidade_id;

        $this->palavras = PalavraChave::query()
            ->when(! $isAdmin && $userUnidadeId, fn ($q) => $q->where('unidade_id', $userUnidadeId))
            ->orderBy('palavra')
            ->get()
            ->toArray();
    }

    public function toggleAtiva(int $id): void
    {
        $this->ensureAdmin();
        $p = PalavraChave::findOrFail($id);
        $p->update(['ativa' => ! $p->ativa]);
        $this->reload();
    }

    public function openCreate(): void
    {
        $this->ensureAdmin();
        $this->reset(['editingId', 'palavra']);
        $this->cor = 'FFFFFF';
        $this->unidadeId = auth()->user()?->isAdmin() ? null : auth()->user()?->unidade_id;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->ensureAdmin();
        $p = PalavraChave::findOrFail($id);
        $this->editingId = $id;
        $this->palavra = $p->palavra;
        $this->cor = $p->cor;
        $this->unidadeId = $p->unidade_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->ensureAdmin();
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $userUnidadeId = $user?->unidade_id;

        // Operador: unidade_id é forçada a do próprio user; Admin: usa a selecionada
        $unidadeId = $isAdmin ? $this->unidadeId : $userUnidadeId;

        $this->validate([
            'palavra' => 'required|string|max:100',
            'cor' => ['required', 'regex:/^[0-9A-Fa-f]{6}$/'],
            'unidadeId' => $isAdmin ? 'required|exists:unidades,id' : 'nullable',
        ]);

        $data = [
            'palavra' => strtoupper($this->palavra),
            'cor' => strtoupper($this->cor),
            'unidade_id' => $unidadeId,
        ];

        if ($this->editingId) {
            PalavraChave::findOrFail($this->editingId)->update($data);
        } else {
            $data['ativa'] = false;
            PalavraChave::create($data);
        }
        $this->showModal = false;
        $this->reload();
    }

    public function delete(int $id): void
    {
        $this->ensureAdmin();
        PalavraChave::findOrFail($id)->delete();
        $this->reload();
    }

    private function ensureAdmin(): void
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.gestor-palavras', [
            'isAdmin' => auth()->user()?->isAdmin() ?? false,
        ]);
    }
}
