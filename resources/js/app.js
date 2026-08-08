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

// WYSIWYG editor for the admin post form. The toolbar is icon-only, so it
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

    // Seed the editor from the hidden input (existing post, or old() input
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
