<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Efetivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'saram', 'nome_guerra', 'nome_completo', 'posto',
        'especialidade', 'email', 'om_origem', 'ativo', 'oculto',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'oculto' => 'boolean',
        ];
    }

    public function ocorrencias(): HasMany
    {
        return $this->hasMany(BcaOcorrencia::class);
    }

    public function scopeAtivo($query)
    {
        return $query->where('ativo', true)->where('oculto', false);
    }

    public function getSaramHifenado(): string
    {
        $s = $this->saram;

        return substr($s, 0, -1).'-'.substr($s, -1);
    }
}
