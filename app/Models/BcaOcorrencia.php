<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BcaOcorrencia extends Model
{
    public $timestamps = false;

    protected $fillable = ['bca_id', 'efetivo_id', 'tipo_match', 'quantidade', 'snippet', 'enviado_em'];

    protected function casts(): array
    {
        return [
            'enviado_em' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function bca(): BelongsTo
    {
        return $this->belongsTo(Bca::class);
    }

    public function efetivo(): BelongsTo
    {
        return $this->belongsTo(Efetivo::class);
    }

    public function foiEnviado(): bool
    {
        return $this->enviado_em !== null;
    }
}
