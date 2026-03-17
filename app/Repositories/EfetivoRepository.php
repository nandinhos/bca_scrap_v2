<?php

namespace App\Repositories;

use App\Models\Efetivo;
use App\Repositories\Contracts\EfetivoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EfetivoRepository implements EfetivoRepositoryInterface
{
    public function getAtivos(): Collection
    {
        return Efetivo::ativo()->orderBy('nome_guerra')->get();
    }

    public function findBySaram(string $saram): ?Efetivo
    {
        return Efetivo::where('saram', $saram)->first();
    }

    public function all(array $filters = []): LengthAwarePaginator
    {
        $query = Efetivo::query()->orderBy('nome_guerra');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nome_guerra', 'ilike', '%'.$filters['search'].'%')
                    ->orWhere('nome_completo', 'ilike', '%'.$filters['search'].'%')
                    ->orWhere('saram', 'like', '%'.$filters['search'].'%');
            });
        }

        return $query->paginate(20);
    }

    public function create(array $data): Efetivo
    {
        return Efetivo::create($data);
    }

    public function update(Efetivo $efetivo, array $data): Efetivo
    {
        $efetivo->update($data);

        return $efetivo->fresh();
    }

    public function delete(Efetivo $efetivo): bool
    {
        return $efetivo->delete();
    }
}
