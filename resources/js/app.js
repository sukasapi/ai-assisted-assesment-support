import './bootstrap';
import Swal from 'sweetalert2';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

window.Swal = Swal;

const swalTheme = {
    confirmButtonColor: '#0058be',
    cancelButtonColor: '#727785',
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

function initSidebarCollapse() {
    const root = document.documentElement;
    const toggle = document.getElementById('sidebar-collapse-toggle');
    if (!toggle) {
        return;
    }

    const icon = document.getElementById('sidebar-collapse-icon');

    const apply = (collapsed) => {
        if (collapsed) {
            root.dataset.sidebarCollapsed = 'true';
            toggle.setAttribute('aria-expanded', 'false');
            toggle.title = 'Perluas menu';
        } else {
            delete root.dataset.sidebarCollapsed;
            toggle.setAttribute('aria-expanded', 'true');
            toggle.title = 'Ciutkan menu';
        }

        if (icon) {
            icon.textContent = collapsed ? 'chevron_right' : 'chevron_left';
        }
    };

    apply(root.dataset.sidebarCollapsed === 'true');

    toggle.addEventListener('click', () => {
        const collapsed = root.dataset.sidebarCollapsed !== 'true';
        apply(collapsed);
        try {
            window.localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0');
        } catch {
            // Abaikan jika localStorage tidak tersedia.
        }
    });
}

document.addEventListener('DOMContentLoaded', initSidebarCollapse);

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
                const isBulkMuatan = textarea.name === 'teks_muatan';
                const normalized = isBulkMuatan
                    ? normalizeBulkHtml(html, plainText)
                    : normalizeEvidenceHtml(html, plainText);
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
                    title: isBulkMuatan
                        ? 'Preview normalisasi teks muatan'
                        : 'Preview normalisasi evidence',
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

function normalizeBulkHtml(html, plainFallback = '') {
    const raw = String(html || '').trim();
    if (!raw) {
        return normalizePlainBulk(plainFallback);
    }
    if (!raw.includes('<')) {
        return normalizePlainBulk(raw);
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(raw, 'text/html');
    const tables = Array.from(doc.querySelectorAll('table'));
    tables.forEach((table) => {
        const rows = Array.from(table.querySelectorAll('tr')).map((tr) => {
            const cells = Array.from(tr.querySelectorAll('th,td'))
                .map((cell) => normalizePlainBulk(cell.textContent || ''))
                .filter(Boolean);
            return cells.join(' | ');
        });
        const replacement = doc.createTextNode(`\n${rows.filter(Boolean).join('\n')}\n`);
        table.replaceWith(replacement);
    });

    const text = doc.body ? domNodeToPlainBulk(doc.body) : plainFallback;
    return normalizePlainBulk(text.trim());
}

const bulkBlockTags = new Set([
    'p',
    'div',
    'li',
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
    'blockquote',
    'pre',
    'tr',
    'section',
    'article',
]);

function domNodeToPlainBulk(node) {
    if (node.nodeType === Node.TEXT_NODE) {
        return String(node.textContent || '').replace(/\u00a0/g, ' ');
    }
    if (node.nodeType !== Node.ELEMENT_NODE) {
        return '';
    }
    const tag = node.tagName.toLowerCase();
    if (tag === 'br') {
        return '\n';
    }
    let inner = '';
    node.childNodes.forEach((child) => {
        inner += domNodeToPlainBulk(child);
    });
    if (bulkBlockTags.has(tag)) {
        const trimmed = inner.replace(/\n+$/u, '');
        return trimmed === '' ? '\n\n' : `${trimmed}\n\n`;
    }
    return inner;
}

/** Tanda baca bermasalah + batas paragraf (\n\n); spasi dalam baris tidak diubah. */
function normalizePlainBulk(text) {
    return normalizeParagraphBreaksBulk(
        String(text || '')
            .replace(/\u00a0/g, ' ')
            .replace(/[\u200B-\u200D\uFEFF\u00AD]/g, '')
            .replace(/[\u201C\u201D\u201E\u00AB\u00BB]/g, '"')
            .replace(/[\u2018\u2019\u201A\u2032]/g, "'")
            .replace(/[\u2013\u2014\u2212]/g, '-')
            .replace(/\u2026/g, '...')
    );
}

function normalizeParagraphBreaksBulk(text) {
    return String(text || '').replace(/\r\n?/g, '\n').replace(/\n{3,}/g, '\n\n');
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

/** Payload bulk: textarea polos (tanpa Quill) — normalisasi + preview sebelum simpan. */
function bindBulkPayloadPlainForms() {
    document.querySelectorAll('form').forEach((form) => {
        const textarea = form.querySelector('textarea[name="teks_muatan"][data-no-wysiwyg]');
        if (!textarea || textarea.dataset.bulkPlainBound === '1') {
            return;
        }
        textarea.dataset.bulkPlainBound = '1';

        form.addEventListener('submit', (event) => {
            if (form.dataset.bulkPlainSubmitting === '1') {
                return;
            }

            const normalized = normalizePlainBulk(textarea.value || '');
            textarea.value = normalized;

            if (textarea.dataset.normalizePreview !== '1') {
                return;
            }

            event.preventDefault();
            const previewText =
                normalized.length > 2000
                    ? `${normalized.slice(0, 2000)}\n...(dipotong)`
                    : normalized;
            window.Swal.fire({
                title: 'Preview normalisasi teks muatan',
                html: `<pre class="text-left text-xs whitespace-pre-wrap max-h-64 overflow-auto">${escapeHtml(previewText)}</pre>`,
                showCancelButton: true,
                confirmButtonText: 'Simpan payload',
                cancelButtonText: 'Ubah lagi',
                ...swalTheme,
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                form.dataset.bulkPlainSubmitting = '1';
                form.requestSubmit();
            });
        });
    });
}

const payloadStatusBadgeClass = {
    belum: 'border-outline-variant/40 bg-surface-container text-on-surface-variant',
    antrian: 'border-primary/30 bg-primary-fixed/40 text-primary',
    memproses: 'border-primary/30 bg-primary-fixed/40 text-primary',
    berhasil: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    gagal: 'border-error/30 bg-error-container/40 text-on-error-container',
};

function applyPayloadStatusToCard(card, data) {
    if (!card || !data) {
        return;
    }

    card.dataset.status = data.status;

    const badge = card.querySelector('.js-payload-status-badge');
    const label = card.querySelector('.js-payload-status-label');
    if (badge && label) {
        badge.dataset.status = data.status;
        label.textContent = data.label || data.status;
        Object.values(payloadStatusBadgeClass).forEach((cls) => {
            cls.split(' ').forEach((c) => badge.classList.remove(c));
        });
        (payloadStatusBadgeClass[data.status] || payloadStatusBadgeClass.belum)
            .split(' ')
            .forEach((c) => badge.classList.add(c));

        let dot = badge.querySelector('.js-payload-status-dot');
        if (data.sedang_berjalan) {
            if (!dot) {
                dot = document.createElement('span');
                dot.className =
                    'js-payload-status-dot inline-block size-2 animate-pulse rounded-full bg-current';
                badge.prepend(dot);
            }
        } else if (dot) {
            dot.remove();
        }
    }

    const pesan = card.querySelector('.js-payload-status-pesan');
    if (pesan) {
        if (data.pesan) {
            pesan.textContent = data.pesan;
            pesan.classList.remove('hidden');
        } else {
            pesan.textContent = '';
            pesan.classList.add('hidden');
        }
    }

    const analyzeBtn = card.querySelector('.js-payload-analyze-btn');
    if (analyzeBtn) {
        analyzeBtn.disabled = Boolean(data.sedang_berjalan);
    }

    const hasil = card.querySelector('.js-payload-hasil-ai');
    const meta = card.querySelector('.js-payload-hasil-meta');
    if (hasil && data.status === 'berhasil' && data.jumlah_usulan > 0) {
        hasil.classList.remove('hidden');
        if (meta) {
            const waktu = data.diproses_pada
                ? new Date(data.diproses_pada).toLocaleString('id-ID')
                : '—';
            meta.textContent = `Diproses ${waktu} · ${data.jumlah_usulan} usulan`;
        }
    }
}

function renderPayloadDetailBody(data) {
    const usulanRows = (data.usulan || [])
        .map(
            (u) => `
        <tr class="align-top border-t border-outline-variant/20">
            <td class="px-2 py-2 font-mono text-xs font-semibold">${escapeHtml(u.kode_kompetensi || '?')}</td>
            <td class="px-2 py-2 text-center">${escapeHtml(String(u.tingkat ?? '—'))}</td>
            <td class="px-2 py-2 text-xs">${escapeHtml(u.ringkasan || '—')}</td>
            <td class="px-2 py-2 text-xs italic text-on-surface-variant">${escapeHtml(u.kutipan || '—')}</td>
        </tr>`
        )
        .join('');

    const usulanTable =
        usulanRows === ''
            ? '<p class="text-sm text-on-surface-variant">Belum ada usulan AI.</p>'
            : `<div class="overflow-x-auto rounded-lg border border-outline-variant/30">
            <table class="min-w-[640px] w-full text-left text-sm">
                <thead class="bg-surface-container-low text-xs font-semibold uppercase text-on-surface-variant">
                    <tr>
                        <th class="px-2 py-2">Kompetensi</th>
                        <th class="px-2 py-2 text-center">Lvl</th>
                        <th class="px-2 py-2">Ringkasan</th>
                        <th class="px-2 py-2">Kutipan</th>
                    </tr>
                </thead>
                <tbody>${usulanRows}</tbody>
            </table>
        </div>`;

    return `
        <div class="space-y-4 text-sm">
            <div class="flex flex-wrap gap-2">
                <span class="rounded-full border px-2.5 py-1 text-xs font-bold ${payloadStatusBadgeClass[data.status] || payloadStatusBadgeClass.belum}">${escapeHtml(data.label || '')}</span>
                ${data.pengunggah ? `<span class="text-xs text-on-surface-variant">Diunggah oleh ${escapeHtml(data.pengunggah)}</span>` : ''}
            </div>
            ${data.pesan ? `<p class="rounded-lg border border-error/20 bg-error-container/30 px-3 py-2 text-xs text-on-error-container">${escapeHtml(data.pesan)}</p>` : ''}
            <dl class="grid gap-2 text-xs text-on-surface-variant sm:grid-cols-2">
                <div><dt class="font-semibold text-on-surface">Alat</dt><dd>${escapeHtml((data.alat?.kode || '') + ' — ' + (data.alat?.nama || ''))}</dd></div>
                <div><dt class="font-semibold text-on-surface">Panjang teks</dt><dd>${escapeHtml(String(data.panjang_teks || 0))} karakter</dd></div>
                <div><dt class="font-semibold text-on-surface">Dibuat</dt><dd>${data.dibuat_pada ? escapeHtml(new Date(data.dibuat_pada).toLocaleString('id-ID')) : '—'}</dd></div>
                <div><dt class="font-semibold text-on-surface">Diproses AI</dt><dd>${data.diproses_pada ? escapeHtml(new Date(data.diproses_pada).toLocaleString('id-ID')) : '—'}</dd></div>
            </dl>
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Teks muatan lengkap</p>
                <pre class="max-h-48 overflow-auto whitespace-pre-wrap rounded-xl border border-outline-variant/40 bg-surface-container-low p-4 text-xs leading-relaxed">${escapeHtml(data.teks_muatan || '')}</pre>
            </div>
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Usulan AI (${data.jumlah_usulan || 0})</p>
                ${usulanTable}
            </div>
        </div>`;
}

function initPayloadDetailModal() {
    const dialog = document.getElementById('payload-detail-dialog');
    const body = document.getElementById('payload-detail-body');
    const title = document.getElementById('payload-detail-title');
    if (!dialog || !body) {
        return;
    }

    document.querySelectorAll('[data-payload-detail-close]').forEach((btn) => {
        btn.addEventListener('click', () => dialog.close());
    });

    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) {
            dialog.close();
        }
    });

    document.querySelectorAll('.js-payload-detail-btn').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const url = btn.dataset.payloadDetailUrl;
            if (!url) {
                return;
            }
            title.textContent = 'Detail payload';
            body.innerHTML = '<p class="text-sm text-on-surface-variant">Memuat…</p>';
            dialog.showModal();
            try {
                const res = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) {
                    throw new Error('Gagal memuat detail payload.');
                }
                const data = await res.json();
                title.textContent = `#${data.id} · ${data.alat?.kode || 'Payload'}`;
                body.innerHTML = renderPayloadDetailBody(data);
            } catch (err) {
                body.innerHTML = `<p class="text-sm text-error">${escapeHtml(err.message || 'Terjadi kesalahan.')}</p>`;
            }
        });
    });
}

