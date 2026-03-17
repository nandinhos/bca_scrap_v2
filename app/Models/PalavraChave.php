<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PalavraChave extends Model
{
    use HasFactory;

    protected $table = 'palavras_chaves';

    protected $fillable = ['palavra', 'cor', 'ativa'];

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    public function scopeAtiva($query)
    {
        return $query->where('ativa', true);
    }
}
