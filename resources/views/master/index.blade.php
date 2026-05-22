@extends('layouts.app')

@section('title', 'Master data — ' . config('app.name'))

@section('content')
    <x-ui.page-header title="Master data">
        <x-slot:description>Referensi kamus kompetensi, alat penilaian, matriks, dan peserta.</x-slot:description>
    </x-ui.page-header>

    @php
        $links = [
            ['route' => 'master.kelompok-kompetensi.index', 'icon' => 'category', 'label' => 'Kelompok kompetensi'],
            ['route' => 'master.kompetensi.index', 'icon' => 'psychology', 'label' => 'Kompetensi'],
            ['route' => 'master.tingkat-kompetensi.index', 'icon' => 'stairs', 'label' => 'Tingkat kompetensi'],
            ['route' => 'master.alat-penilaian.index', 'icon' => 'construction', 'label' => 'Alat penilaian'],
            ['route' => 'master.versi-matriks.index', 'icon' => 'grid_view', 'label' => 'Versi matriks', 'hint' => 'Termasuk pemetaan per versi'],
            ['route' => 'master.peserta.index', 'icon' => 'group', 'label' => 'Peserta'],
            ['route' => 'master.template-prompt-ai.index', 'icon' => 'description', 'label' => 'Template prompt AI', 'hint' => 'STAR, kerangka analisis, dll.'],
            ['route' => 'master.model-ai.index', 'icon' => 'model_training', 'label' => 'Model AI (OpenRouter)'],
        ];
        if (auth()->user()->role === 'admin') {
            $links[] = ['route' => 'master.log-aktivitas.index', 'icon' => 'history', 'label' => 'Log aktivitas'];
            $links[] = ['route' => 'master.log-ai.index', 'icon' => 'smart_toy', 'label' => 'Log AI'];
            $links[] = ['route' => 'peserta.impor-csv', 'icon' => 'upload_file', 'label' => 'Impor peserta (CSV)'];
        }
    @endphp

    <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($links as $item)
            <li>
                <a href="{{ route($item['route']) }}" class="card-depth flex flex-col gap-2 p-4 transition-shadow hover:shadow-lg">
                    <span class="material-symbols-outlined text-2xl text-primary">{{ $item['icon'] }}</span>
                    <span class="text-sm font-semibold text-on-surface">{{ $item['label'] }}</span>
                    @if (! empty($item['hint']))
                        <span class="text-xs text-on-surface-variant">{{ $item['hint'] }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
@endsection
