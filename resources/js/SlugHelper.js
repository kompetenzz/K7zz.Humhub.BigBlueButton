export class SlugHelper {
    constructor(options) {
        this.titleSelector = options.titleSelector || '#title';
        this.slugSelector = options.slugSelector || '#slug';
        this.autogenerate = options.autogenerate !== false;

        this.titleInput = document.querySelector(this.titleSelector);
        this.slugInput = document.querySelector(this.slugSelector);

        if (!this.titleInput || !this.slugInput) return;

        this.init();
    }

    init() {
        this.titleInput.addEventListener('input', () => {
            if (!this.autogenerate) return;
            if (this.slugInput.value.trim() !== '') return;

            this.slugInput.value = SlugHelper.slugify(this.titleInput.value);
        });

        // Optional: Markiere manuelle Änderung → autogenerate deaktivieren
        this.slugInput.addEventListener('input', () => {
            if (this.slugInput.value.trim() !== '') {
                this.autogenerate = false;
            }
        });
    }

    static slugify(text) {
        return text
            .toLowerCase()
            .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
            .replace(/[^a-z0-9\- ]/g, '')
            .replace(/\s+/g, '-')
            .replace(/\-+/g, '-')
            .replace(/^\-+|\-+$/g, '');
    }

    static slugifyWithSuffix(text, suffixLength = 4) {
        const base = SlugHelper.slugify(text);
        const rand = Math.random().toString(36).substring(2, 2 + suffixLength);
        return `${base}-${rand}`;
    }
}