function initPayloadBulkStatusPolling() {
    const root = document.querySelector('[data-payload-status-url]');
    if (!root) {
        return;
    }

    const url = root.dataset.payloadStatusUrl;
    let timer = null;

    const poll = async () => {
        try {
            const res = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) {
                return;
            }
            const json = await res.json();
            Object.entries(json.payloads || {}).forEach(([id, data]) => {
                const card = root.querySelector(`.js-payload-card[data-payload-id="${id}"]`);
                applyPayloadStatusToCard(card, data);
            });

            if (json.ada_yang_berjalan) {
                timer = window.setTimeout(poll, 3000);
            } else if (timer) {
                window.clearTimeout(timer);
                timer = null;
            }
        } catch {
            timer = window.setTimeout(poll, 5000);
        }
    };

    poll();

    document.querySelectorAll('.js-payload-bulk-form').forEach((form) => {
        form.addEventListener('submit', () => {
            const id = form.dataset.payloadId;
            const card = root.querySelector(`.js-payload-card[data-payload-id="${id}"]`);
            if (card) {
                applyPayloadStatusToCard(card, {
                    status: 'antrian',
                    label: 'Dalam antrian',
                    sedang_berjalan: true,
                    pesan: null,
                });
            }
            window.setTimeout(poll, 1500);
        });
    });
}

