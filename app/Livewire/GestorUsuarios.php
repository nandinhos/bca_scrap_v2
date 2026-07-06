<?php

namespace App\Livewire;

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

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password']);
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
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email'.($this->editingId ? ",{$this->editingId}" : ''),
            'role' => 'required|in:admin,operador',
        ];
        if (! $this->editingId) {
            $rules['password'] = 'required|min:8';
        }
        $this->validate($rules);

        $data = ['name' => $this->name, 'email' => $this->email, 'role' => $this->role];
        if ($this->password) {
            $data['password'] = bcrypt($this->password);
        }

        $this->editingId ? User::findOrFail($this->editingId)->update($data) : User::create($data);
        $this->showModal = false;
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.gestor-usuarios', ['usuarios' => User::orderBy('name')->paginate(20)]);
    }
}
