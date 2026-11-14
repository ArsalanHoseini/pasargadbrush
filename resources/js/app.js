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

/**
 * Admin manual order entry: dynamic order lines whose variant list and
 * dimension fields follow the chosen product (catalog comes from the server).
 */
Alpine.data('manualOrderForm', (catalog, initialItems) => {
    let nextKey = 0;
    const blankItem = () => ({
        product_id: '', product_variant_id: '', name: '', quantity: 1,
        unit_price_toman: '', dimensions: {}, customization_notes: '',
    });
    const withKey = (item) => ({
        ...blankItem(),
        ...item,
        dimensions: { ...(item.dimensions ?? {}) },
        key: nextKey++,
    });

    return {
        catalog,
        items: (initialItems.length ? initialItems : [blankItem()]).map(withKey),

        addItem() {
            this.items.push(withKey(blankItem()));
        },
        removeItem(index) {
            this.items.splice(index, 1);
        },
        productOf(item) {
            return this.catalog[item.product_id] ?? null;
        },
        variantsOf(item) {
            return this.productOf(item)?.variants ?? [];
        },
        fieldsOf(item) {
            return this.productOf(item)?.fields ?? [];
        },
        productChanged(item) {
            item.product_variant_id = '';
            item.dimensions = {};
        },
        variantChanged(item) {
            const variant = this.variantsOf(item).find((v) => String(v.id) === String(item.product_variant_id));
            if (variant && (item.unit_price_toman === '' || item.unit_price_toman === null)) {
                item.unit_price_toman = variant.price_toman;
            }
        },
        lineTotal(item) {
            return (Number(item.quantity) || 0) * (Number(item.unit_price_toman) || 0);
        },
        subtotal() {
            return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
        },
        toman(amount) {
            return Number(amount).toLocaleString('en-US') + ' تومان';
        },
    };
});

/**
 * Admin confirmation dialog: any form with data-confirm="message" asks first
 * in a proper modal (admin layout) instead of the browser's confirm().
 */
Alpine.store('confirmDialog', {
    open: false,
    message: '',
    form: null,
    submitter: null,

    ask(form, submitter) {
        this.form = form;
        this.submitter = submitter;
        this.message = form.dataset.confirm;
        this.open = true;
    },

    accept() {
        const { form, submitter } = this;
        this.close();
        form.dataset.confirmed = '1';
        form.requestSubmit(submitter ?? undefined);
    },

    close() {
        this.open = false;
    },
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) {
        return;
    }

    if (form.dataset.confirmed) {
        delete form.dataset.confirmed;
        return;
    }

    event.preventDefault();
    Alpine.store('confirmDialog').ask(form, event.submitter);
});

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
