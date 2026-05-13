import './bootstrap';
import Swal from 'sweetalert2';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

window.Swal = Swal;

const swalTheme = {
    confirmButtonColor: '#27272a',
    cancelButtonColor: '#71717a',
};

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;

    return div.innerHTML;
}

function showServerAlerts() {
    const node = document.getElementById('app-alerts');
    if (!node?.textContent?.trim()) {
        return;
    }

    let data;
    try {
        data = JSON.parse(node.textContent);
    } catch {
        return;
    }

    const hasValidation =
        data.validation && typeof data.validation === 'object' && Object.keys(data.validation).length > 0;

    if (hasValidation) {
        const items = Object.values(data.validation)
            .flat()
            .map((msg) => `<li class="mt-1 text-left text-sm">${escapeHtml(String(msg))}</li>`)
            .join('');

        Swal.fire({
            icon: 'error',
            title: 'Periksa kembali',
            html: `<ul class="mx-auto max-w-md list-disc pl-5 text-zinc-700">${items}</ul>`,
            ...swalTheme,
        });

        return;
    }

    if (data.error?.text) {
        Swal.fire({
            icon: 'error',
            title: data.error.title ?? 'Terjadi kesalahan',
            text: data.error.text,
            ...swalTheme,
        });

        return;
    }

    if (data.success?.text) {
        Swal.fire({
            icon: 'success',
            title: data.success.title ?? 'Berhasil',
            text: data.success.text,
            ...swalTheme,
        });
    }
}

document.addEventListener('DOMContentLoaded', showServerAlerts);

function bindAiProcessingAlerts() {
    const forms = document.querySelectorAll('.js-ai-processing-form');
    if (!forms.length || !window.Swal) {
        return;
    }

    forms.forEach((form) => {
        form.addEventListener('submit', () => {
            const mode = form.dataset.aiMode === 'bulk' ? 'bulk' : 'incremental';
            const title =
                mode === 'bulk' ? 'Memproses AI bulk' : 'Memproses AI';
            const text =
                mode === 'bulk'
                    ? 'Mohon tunggu, payload sedang dianalisis sekarang.'
                    : 'Mohon tunggu, evidence sedang dianalisis sekarang.';

            window.Swal.fire({
                title,
                text,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    window.Swal.showLoading();
                },
                ...swalTheme,
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', bindAiProcessingAlerts);

function bindWysiwygEditors() {
    const areas = document.querySelectorAll(
        'textarea:not([data-no-wysiwyg]):not([disabled])'
    );
    if (!areas.length) {
        return;
    }

    areas.forEach((textarea) => {
        if (textarea.dataset.wysiwygBound === '1') {
            return;
        }
        if (textarea.closest('.swal2-container, .swal2-popup')) {
            return;
        }
        textarea.dataset.wysiwygBound = '1';
        const wasRequired = textarea.required;
        if (wasRequired) {
            // Hindari error HTML5 "invalid form control is not focusable"
            // karena textarea asli disembunyikan saat diganti Quill.
            textarea.required = false;
            textarea.removeAttribute('required');
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'mt-1 rounded-md border border-zinc-300 bg-white';
        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(wrapper, textarea.nextSibling);

        const editor = document.createElement('div');
        editor.style.minHeight = '10rem';
        wrapper.appendChild(editor);

        const quill = new Quill(editor, {
            theme: 'snow',
            placeholder: textarea.getAttribute('placeholder') || 'Tulis atau tempel isi di sini...',
            modules: {
                toolbar: [
                    [{ header: [1, 2, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['blockquote', 'code-block'],
                    ['clean'],
                ],
            },
        });

        const initial = textarea.value || '';
        if (initial.trim() !== '') {
            quill.setText(initial);
        }

        const richInput = document.createElement('input');
        richInput.type = 'hidden';
        richInput.name = `${textarea.name}_rich`;
        textarea.parentNode.insertBefore(richInput, textarea.nextSibling);

        const normalizedInput = document.createElement('input');
        normalizedInput.type = 'hidden';
        normalizedInput.name = `${textarea.name}_normalized`;
        textarea.parentNode.insertBefore(normalizedInput, richInput.nextSibling);

        const form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', (event) => {
                if (form.dataset.wysiwygSubmitting === '1') {
                    return;
                }

                const html = quill.root.innerHTML || '';
                const plainText = quill.getText().replace(/\u00a0/g, ' ').trimEnd();
                const normalized = normalizeEvidenceHtml(html, plainText);
                textarea.value = normalized;
                richInput.value = html;
                normalizedInput.value = normalized;

                if (wasRequired && !normalized.trim()) {
                    event.preventDefault();
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Input wajib diisi',
                        text: 'Kolom teks tidak boleh kosong.',
                        ...swalTheme,
                    }).then(() => {
                        quill.focus();
                    });
                    return;
                }

                if (textarea.dataset.normalizePreview !== '1') {
                    return;
                }

                event.preventDefault();
                const previewText =
                    normalized.length > 2000 ? `${normalized.slice(0, 2000)}\n...(dipotong)` : normalized;
                window.Swal.fire({
                    title: 'Preview normalisasi evidence',
                    html: `<div class="max-h-72 overflow-auto rounded bg-zinc-100 p-3 text-left text-xs whitespace-pre-wrap">${escapeHtml(previewText || '(kosong)')}</div>`,
                    showCancelButton: true,
                    confirmButtonText: 'Gunakan hasil normalisasi',
                    cancelButtonText: 'Batal',
                    ...swalTheme,
                }).then((res) => {
                    if (!res.isConfirmed) {
                        return;
                    }
                    form.dataset.wysiwygSubmitting = '1';
                    form.requestSubmit();
                });
            });
        }
    });
}

function normalizeEvidenceHtml(html, plainFallback = '') {
    const raw = String(html || '').trim();
    if (!raw) {
        return normalizePlain(plainFallback);
    }
    if (!raw.includes('<')) {
        return normalizePlain(raw);
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(raw, 'text/html');
    const tables = Array.from(doc.querySelectorAll('table'));
    tables.forEach((table) => {
        const rows = Array.from(table.querySelectorAll('tr')).map((tr) => {
            const cells = Array.from(tr.querySelectorAll('th,td'))
                .map((cell) => normalizePlain(cell.textContent || ''))
                .filter(Boolean);
            return cells.join(' | ');
        });
        const replacement = doc.createTextNode(`\n${rows.filter(Boolean).join('\n')}\n`);
        table.replaceWith(replacement);
    });

    const text = doc.body ? doc.body.textContent || '' : plainFallback;
    return normalizePlain(text);
}

function normalizePlain(text) {
    return String(text || '')
        .replace(/\u00a0/g, ' ')
        .replace(/\r\n?/g, '\n')
        .replace(/[ \t]+/g, ' ')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

document.addEventListener('DOMContentLoaded', bindWysiwygEditors);

/** Notifikasi dari kode lain (Livewire, fetch, dll.) */
window.notifySuccess = (text, title = 'Berhasil') =>
    Swal.fire({ icon: 'success', title, text, ...swalTheme });

window.notifyError = (text, title = 'Terjadi kesalahan') =>
    Swal.fire({ icon: 'error', title, text, ...swalTheme });
