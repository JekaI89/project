import { computed, ref, unref } from 'vue';

/**
 * Client-side multi-column sort for table rows already loaded in the page.
 *
 * @param {import('vue').Ref|import('vue').ComputedRef|Array} itemsSource
 * @param {{ key: string, dir?: 'asc'|'desc' }} [initial]
 */
export function useSortableTable(itemsSource, initial = { key: null, dir: 'asc' }) {
    const sortKey = ref(initial.key);
    const sortDir = ref(initial.dir || 'asc');

    function toggle(key) {
        if (sortKey.value === key) {
            sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortKey.value = key;
            sortDir.value = 'asc';
        }
    }

    function indicator(key) {
        if (sortKey.value !== key) return '';
        return sortDir.value === 'asc' ? ' ▲' : ' ▼';
    }

    function thClass(key) {
        return sortKey.value === key ? 'sortable sorted' : 'sortable';
    }

    const sortedItems = computed(() => {
        const items = [...(unref(itemsSource) || [])];
        const key = sortKey.value;
        if (!key) return items;

        const dir = sortDir.value === 'desc' ? -1 : 1;

        items.sort((a, b) => {
            const av = a?.[key];
            const bv = b?.[key];

            // nulls last
            if (av == null && bv == null) return 0;
            if (av == null) return 1;
            if (bv == null) return -1;

            const an = typeof av === 'number' || (typeof av === 'string' && av !== '' && !isNaN(Number(av)) && !isNaN(parseFloat(av)));
            const bn = typeof bv === 'number' || (typeof bv === 'string' && bv !== '' && !isNaN(Number(bv)) && !isNaN(parseFloat(bv)));

            // both numeric-like
            if (an && bn && !Number.isNaN(Number(av)) && !Number.isNaN(Number(bv))) {
                const na = Number(av);
                const nb = Number(bv);
                if (na < nb) return -1 * dir;
                if (na > nb) return 1 * dir;
                return 0;
            }

            // booleans
            if (typeof av === 'boolean' && typeof bv === 'boolean') {
                return (av === bv ? 0 : av ? 1 : -1) * dir;
            }

            const sa = String(av).toLocaleLowerCase('ru');
            const sb = String(bv).toLocaleLowerCase('ru');
            return sa.localeCompare(sb, 'ru', { numeric: true, sensitivity: 'base' }) * dir;
        });

        return items;
    });

    return {
        sortKey,
        sortDir,
        sortedItems,
        toggle,
        indicator,
        thClass,
    };
}
