import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

/**
 * Menu page behaviour: client-side search, scroll-spy on the category strip,
 * and the item bottom sheet. Registered before Alpine.start() so the markup
 * can reference it with x-data="menuPage".
 */
Alpine.data('menuPage', () => ({
    query: '',
    active: null,
    sheet: null,
    sections: [],
    onScroll: null,
    // A tap on a tab starts a smooth scroll; ignore scroll-spy until it lands,
    // otherwise the tab flickers through every section on the way past.
    lockedUntil: 0,

    init() {
        this.sections = [...this.$el.querySelectorAll('[data-category]')];
        this.active = this.sections[0]?.dataset.category ?? null;

        this.onScroll = () => this.spy();
        window.addEventListener('scroll', this.onScroll, { passive: true });
        window.addEventListener('resize', this.onScroll, { passive: true });

        this.$watch('sheet', (open) => {
            document.body.style.overflow = open ? 'hidden' : '';
        });
    },

    destroy() {
        window.removeEventListener('scroll', this.onScroll);
        window.removeEventListener('resize', this.onScroll);
    },

    /**
     * Highlight the section currently under the sticky header. Done from a
     * scroll handler rather than an IntersectionObserver so the last section
     * still wins at the bottom of the page, where it can never reach the top.
     */
    spy() {
        if (this.searching || Date.now() < this.lockedUntil) return;

        const atBottom =
            window.innerHeight + window.scrollY >= document.body.scrollHeight - 4;

        if (atBottom) {
            this.setActive(this.sections.at(-1)?.dataset.category);
            return;
        }

        const line = 150; // just below the sticky header + tab strip
        let current = this.sections[0];
        for (const section of this.sections) {
            if (section.getBoundingClientRect().top <= line) current = section;
        }
        this.setActive(current?.dataset.category);
    },

    /** Highlight a tab and keep it inside the visible part of the strip. */
    setActive(id) {
        if (!id || id === this.active) return;
        this.active = id;
        const tab = this.$refs.tabs?.querySelector(`[data-tab="${id}"]`);
        tab?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    },

    goTo(id) {
        this.query = '';
        this.lockedUntil = Date.now() + 1000;
        this.setActive(id);
        document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    matches(haystack) {
        const q = this.query.trim().toLowerCase();
        return q === '' || haystack.includes(q);
    },

    /**
     * Whether a category section still has a visible item. Reads this.query so
     * Alpine re-evaluates it whenever the search box changes.
     */
    sectionHasMatch(section) {
        if (!this.searching) return true;
        return [...section.querySelectorAll('[data-search]')].some((el) =>
            this.matches(el.dataset.search),
        );
    },

    get searching() {
        return this.query.trim() !== '';
    },

    get resultCount() {
        if (!this.searching) return null;
        return [...this.$el.querySelectorAll('[data-search]')].filter((el) =>
            this.matches(el.dataset.search),
        ).length;
    },

    /** Build the sheet's contents from the row that was tapped. */
    open(button) {
        const text = (sel) => button.querySelector(sel)?.textContent.trim() ?? '';

        this.sheet = {
            name: text('[data-name]'),
            description: text('[data-desc]'),
            price: text('[data-price]'),
            image: button.dataset.full || null,
            featured: button.dataset.featured === '1',
            available: button.dataset.available === '1',
        };
    },

    close() {
        this.sheet = null;
    },
}));

window.Alpine = Alpine;
Alpine.start();
