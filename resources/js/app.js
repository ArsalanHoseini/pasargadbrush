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

async function initScrollAnimations() {
    const animatedEls = document.querySelectorAll('[data-animate]');
    const counters = document.querySelectorAll('[data-counter]');

    if (animatedEls.length === 0 && counters.length === 0) {
        return;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const [{ gsap }, { ScrollTrigger }] = await Promise.all([
        import('gsap'),
        import('gsap/ScrollTrigger'),
    ]);
    gsap.registerPlugin(ScrollTrigger);

    animatedEls.forEach((el, index) => {
        gsap.from(el, {
            opacity: 0,
            y: 24,
            duration: 0.6,
            ease: 'power2.out',
            delay: (index % 4) * 0.08,
            scrollTrigger: { trigger: el, start: 'top 85%' },
        });
    });

    counters.forEach((el) => {
        const target = parseFloat(el.dataset.counter);
        const suffix = el.dataset.counterSuffix ?? '';

        ScrollTrigger.create({
            trigger: el,
            start: 'top 85%',
            once: true,
            onEnter: () => {
                const tweened = { value: 0 };
                gsap.to(tweened, {
                    value: target,
                    duration: 1.4,
                    ease: 'power1.out',
                    onUpdate() {
                        el.textContent = Math.floor(tweened.value).toLocaleString('fa-IR') + suffix;
                    },
                });
            },
        });
    });
}

initScrollAnimations();
