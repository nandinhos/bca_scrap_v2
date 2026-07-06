<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PalavraChave extends Model
{
    use HasFactory;

    protected $table = 'palavras_chaves';

    protected $fillable = ['palavra', 'cor', 'ativa', 'unidade_id'];

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    public function scopeAtiva($query)
    {
        return $query->where('ativa', true);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }
}
