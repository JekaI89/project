<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useSortableTable } from '@/composables/useSortableTable';

const props = defineProps({
    /** @deprecated use list.items — kept for safety */
    items: { type: Array, default: undefined },
    /** Deferred payload: { items, error, count } */
    list: { type: Object, default: undefined },
    filters: { type: Object, default: () => ({}) },
    meta: { type: Object, default: () => ({}) },
});

const rab = ref(!!props.filters.rab);
const old = ref(!!props.filters.old);
const db = ref(props.filters.db || '');
const de = ref(props.filters.de || '');
const q = ref(props.filters.q || '');

/** true until deferred `list` arrives */
const loadingList = computed(() => props.list === undefined && props.items === undefined);

const tableItems = computed(() => {
    if (props.list?.items) {
        return props.list.items;
    }
    if (Array.isArray(props.items)) {
        return props.items;
    }
    return [];
});

const listError = computed(() => props.list?.error ?? props.meta?.error ?? null);
const listCount = computed(() => {
    if (props.list?.count != null) {
        return props.list.count;
    }
    return tableItems.value.length;
});

const { sortedItems, toggle, indicator, thClass } = useSortableTable(tableItems, {
    key: 'ord_id',
    dir: 'desc',
});

function reload() {
    router.get(
        '/orders',
        {
            rab: rab.value ? 1 : 0,
            old: old.value ? 1 : 0,
            db: db.value,
            de: de.value,
            q: q.value,
        },
        { preserveState: true, replace: true }
    );
}

const statusForm = useForm({ ord_id: 0, status: 0, podr_id: 0, param: 0 });

/**
 * Toggle Delphi status flag (st1..st6, st10).
 * param=1 set on, param=0 set off.
 */
function toggleStatus(row, status, currentlyOn) {
    if (!props.meta.write_allowed) {
        alert('Запись выключена (PRODUCT_DB_ALLOW_WRITE=false)');
        return;
    }
    if (!props.meta.can_edit) {
        alert('Нет права редактирования заказов');
        return;
    }
    statusForm.ord_id = row.ord_id;
    statusForm.status = status;
    statusForm.param = currentlyOn ? 0 : 1;
    statusForm.podr_id = 0;
    statusForm.post('/orders/status', { preserveScroll: true });
}

function rowStyle(row) {
    if (row.color) {
        return { backgroundColor: row.color };
    }
    return {};
}

function flagTitle(on, date) {
    if (!on) return '';
    return date ? `установлено ${date}` : 'установлено';
}
</script>

