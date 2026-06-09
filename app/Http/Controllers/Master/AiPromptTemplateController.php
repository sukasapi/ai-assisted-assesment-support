<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreAiPromptTemplateRequest;
use App\Http\Requests\Master\UpdateAiPromptTemplateRequest;
use App\Models\AiPromptTemplate;
use App\Models\AssessmentTool;
use Illuminate\Http\RedirectResponse;

class AiPromptTemplateController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->route('master.template-prompt-ai.index');
    }

    public function store(StoreAiPromptTemplateRequest $request): RedirectResponse
    {
        $data = $this->dataTemplate($request->safe()->except(['id_alat_penilaian']));
        $template = AiPromptTemplate::query()->create($data);
        $template->tools()->sync($request->input('id_alat_penilaian', []));

        return redirect()->route('master.template-prompt-ai.index')->with('status', 'Template prompt AI disimpan.');
    }

    public function edit(AiPromptTemplate $templatePromptAi): RedirectResponse
    {
        return redirect()->route('master.template-prompt-ai.index');
    }

    public function update(UpdateAiPromptTemplateRequest $request, AiPromptTemplate $templatePromptAi): RedirectResponse
    {
        $data = $this->dataTemplate($request->safe()->except(['id_alat_penilaian']));
        $templatePromptAi->update($data);
        $templatePromptAi->tools()->sync($request->input('id_alat_penilaian', []));

        return redirect()->route('master.template-prompt-ai.index')->with('status', 'Template prompt AI diperbarui.');
    }

    public function destroy(AiPromptTemplate $templatePromptAi): RedirectResponse
    {
        if ($templatePromptAi->assessments()->exists()) {
            return redirect()
                ->route('master.template-prompt-ai.index')
                ->with('error', 'Tidak dapat menghapus: template masih dipakai pada asesmen (default global).');
        }

        if ($templatePromptAi->toolAiPromptOverrides()->exists()) {
            return redirect()
                ->route('master.template-prompt-ai.index')
                ->with('error', 'Tidak dapat menghapus: template masih dipakai pada pengaturan per alat asesmen.');
        }

        $templatePromptAi->delete();

        return redirect()->route('master.template-prompt-ai.index')->with('status', 'Template prompt AI dihapus.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function dataTemplate(array $data): array
    {
        $data['teks_instruksi'] = trim((string) ($data['teks_instruksi'] ?? ''));

        return $data;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, AssessmentTool>
     */
    public static function daftarAlatAktif(): \Illuminate\Database\Eloquent\Collection
    {
        return AssessmentTool::query()
            ->whereNull('dihapus_pada')
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('kode')
            ->get(['id', 'kode', 'nama']);
    }
}
