import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Admin rich text editor (Quill). Headings H1–H4, RTL alignment, and images
 * picked from (or uploaded to) the media library instead of base64 blobs.
 */
Alpine.data('richTextEditor', (initialHtml) => {
    // Kept outside Alpine's reactive state: Quill breaks when wrapped in a Proxy.
    let quill = null;

    return {
        words: 0,
        characters: 0,
        headingCount: { h1: 0, h2: 0, h3: 0 },

        async init() {
            const [{ default: Quill }] = await Promise.all([
                import('quill'),
                import('quill/dist/quill.snow.css'),
            ]);

            quill = new Quill(this.$refs.editor, {
                theme: 'snow',
                placeholder: 'متن را اینجا بنویسید… برای تیتر، از فهرست «پاراگراف» در نوار ابزار H2 یا H3 را انتخاب کنید.',
                modules: {
                    toolbar: {
                        container: [
                            [{ header: [1, 2, 3, 4, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ color: [] }, { background: [] }],
                            [{ list: 'ordered' }, { list: 'bullet' }],
                            [{ align: [] }, { direction: 'rtl' }],
                            ['blockquote', 'link', 'image'],
                            ['clean'],
                        ],
                        handlers: {
                            image: () => this.insertImage(),
                        },
                    },
                },
            });

            quill.root.setAttribute('dir', 'rtl');
            quill.root.innerHTML = initialHtml;
            this.sync();
            quill.on('text-change', () => this.sync());
            this.translateToolbar();
        },

        sync() {
            const html = quill.root.innerHTML;
            this.$refs.input.value = html === '<p><br></p>' ? '' : html;

            const text = quill.getText().trim();
            this.characters = text.length;
            this.words = text === '' ? 0 : text.split(/\s+/u).length;
            this.headingCount = {
                h1: quill.root.querySelectorAll('h1').length,
                h2: quill.root.querySelectorAll('h2').length,
                h3: quill.root.querySelectorAll('h3').length,
            };
        },

        insertImage() {
            const range = quill.getSelection(true);

            Alpine.store('mediaLibrary').open({
                multiple: false,
                onSelect: ([item]) => {
                    if (!item) {
                        return;
                    }
                    const index = range ? range.index : quill.getLength();
                    quill.insertEmbed(index, 'image', item.url, 'user');
                    const [image] = quill.root.querySelectorAll(`img[src="${CSS.escape(item.url)}"]`);
                    image?.setAttribute('alt', item.alt || '');
                    quill.setSelection(index + 1, 0);
                    this.sync();
                },
            });
        },

        /** Persian tooltips for the toolbar buttons. */
        translateToolbar() {
            const toolbar = this.$root.querySelector('.ql-toolbar');
            const titles = {
                'ql-bold': 'پررنگ', 'ql-italic': 'مورب', 'ql-underline': 'زیرخط', 'ql-strike': 'خط‌خورده',
                'ql-list': 'فهرست', 'ql-link': 'پیوند', 'ql-image': 'تصویر از کتابخانه مدیا',
                'ql-blockquote': 'نقل‌قول', 'ql-clean': 'پاک کردن قالب‌بندی', 'ql-direction': 'جهت متن',
                'ql-header': 'نوع تیتر', 'ql-align': 'تراز', 'ql-color': 'رنگ متن', 'ql-background': 'رنگ پس‌زمینه',
            };
            toolbar?.querySelectorAll('button, .ql-picker').forEach((element) => {
                const key = [...element.classList].find((name) => titles[name]);
                if (key) {
                    element.setAttribute('title', titles[key]);
                }
            });
        },
    };
});

/**
 * The shared media library modal (admin layout): browse, search, upload by
 * drag-and-drop and pick one or more images. Callers pass onSelect.
 */
Alpine.store('mediaLibrary', {
    isOpen: false,
    multiple: false,
    items: [],
    selected: [],
    page: 1,
    lastPage: 1,
    total: 0,
    query: '',
    loading: false,
    uploads: [],
    isDragging: false,
    onSelect: null,
    endpoint: '',
    csrf: '',

    configure(endpoint, csrf) {
        this.endpoint = endpoint;
        this.csrf = csrf;
    },

    open({ multiple = false, selectedIds = [], onSelect }) {
        this.multiple = multiple;
        this.onSelect = onSelect;
        this.selected = [];
        this.preselectedIds = selectedIds.map(Number);
        this.isOpen = true;
        this.fetch(1);
    },

    close() {
        this.isOpen = false;
        this.uploads = this.uploads.filter((upload) => upload.status === 'uploading');
    },

    async fetch(page = 1) {
        this.loading = true;
        const url = new URL(this.endpoint, window.location.origin);
        url.searchParams.set('page', page);
        if (this.query.trim() !== '') {
            url.searchParams.set('q', this.query.trim());
        }

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const json = await response.json();
            this.items = json.data;
            this.page = json.current_page;
            this.lastPage = json.last_page;
            this.total = json.total;
        } finally {
            this.loading = false;
        }
    },

    isSelected(item) {
        return this.selected.some((selected) => selected.id === item.id);
    },

    isAlreadyUsed(item) {
        return (this.preselectedIds ?? []).includes(item.id);
    },

    toggle(item) {
        if (this.isSelected(item)) {
            this.selected = this.selected.filter((selected) => selected.id !== item.id);
            return;
        }
        this.selected = this.multiple ? [...this.selected, item] : [item];
    },

    confirm() {
        this.onSelect?.(this.selected);
        this.close();
    },

    pick(item) {
        this.selected = [item];
        this.confirm();
    },

    async upload(fileList) {
        const files = [...fileList];

        for (const file of files) {
            const entry = Alpine.reactive({ name: file.name, progress: 0, status: 'uploading', error: '' });
            this.uploads.unshift(entry);

            try {
                const item = await uploadMedia(this.endpoint, this.csrf, file, (progress) => { entry.progress = progress; });
                entry.status = 'done';
                this.items = [item, ...this.items.filter((existing) => existing.id !== item.id)];
                this.total++;
                if (this.multiple) {
                    this.selected = [...this.selected, item];
                } else {
                    this.selected = [item];
                }
            } catch (error) {
                entry.status = 'failed';
                entry.error = error.message;
            }
        }
    },
});

