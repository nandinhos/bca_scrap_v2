<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bca extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero', 'data', 'url', 'pdf_hash', 'texto_completo', 'processado_em', 'analisado_em', 'keywords_encontradas',
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

    /**
     * URL direta do PDF bruto na fonte oficial (ICEA), sem zero-leading em dia/mês.
     */
    public function urlPdfExterno(): ?string
    {
        if (! $this->data || empty($this->numero) || (string) $this->numero === '0') {
            return null;
        }

        $base = rtrim((string) config('bca.icea_url', 'http://www.icea.intraer/app/arcadia/busca_bca/boletim_bca/'), '/').'/';
        $dia = $this->data->format('j');
        $mes = $this->data->format('n');
        $ano = $this->data->year;

        return "{$base}bca_{$this->numero}_{$dia}-{$mes}-{$ano}.pdf";
    }
}
