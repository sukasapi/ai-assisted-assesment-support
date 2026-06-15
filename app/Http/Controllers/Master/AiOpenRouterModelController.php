<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SetUtamaAiOpenRouterModelRequest;
use App\Http\Requests\Master\StoreAiOpenRouterModelRequest;
use App\Http\Requests\Master\UpdateAiOpenRouterModelRequest;
use App\Models\AiOpenRouterModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AiOpenRouterModelController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->route('master.model-ai.index');
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

    public function edit(AiOpenRouterModel $modelAi): RedirectResponse
    {
        return redirect()->route('master.model-ai.index');
    }

    public function update(UpdateAiOpenRouterModelRequest $request, AiOpenRouterModel $modelAi): RedirectResponse
    {
        DB::transaction(function () use ($request, $modelAi): void {
            $data = $request->validated();
            if (array_key_exists('utama', $data) && ($data['utama'] ?? false)) {
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

    public function setUtama(SetUtamaAiOpenRouterModelRequest $request, AiOpenRouterModel $modelAi): RedirectResponse
    {
        DB::transaction(function () use ($request, $modelAi): void {
            if ($request->boolean('utama')) {
                AiOpenRouterModel::query()->whereKeyNot($modelAi->id)->update(['utama' => false]);
                $modelAi->update(['utama' => true]);
            } else {
                $modelAi->update(['utama' => false]);
            }
        });

        return back()->with(
            'status',
            $request->boolean('utama') ? 'Model utama diperbarui.' : 'Model tidak lagi menjadi utama.',
        );
    }
}
