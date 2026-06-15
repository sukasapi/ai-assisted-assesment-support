@props([
    'updateRoute' => null,
    'createModalId' => 'modal-tambah',
    'editModalId' => 'modal-ubah',
    'arrayFields' => [],
])

@php
    $editOldPayload = null;
    if ($errors->any() && old('_method') === 'PUT' && old('_edit_id')) {
        $editOldPayload = collect(old())
            ->except(['_method', '_token'])
            ->put('id', old('_edit_id'))
            ->all();
    }
@endphp

<script>
    (function () {
        const routes = { update: @json($updateRoute) };
        const createModalId = @json($createModalId);
        const editModalId = @json($editModalId);
        const arrayFields = @json($arrayFields);
        const replaceId = (url, id) => url ? url.replace('999999999', String(id)) : '';

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

        const isTruthyField = (value) => [true, 1, '1', 'true', 'on', 'yes'].includes(value);

        const setFormValue = (form, name, value) => {
            if (arrayFields.includes(name) && Array.isArray(value)) {
                form.querySelectorAll(`[name="${name}[]"]`).forEach((el) => {
                    el.checked = value.map(String).includes(el.value);
                });
                return;
            }
            const fields = form.querySelectorAll(`[name="${name}"]`);
            fields.forEach((field) => {
                if (field.type === 'checkbox') {
                    field.checked = isTruthyField(value);
                } else if (field.type === 'radio') {
                    field.checked = field.value === String(value ?? '');
                } else if (field.tagName === 'SELECT') {
                    field.value = value === null || value === undefined ? '' : String(value);
                } else {
                    field.value = value ?? '';
                }
            });
        };

        const prefillEdit = (dialog, payload) => {
            const form = dialog.querySelector('form');
            if (!form || !payload) return;
            if (routes.update && payload.id) {
                form.setAttribute('action', replaceId(routes.update, payload.id));
            }
            const editId = form.querySelector('[name="_edit_id"]');
            if (editId) editId.value = payload.id ?? '';
            Object.entries(payload).forEach(([key, value]) => {
                if (key === 'id') return;
                setFormValue(form, key, value);
            });
        };

        document.querySelectorAll('[data-open-modal]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const modalId = btn.dataset.openModal;
                const dialog = document.getElementById(modalId);
                if (!dialog) return;
                if (modalId === editModalId && btn.dataset.edit) {
                    prefillEdit(dialog, parseEditPayload(btn.dataset.edit));
                }
                dialog.showModal?.();
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.getElementById(btn.dataset.closeModal)?.close?.();
            });
        });

        document.querySelectorAll('dialog').forEach((dialog) => {
            dialog.addEventListener('click', (e) => {
                if (e.target === dialog) dialog.close();
            });
        });

        @if ($errors->any())
            @if ($editOldPayload)
                (function () {
                    const dialog = document.getElementById(editModalId);
                    if (dialog) {
                        prefillEdit(dialog, @json($editOldPayload));
                        dialog.showModal?.();
                    }
                })();
            @else
                document.getElementById(createModalId)?.showModal?.();
            @endif
        @endif
    })();
</script>