/**
 * Upload one image to the media library with progress; resolves to the
 * picker item, rejects with the (Persian) validation message.
 */
function uploadMedia(endpoint, csrf, file, onProgress) {
    return new Promise((resolve, reject) => {
        const body = new FormData();
        body.append('file', file);

        const request = new XMLHttpRequest();
        request.open('POST', endpoint);
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-CSRF-TOKEN', csrf);
        request.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable) {
                onProgress(Math.round((event.loaded / event.total) * 100));
            }
        });
        request.addEventListener('load', () => {
            let json = {};
            try {
                json = JSON.parse(request.responseText);
            } catch {
                // Non-JSON error page; fall through to the generic message.
            }
            if (request.status >= 200 && request.status < 300) {
                resolve(json.data);
                return;
            }
            const message = json.errors ? Object.values(json.errors).flat()[0] : (json.message || 'آپلود ناموفق بود.');
            reject(new Error(request.status === 413 ? 'حجم فایل بیش از حد مجاز سرور است.' : message));
        });
        request.addEventListener('error', () => reject(new Error('خطای شبکه — دوباره تلاش کنید.')));
        request.send(body);
    });
}

/**
 * A form field holding one or more media ids (featured image, gallery),
 * showing the chosen images with reorder/remove, filled from the library modal.
 */
Alpine.data('mediaPicker', ({ multiple = false, initial = [] }) => ({
    multiple,
    items: initial,

    open() {
        Alpine.store('mediaLibrary').open({
            multiple: this.multiple,
            selectedIds: this.items.map((item) => item.id),
            onSelect: (picked) => {
                if (this.multiple) {
                    const known = new Set(this.items.map((item) => item.id));
                    this.items = [...this.items, ...picked.filter((item) => !known.has(item.id))];
                } else {
                    this.items = picked.slice(0, 1);
                }
            },
        });
    },
    remove(index) {
        this.items.splice(index, 1);
    },
    move(index, offset) {
        const target = index + offset;
        if (target < 0 || target >= this.items.length) {
            return;
        }
        const [item] = this.items.splice(index, 1);
        this.items.splice(target, 0, item);
    },
    makeFirst(index) {
        this.move(index, -index);
    },
}));

