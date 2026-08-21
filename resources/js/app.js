// The navbar's dropdowns and burger menu. Importing them registers their
// data-attribute handlers; Tabler's own bundle only adds widgets we don't use.
import 'bootstrap/js/dist/collapse';
import 'bootstrap/js/dist/dropdown';
import Toast from 'bootstrap/js/dist/toast';
import Quill from 'quill';

document.querySelectorAll('.toast').forEach((el) => Toast.getOrCreateInstance(el).show());
document.querySelectorAll('.toast-progress').forEach((bar) => {
    bar.addEventListener('animationend', () => Toast.getOrCreateInstance(bar.closest('.toast')).hide());
});

// Flyout submenus in the navbar: Bootstrap 5 has no nested dropdowns, so
// their toggles carry no data-bs-toggle, and this handler shows/hides them.
// stopPropagation keeps Bootstrap's document handler from closing the whole
// dropdown; the parent dropdown closing sweeps every submenu shut.
document.querySelectorAll('.navbar [data-submenu]').forEach((toggle) => {
    toggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        const menu = toggle.nextElementSibling;

        toggle.closest('.dropdown-menu').querySelectorAll('.dropdown-menu.show').forEach((other) => {
            if (other !== menu) {
                other.classList.remove('show');
                other.previousElementSibling.setAttribute('aria-expanded', 'false');
            }
        });

        toggle.setAttribute('aria-expanded', menu.classList.toggle('show') ? 'true' : 'false');
    });
});

document.querySelectorAll('.navbar .nav-item.dropdown').forEach((dropdown) => {
    dropdown.addEventListener('hide.bs.dropdown', () => {
        dropdown.querySelectorAll('.dropdown-menu .dropdown-menu.show').forEach((menu) => {
            menu.classList.remove('show');
            menu.previousElementSibling.setAttribute('aria-expanded', 'false');
        });
    });
});

const resetLink = document.getElementById('reset-link');
const resetLinkCopy = document.getElementById('reset-link-copy');

if (resetLink && resetLinkCopy) {
    const icon = resetLinkCopy.querySelector('i');

    resetLinkCopy.addEventListener('click', async () => {
        await navigator.clipboard.writeText(resetLink.value);

        icon.className = 'fa-solid fa-check';
        setTimeout(() => icon.className = 'fa-solid fa-copy', 2000);
    });
}

// A publication date only applies to grouped pages, and is required once
// the page is no longer a draft — so the block follows the group select
// and the label's asterisk follows the draft toggle. Cosmetic only; the
// server nulls the date for ungrouped pages and validates regardless.
const groupSelect = document.getElementById('page_group_id');
const publishedAtField = document.getElementById('published-at-field');
const draftToggle = document.querySelector('input[name="is_draft"]');

if (groupSelect && publishedAtField && draftToggle) {
    const dateLabel = publishedAtField.querySelector('.form-label');
    const sync = () => {
        publishedAtField.classList.toggle('d-none', groupSelect.value === '');
        dateLabel.classList.toggle('required', !draftToggle.checked);
    };
    groupSelect.addEventListener('change', sync);
    draftToggle.addEventListener('change', sync);
    sync();
}

// Live preview of the public URL a page or group will get, mirroring
// Page::url() / PageGroup::url(). The server still has the last word: it
// generates the slug itself and rejects duplicate or reserved ones.
const urlPreview = document.getElementById('url-preview');

if (urlPreview) {
    const slugInput = document.getElementById('slug');
    const sourceInput = document.getElementById(urlPreview.dataset.source);
    const parentSelect = document.getElementById(urlPreview.dataset.parent);
    const output = urlPreview.querySelector('span');

    // Approximates Str::slug(): decompose accented letters to ASCII, drop
    // what is neither a letter, a digit nor a separator, collapse the rest.
    // The map covers the Latin letters no decomposition splits; a script
    // NFKD cannot reach at all (Cyrillic, Greek) drops out here and is
    // transliterated server-side only.
    const untangled = { ø: 'o', æ: 'ae', œ: 'oe', ß: 'ss', đ: 'd', ł: 'l', þ: 'th', ð: 'd' };
    const slugify = (value) => value
        .normalize('NFKD')
        .replaceAll('@', '-at-')
        .toLowerCase()
        .replace(/[øæœßđłþð]/g, (letter) => untangled[letter])
        .replace(/[^a-z0-9\s_-]+/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');

    const sync = () => {
        const slug = slugify(slugInput.value.trim() || sourceInput.value);
        const parentPath = parentSelect.selectedOptions[0]?.dataset.path;
        const isHome = !parentPath && slug === urlPreview.dataset.homeSlug;

        output.textContent = `${urlPreview.dataset.base}/${[parentPath, isHome ? '' : slug].filter(Boolean).join('/')}`;
        urlPreview.classList.toggle('d-none', slug === '');
    };

    [slugInput, sourceInput].forEach((input) => input.addEventListener('input', sync));
    parentSelect.addEventListener('change', sync);
    sync();
}

// The settings form's color picker feeds the text field next to it, which is
// the one that submits — an unparseable value reaches the server that way.
const colorInput = document.getElementById('primary_color');
const colorPicker = document.getElementById('primary_color_picker');

if (colorInput && colorPicker) {
    colorPicker.addEventListener('input', () => {
        colorInput.value = colorPicker.value;
        colorInput.classList.remove('is-invalid');
    });

    colorInput.addEventListener('input', () => {
        const hex = colorInput.value.trim();
        const parsed = /^#?([0-9a-f]{6})$/i.exec(hex);

        if (parsed) {
            colorPicker.value = `#${parsed[1].toLowerCase()}`;
        }

        colorInput.classList.toggle('is-invalid', !parsed);
    });
}

// WYSIWYG editor for the admin page form. The toolbar is icon-only, so it
// involves no translatable copy.
const editorElement = document.getElementById('body-editor');

if (editorElement) {
    const input = document.getElementById('body');

    const quill = new Quill(editorElement, {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ header: 2 }, { header: 3 }],
                ['bold', 'italic', 'strike'],
                ['link', 'blockquote'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['clean'],
            ],
        },
    });

    // Quill leaves its editable div unlabelled and roleless; without this the
    // field has no accessible name (the visible one is a <div>, not a <label>).
    quill.root.setAttribute('role', 'textbox');
    quill.root.setAttribute('aria-multiline', 'true');
    quill.root.setAttribute('aria-labelledby', 'body-label');

    // Seed the editor from the hidden input (existing page, or old() input
    // after a failed validation round-trip).
    if (input.value) {
        quill.setContents(quill.clipboard.convert({ html: input.value }));
    }

    // Keep the hidden input in sync. An empty document submits as an empty
    // string, so the server-side "required" rule still triggers. Quill's
    // getSemanticHTML() encodes regular spaces as &nbsp; (quill#4509); those
    // would break word-wrapping on the public site, so turn them back.
    const sync = () => {
        input.value = quill.getText().trim() === ''
            ? ''
            : quill.getSemanticHTML().replaceAll('&nbsp;', ' ');
    };
    quill.on('text-change', sync);
    input.form?.addEventListener('submit', sync);
}
