@extends('layouts.app')

@section('title', 'Master data — ' . config('app.name'))

@section('content')
    <x-ui.page-header title="Master data">
        <x-slot:description>Referensi kamus kompetensi, alat penilaian, matriks, konfigurasi AI, dan pengguna.</x-slot:description>
    </x-ui.page-header>

    @php
        $isAdmin = auth()->user()?->isAdmin();

        $sections = [
            [
                'title' => 'Assessment',
                'description' => 'Kamus kompetensi, alat penilaian, dan versi matriks pemetaan.',
                'items' => [
                    ['route' => 'master.kompetensi.index', 'icon' => 'account_tree', 'label' => 'Kompetensi', 'hint' => 'Kelompok, kompetensi, dan tingkat dalam satu halaman'],
                    ['route' => 'master.alat-penilaian.index', 'icon' => 'construction', 'label' => 'Alat penilaian'],
                    ['route' => 'master.versi-matriks.index', 'icon' => 'grid_view', 'label' => 'Versi matriks', 'hint' => 'Termasuk pemetaan per versi'],
                ],
            ],
            [
                'title' => 'Konfigurasi AI',
                'description' => 'Model, template prompt, dan monitoring panggilan AI.',
                'items' => array_values(array_filter([
                    ['route' => 'master.model-ai.index', 'icon' => 'model_training', 'label' => 'Model AI (OpenRouter)'],
                    ['route' => 'master.template-prompt-ai.index', 'icon' => 'description', 'label' => 'Template prompt AI', 'hint' => 'STAR, kerangka analisis, dll.'],
                    $isAdmin ? ['route' => 'master.log-ai.index', 'icon' => 'smart_toy', 'label' => 'Log AI'] : null,
                ])),
            ],
            [
                'title' => 'Pengguna',
                'description' => 'Akun aplikasi, data peserta, dan jejak aktivitas.',
                'items' => array_values(array_filter([
                    $isAdmin ? ['route' => 'master.pengguna.index', 'icon' => 'manage_accounts', 'label' => 'Pengguna aplikasi'] : null,
                    ['route' => 'master.peserta.index', 'icon' => 'group', 'label' => 'Peserta'],
                    $isAdmin ? ['route' => 'master.log-aktivitas.index', 'icon' => 'history', 'label' => 'Log aktivitas'] : null,
                    $isAdmin ? ['route' => 'master.token-api.index', 'icon' => 'vpn_key', 'label' => 'Token API'] : null,
                ])),
            ],
        ];
    @endphp

    <div class="space-y-6">
        @foreach ($sections as $section)
            @if (count($section['items']) > 0)
                <section class="card-depth overflow-hidden rounded-xl">
                    <div class="border-b border-outline-variant/30 px-6 py-4">
                        <h2 class="text-base font-semibold text-on-surface">{{ $section['title'] }}</h2>
                        @if (! empty($section['description']))
                            <p class="mt-0.5 text-sm text-on-surface-variant">{{ $section['description'] }}</p>
                        @endif
                    </div>
                    <ul class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($section['items'] as $item)
                            <li>
                                <a href="{{ route($item['route']) }}" class="flex h-full flex-col gap-2 rounded-lg border border-outline-variant/30 bg-surface-container-lowest p-4 transition-shadow hover:border-primary/30 hover:shadow-md">
                                    <span class="material-symbols-outlined text-2xl text-primary">{{ $item['icon'] }}</span>
                                    <span class="text-sm font-semibold text-on-surface">{{ $item['label'] }}</span>
                                    @if (! empty($item['hint']))
                                        <span class="text-xs text-on-surface-variant">{{ $item['hint'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endforeach
    </div>
@endsection
