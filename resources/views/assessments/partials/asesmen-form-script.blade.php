<script>
    (function () {
        const routes = {
            store: @json($storeUrlTemplate),
            update: @json($updateUrlTemplate),
        };
        const replaceId = (url, id) => url.replace('999999999', String(id));

        const openModal = (id) => document.getElementById(id)?.showModal?.();
        const closeModal = (id) => document.getElementById(id)?.close?.();

        const parseEditPayload = (raw) => {
            if (!raw) return null;
            try {
                return JSON.parse(raw);
            } catch {
                const textarea = document.createElement('textarea');
                textarea.innerHTML = raw;
                try {
                    return JSON.parse(textarea.value);
                } catch {
                    console.error('Gagal memuat data edit modal.');
                    return null;
                }
            }
        };

        document.querySelectorAll('[data-open-modal]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const modalId = btn.dataset.openModal;
                if (modalId === 'modal-asesmen-tambah' && btn.dataset.sesiId) {
                    bukaModalTambah(btn.dataset);
                } else if (modalId === 'modal-asesmen-ubah' && btn.dataset.edit) {
                    bukaModalUbah(parseEditPayload(btn.dataset.edit));
                } else if (modalId) {
                    openModal(modalId);
                }
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach((btn) => {
            btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
        });

        document.querySelectorAll('dialog[data-competency-modal]').forEach((dialog) => {
            dialog.addEventListener('click', (e) => {
                if (e.target === dialog && dialog.dataset.modalSize !== 'fullscreen') {
                    dialog.close();
                }
            });
        });

        document.querySelectorAll('[data-metode-koleksi-group]').forEach((group) => {
            const sync = () => {
                group.querySelectorAll('.asesmen-metode-card').forEach((card) => {
                    const input = card.querySelector('input[type="radio"]');
                    card.classList.toggle('asesmen-metode-card--active', Boolean(input?.checked));
                });
            };
            group.querySelectorAll('input[type="radio"]').forEach((input) => {
                input.addEventListener('change', sync);
            });
            sync();
        });

        const setFormValue = (form, name, value) => {
            const fields = form.querySelectorAll(`[name="${name}"]`);
            if (fields.length === 0 && name === 'id_asesor[]') {
                return;
            }
            fields.forEach((field) => {
                if (field.type === 'checkbox' && field.name === 'tanpa_intray') {
                    field.checked = Boolean(value);
                } else if (field.type === 'radio') {
                    field.checked = field.value === String(value);
                } else if (field.tagName === 'SELECT') {
                    field.value = value ?? '';
                } else {
                    field.value = value ?? '';
                }
            });
            if (name === 'metode_koleksi_bukti') {
                form.querySelectorAll('[data-metode-koleksi-group]').forEach((group) => {
                    group.querySelectorAll('.asesmen-metode-card').forEach((card) => {
                        const input = card.querySelector('input[type="radio"]');
                        card.classList.toggle('asesmen-metode-card--active', Boolean(input?.checked));
                    });
                });
            }
        };

        let activePesertaFormKey = null;

        const updatePesertaTriggerLabel = (formKey) => {
            const list = document.querySelector(`[data-peserta-selected-list="${formKey}"]`);
            const label = document.querySelector(`[data-peserta-trigger-label="${formKey}"]`);
            const count = list?.querySelectorAll('[data-peserta-chip]').length ?? 0;
            if (label) {
                label.textContent = count === 0
                    ? 'Klik untuk memilih peserta…'
                    : `${count} peserta dipilih`;
            }
            const countEl = document.querySelector('[data-peserta-picker-count]');
            if (countEl && activePesertaFormKey === formKey) {
                countEl.textContent = `${count} peserta dipilih`;
            }
        };

        const tambahChipPeserta = (formKey, id, nama) => {
            const list = document.querySelector(`[data-peserta-selected-list="${formKey}"]`);
            if (!list || list.querySelector(`[data-peserta-chip="${id}"]`)) return;
            const chip = document.createElement('span');
            chip.className = 'asesmen-peserta-chip';
            chip.dataset.pesertaChip = id;
            chip.innerHTML = `
                <input type="hidden" name="id_peserta[]" value="${id}">
                <span>${nama}</span>
                <button type="button" data-peserta-chip-remove="${id}" aria-label="Hapus ${nama}">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            `;
            chip.querySelector('[data-peserta-chip-remove]')?.addEventListener('click', () => {
                chip.remove();
                const cb = document.querySelector(`[data-peserta-checkbox][value="${id}"]`);
                if (cb) cb.checked = false;
                updatePesertaTriggerLabel(formKey);
            });
            list.appendChild(chip);
            updatePesertaTriggerLabel(formKey);
        };

        const hapusChipPeserta = (formKey, id) => {
            document.querySelector(`[data-peserta-selected-list="${formKey}"] [data-peserta-chip="${id}"]`)?.remove();
            updatePesertaTriggerLabel(formKey);
        };

        const syncModalCheckboxDariForm = (formKey) => {
            const selected = new Set(
                [...document.querySelectorAll(`[data-peserta-selected-list="${formKey}"] [data-peserta-chip]`)]
                    .map((el) => el.dataset.pesertaChip)
            );
            document.querySelectorAll('[data-peserta-checkbox]').forEach((cb) => {
                cb.checked = selected.has(cb.value);
            });
            updatePesertaTriggerLabel(formKey);
        };

        const setPesertaIds = (formKey, ids, poolRows) => {
            const list = document.querySelector(`[data-peserta-selected-list="${formKey}"]`);
            if (!list) return;
            list.innerHTML = '';
            (ids || []).forEach((id) => {
                const row = poolRows?.find((r) => r.dataset.pesertaId === String(id))
                    ?? document.querySelector(`[data-peserta-id="${id}"]`);
                const nama = row?.dataset.pesertaNama ?? `Peserta #${id}`;
                tambahChipPeserta(formKey, String(id), nama);
            });
            syncModalCheckboxDariForm(formKey);
        };

        document.querySelectorAll('[data-peserta-picker-trigger]').forEach((btn) => {
            btn.addEventListener('click', () => {
                activePesertaFormKey = btn.dataset.pesertaPickerTrigger;
                syncModalCheckboxDariForm(activePesertaFormKey);
                openModal('modal-pilih-peserta');
            });
        });

        const onPesertaCheckboxChange = (cb) => {
            if (!activePesertaFormKey) return;
            const row = cb.closest('[data-peserta-id]');
            if (!row) return;
            if (cb.checked) {
                tambahChipPeserta(activePesertaFormKey, cb.value, row.dataset.pesertaNama);
            } else {
                hapusChipPeserta(activePesertaFormKey, cb.value);
            }
        };

        document.querySelectorAll('[data-peserta-checkbox]').forEach((cb) => {
            cb.addEventListener('change', () => onPesertaCheckboxChange(cb));
        });

        document.querySelectorAll('.peserta-picker-row').forEach((row) => {
            row.addEventListener('click', (e) => {
                if (e.target.closest('[data-peserta-checkbox]')) return;
                const cb = row.querySelector('[data-peserta-checkbox]');
                if (!cb) return;
                cb.checked = !cb.checked;
                onPesertaCheckboxChange(cb);
            });
        });

        document.querySelectorAll('[data-peserta-chip-remove]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const chip = btn.closest('[data-peserta-chip]');
                const wrap = btn.closest('[data-peserta-selected-list]');
                const formKey = wrap?.dataset.pesertaSelectedList;
                const id = btn.dataset.pesertaChipRemove;
                chip?.remove();
                const modalCb = document.querySelector(`[data-peserta-checkbox][value="${id}"]`);
                if (modalCb) modalCb.checked = false;
                if (formKey) updatePesertaTriggerLabel(formKey);
            });
        });

        const cariPeserta = document.getElementById('modal-pilih-peserta-cari');
        cariPeserta?.addEventListener('input', () => {
            const term = cariPeserta.value.trim().toLowerCase();
            document.querySelectorAll('.peserta-picker-row').forEach((row) => {
                const match = !term || (row.dataset.search || '').includes(term);
                row.classList.toggle('hidden', !match);
            });
        });

        document.getElementById('form-asesmen-tambah')?.addEventListener('submit', (e) => {
            const count = document.querySelectorAll('[data-peserta-selected-list="tambah"] [data-peserta-chip]').length;
            if (count === 0) {
                e.preventDefault();
                activePesertaFormKey = 'tambah';
                openModal('modal-pilih-peserta');
            }
        });

        let activeAsesorFormKey = null;

        const updateAsesorTriggerLabel = (formKey) => {
            const list = document.querySelector(`[data-asesor-selected-list="${formKey}"]`);
            const label = document.querySelector(`[data-asesor-trigger-label="${formKey}"]`);
            const count = list?.querySelectorAll('[data-asesor-chip]').length ?? 0;
            if (label) {
                label.textContent = count === 0
                    ? 'Tambah Admin Penilai'
                    : `${count} admin dipilih`;
            }
            const countEl = document.querySelector('[data-asesor-picker-count]');
            if (countEl && activeAsesorFormKey === formKey) {
                countEl.textContent = `${count} admin dipilih`;
            }
        };

        const tambahChipAsesor = (formKey, id, nama, peran) => {
            const list = document.querySelector(`[data-asesor-selected-list="${formKey}"]`);
            if (!list || list.querySelector(`[data-asesor-chip="${id}"]`)) return;
            const chip = document.createElement('span');
            chip.className = 'asesmen-peserta-chip';
            chip.dataset.asesorChip = id;
            chip.innerHTML = `
                <input type="hidden" name="id_asesor[]" value="${id}">
                <span>${nama} <span class="font-normal text-on-surface-variant">(${peran})</span></span>
                <button type="button" data-asesor-chip-remove="${id}" aria-label="Hapus ${nama}">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            `;
            chip.querySelector('[data-asesor-chip-remove]')?.addEventListener('click', () => {
                chip.remove();
                const cb = document.querySelector(`[data-asesor-checkbox][value="${id}"]`);
                if (cb) cb.checked = false;
                updateAsesorTriggerLabel(formKey);
            });
            list.appendChild(chip);
            updateAsesorTriggerLabel(formKey);
        };

        const hapusChipAsesor = (formKey, id) => {
            document.querySelector(`[data-asesor-selected-list="${formKey}"] [data-asesor-chip="${id}"]`)?.remove();
            updateAsesorTriggerLabel(formKey);
        };

        const syncModalCheckboxAsesorDariForm = (formKey) => {
            const selected = new Set(
                [...document.querySelectorAll(`[data-asesor-selected-list="${formKey}"] [data-asesor-chip]`)]
                    .map((el) => el.dataset.asesorChip)
            );
            document.querySelectorAll('[data-asesor-checkbox]').forEach((cb) => {
                cb.checked = selected.has(cb.value);
            });
            updateAsesorTriggerLabel(formKey);
        };

        const setAsesorIds = (formKey, ids) => {
            const list = document.querySelector(`[data-asesor-selected-list="${formKey}"]`);
            if (!list) return;
            list.innerHTML = '';
            (ids || []).forEach((id) => {
                const row = document.querySelector(`[data-asesor-id="${id}"]`);
                const nama = row?.dataset.asesorNama ?? `Admin #${id}`;
                const peran = row?.dataset.asesorPeran ?? 'ADMIN';
                tambahChipAsesor(formKey, String(id), nama, peran);
            });
            syncModalCheckboxAsesorDariForm(formKey);
        };

        document.querySelectorAll('[data-asesor-picker-trigger]').forEach((btn) => {
            btn.addEventListener('click', () => {
                activeAsesorFormKey = btn.dataset.asesorPickerTrigger;
                syncModalCheckboxAsesorDariForm(activeAsesorFormKey);
                openModal('modal-pilih-asesor');
            });
        });

        const onAsesorCheckboxChange = (cb) => {
            if (!activeAsesorFormKey) return;
            const row = cb.closest('[data-asesor-id]');
            if (!row) return;
            if (cb.checked) {
                tambahChipAsesor(activeAsesorFormKey, cb.value, row.dataset.asesorNama, row.dataset.asesorPeran);
            } else {
                hapusChipAsesor(activeAsesorFormKey, cb.value);
            }
        };

        document.querySelectorAll('[data-asesor-checkbox]').forEach((cb) => {
            cb.addEventListener('change', () => onAsesorCheckboxChange(cb));
        });

        document.querySelectorAll('.asesor-picker-row').forEach((row) => {
            row.addEventListener('click', (e) => {
                if (e.target.closest('[data-asesor-checkbox]')) return;
                const cb = row.querySelector('[data-asesor-checkbox]');
                if (!cb) return;
                cb.checked = !cb.checked;
                onAsesorCheckboxChange(cb);
            });
        });

        document.querySelectorAll('[data-asesor-chip-remove]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const chip = btn.closest('[data-asesor-chip]');
                const wrap = btn.closest('[data-asesor-selected-list]');
                const formKey = wrap?.dataset.asesorSelectedList;
                const id = btn.dataset.asesorChipRemove;
                chip?.remove();
                const modalCb = document.querySelector(`[data-asesor-checkbox][value="${id}"]`);
                if (modalCb) modalCb.checked = false;
                if (formKey) updateAsesorTriggerLabel(formKey);
            });
        });

        const cariAsesor = document.getElementById('modal-pilih-asesor-cari');
        cariAsesor?.addEventListener('input', () => {
            const term = cariAsesor.value.trim().toLowerCase();
            document.querySelectorAll('.asesor-picker-row').forEach((row) => {
                const match = !term || (row.dataset.search || '').includes(term);
                row.classList.toggle('hidden', !match);
            });
        });

        window.bukaModalTambah = function (data) {
            const dialog = document.getElementById('modal-asesmen-tambah');
            const form = document.getElementById('form-asesmen-tambah');
            if (!dialog || !form || !data.sesiId) return;
            form.setAttribute('action', replaceId(routes.store, data.sesiId));
            form.querySelector('[name="id_sesi_asesmen"]').value = data.sesiId;
            const sesiLabel = data.sesiLabel || 'Sesi assessment';
            form.querySelector('[name="_sesi_label"]').value = sesiLabel;
            dialog.querySelector('[data-sesi-label]').textContent = sesiLabel;
            openModal('modal-asesmen-tambah');
        };

        window.bukaModalUbah = function (payload) {
            const dialog = document.getElementById('modal-asesmen-ubah');
            const form = document.getElementById('form-asesmen-ubah');
            if (!dialog || !form || !payload?.id) return;
            form.setAttribute('action', replaceId(routes.update, payload.id));
            const editId = form.querySelector('[name="_edit_id"]');
            if (editId) editId.value = payload.id;
            const label = payload.label || 'Asesmen';
            form.querySelector('[name="_asesmen_label"]').value = label;
            dialog.querySelector('[data-asesmen-label]').textContent = label;
            Object.entries(payload).forEach(([key, value]) => {
                if (key === 'id' || key === 'label' || key === 'id_asesor') return;
                setFormValue(form, key, value);
            });
            setAsesorIds('ubah', payload.id_asesor || []);
            openModal('modal-asesmen-ubah');
        };

        window.bukaModalTambahDariSesi = function (sesiId, sesiLabel) {
            bukaModalTambah({ sesiId: String(sesiId), sesiLabel });
        };

        @if ($errors->any())
            @if (old('_method') === 'PUT' && old('_edit_id'))
                bukaModalUbah({!! json_encode([
                    'id' => old('_edit_id'),
                    'label' => old('_asesmen_label', 'Asesmen'),
                    'id_peserta' => old('id_peserta'),
                    'id_versi_matriks' => old('id_versi_matriks'),
                    'tujuan' => old('tujuan'),
                    'metode_koleksi_bukti' => old('metode_koleksi_bukti'),
                    'tanpa_intray' => old('tanpa_intray'),
                    'id_template_prompt_ai' => old('id_template_prompt_ai'),
                    'id_asesor' => old('id_asesor', []),
                ]) !!});
            @elseif (old('id_sesi_asesmen'))
                bukaModalTambah({!! json_encode([
                    'sesiId' => old('id_sesi_asesmen'),
                    'sesiLabel' => old('_sesi_label', 'Sesi assessment'),
                ]) !!});
                setPesertaIds('tambah', {!! json_encode(old('id_peserta', [])) !!});
                setAsesorIds('tambah', {!! json_encode(old('id_asesor', [])) !!});
            @endif
        @endif

        @isset($bukaModalAsesmenSesi)
            bukaModalTambahDariSesi(@json($bukaModalAsesmenSesi['id']), @json($bukaModalAsesmenSesi['label']));
        @endisset
    })();
</script>
