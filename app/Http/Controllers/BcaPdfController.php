<?php

namespace App\Http\Controllers;

use App\Models\Bca;
use Illuminate\Support\Facades\Storage;

class BcaPdfController extends Controller
{
    /** Download do PDF do BCA somente para usuario autenticado. */
    public function download(string $data)
    {
        $bca = Bca::where('data', $data)->firstOrFail();

        abort_unless($bca->url && Storage::disk('bcas')->exists($bca->url), 404);

        return Storage::disk('bcas')->download($bca->url, "bca-{$data}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
