import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('richTextEditor', (initialHtml) => ({
    quill: null,

    async init() {
        const [{ default: Quill }] = await Promise.all([
            import('quill'),
            import('quill/dist/quill.snow.css'),
        ]);

        this.quill = new Quill(this.$refs.editor, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link', 'image'],
                    ['clean'],
                ],
            },
        });

        this.quill.root.innerHTML = initialHtml;
        this.quill.on('text-change', () => {
            this.$refs.input.value = this.quill.root.innerHTML;
        });
    },
}));

Alpine.start();
