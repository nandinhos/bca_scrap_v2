<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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
     * URL direta do PDF bruto na fonte oficial (ICEA).
     * Em dias/meses de 1 dígito, o ICEA alterna entre j-n-Y e d-m-Y.
     */
    public function urlPdfExterno(): ?string
    {
        if (! $this->data || empty($this->numero) || (string) $this->numero === '0') {
            return null;
        }

        $base = rtrim((string) config('bca.icea_url', 'http://www.icea.intraer/app/arcadia/busca_bca/boletim_bca/'), '/').'/';
        $ano = $this->data->year;
        $semZero = "{$base}bca_{$this->numero}_{$this->data->format('j')}-{$this->data->format('n')}-{$ano}.pdf";
        $comZero = "{$base}bca_{$this->numero}_{$this->data->format('d')}-{$this->data->format('m')}-{$ano}.pdf";

        if ($semZero === $comZero || app()->runningUnitTests()) {
            return $semZero;
        }

        return Cache::remember("bca:icea_url:{$this->data->format('Y-m-d')}:{$this->numero}", now()->addHours(24), function () use ($semZero, $comZero) {
            foreach ([$semZero, $comZero] as $url) {
                try {
                    if (Http::timeout(3)->head($url)->successful()) {
                        return $url;
                    }
                } catch (\Throwable) {
                }
            }

            return $semZero;
        });
    }
}
