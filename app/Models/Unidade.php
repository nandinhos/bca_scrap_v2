<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unidade extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'sigla', 'codigo', 'ativo'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function efetivos(): HasMany
    {
        return $this->hasMany(Efetivo::class);
    }

    public function scopeAtiva($query)
    {
        return $query->where('ativo', true);
    }
}
