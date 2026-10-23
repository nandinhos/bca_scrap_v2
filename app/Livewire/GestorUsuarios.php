<?php

namespace App\Livewire;

use App\Models\Unidade;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Usuários')]
class GestorUsuarios extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'operador';

    public ?int $unidadeId = null;

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'unidadeId']);
        $this->role = 'operador';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $u = User::findOrFail($id);
        $this->editingId = $id;
        $this->name = $u->name;
        $this->email = $u->email;
        $this->password = '';
        $this->role = $u->role;
        $this->unidadeId = $u->unidade_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email'.($this->editingId ? ",{$this->editingId}" : ''),
            'role' => 'required|in:admin,operador',
            // Operador sem unidade nao acessa nada (fail-closed); admin nao precisa.
            'unidadeId' => 'required_if:role,operador|nullable|exists:unidades,id',
        ];
        if (! $this->editingId) {
            $rules['password'] = 'required|min:8';
        }
        $this->validate($rules);

        $data = ['name' => $this->name, 'email' => $this->email, 'role' => $this->role,
            'unidade_id' => $this->role === 'admin' ? null : $this->unidadeId];
        if ($this->password) {
            $data['password'] = bcrypt($this->password);
        }

        $this->editingId ? User::findOrFail($this->editingId)->update($data) : User::create($data);
        $this->showModal = false;
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.gestor-usuarios', [
            'usuarios' => User::orderBy('name')->paginate(20),
            'unidades' => Unidade::orderBy('sigla')->get(),
        ]);
    }
}
