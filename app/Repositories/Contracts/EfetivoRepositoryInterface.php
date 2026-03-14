<?php
namespace App\Repositories\Contracts;

use App\Models\Efetivo;
use Illuminate\Database\Eloquent\Collection;

interface EfetivoRepositoryInterface
{
    public function getAtivos(): Collection;
    public function findBySaram(string $saram): ?Efetivo;
    public function all(array $filters = []): \Illuminate\Pagination\LengthAwarePaginator;
    public function create(array $data): Efetivo;
    public function update(Efetivo $efetivo, array $data): Efetivo;
    public function delete(Efetivo $efetivo): bool;
}
