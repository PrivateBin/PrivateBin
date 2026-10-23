/**
 * PrivateBin "splitview" template
 *
 * Only what HTML and CSS cannot do lives here. Layout, hover/focus/drag states,
 * the "has files" look and all animations are in css/splitview/splitview.css;
 * a click anywhere in the file box opens the file dialog through a <label> in
 * tpl/splitview.php.
 *
 * Cumulative file attachments: PrivateBin replaces its attachment list each
 * time files are dropped, pasted or picked. This script keeps its own list
 * instead, shows it with a remove button (x) per file, and hands the merged
 * list back to PrivateBin through the normal file input, so reading,
 * encryption and upload are untouched.
 *
 * External file, because the default content security policy forbids inline
 * scripts. Needs no library, only the global `PrivateBin` of js/privatebin.js
 * (optional: without it only the wait for pending reads in remove() is skipped).
 */
(function () {
    'use strict';

    const byId = id => document.getElementById(id);

    /* ---- cumulative file attachments ----------------------------------- */

    const input = byId('file');
    const attach = byId('attach');
    const box = byId('splitdropbox');
    const listEl = byId('splitfilelist');

    // file upload disabled on the server: nothing to do
    if (!input || !attach || !box || !listEl || typeof DataTransfer === 'undefined') {
        return;
    }

    // translated by the template, English only as a fallback
    const text = {
        remove: box.dataset.removeLabel || 'Remove',
        empty: box.dataset.emptyLabel || 'This file is empty (0 bytes).',
        unreadable: box.dataset.unreadableLabel || 'Could not read: %s'
    };

    let list = [];            // File[] - the single source of truth
    const rows = new Map();   // file key -> <li>, so a change only touches what changed
    const thumbs = new Map(); // file key -> blob URL of an image thumbnail
    let syncing = false;      // true while our own change event is dispatched
    let notice = null;        // the "could not read" message, if shown

    const keyOf = file => [file.name, file.size, file.lastModified].join('|');

    const formatSize = bytes => {
        if (bytes < 1024) {
            return bytes + ' B';
        }
        if (bytes < 1048576) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }
        return (bytes / 1048576).toFixed(1) + ' MB';
    };

    const warn = message => {
        if (window.console && console.warn) {
            console.warn('splitview: ' + message);
        }
    };

    const describe = error =>
        error ? (error.name || 'Error') + (error.message ? ': ' + error.message : '') : 'unknown error';

    // PrivateBin only accepts new files while the attach area is shown,
    // i.e. when creating a document and not when viewing one
    const canAttach = () => !attach.classList.contains('hidden');

    // write our list into the real <input>, which is what PrivateBin reads
    const writeInput = () => {
        const transfer = new DataTransfer();
        list.forEach(file => transfer.items.add(file));
        input.files = transfer.files;
    };

    // write the list into the input and make PrivateBin read it again
    const sync = () => {
        writeInput();
        syncing = true;
        try {
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } finally {
            syncing = false;
        }
        render();
    };

    const add = files => {
        const known = new Set(list.map(keyOf));
        let added = false;
        Array.from(files).forEach(file => {
            const key = keyOf(file);
            if (!known.has(key)) {
                known.add(key);
                list.push(file);
                added = true;
            }
        });
        return added;
    };

    /*
     * True once PrivateBin has finished reading every file of the current
     * list. Its readers store the result by position, so changing the order of
     * the list (removing a file) while files are still being read could mix up
     * file contents. Adding at the end is safe.
     */
    const readersIdle = () => {
        try {
            const data = window.PrivateBin.AttachmentViewer.getAttachmentsData();
            return data.length === list.length && data.every(Boolean);
        } catch (e) {
            return true; // PrivateBin internals not reachable: do not block
        }
    };

    const remove = (file, attempt = 0) => {
        if (attempt < 100 && !readersIdle()) {
            // still reading: try again shortly (gives up after about 6 seconds)
            window.setTimeout(() => remove(file, attempt + 1), 60);
            return;
        }
        const index = list.indexOf(file);
        if (index < 0) {
            return;
        }
        list.splice(index, 1);
        sync();
    };

    const clearNotice = () => {
        if (notice) {
            notice.remove();
            notice = null;
        }
    };

    const clearAll = () => {
        list = [];
        clearNotice();
        render();
    };

    const createRow = (file, key) => {
        const row = document.createElement('li');
        row.className = 'split-file';

        let thumb;
        if (/^image\/(png|jpe?g|gif|webp|bmp|avif)$/i.test(file.type)) {
            const url = URL.createObjectURL(file);
            thumbs.set(key, url);
            thumb = document.createElement('img');
            thumb.alt = '';
            thumb.src = url;
            thumb.className = 'split-file-thumb';
        } else {
            const dot = file.name.lastIndexOf('.');
            thumb = document.createElement('span');
            thumb.className = 'split-file-thumb split-file-ext';
            thumb.textContent = dot > 0 ? file.name.slice(dot + 1, dot + 5).toUpperCase() : 'FILE';
        }

        const name = document.createElement('span');
        name.className = 'split-file-name';
        name.textContent = file.name;
        name.title = file.name;

        const size = document.createElement('span');
        size.className = 'split-file-size';
        size.textContent = formatSize(file.size);
        if (file.size === 0) {
            // nothing could be read: say so instead of silently attaching an empty file
            row.classList.add('split-file-empty');
            size.title = text.empty;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn-close split-file-remove';
        button.title = text.remove;
        button.setAttribute('aria-label', text.remove + ': ' + file.name);
        button.addEventListener('click', event => {
            event.preventDefault();
            remove(file);
        });

        row.append(thumb, name, size, button);
        return row;
    };

    // show the list; rows of unchanged files stay as they are, so only new rows animate
    function render() {
        const keys = new Set(list.map(keyOf));
        rows.forEach((row, key) => {
            if (!keys.has(key)) {
                row.remove();
                rows.delete(key);
                if (thumbs.has(key)) {
                    URL.revokeObjectURL(thumbs.get(key));
                    thumbs.delete(key);
                }
            }
        });
        list.forEach((file, index) => {
            const key = keyOf(file);
            if (!rows.has(key)) {
                rows.set(key, createRow(file, key));
            }
            const row = rows.get(key);
            if (listEl.children[index] !== row) {
                listEl.insertBefore(row, listEl.children[index] || null);
            }
        });
    }

    /* ---- reading dropped and pasted files ------------------------------ */

    // read a blob completely and return it as a File that lives in memory
    const readCopy = (blob, original) => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            try {
                resolve(new File([reader.result], original.name, {
                    type: original.type,
                    lastModified: original.lastModified
                }));
            } catch (e) {
                reject(e);
            }
        };
        reader.onerror = () => reject(reader.error);
        try {
            reader.readAsArrayBuffer(blob);
        } catch (e) {
            reject(e);
        }
    });

    /*
     * Resolves to {file} on success or {failed: name} when the browser does
     * not let the page read the file (for example a browser confined by a
     * sandbox). Such a file must never reach PrivateBin: it would be listed as
     * 0 bytes and PrivateBin would wait forever for its data.
     *
     * The read starts straight away, inside the drop or paste event, because
     * some browsers only guarantee access to dropped files while the event is
     * handled. handlePromise (optional) is the same dropped file as a File
     * System Access handle (Chromium); it is only used if the normal way fails.
     */
    const snapshot = (file, handlePromise) => {
        let firstError = null;
        return readCopy(file, file).catch(error => {
            firstError = error;
            return Promise.resolve(handlePromise).then(handle => {
                if (!handle || handle.kind !== 'file') {
                    throw error;
                }
                return handle.getFile().then(viaHandle => readCopy(viaHandle, file));
            });
        }).then(copy => {
            if (firstError) {
                warn('"' + file.name + '" could not be read directly (' + describe(firstError) +
                    '), but was read through the file system handle');
            }
            if (file.size === 0 && copy.size > 0) {
                warn('"' + file.name + '" was reported as 0 bytes by the browser, ' +
                    copy.size + ' bytes could be read');
            }
            return { file: copy };
        }, error => {
            warn('could not read "' + file.name + '" (browser reports ' + file.size + ' bytes, type "' +
                file.type + '"): ' + describe(firstError || error));
            return { failed: file.name };
        });
    };

    // File System Access handles of the dropped files, in the order of
    // dataTransfer.files; they have to be requested while the event is handled
    const handlesOf = dataTransfer => {
        const handles = [];
        Array.from((dataTransfer && dataTransfer.items) || []).forEach(item => {
            if (item.kind !== 'file') {
                return;
            }
            let promise = null;
            try {
                if (typeof item.getAsFileSystemHandle === 'function') {
                    promise = item.getAsFileSystemHandle().catch(() => null);
                }
            } catch (e) {
                promise = null;
            }
            handles.push(promise);
        });
        return handles;
    };

    // tell the user which files the browser would not hand over
    const showNotice = names => {
        clearNotice();
        if (!names.length) {
            return;
        }
        notice = document.createElement('div');
        notice.className = 'split-file-notice';
        notice.setAttribute('role', 'alert');
        notice.textContent = text.unreadable.replace('%s', names.join(', '));
        notice.addEventListener('click', () => {
            clearNotice();
            input.click();
        });
        listEl.before(notice);
    };

    /*
     * PrivateBin's own drop and paste handlers would replace the list. They
     * bail out while the attach area counts as read-only (hidden), so it is
     * hidden for the duration of the event, the files are added here and the
     * area is shown again straight afterwards. "split-taking-over" keeps it on
     * screen meanwhile (see the CSS), so nothing flashes.
     */
    const takeOver = (files, handles) => {
        clearNotice();
        const copies = files.map((file, index) => snapshot(file, handles && handles[index]));

        attach.classList.add('hidden', 'split-taking-over');
        window.setTimeout(() => attach.classList.remove('hidden', 'split-taking-over'), 0);

        Promise.all(copies).then(results => {
            const readable = results.filter(result => result.file).map(result => result.file);
            const failed = results.filter(result => result.failed).map(result => result.failed);
            if (add(readable)) {
                sync();
            }
            showNotice(failed);
        });
    };

    document.addEventListener('drop', event => {
        const files = event.dataTransfer && event.dataTransfer.files;
        if (canAttach() && files && files.length) {
            takeOver(Array.from(files), handlesOf(event.dataTransfer));
        }
    }, true);

    document.addEventListener('paste', event => {
        if (!canAttach() || !event.clipboardData) {
            return;
        }
        const files = Array.from(event.clipboardData.items || [])
            .filter(item => item.kind === 'file')
            .map(item => item.getAsFile())
            .filter(Boolean);
        if (files.length) {
            takeOver(files);
        }
    }, true);

    // files picked with the file dialog: merge them with the list before
    // PrivateBin's own change handler reads the input
    document.addEventListener('change', event => {
        if (event.target === input && !syncing) {
            add(input.files);
            writeInput();
            render();
        }
    }, true);

    // "Remove attachment": forget our list, PrivateBin clears the rest
    byId('fileremovebutton').addEventListener('click', clearAll, true);

    // PrivateBin empties the file input when starting a new document ("New",
    // "Clone") without any event of its own. It does that synchronously inside
    // the click on that button, so by the time this listener runs it is done.
    document.addEventListener('click', () => {
        if (list.length && input.files.length === 0 && !syncing) {
            clearAll();
        }
    });
})();
