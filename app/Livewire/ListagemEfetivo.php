<?php

namespace App\Livewire;

use App\Models\Efetivo;
use App\Models\Unidade;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Efetivo')]
class ListagemEfetivo extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $saram = '';

    public string $nomeGuerra = '';

    public string $nomeCompleto = '';

    public string $posto = '';

    public string $especialidade = '';

    public string $email = '';

    public string $omOrigem = 'GAC-PAC';

    public bool $ativo = true;

    public bool $oculto = false;

    public array $selectedIds = [];

    public bool $showImportLog = false;

    public bool $showImportModal = false;

    public array $importLog = [];

    public $uploadedFile = null;

    public bool $importing = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'saram', 'nomeGuerra', 'nomeCompleto', 'posto', 'especialidade', 'email', 'omOrigem', 'ativo', 'oculto']);
        $this->omOrigem = 'GAC-PAC';
        $this->ativo = true;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $e = Efetivo::findOrFail($id);
        $this->editingId = $id;
        $this->saram = $e->saram;
        $this->nomeGuerra = $e->nome_guerra;
        $this->nomeCompleto = $e->nome_completo;
        $this->posto = $e->posto;
        $this->especialidade = $e->especialidade ?? '';
        $this->email = $e->email ?? '';
        $this->omOrigem = $e->om_origem;
        $this->ativo = $e->ativo;
        $this->oculto = $e->oculto;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'saram' => 'required|string|max:8',
            'nomeGuerra' => 'required|string|max:50',
            'nomeCompleto' => 'required|string|max:200',
            'posto' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $data = [
            'saram' => $this->saram,
            'nome_guerra' => strtoupper($this->nomeGuerra),
            'nome_completo' => strtoupper($this->nomeCompleto),
            'posto' => $this->posto,
            'especialidade' => $this->especialidade ?: null,
            'email' => $this->email ?: null,
            'om_origem' => $this->omOrigem,
            'ativo' => $this->ativo,
            'oculto' => $this->oculto,
        ];

        if ($this->editingId) {
            Efetivo::findOrFail($this->editingId)->update($data);
        } else {
            Efetivo::create($data);
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function exportCsv(): void
    {
        $efetivos = Efetivo::orderBy('nome_guerra')->get();
        $headers = ['saram', 'nome_completo', 'nome_guerra', 'posto', 'especialidade', 'email', 'om_origem', 'unidade_id'];

        $content = implode(',', $headers)."\n";
        foreach ($efetivos as $e) {
            $row = [
                $e->saram,
                $e->nome_completo,
                $e->nome_guerra,
                $e->posto,
                $e->especialidade ?? '',
                $e->email ?? '',
                $e->om_origem ?? '',
                $e->unidade_id ?? '',
            ];
            $content .= implode(',', array_map(fn ($v) => "\"{$v}\"", $row))."\n";
        }

        $filename = 'efetivo_'.now()->format('Y-m-d_His').'.csv';
        response()->streamDownload(fn () => print($content), $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ])->send();
    }

    public function importCsv(): void
    {
        if (! $this->uploadedFile) {
            return;
        }

        $this->importing = true;
        $this->importLog = [];
        $lines = file($this->uploadedFile->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (! $lines || count($lines) < 2) {
            $this->importLog[] = 'ERRO: Arquivo vazio ou sem dados';
            $this->importing = false;
            $this->showImportLog = true;

            return;
        }

        $headers = str_getcsv(array_shift($lines));
        $headers = array_map('trim', $headers);

        $required = ['saram', 'nome_completo', 'nome_guerra', 'posto'];
        foreach ($required as $field) {
            if (! in_array($field, $headers)) {
                $this->importLog[] = "ERRO: Header obrigatório '{$field}' não encontrado";
                $this->importing = false;
                $this->showImportLog = true;

                return;
            }
        }

        $unidades = Unidade::pluck('id')->toArray();

        foreach ($lines as $lineNum => $line) {
            $lineNum += 2;
            $fields = str_getcsv($line);

            if (count($fields) < count($headers)) {
                $this->importLog[] = "LINHA {$lineNum}: IGNORED - Número de colunas insuficiente";
                continue;
            }

            $row = array_combine($headers, $fields);

            $saram = trim($row['saram'] ?? '');
            $nomeCompleto = trim($row['nome_completo'] ?? '');
            $nomeGuerra = trim($row['nome_guerra'] ?? '');
            $posto = trim($row['posto'] ?? '');

            if (empty($saram) || empty($nomeCompleto) || empty($nomeGuerra) || empty($posto)) {
                $this->importLog[] = "LINHA {$lineNum}: IGNORED - Campos obrigatórios vazios (saram, nome_completo, nome_guerra, posto)";
                continue;
            }

            if (Efetivo::where('saram', $saram)->exists()) {
                $this->importLog[] = "LINHA {$lineNum}: IGNORED - SARAM {$saram} já existe no banco";
                continue;
            }

            $email = trim($row['email'] ?? '');
            if (! empty($email) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->importLog[] = "LINHA {$lineNum}: IGNORED - Email inválido '{$email}'";
                continue;
            }

            $unidadeId = trim($row['unidade_id'] ?? '');
            if (! empty($unidadeId) && ! in_array((int) $unidadeId, $unidades)) {
                $this->importLog[] = "LINHA {$lineNum}: IGNORED - unidade_id {$unidadeId} não encontrado na tabela unidades";
                continue;
            }

            try {
                Efetivo::create([
                    'saram' => $saram,
                    'nome_completo' => strtoupper($nomeCompleto),
                    'nome_guerra' => strtoupper($nomeGuerra),
                    'posto' => $posto,
                    'especialidade' => trim($row['especialidade'] ?? '') ?: null,
                    'email' => $email ?: null,
                    'om_origem' => trim($row['om_origem'] ?? '') ?: null,
                    'unidade_id' => $unidadeId ? (int) $unidadeId : null,
                    'ativo' => true,
                    'oculto' => false,
                ]);
                $this->importLog[] = "LINHA {$lineNum}: OK - {$saram} | {$nomeGuerra} | {$posto}";
            } catch (\Throwable $e) {
                $this->importLog[] = "LINHA {$lineNum}: ERRO - {$saram} | {$e->getMessage()}";
            }
        }

        $this->importing = false;
        $this->showImportModal = false;
        $this->showImportLog = true;
        $this->uploadedFile = null;
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        $count = Efetivo::whereIn('id', $this->selectedIds)->delete();
        $this->importLog = ["OK: {$count} militar(es) removido(s)"];
        $this->selectedIds = [];
        $this->showImportLog = true;
    }

    public function toggleSelectAll(): void
    {
        if (empty($this->selectedIds)) {
            $this->selectedIds = Efetivo::query()
                ->when($this->search, fn ($q) => $q->where('nome_guerra', 'ilike', "%{$this->search}%")
                    ->orWhere('nome_completo', 'ilike', "%{$this->search}%")
                    ->orWhere('saram', 'like', "%{$this->search}%"))
                ->pluck('id')->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function getImportLogText(): string
    {
        return implode("\n", $this->importLog);
    }

    public function render()
    {
        return view('livewire.listagem-efetivo', [
            'efetivos' => Efetivo::query()
                ->when($this->search, fn ($q) => $q->where('nome_guerra', 'ilike', "%{$this->search}%")
                    ->orWhere('nome_completo', 'ilike', "%{$this->search}%")
                    ->orWhere('saram', 'like', "%{$this->search}%"))
                ->orderBy('nome_guerra')
                ->paginate(20),
        ]);
    }
}
