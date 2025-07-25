
export class Slugifyer {
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

            this.slugInput.value = Slugifyer.slugify(this.titleInput.value);
        });

        if (this.slugInput.value.trim() === '') {
            this.slugInput.value = Slugifyer.slugify(this.titleInput.value);
        }
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
        const base = Slugifyer.slugify(text);
        const rand = Math.random().toString(36).substring(2, 2 + suffixLength);
        return `${base}-${rand}`;
    }
}

$(document).on('humhub:ready', function (evt, contentContainer) {
    const slugInputs = document.querySelectorAll('[data-slugify]');

    slugInputs.forEach((slugInput) => {
        const titleSelector = slugInput.dataset.slugifyTitleSelector || '#title';
        const autogenerate = slugInput.dataset.slugifyAutogenerate !== 'false';

        new Slugifyer({
            titleSelector,
            slugSelector: `#${slugInput.id}`,
            autogenerate
        });
    });
});