function initPayloadDeleteConfirm() {
    document.querySelectorAll('.js-payload-delete-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.payloadDeleteConfirmed === '1') {
                return;
            }
            event.preventDefault();
            window.Swal.fire({
                title: 'Hapus payload ini?',
                text: 'Teks muatan dan hasil analisis AI yang belum disetujui sebagai mapping perilaku kunci akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#b3261e',
                ...swalTheme,
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                form.dataset.payloadDeleteConfirmed = '1';
                form.requestSubmit();
            });
        });
    });
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function initPkSahkanAjax() {
    document.querySelectorAll('.js-pk-sahkan-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const url = form.action;
            const btn = form.querySelector('button[type="submit"]');
            if (!url || !btn) {
                return;
            }

            const token = getCsrfToken();
            btn.disabled = true;

            try {
                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                let data = {};
                try {
                    data = await res.json();
                } catch {
                    // abaikan
                }

                if (res.status === 419) {
                    window.notifyError?.('Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.');
                    btn.disabled = false;

                    return;
                }

                if (!res.ok || data.success === false) {
                    const msg =
                        data.message ||
                        (Array.isArray(data.errors?.perilaku_kunci)
                            ? data.errors.perilaku_kunci[0]
                            : null) ||
                        'Gagal menyahkan mapping.';
                    window.notifyError?.(msg);
                    btn.disabled = false;

                    return;
                }

                const row = form.closest('tr');
                if (row) {
                    row.classList.remove('opacity-95');
                }

                const wrap = document.createElement('p');
                wrap.className = 'mt-2 text-[10px] font-medium text-emerald-700';
                wrap.textContent = '✓ Resmi';
                form.replaceWith(wrap);

                const badge = row?.querySelector('[data-pk-status-badge]');
                if (badge && data.status_label) {
                    badge.textContent = data.status_label;
                    badge.className = `inline-block rounded-full border px-2.5 py-1 text-[10px] font-bold ${data.status_kelas || ''}`;
                }

                window.notifySuccess?.(data.message || 'Mapping disimpan.');
            } catch {
                window.notifyError?.('Tidak dapat menghubungi server. Coba lagi.');
                btn.disabled = false;
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', bindBulkPayloadPlainForms);
document.addEventListener('DOMContentLoaded', initPayloadDetailModal);
document.addEventListener('DOMContentLoaded', initPayloadBulkStatusPolling);
document.addEventListener('DOMContentLoaded', initPayloadDeleteConfirm);
document.addEventListener('DOMContentLoaded', initPkSahkanAjax);

/** Notifikasi dari kode lain (Livewire, fetch, dll.) */
window.notifySuccess = (text, title = 'Berhasil') =>
    Swal.fire({ icon: 'success', title, text, ...swalTheme });

window.notifyError = (text, title = 'Terjadi kesalahan') =>
    Swal.fire({ icon: 'error', title, text, ...swalTheme });