/**
 * Media library page: drag-and-drop / multi-file upload with per-file
 * progress; the page reloads once everything has finished.
 */
Alpine.data('mediaUploader', (endpoint, csrf) => ({
    uploads: [],
    isDragging: false,

    get isBusy() {
        return this.uploads.some((upload) => upload.status === 'uploading');
    },

    async upload(fileList) {
        const entries = [...fileList].map((file) => ({ file, name: file.name, progress: 0, status: 'waiting', error: '' }));
        this.uploads = [...entries, ...this.uploads];

        for (const entry of this.uploads.filter((upload) => upload.status === 'waiting')) {
            entry.status = 'uploading';
            try {
                await uploadMedia(endpoint, csrf, entry.file, (progress) => { entry.progress = progress; });
                entry.status = 'done';
            } catch (error) {
                entry.status = 'failed';
                entry.error = error.message;
            }
        }

        if (this.uploads.every((upload) => upload.status === 'done')) {
            window.location.reload();
        }
    },
}));

/**
 * Admin menu editor: a two-level tree of links (header / footer), edited
 * in place and submitted as one ordered list.
 */
Alpine.data('menuEditor', (initialItems, linkGroups) => {
    let nextKey = 0;
    const withKeys = (item) => ({
        key: nextKey++,
        label: '',
        url: '',
        type: 'link',
        open_in_new_tab: false,
        ...item,
        children: (item.children ?? []).map(withKeys),
    });

    return {
        items: initialItems.map(withKeys),
        linkGroups,
        openKey: null,

        add(type = 'link', parent = null) {
            const item = withKeys({ type, label: type === 'categories' ? 'محصولات' : '' });
            (parent ? parent.children : this.items).push(item);
            this.openKey = item.key;
        },
        addFromOption(option) {
            this.items.push(withKeys({ label: option.label, url: option.url }));
        },
        remove(list, index) {
            list.splice(index, 1);
        },
        move(list, index, offset) {
            const target = index + offset;
            if (target < 0 || target >= list.length) {
                return;
            }
            const [item] = list.splice(index, 1);
            list.splice(target, 0, item);
        },
        canIndent(index) {
            return index > 0 && this.items[index].type !== 'categories' && this.items[index - 1].type !== 'categories';
        },
        indent(index) {
            if (!this.canIndent(index)) {
                return;
            }
            const [item] = this.items.splice(index, 1);
            this.items[index - 1].children.push({ ...item, children: [] }, ...item.children);
        },
        outdent(parent, childIndex) {
            const parentIndex = this.items.indexOf(parent);
            const [item] = parent.children.splice(childIndex, 1);
            this.items.splice(parentIndex + 1, 0, item);
        },
        get hasCategories() {
            return this.items.some((item) => item.type === 'categories');
        },
        serialized() {
            const clean = ({ label, url, type, open_in_new_tab }) => ({ label, url, type, open_in_new_tab });
            return JSON.stringify(this.items.map((item) => ({
                ...clean(item),
                children: item.type === 'categories' ? [] : item.children.map(clean),
            })));
        },
    };
});

/**
 * Text field with a live character count against an SEO-recommended range.
 */
Alpine.data('charCounter', (initial, min, max) => ({
    value: initial ?? '',
    min,
    max,
    get length() {
        return [...this.value].length;
    },
    get state() {
        if (this.length === 0) {
            return 'empty';
        }
        if (this.length < this.min) {
            return 'short';
        }
        return this.length > this.max ? 'long' : 'good';
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
/**
 * Admin list bulk selection: row checkboxes (data-bulk-id) belong to the
 * #bulk-form outside the table via the form attribute; the bar appears
 * once something is selected.
 */
Alpine.data('bulkSelection', () => ({
    selected: [],

    get rowIds() {
        return [...this.$root.querySelectorAll('input[data-bulk-id]')].map((input) => input.value);
    },
    get allSelected() {
        return this.selected.length > 0 && this.selected.length === this.rowIds.length;
    },
    toggleAll(checked) {
        this.selected = checked ? this.rowIds : [];
    },
}));

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
