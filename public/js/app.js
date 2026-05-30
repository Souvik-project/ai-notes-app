import '/js/bootstrap.js';

const state = {
    page: 1,
    lastPage: 1,
    limit: 8,
    semanticMode: false,
};

const elements = {
    form: document.querySelector('#note-form'),
    noteId: document.querySelector('#note-id'),
    title: document.querySelector('#title'),
    tags: document.querySelector('#tags'),
    content: document.querySelector('#content'),
    formError: document.querySelector('#form-error'),
    notes: document.querySelector('#notes'),
    status: document.querySelector('#status'),
    search: document.querySelector('#search'),
    semanticSearch: document.querySelector('#semantic-search'),
    resetForm: document.querySelector('#reset-form'),
    prevPage: document.querySelector('#prev-page'),
    nextPage: document.querySelector('#next-page'),
    pageLabel: document.querySelector('#page-label'),
};

const headers = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
};

async function request(url, options = {}) {
    const response = await fetch(url, {
        headers,
        ...options,
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message = payload.message || Object.values(payload.errors || {})[0]?.[0] || 'Request failed';
        throw new Error(message);
    }

    return payload;
}

async function loadNotes() {
    elements.status.textContent = 'Loading notes...';
    elements.notes.innerHTML = '';

    try {
        let payload;
        const query = elements.search.value.trim();

        if (state.semanticMode && query.length > 1) {
            payload = await request(`/api/notes/search?q=${encodeURIComponent(query)}&limit=${state.limit}`);
            renderNotes(payload.data.map((item) => ({ ...item.note, score: item.score })));
            state.lastPage = 1;
        } else {
            payload = await request(`/api/notes?page=${state.page}&limit=${state.limit}&search=${encodeURIComponent(query)}`);
            renderNotes(payload.data);
            state.lastPage = payload.meta.pagination.last_page;
        }

        elements.status.textContent = payload.data.length ? '' : 'No notes found.';
        updatePager();
    } catch (error) {
        elements.status.textContent = error.message;
    }
}

function renderNotes(notes) {
    elements.notes.innerHTML = notes.map((note) => `
        <article class="note-card">
            <div>
                <div class="note-head">
                    <h2>${escapeHtml(note.title)}</h2>
                    ${note.score ? `<span>${Math.round(note.score * 100)}%</span>` : ''}
                </div>
                <p>${escapeHtml(note.content)}</p>
            </div>
            <div class="tag-row">
                ${(note.tags || []).map((tag) => `<span>${escapeHtml(tag)}</span>`).join('')}
            </div>
            ${note.summary ? `<div class="summary">${escapeHtml(note.summary)}</div>` : ''}
            <div class="card-actions">
                <button type="button" data-action="edit" data-id="${note.id}">Edit</button>
                <button type="button" data-action="summary" data-id="${note.id}">Summarize</button>
                <button type="button" data-action="delete" data-id="${note.id}">Delete</button>
            </div>
        </article>
    `).join('');
}

function updatePager() {
    elements.pageLabel.textContent = state.semanticMode ? 'Semantic results' : `Page ${state.page} of ${state.lastPage}`;
    elements.prevPage.disabled = state.semanticMode || state.page <= 1;
    elements.nextPage.disabled = state.semanticMode || state.page >= state.lastPage;
}

function resetForm() {
    elements.noteId.value = '';
    elements.form.reset();
    elements.formError.textContent = '';
    elements.title.focus();
}

function formPayload() {
    return {
        title: elements.title.value.trim(),
        content: elements.content.value.trim(),
        tags: elements.tags.value.split(',').map((tag) => tag.trim()).filter(Boolean),
    };
}

async function submitNote(event) {
    event.preventDefault();
    elements.formError.textContent = '';

    const id = elements.noteId.value;
    const method = id ? 'PUT' : 'POST';
    const url = id ? `/api/notes/${id}` : '/api/notes';

    try {
        await request(url, {
            method,
            body: JSON.stringify(formPayload()),
        });
        resetForm();
        state.semanticMode = false;
        await loadNotes();
    } catch (error) {
        elements.formError.textContent = error.message;
    }
}

async function handleCardAction(event) {
    const button = event.target.closest('button[data-action]');
    if (!button) return;

    const { action, id } = button.dataset;

    if (action === 'edit') {
        const payload = await request(`/api/notes/${id}`);
        elements.noteId.value = payload.data.id;
        elements.title.value = payload.data.title;
        elements.content.value = payload.data.content;
        elements.tags.value = (payload.data.tags || []).join(', ');
        elements.title.focus();
    }

    if (action === 'summary') {
        await request(`/api/notes/${id}/summary`, { method: 'POST', body: '{}' });
        await loadNotes();
    }

    if (action === 'delete' && window.confirm('Delete this note?')) {
        await request(`/api/notes/${id}`, { method: 'DELETE', body: '{}' });
        await loadNotes();
    }
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

elements.form?.addEventListener('submit', submitNote);
elements.resetForm?.addEventListener('click', resetForm);
elements.notes?.addEventListener('click', handleCardAction);
elements.semanticSearch?.addEventListener('click', () => {
    state.semanticMode = true;
    state.page = 1;
    loadNotes();
});
elements.search?.addEventListener('input', () => {
    state.semanticMode = false;
    state.page = 1;
    loadNotes();
});
elements.prevPage?.addEventListener('click', () => {
    state.page = Math.max(1, state.page - 1);
    loadNotes();
});
elements.nextPage?.addEventListener('click', () => {
    state.page = Math.min(state.lastPage, state.page + 1);
    loadNotes();
});

if (elements.notes) {
    loadNotes();
}
