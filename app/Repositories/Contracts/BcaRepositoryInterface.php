<?php
namespace App\Repositories\Contracts;

use App\Models\Bca;
use Illuminate\Database\Eloquent\Collection;

interface BcaRepositoryInterface
{
    public function findByData(string $data): ?Bca;
    public function createOrUpdateByData(string $data, array $attributes): Bca;
    public function recent(int $limit = 30): Collection;
}
