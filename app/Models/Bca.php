<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bca extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero', 'data', 'url', 'texto_completo', 'processado_em', 'analisado_em', 'keywords_encontradas',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'processado_em' => 'datetime',
            'analisado_em' => 'datetime',
            'keywords_encontradas' => 'array',
        ];
    }

    public function ocorrencias(): HasMany
    {
        return $this->hasMany(BcaOcorrencia::class);
    }

    public function scopeProcessado($query)
    {
        return $query->whereNotNull('processado_em');
    }
}
