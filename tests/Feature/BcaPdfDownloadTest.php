<?php

use App\Models\Bca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('PDF do BCA exige login e baixa autenticado', function () {
    Storage::fake('bcas');
    Storage::disk('bcas')->put('bcas/2026-10-22.pdf', '%PDF-1.4 conteudo');
    Bca::factory()->create(['data' => '2026-10-22', 'url' => 'bcas/2026-10-22.pdf']);

    $this->get('/bca/2026-10-22/pdf')->assertRedirect('/login');

    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/bca/2026-10-22/pdf')
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
