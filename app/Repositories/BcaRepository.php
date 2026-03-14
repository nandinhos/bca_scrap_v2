<?php
namespace App\Repositories;

use App\Models\Bca;
use App\Repositories\Contracts\BcaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class BcaRepository implements BcaRepositoryInterface
{
    public function findByData(string $data): ?Bca
    {
        return Bca::where('data', $data)->first();
    }

    public function createOrUpdateByData(string $data, array $attributes): Bca
    {
        return Bca::updateOrCreate(['data' => $data], $attributes);
    }

    public function recent(int $limit = 30): Collection
    {
        return Bca::orderByDesc('data')->limit($limit)->get();
    }
}
