<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BcaExecucao extends Model
{
    protected $table = 'bca_execucoes';

    public $timestamps = false;

    protected $fillable = [
        'tipo', 'data_execucao', 'status', 'mensagem', 'registros_processados',
    ];

    protected function casts(): array
    {
        return [
            'data_execucao' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
