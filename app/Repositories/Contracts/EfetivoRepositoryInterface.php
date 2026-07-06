<?php

namespace App\Repositories\Contracts;

use App\Models\Efetivo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface EfetivoRepositoryInterface
{
    public function getAtivos(): Collection;

    public function findBySaram(string $saram): ?Efetivo;

    public function all(array $filters = []): LengthAwarePaginator;

    public function create(array $data): Efetivo;

    public function update(Efetivo $efetivo, array $data): Efetivo;

    public function delete(Efetivo $efetivo): bool;
}
