/* Admin Panel JS */

// ── Sidebar toggle ────────────────────────────────────────
const sidebar  = document.getElementById('adminSidebar');
const mainWrap = document.getElementById('adminMain');
const toggle   = document.getElementById('sidebarToggle');

let overlay = document.createElement('div');
overlay.className = 'sidebar-overlay';
document.body.appendChild(overlay);

if (toggle) {
    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    });
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });
}

// ── Delete confirmation ───────────────────────────────────
document.addEventListener('click', e => {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;
    e.preventDefault();
    const msg = btn.dataset.confirm || 'Are you sure?';
    if (confirm(msg)) {
        const form = btn.closest('form');
        if (form) {
            // Inject a hidden input so the button name/value reaches the server
            const hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = btn.name;
            hidden.value = btn.value || '1';
            form.appendChild(hidden);
            form.submit();
        } else {
            window.location.href = btn.href;
        }
    }
});

// ── Media drag-drop ordering ──────────────────────────────
const mediaGrid = document.getElementById('mediaGrid');
if (mediaGrid && typeof Sortable !== 'undefined') {
    Sortable.create(mediaGrid, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: () => saveMediaOrder()
    });
}

function saveMediaOrder() {
    const items = document.querySelectorAll('#mediaGrid .media-item');
    const order = Array.from(items).map((el, i) => ({ id: el.dataset.id, order: i }));
    const projectId = document.getElementById('mediaGrid')?.dataset.project;

    fetch(BASE_URL + '/admin/ajax/reorder-media.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order, project_id: projectId, csrf_token: CSRF_TOKEN })
    }).catch(() => {});
}

// ── Category/Service drag-drop ordering ───────────────────
const sortableList = document.getElementById('sortableList');
if (sortableList && typeof Sortable !== 'undefined') {
    Sortable.create(sortableList, {
        animation: 150,
        handle: '.order-handle',
        ghostClass: 'sortable-ghost',
        onEnd: () => saveListOrder()
    });
}

function saveListOrder() {
    const items = document.querySelectorAll('#sortableList [data-id]');
    const order = Array.from(items).map((el, i) => ({ id: el.dataset.id, order: i }));
    const endpoint = document.getElementById('sortableList')?.dataset.endpoint;
    if (!endpoint) return;

    fetch(BASE_URL + '/admin/ajax/' + endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order, csrf_token: CSRF_TOKEN })
    }).catch(() => {});
}

// ── Cover image preview ───────────────────────────────────
const coverInput = document.getElementById('coverInput');
const coverPreview = document.getElementById('coverPreview');
if (coverInput && coverPreview) {
    coverInput.addEventListener('change', () => {
        const file = coverInput.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            coverPreview.src = e.target.result;
            coverPreview.style.display = 'block';
            const placeholder = document.getElementById('coverPlaceholder');
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
}

// ── Upload zone drag-over styling (generic, skips mediaFilesInput which has its own handler) ──
document.querySelectorAll('.upload-zone').forEach(zone => {
    const input = zone.querySelector('input[type=file]');
    if (!input || input.id === 'mediaFilesInput') return; // project-edit handles its own
    zone.addEventListener('click', () => input?.click());
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('dragover');
        if (input && e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            input.dispatchEvent(new Event('change'));
        }
    });
});

// ── Auto-generate slug from title ─────────────────────────
const titleInput = document.getElementById('titleInput');
const slugInput  = document.getElementById('slugInput');
if (titleInput && slugInput) {
    titleInput.addEventListener('input', () => {
        if (!slugInput.dataset.manual) {
            slugInput.value = titleInput.value
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    });
    slugInput.addEventListener('input', () => { slugInput.dataset.manual = '1'; });
}
