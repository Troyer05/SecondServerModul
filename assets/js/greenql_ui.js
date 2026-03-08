(() => {
    const state = window.GreenQLState || { currentDb: '', currentTable: '', mode: 'ui' };
    state.mode = state.mode === 'query' ? 'query' : 'ui';

    const editor = document.getElementById('queryEditor');
    const runBtn = document.getElementById('runQueryBtn');
    const clearBtn = document.getElementById('clearQueryBtn');
    const messageStack = document.getElementById('messageStack');
    const resultBox = document.getElementById('resultBox');
    const schemaBox = document.getElementById('schemaBox');
    const previewBox = document.getElementById('previewBox');
    const managerBox = document.getElementById('managerBox');
    const runExamples = document.getElementById('runExamples');
    const statTables = document.getElementById('statTables');
    const statRows = document.getElementById('statRows');
    const activeModeLabel = document.getElementById('activeModeLabel');
    const modeButtons = Array.from(document.querySelectorAll('[data-mode-switch]'));
    const modeViews = Array.from(document.querySelectorAll('[data-mode-view]'));

    const post = async (payload) => {
        const form = new FormData();

        Object.entries(payload).forEach(([key, value]) => {
            if (value && typeof value === 'object' && !(value instanceof File)) {
                if (Array.isArray(value)) {
                    value.forEach((entry) => form.append(key + '[]', entry));
                } else {
                    Object.entries(value).forEach(([subKey, subValue]) => {
                        form.append(key + '[' + subKey + ']', subValue);
                    });
                }
            } else {
                form.append(key, value ?? '');
            }
        });

        const res = await fetch(window.location.pathname + window.location.search, {
            method: 'POST',
            body: form
        });

        return res.json();
    };

    const renderMessages = (messages) => {
        messageStack.innerHTML = '';

        if (!messages || !messages.length) return;

        messages.forEach((message) => {
            const div = document.createElement('div');
            div.className = 'message ' + (message.ok ? 'ok' : 'err');
            div.textContent = message.text;
            messageStack.appendChild(div);
        });
    };

    const applyMode = (mode) => {
        state.mode = mode === 'query' ? 'query' : 'ui';
        localStorage.setItem('greenql_mode', state.mode);

        modeButtons.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.modeSwitch === state.mode);
        });

        modeViews.forEach((view) => {
            view.classList.toggle('is-active', view.dataset.modeView === state.mode);
        });

        if (activeModeLabel) {
            activeModeLabel.textContent = state.mode === 'query' ? 'Query' : 'UI';
        }
    };

    const syncUrl = () => {
        const url = new URL(window.location.href);

        if (state.currentDb) url.searchParams.set('db', state.currentDb);
        else url.searchParams.delete('db');

        if (state.currentTable) url.searchParams.set('t', state.currentTable);
        else url.searchParams.delete('t');

        url.searchParams.set('mode', state.mode);
        window.history.replaceState({}, '', url);
    };

    const applySnapshot = (snapshot, ctx) => {
        if (!snapshot) return;

        previewBox.innerHTML = snapshot.preview_html || '<div class="empty-state">Keine Daten.</div>';
        schemaBox.innerHTML = snapshot.schema_html || '<div class="empty-state">Keine Struktur.</div>';
        managerBox.innerHTML = snapshot.manager_html || '<div class="empty-state">Keine Manager-Daten.</div>';
        statTables.textContent = snapshot.stats ? snapshot.stats.tables : '0';
        statRows.textContent = snapshot.stats ? snapshot.stats.rows : '0';

        if (ctx) {
            state.currentDb = ctx.db || '';
            state.currentTable = ctx.table || '';
        }

        syncUrl();
    };

    const runQuery = async () => {
        applyMode('query');
        runBtn.disabled = true;
        runBtn.textContent = 'Läuft...';

        try {
            const data = await post({
                greenql_action: 'run_query',
                query: editor.value,
                current_db: state.currentDb,
                current_table: state.currentTable
            });

            renderMessages(data.messages || []);
            resultBox.innerHTML = data.result_html || '<div class="empty-state">Kein Tabellenergebnis.</div>';
            applySnapshot(data.snapshot, data.ctx);
        } catch (e) {
            renderMessages([{ ok: false, text: 'Query konnte nicht ausgeführt werden.' }]);
        } finally {
            runBtn.disabled = false;
            runBtn.textContent = 'Ausführen';
        }
    };

    const submitDbForm = async (form) => {
        applyMode('ui');
        const input = form.querySelector('[name="db_name"]');
        const dbName = input ? input.value.trim() : '';

        const data = await post({
            greenql_action: 'save_db',
            db_name: dbName,
            current_db: state.currentDb,
            current_table: state.currentTable
        });

        renderMessages(data.messages || []);
        applySnapshot(data.snapshot, data.ctx);
    };

    const submitTableForm = async (form) => {
        applyMode('ui');
        const tableName = form.querySelector('[name="table_name"]')?.value.trim() || '';
        const tableCols = form.querySelector('[name="table_cols"]')?.value || '';

        const data = await post({
            greenql_action: 'save_table',
            db_name: state.currentDb,
            table_name: tableName,
            table_cols: tableCols,
            current_db: state.currentDb,
            current_table: state.currentTable
        });

        renderMessages(data.messages || []);
        applySnapshot(data.snapshot, data.ctx);
    };

    const submitEntryForm = async (form) => {
        applyMode('ui');
        const fields = {};

        form.querySelectorAll('textarea[name^="fields["]').forEach((el) => {
            const match = el.name.match(/^fields\[(.+)\]$/);
            if (match) fields[match[1]] = el.value;
        });

        const data = await post({
            greenql_action: 'save_entry',
            db_name: state.currentDb,
            table_name: state.currentTable,
            entry_id: form.querySelector('[name="entry_id"]')?.value || 0,
            fields,
            current_db: state.currentDb,
            current_table: state.currentTable
        });

        renderMessages(data.messages || []);
        applySnapshot(data.snapshot, data.ctx);
    };

    const deleteDb = async () => {
        if (!state.currentDb) return;
        if (!confirm('Base wirklich löschen? Alle Tabellen und Einträge darin werden entfernt.')) return;

        applyMode('ui');

        const data = await post({
            greenql_action: 'delete_db',
            db_name: state.currentDb,
            current_db: state.currentDb,
            current_table: state.currentTable
        });

        renderMessages(data.messages || []);
        applySnapshot(data.snapshot, data.ctx);
        resultBox.innerHTML = '<div class="empty-state">Base gelöscht. Query oder UI verwenden, um neu zu starten.</div>';
    };

    const deleteTable = async () => {
        if (!state.currentDb || !state.currentTable) return;
        if (!confirm('Tabelle wirklich löschen?')) return;

        applyMode('ui');

        const data = await post({
            greenql_action: 'delete_table',
            db_name: state.currentDb,
            table_name: state.currentTable,
            current_db: state.currentDb,
            current_table: state.currentTable
        });

        renderMessages(data.messages || []);
        applySnapshot(data.snapshot, data.ctx);
    };

    const loadEntryForm = async (entryId = 0) => {
        applyMode('ui');

        const data = await post({
            greenql_action: 'load_entry_form',
            db_name: state.currentDb,
            table_name: state.currentTable,
            entry_id: entryId,
            current_db: state.currentDb,
            current_table: state.currentTable
        });

        if (data.entry_form_html) {
            const managerFormHost = managerBox.querySelector('.manager-form:last-child');
            if (managerFormHost) {
                managerFormHost.innerHTML = data.entry_form_html;
            }
        }
    };

    const deleteEntry = async (entryId) => {
        if (!entryId) return;
        if (!confirm('Entry #' + entryId + ' wirklich löschen?')) return;

        applyMode('ui');

        const data = await post({
            greenql_action: 'delete_entry',
            db_name: state.currentDb,
            table_name: state.currentTable,
            entry_id: entryId,
            current_db: state.currentDb,
            current_table: state.currentTable
        });

        renderMessages(data.messages || []);
        applySnapshot(data.snapshot, data.ctx);
    };

    runBtn?.addEventListener('click', runQuery);

    clearBtn?.addEventListener('click', () => {
        editor.value = '';
        editor.focus();
    });

    editor?.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            runQuery();
        }
    });

    document.querySelectorAll('.ghost-chip').forEach((chip) => {
        chip.addEventListener('click', () => {
            applyMode('query');
            editor.value = chip.dataset.query || '';
            editor.focus();
        });
    });

    runExamples?.addEventListener('click', () => {
        applyMode('query');
        const target = state.currentTable || 'users';
        editor.value = [
            'SHOW BASES;',
            state.currentDb ? 'SHOW TABLES IN ' + state.currentDb + ';' : 'GROW BASE demo;',
            'PICK * FROM ' + target + ' LIMIT 20;'
        ].join('\n');
        editor.focus();
    });

    modeButtons.forEach((btn) => {
        btn.addEventListener('click', () => applyMode(btn.dataset.modeSwitch || 'ui'));
    });

    managerBox?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;

        try {
            if (form.id === 'dbManagerForm') await submitDbForm(form);
            if (form.id === 'tableManagerForm') await submitTableForm(form);
            if (form.id === 'entryManagerForm') await submitEntryForm(form);
        } catch (err) {
            renderMessages([{ ok: false, text: 'Formular konnte nicht gespeichert werden.' }]);
        }
    });

    managerBox?.addEventListener('click', async (e) => {
        const rootDb = e.target.closest('[data-root-db]');
        const openTable = e.target.closest('[data-open-table]');
        const editEntry = e.target.closest('[data-entry-edit]');
        const deleteEntryBtn = e.target.closest('[data-entry-delete]');

        if (rootDb) {
            applyMode('ui');
            state.currentDb = rootDb.dataset.rootDb || '';
            state.currentTable = '';
            syncUrl();
            window.location.href = window.location.href;
            return;
        }

        if (openTable) {
            applyMode('ui');
            const url = new URL(window.location.href);
            if (state.currentDb) url.searchParams.set('db', state.currentDb);
            url.searchParams.set('t', openTable.dataset.openTable || '');
            url.searchParams.set('mode', 'ui');
            window.location.href = url;
            return;
        }

        if (e.target.id === 'deleteDbBtn') {
            await deleteDb();
            return;
        }

        if (e.target.id === 'deleteTableBtn') {
            await deleteTable();
            return;
        }

        if (e.target.id === 'newEntryBtn') {
            await loadEntryForm(0);
            return;
        }

        if (editEntry) {
            await loadEntryForm(editEntry.dataset.entryEdit || 0);
            return;
        }

        if (deleteEntryBtn) {
            await deleteEntry(deleteEntryBtn.dataset.entryDelete || 0);
        }
    });

    previewBox?.addEventListener('click', async (e) => {
        const editEntry = e.target.closest('[data-entry-edit]');
        const deleteEntryBtn = e.target.closest('[data-entry-delete]');

        if (editEntry) {
            await loadEntryForm(editEntry.dataset.entryEdit || 0);
        }

        if (deleteEntryBtn) {
            await deleteEntry(deleteEntryBtn.dataset.entryDelete || 0);
        }
    });

    const urlMode = new URL(window.location.href).searchParams.get('mode');
    const storedMode = localStorage.getItem('greenql_mode');
    applyMode(urlMode || storedMode || state.mode || 'ui');
    syncUrl();
})();
