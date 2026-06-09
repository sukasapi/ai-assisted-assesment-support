<dialog id="modal-pilih-sesi-asesmen" data-competency-modal data-modal-size="md" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <div class="competency-modal__form">
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-on-surface">Buat asesmen</h3>
                <p class="mt-1 text-sm text-on-surface-variant">Pilih sesi assessment tempat asesmen baru akan dibuat.</p>
            </div>
            <button type="button" data-close-modal="modal-pilih-sesi-asesmen" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            @if ($daftarSesi->isEmpty())
                <p class="text-sm text-on-surface-variant">Belum ada sesi assessment. Buat sesi terlebih dahulu, lalu kembali ke halaman ini.</p>
            @else
                <div>
                    <label for="pilih-sesi-asesmen-select" class="block text-xs font-semibold uppercase text-on-surface-variant">Sesi assessment</label>
                    <select id="pilih-sesi-asesmen-select" class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">
                        @foreach ($daftarSesi as $sesi)
                            <option value="{{ $sesi->id }}" data-label="{{ $sesi->kode_sesi }} — {{ $sesi->nama }}">
                                {{ $sesi->kode_sesi }} — {{ $sesi->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-pilih-sesi-asesmen" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            @if ($daftarSesi->isNotEmpty())
                <button type="button" id="btn-lanjut-buat-asesmen" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Lanjut</button>
            @else
                <a href="{{ route('sesi-asesmen.index') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Ke sesi assessment</a>
            @endif
        </footer>
    </div>
</dialog>

<script>
    (function () {
        document.querySelectorAll('[data-open-modal="modal-pilih-sesi-asesmen"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.getElementById('modal-pilih-sesi-asesmen')?.showModal?.();
            });
        });
        document.querySelectorAll('[data-close-modal="modal-pilih-sesi-asesmen"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.getElementById('modal-pilih-sesi-asesmen')?.close?.();
            });
        });
        document.getElementById('modal-pilih-sesi-asesmen')?.addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                e.currentTarget.close();
            }
        });
        document.getElementById('btn-lanjut-buat-asesmen')?.addEventListener('click', () => {
            const select = document.getElementById('pilih-sesi-asesmen-select');
            const option = select?.selectedOptions[0];
            if (!option?.value || typeof window.bukaModalTambah !== 'function') {
                return;
            }
            document.getElementById('modal-pilih-sesi-asesmen')?.close?.();
            window.bukaModalTambah({
                sesiId: option.value,
                sesiLabel: option.dataset.label || option.textContent.trim(),
            });
        });
    })();
</script>
