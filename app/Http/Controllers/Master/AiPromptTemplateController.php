<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreAiPromptTemplateRequest;
use App\Http\Requests\Master\UpdateAiPromptTemplateRequest;
use App\Models\AiPromptTemplate;
use App\Models\AssessmentTool;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiPromptTemplateController extends Controller
{
    public function create(): View
    {
        return view('master.ai-prompt-templates.create', [
            'alatPenilaian' => $this->daftarAlat(),
            'alatTerpilih' => [],
        ]);
    }

    public function store(StoreAiPromptTemplateRequest $request): RedirectResponse
    {
        $data = $this->dataTemplate($request->safe()->except(['id_alat_penilaian']));
        $template = AiPromptTemplate::query()->create($data);
        $template->tools()->sync($request->input('id_alat_penilaian', []));

        return redirect()->route('master.template-prompt-ai.index')->with('status', 'Template prompt AI disimpan.');
    }

    public function edit(AiPromptTemplate $templatePromptAi): View
    {
        $templatePromptAi->load('tools');

        return view('master.ai-prompt-templates.edit', [
            'item' => $templatePromptAi,
            'alatPenilaian' => $this->daftarAlat(),
            'alatTerpilih' => $templatePromptAi->tools->pluck('id')->all(),
        ]);
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
    private function daftarAlat(): \Illuminate\Database\Eloquent\Collection
    {
        return AssessmentTool::query()
            ->whereNull('dihapus_pada')
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('kode')
            ->get(['id', 'kode', 'nama']);
    }
}
