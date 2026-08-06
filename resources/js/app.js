import Quill from 'quill';

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
