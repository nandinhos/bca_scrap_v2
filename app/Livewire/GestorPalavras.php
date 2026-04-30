<?php

namespace App\Livewire;

use App\Models\PalavraChave;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Palavras-chave')]
class GestorPalavras extends Component
{
    public array $palavras = [];

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $palavra = '';

    public string $cor = 'FFFFFF';

    public function mount(): void
    {
        $this->reload();
    }

    private function reload(): void
    {
        $this->palavras = PalavraChave::orderBy('palavra')->get()->toArray();
    }

    public function toggleAtiva(int $id): void
    {
        $p = PalavraChave::findOrFail($id);
        $p->update(['ativa' => ! $p->ativa]);
        $this->reload();
    }

    public function toggleAll(bool $ativa): void
    {
        $this->ensureAdmin();
        PalavraChave::query()->update(['ativa' => $ativa]);
        $this->reload();
    }

    public function openCreate(): void
    {
        $this->ensureAdmin();
        $this->reset(['editingId', 'palavra']);
        $this->cor = 'FFFFFF';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->ensureAdmin();
        $p = PalavraChave::findOrFail($id);
        $this->editingId = $id;
        $this->palavra = $p->palavra;
        $this->cor = $p->cor;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->ensureAdmin();
        $this->validate([
            'palavra' => 'required|string|max:100',
            'cor' => ['required', 'regex:/^[0-9A-Fa-f]{6}$/'],
        ]);
        if ($this->editingId) {
            PalavraChave::findOrFail($this->editingId)->update(['palavra' => strtoupper($this->palavra), 'cor' => strtoupper($this->cor)]);
        } else {
            PalavraChave::create(['palavra' => strtoupper($this->palavra), 'cor' => strtoupper($this->cor), 'ativa' => false]);
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
        return view('livewire.gestor-palavras');
    }
}
