<dialog
    id="modal-unduh-mapping-pk"
    data-competency-modal
    data-modal-size="md"
    data-export-url="{{ route('asesmen.perilaku.export-csv', $asesmen) }}"
    class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl"
>
    <div class="competency-modal__form">
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-on-surface">Unduh data mapping</h3>
                <p class="mt-1 text-sm text-on-surface-variant">Pilih pemisah kolom (delimiter) untuk berkas CSV yang akan diunduh.</p>
            </div>
            <button type="button" data-close-modal="modal-unduh-mapping-pk" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body">
            <fieldset>
                <legend class="text-sm font-medium text-on-surface">Pemisah kolom (delimiter)</legend>
                <div class="mt-3 space-y-2">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-on-surface">
                        <input type="radio" name="delimiter_unduh_mapping" value="," checked class="size-4 border-outline-variant text-on-surface">
                        Koma (<span class="font-mono">,</span>) — umum untuk locale US / Excel regional tertentu
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-on-surface">
                        <input type="radio" name="delimiter_unduh_mapping" value=";" class="size-4 border-outline-variant text-on-surface">
                        Titik koma (<span class="font-mono">;</span>) — umum untuk Excel locale Indonesia / Eropa
                    </label>
                </div>
            </fieldset>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-unduh-mapping-pk" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="button" id="btn-unduh-mapping-pk" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">
                <span class="material-symbols-outlined text-base">download</span>
                Unduh CSV
            </button>
        </footer>
    </div>
</dialog>

<script>
    (function () {
        const dialog = document.getElementById('modal-unduh-mapping-pk');
        if (!dialog) {
            return;
        }

        document.querySelectorAll('[data-open-modal="modal-unduh-mapping-pk"]').forEach((btn) => {
            btn.addEventListener('click', () => dialog.showModal());
        });

        document.querySelectorAll('[data-close-modal="modal-unduh-mapping-pk"]').forEach((btn) => {
            btn.addEventListener('click', () => dialog.close());
        });

        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) {
                dialog.close();
            }
        });

        document.getElementById('btn-unduh-mapping-pk')?.addEventListener('click', () => {
            const sep = dialog.querySelector('input[name="delimiter_unduh_mapping"]:checked')?.value;
            const base = dialog.dataset.exportUrl;
            if (!sep || !base) {
                return;
            }
            window.location.href = `${base}?delimiter=${encodeURIComponent(sep)}`;
            dialog.close();
        });
    })();
</script>