<template>
    <Head title="Текущие заказы" />
    <AppLayout>
        <div class="page-header compact-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div class="page-title-block">
                    <h1 class="h4 mb-0">
                        Текущие заказы
                        <span v-if="!loadingList" class="text-muted fw-normal small">· {{ listCount }}</span>
                        <span v-else class="text-muted fw-normal small">· загрузка…</span>
                    </h1>
                </div>
                <div class="d-flex flex-wrap page-filters compact-filters">
                    <div class="form-check form-check-inline m-0">
                        <input id="rab" v-model="rab" type="checkbox" class="form-check-input" @change="reload" />
                        <label for="rab" class="form-check-label small">в работе</label>
                    </div>
                    <div class="form-check form-check-inline m-0">
                        <input id="old" v-model="old" type="checkbox" class="form-check-input" @change="reload" />
                        <label for="old" class="form-check-label small">закрытые</label>
                    </div>
                    <label class="small text-muted mb-0">с</label>
                    <input v-model="db" type="date" class="form-control form-control-sm filter-date" @change="reload" />
                    <label class="small text-muted mb-0">по</label>
                    <input v-model="de" type="date" class="form-control form-control-sm filter-date" @change="reload" />
                    <input
                        v-model="q"
                        type="search"
                        class="form-control form-control-sm filter-q"
                        placeholder="Фильтр…"
                        @keyup.enter="reload"
                    />
                    <button class="btn btn-sm btn-primary" type="button" @click="reload">Обновить</button>
                </div>
            </div>
        </div>

        <div v-if="meta.product_db && !meta.product_db.ok" class="alert alert-warning">
            Product DB: {{ meta.product_db.message }}
        </div>
        <div v-if="listError" class="alert alert-danger">{{ listError }}</div>

        <div v-if="loadingList" class="card shadow-sm orders-card">
            <div class="p-5 text-center text-muted">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <div class="fw-medium">Загрузка заказов…</div>
                <div class="small mt-1">
                    Список строится процедурой MySQL (может занять 10–40 сек). Страница уже открыта —
                    данные появятся автоматически.
                </div>
            </div>
        </div>

        <div v-else class="card shadow-sm orders-card">
            <div class="table-responsive orders-table-wrap">
                <table class="table table-sm table-hover data-table orders-table mb-0 align-middle">
                    <thead class="table-light sticky-top">
                        <tr class="orders-group-row">
                            <th colspan="12" class="border-bottom-0"></th>
                            <th colspan="4" class="text-center orders-group-medyn">Производство Медынь</th>
                            <th colspan="4" class="text-center orders-group-piro">Производство Пирогово</th>
                            <th colspan="2" class="border-bottom-0"></th>
                        </tr>
                        <tr>
                            <th :class="thClass('st0_raw')" @click="toggle('st0_raw')">Дата заказа{{ indicator('st0_raw') }}</th>
                            <th :class="thClass('ord_num')" @click="toggle('ord_num')">Номер{{ indicator('ord_num') }}</th>
                            <th :class="thClass('indexs')" @click="toggle('indexs')">Индекс{{ indicator('indexs') }}</th>
                            <th :class="thClass('clients')" @click="toggle('clients')">Клиент{{ indicator('clients') }}</th>
                            <th :class="thClass('payment_num')" @click="toggle('payment_num')">Счёт{{ indicator('payment_num') }}</th>
                            <th :class="thClass('payment')" @click="toggle('payment')">Оплата{{ indicator('payment') }}</th>
                            <th :class="thClass('series')" @click="toggle('series')">Серия{{ indicator('series') }}</th>
                            <th :class="thClass('model')" @click="toggle('model')" style="min-width: 12rem">
                                Модель и комплектация{{ indicator('model') }}
                            </th>
                            <th class="text-end" :class="thClass('quant')" @click="toggle('quant')">
                                Кол-во{{ indicator('quant') }}
                            </th>
                            <th :class="thClass('ready_raw')" @click="toggle('ready_raw')">
                                Срок готовности{{ indicator('ready_raw') }}
                            </th>
                            <th :class="thClass('comments')" @click="toggle('comments')" style="min-width: 10rem">
                                Комментарии{{ indicator('comments') }}
                            </th>
                            <th :class="thClass('conditions')" @click="toggle('conditions')">
                                Статус{{ indicator('conditions') }}
                            </th>
                            <th class="text-center orders-col-medyn" title="Резерв со склада гот.прод.">Резерв</th>
                            <th class="text-center orders-col-medyn" title="Принят">Принят</th>
                            <th class="text-center orders-col-medyn" title="Произведён">Произв.</th>
                            <th class="orders-col-medyn" style="min-width: 8rem" title="Зарезервирован / Применена ТК">
                                ТК Медынь
                            </th>
                            <th class="text-center orders-col-piro" title="Резерв со склада гот.прод.">Резерв</th>
                            <th class="text-center orders-col-piro" title="Принят">Принят</th>
                            <th class="text-center orders-col-piro" title="Произведён">Произв.</th>
                            <th class="orders-col-piro" style="min-width: 8rem" title="Зарезервирован / Применена ТК">
                                ТК Пирогово
                            </th>
                            <th class="text-center" title="Поступил на склад / Реально отгружен">Склад</th>
                            <th :class="thClass('ord_id')" @click="toggle('ord_id')">ID{{ indicator('ord_id') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in sortedItems"
                            :key="row.ord_id"
                            :style="rowStyle(row)"
                            :class="{
                                'table-warning': row.err || row.err_tk,
                                'orders-locked': row.lock_user > 0,
                            }"
                        >
                            <td class="small text-nowrap">{{ row.st0 }}</td>
                            <td class="fw-medium text-nowrap">{{ row.ord_num }}</td>
                            <td class="text-center small">{{ row.indexs }}</td>
                            <td>{{ row.clients }}</td>
                            <td class="small">{{ row.payment_num }}</td>
                            <td class="small">{{ row.payment }}</td>
                            <td class="small">{{ row.series }}</td>
                            <td class="small">
                                <div class="text-truncate" style="max-width: 280px" :title="row.model">
                                    {{ row.model }}
                                </div>
                            </td>
                            <td class="text-end font-monospace">{{ row.quant }}</td>
                            <td class="small text-nowrap">{{ row.ready }}</td>
                            <td class="small">
                                <div class="text-truncate" style="max-width: 220px" :title="row.comments">
                                    {{ row.comments }}
                                </div>
                            </td>
                            <td class="small fw-medium text-center">{{ row.conditions }}</td>
                            <td class="text-center orders-col-medyn">
                                <input type="checkbox" class="form-check-input" :checked="row.st1" :title="flagTitle(row.st1, row.st1d)" :disabled="!meta.write_allowed || !meta.can_edit" @change="toggleStatus(row, 1, row.st1)" />
                            </td>
                            <td class="text-center orders-col-medyn">
                                <input type="checkbox" class="form-check-input" :checked="row.st2" :title="flagTitle(row.st2, row.st2d)" :disabled="!meta.write_allowed || !meta.can_edit" @change="toggleStatus(row, 2, row.st2)" />
                            </td>
                            <td class="text-center orders-col-medyn">
                                <input type="checkbox" class="form-check-input" :checked="row.st3" :title="flagTitle(row.st3, row.st3d)" :disabled="!meta.write_allowed || !meta.can_edit" @change="toggleStatus(row, 3, row.st3)" />
                            </td>
                            <td class="small orders-col-medyn">
                                <div class="text-truncate" style="max-width: 160px" :title="row.tk1">{{ row.tk1 }}</div>
                            </td>
                            <td class="text-center orders-col-piro">
                                <input type="checkbox" class="form-check-input" :checked="row.st6" :title="flagTitle(row.st6, row.st6d)" :disabled="!meta.write_allowed || !meta.can_edit" @change="toggleStatus(row, 6, row.st6)" />
                            </td>
                            <td class="text-center orders-col-piro">
                                <input type="checkbox" class="form-check-input" :checked="row.st4" :title="flagTitle(row.st4, row.st4d)" :disabled="!meta.write_allowed || !meta.can_edit" @change="toggleStatus(row, 4, row.st4)" />
                            </td>
                            <td class="text-center orders-col-piro">
                                <input type="checkbox" class="form-check-input" :checked="row.st5" :title="flagTitle(row.st5, row.st5d)" :disabled="!meta.write_allowed || !meta.can_edit" @change="toggleStatus(row, 5, row.st5)" />
                            </td>
                            <td class="small orders-col-piro">
                                <div class="text-truncate" style="max-width: 160px" :title="row.tk2">{{ row.tk2 }}</div>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" :checked="row.st10" :title="flagTitle(row.st10, row.st10d)" :disabled="!meta.write_allowed || !meta.can_edit" @change="toggleStatus(row, 10, row.st10)" />
                            </td>
                            <td class="small text-muted font-monospace">{{ row.ord_id }}</td>
                        </tr>
                        <tr v-if="!sortedItems.length">
                            <td colspan="22" class="text-center text-muted py-4">Нет заказов</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.orders-card { max-width: 100%; min-width: 0; }
.orders-table-wrap { max-height: 72vh; max-width: 100%; overflow: auto; }
.orders-table { font-size: 0.8rem; width: max-content; min-width: 100%; }
.orders-group-row th { font-size: 0.75rem; font-weight: 600; padding-top: 0.35rem; padding-bottom: 0.2rem; }
.orders-group-medyn, .orders-col-medyn { background: #e8f0fe !important; }
.orders-group-piro, .orders-col-piro { background: #f3e8ff !important; }
.orders-table thead th { white-space: nowrap; vertical-align: bottom; }
.orders-locked { opacity: 0.85; }
</style>
