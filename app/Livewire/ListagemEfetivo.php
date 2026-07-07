<?php

namespace App\Livewire;

use App\Models\Efetivo;
use App\Models\Unidade;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Efetivo')]
class ListagemEfetivo extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $saram = '';

    public string $nomeGuerra = '';

    public string $nomeCompleto = '';

    public string $posto = '';

    public string $especialidade = '';

    public string $email = '';

    public string $omOrigem = '';

    public bool $ativo = true;

    public bool $oculto = false;

    public ?int $unidadeId = null;

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
        $this->reset(['editingId', 'saram', 'nomeGuerra', 'nomeCompleto', 'posto', 'especialidade', 'email', 'omOrigem', 'ativo', 'oculto', 'unidadeId']);
        $this->ativo = true;
        $user = auth()->user();
        $this->unidadeId = $user?->isAdmin() ? null : $user?->unidade_id;
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
        $this->unidadeId = $e->unidade_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $userUnidadeId = $user?->unidade_id;

        $this->validate([
            'saram' => 'required|string|max:8',
            'nomeGuerra' => 'required|string|max:50',
            'nomeCompleto' => 'required|string|max:200',
            'posto' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'unidadeId' => $isAdmin ? 'required|exists:unidades,id' : 'nullable',
        ]);

        $unidadeId = $isAdmin ? $this->unidadeId : $userUnidadeId;

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
            'unidade_id' => $unidadeId,
        ];

        if ($this->editingId) {
            Efetivo::findOrFail($this->editingId)->update($data);
        } else {
            Efetivo::create($data);
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function toggleSelectAll(): void
    {
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $userUnidadeId = $user?->unidade_id;

        if (count($this->selectedIds) > 0) {
            $this->selectedIds = [];

            return;
        }

        $ids = Efetivo::query()
            ->when(! $isAdmin && $userUnidadeId, fn ($q) => $q->where('unidade_id', $userUnidadeId))
            ->when($this->search, fn ($q) => $q->where('nome_guerra', 'ilike', "%{$this->search}%")
                ->orWhere('nome_completo', 'ilike', "%{$this->search}%")
                ->orWhere('saram', 'like', "%{$this->search}%"))
            ->pluck('id')
            ->toArray();

        $this->selectedIds = $ids;
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $userUnidadeId = $user?->unidade_id;

        Efetivo::query()
            ->whereIn('id', $this->selectedIds)
            ->when(! $isAdmin && $userUnidadeId, fn ($q) => $q->where('unidade_id', $userUnidadeId))
            ->delete();
        $this->selectedIds = [];
        $this->resetPage();
    }

    public function exportCsv()
    {
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $userUnidadeId = $user?->unidade_id;

        $efetivos = Efetivo::query()
            ->when(! $isAdmin && $userUnidadeId, fn ($q) => $q->where('unidade_id', $userUnidadeId))
            ->orderBy('nome_guerra')
            ->get();

        $filename = 'efetivo_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($efetivos) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['SARAM', 'Nome Guerra', 'Nome Completo', 'Posto', 'Especialidade', 'Email', 'OM Origem', 'Ativo', 'Oculto']);
            foreach ($efetivos as $e) {
                fputcsv($out, [
                    $e->saram,
                    $e->nome_guerra,
                    $e->nome_completo,
                    $e->posto,
                    $e->especialidade,
                    $e->email,
                    $e->om_origem,
                    $e->ativo ? '1' : '0',
                    $e->oculto ? '1' : '0',
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCsv(): void
    {
        $this->validate([
            'uploadedFile' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $userUnidadeId = $user?->unidade_id;

        $this->importing = true;
        $this->importLog = [];
        $this->showImportLog = false;
        $this->showImportModal = false;

        $path = $this->uploadedFile->getRealPath();
        $handle = fopen($path, 'r');
        $firstLine = fgets($handle);
        $hasBom = str_starts_with($firstLine, "\xEF\xBB\xBF");
        if (! $hasBom) {
            rewind($handle);
        }

        $header = fgetcsv($handle, 0, ',', '"', '\\');
        $imported = 0;
        $errors = 0;
        $lineNum = 1;

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $lineNum++;
            if (count($row) < 3) {
                $errors++;
                $this->importLog[] = ['linha' => $lineNum, 'status' => 'erro', 'msg' => 'Linha inválida (menos de 3 colunas)'];

                continue;
            }
            $data = array_combine($header, array_pad($row, count($header), null));
            $saram = trim($data['SARAM'] ?? '');
            $nomeGuerra = trim($data['Nome Guerra'] ?? '');
            $nomeCompleto = trim($data['Nome Completo'] ?? '');

            if ($saram === '' || $nomeGuerra === '' || $nomeCompleto === '') {
                $errors++;
                $this->importLog[] = ['linha' => $lineNum, 'status' => 'erro', 'msg' => "SARAM/Nome Guerra/Nome Completo obrigatórios ({$saram})"];

                continue;
            }

            $csvUnidadeId = isset($data['Unidade']) ? (int) trim($data['Unidade']) : 0;
            if ($isAdmin && $csvUnidadeId > 0) {
                $unidadeId = $csvUnidadeId;
            } elseif ($isAdmin) {
                $unidadeId = $userUnidadeId;
            } else {
                $unidadeId = $userUnidadeId;
            }

            try {
                Efetivo::updateOrCreate(
                    ['saram' => $saram],
                    [
                        'nome_guerra' => strtoupper($nomeGuerra),
                        'nome_completo' => strtoupper($nomeCompleto),
                        'posto' => trim($data['Posto'] ?? ''),
                        'especialidade' => trim($data['Especialidade'] ?? '') ?: null,
                        'email' => trim($data['Email'] ?? '') ?: null,
                        'om_origem' => trim($data['OM Origem'] ?? '') ?: null,
                        'ativo' => (trim($data['Ativo'] ?? '1') === '1'),
                        'oculto' => (trim($data['Oculto'] ?? '0') === '1'),
                        'unidade_id' => $unidadeId,
                    ]
                );
                $imported++;
            } catch (\Throwable $e) {
                $errors++;
                $this->importLog[] = ['linha' => $lineNum, 'status' => 'erro', 'msg' => $e->getMessage()];
            }
        }
        fclose($handle);

        $this->importLog[] = ['linha' => '-', 'status' => 'ok', 'msg' => "Importação concluída: {$imported} registro(s), {$errors} erro(s)."];
        $this->showImportLog = true;
        $this->importing = false;
        $this->uploadedFile = null;
        $this->resetPage();
    }

    public function getImportLogText(): string
    {
        $lines = ["Linha | Status | Mensagem"];
        foreach ($this->importLog as $l) {
            $lines[] = "{$l['linha']} | {$l['status']} | {$l['msg']}";
        }

        return implode("\n", $lines);
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $userUnidadeId = $user?->unidade_id;

        return view('livewire.listagem-efetivo', [
            'efetivos' => Efetivo::query()
                ->when(! $isAdmin && $userUnidadeId, fn ($q) => $q->where('unidade_id', $userUnidadeId))
                ->when($this->search, fn ($q) => $q->where('nome_guerra', 'ilike', "%{$this->search}%")
                    ->orWhere('nome_completo', 'ilike', "%{$this->search}%")
                    ->orWhere('saram', 'like', "%{$this->search}%"))
                ->orderBy('nome_guerra')
                ->paginate(20),
            'isAdmin' => $isAdmin,
            'unidades' => Unidade::orderBy('sigla')->get(),
        ]);
    }
}
