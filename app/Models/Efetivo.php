<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Efetivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'saram', 'nome_guerra', 'nome_completo', 'posto',
        'especialidade', 'email', 'om_origem', 'ativo', 'oculto',
        'unidade_id',
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

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
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

    /** Posto/graduação + nome de guerra ("Cel FONTES"); civis aparecem só pelo nome. */
    public function getPostoNomeGuerra(): string
    {
        $posto = strcasecmp(trim((string) $this->posto), 'Civil') === 0 ? '' : $this->posto;

        return trim("{$posto} {$this->nome_guerra}");
    }
}
