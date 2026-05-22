<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreAiOpenRouterModelRequest;
use App\Http\Requests\Master\UpdateAiOpenRouterModelRequest;
use App\Models\AiOpenRouterModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AiOpenRouterModelController extends Controller
{
    public function create(): View
    {
        return view('master.ai-models.create');
    }

    public function store(StoreAiOpenRouterModelRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $request->validated();
            if ($data['utama'] ?? false) {
                AiOpenRouterModel::query()->update(['utama' => false]);
            }
            AiOpenRouterModel::query()->create($data);
        });

        return redirect()->route('master.model-ai.index')->with('status', 'Model AI disimpan.');
    }

    public function edit(AiOpenRouterModel $modelAi): View
    {
        return view('master.ai-models.edit', ['item' => $modelAi]);
    }

    public function update(UpdateAiOpenRouterModelRequest $request, AiOpenRouterModel $modelAi): RedirectResponse
    {
        DB::transaction(function () use ($request, $modelAi): void {
            $data = $request->validated();
            if ($data['utama'] ?? false) {
                AiOpenRouterModel::query()->whereKeyNot($modelAi->id)->update(['utama' => false]);
            }
            $modelAi->update($data);
        });

        return redirect()->route('master.model-ai.index')->with('status', 'Model AI diperbarui.');
    }

    public function destroy(AiOpenRouterModel $modelAi): RedirectResponse
    {
        $modelAi->delete();

        return redirect()->route('master.model-ai.index')->with('status', 'Model AI dihapus.');
    }
}
