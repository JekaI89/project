<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const nav = computed(() => page.props.modules || []);
const flash = computed(() => page.props.flash || {});
const appName = computed(() => page.props.app?.name || 'Product');

const logout = useForm({});
const mobileNav = ref(false);
const navigating = ref(false);
let removeStart = null;
let removeFinish = null;
let removeError = null;

onMounted(() => {
    removeStart = router.on('start', () => {
        navigating.value = true;
    });
    removeFinish = router.on('finish', () => {
        navigating.value = false;
    });
    removeError = router.on('error', () => {
        navigating.value = false;
    });
});

onUnmounted(() => {
    removeStart?.();
    removeFinish?.();
    removeError?.();
});

function submitLogout() {
    logout.post('/logout');
}

function currentPath() {
    return window.location.pathname + (window.location.search || '');
}

function pathOnly() {
    return window.location.pathname;
}

function isMatch(item) {
    const path = pathOnly();
    const full = currentPath();
    const matches = item.match || [item.href || ''];
    return matches.some((m) => {
        if (!m) return false;
        if (m.startsWith('mode=')) {
            if (m === 'mode=parts') {
                return full.includes('mode=parts') || (path === '/store' && !full.includes('mode='));
            }
            return full.includes(m);
        }
        if (m.startsWith('tab=')) {
            if (m === 'tab=system') {
                return full.includes('tab=system') || (path === '/settings' && !full.includes('tab='));
            }
            if (m === 'tab=config') {
                return full.includes('tab=config');
            }
            if (m === 'tab=users') {
                return full.includes('tab=users');
            }
            return full.includes(m);
        }
        if (m === '/') return path === '/';
        return path === m || path.startsWith(m + '/') || full.startsWith(m);
    });
}

function isSectionActive(item) {
    if (item.children?.length) {
        return item.children.some((c) => isMatch(c) || isSectionActive(c)) || isMatch(item);
    }
    return isMatch(item);
}

const activeSection = computed(() => {
    for (const mod of nav.value) {
        if (mod.children?.length && isSectionActive(mod)) {
            return mod;
        }
    }
    return null;
});

const sectionChildren = computed(() => activeSection.value?.children || []);
const showSectionTabs = computed(() => sectionChildren.value.length > 0);
const sectionLabel = computed(() => activeSection.value?.label || '');

const mainNav = computed(() => (nav.value || []).filter((m) => m.key !== 'settings'));
const settingsItem = computed(() => (nav.value || []).find((m) => m.key === 'settings' || m.href === '/settings'));
</script>

<template>
    <div class="app-shell d-flex">
        <!-- SideNavBar — Kinetic Enterprise -->
        <aside class="app-sidebar d-none d-md-flex flex-column">
            <div class="brand">
                <h1 class="brand-title">Enterprise Resource</h1>
                <p class="brand-sub">Global Admin · {{ appName }}</p>
            </div>

            <nav class="nav flex-column flex-grow-1">
                <Link
                    v-for="mod in mainNav"
                    :key="mod.key"
                    :href="mod.href || '#'"
                    class="nav-link"
                    :class="{ active: isSectionActive(mod) }"
                    prefetch
                >
                    <i :class="['bi', mod.icon || 'bi-circle']"></i>
                    <span>{{ mod.label }}</span>
                </Link>
            </nav>

            <div class="sidebar-footer">
                <nav class="nav flex-column">
                    <Link
                        v-if="settingsItem"
                        :href="settingsItem.href || '/settings'"
                        class="nav-link"
                        :class="{ active: isSectionActive(settingsItem) }"
                        prefetch
                    >
                        <i class="bi bi-gear"></i>
                        <span>{{ settingsItem.label || 'Настройки' }}</span>
                    </Link>
                    <Link v-else href="/settings" class="nav-link" prefetch>
                        <i class="bi bi-gear"></i>
                        <span>Настройки</span>
                    </Link>
                    <button
                        type="button"
                        class="nav-link border-0 bg-transparent text-start w-auto"
                        style="cursor: pointer"
                        :disabled="logout.processing"
                        @click="submitLogout"
                    >
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Выход</span>
                    </button>
                </nav>
            </div>
        </aside>

        <div class="app-main d-flex flex-column">
            <!-- TopNavBar -->
            <header class="app-topbar d-flex align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary d-md-none"
                        @click="mobileNav = !mobileNav"
                    >
                        <i class="bi bi-list"></i>
                    </button>
                    <h2 class="topbar-title d-none d-md-block">ERP Core</h2>
                    <span class="d-md-none fw-bold text-truncate">{{ appName }}</span>
                </div>

                <div class="d-flex align-items-center gap-2 gap-md-3 flex-shrink-0">
                    <button type="button" class="icon-btn d-none d-sm-inline-flex" title="Уведомления" disabled>
                        <i class="bi bi-bell"></i>
                    </button>
                    <button type="button" class="icon-btn d-none d-sm-inline-flex" title="Справка" disabled>
                        <i class="bi bi-question-circle"></i>
                    </button>

                    <div class="user-chip d-none d-sm-flex" v-if="user">
                        <span class="user-avatar"><i class="bi bi-person"></i></span>
                        <div class="user-meta d-none d-lg-flex flex-column">
                            <span class="user-name">{{ user.name }}</span>
                            <span class="user-podr" v-if="user.podr_name">{{ user.podr_name }}</span>
                        </div>
                    </div>

                    <button
                        class="btn-logout"
                        type="button"
                        @click="submitLogout"
                        :disabled="logout.processing"
                    >
                        Выход
                    </button>
                </div>
            </header>

            <!-- Mobile nav -->
            <div v-if="mobileNav" class="d-md-none border-bottom px-2 py-2" style="background: var(--ke-surface-low)">
                <div class="d-flex flex-column gap-1">
                    <template v-for="mod in nav" :key="'m-' + mod.key">
                        <Link
                            :href="mod.href || '#'"
                            class="btn btn-sm text-start"
                            :class="isSectionActive(mod) ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="mobileNav = false"
                        >
                            {{ mod.label }}
                        </Link>
                        <Link
                            v-for="sub in mod.children || []"
                            :key="sub.key"
                            :href="sub.href || '#'"
                            class="btn btn-sm text-start ms-3"
                            :class="isMatch(sub) ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="mobileNav = false"
                        >
                            {{ sub.label }}
                        </Link>
                    </template>
                </div>
            </div>

            <!-- Submenu tabs -->
            <div v-if="showSectionTabs" class="warehouse-topnav">
                <div class="warehouse-topnav-inner px-3 px-md-4">
                    <span class="warehouse-topnav-label">{{ sectionLabel }}</span>
                    <nav class="warehouse-topnav-tabs" :aria-label="sectionLabel">
                        <Link
                            v-for="sub in sectionChildren"
                            :key="sub.key"
                            :href="sub.href"
                            class="warehouse-top-tab"
                            :class="{ active: isMatch(sub) }"
                        >
                            {{ sub.label }}
                        </Link>
                    </nav>
                </div>
            </div>

            <main class="app-canvas p-3 p-md-4 flex-grow-1 position-relative">
                <div v-if="flash.success" class="alert alert-success py-2">{{ flash.success }}</div>
                <div v-if="flash.error" class="alert alert-danger py-2">{{ flash.error }}</div>
                <div v-if="flash.warning" class="alert alert-warning py-2">{{ flash.warning }}</div>
                <slot />

                <div v-if="navigating" class="nav-loading-overlay" aria-live="polite" aria-busy="true">
                    <div class="nav-loading-box">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <div class="small fw-medium">Загрузка…</div>
                        <div class="text-muted" style="font-size: 0.75rem">Получение данных с сервера</div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.nav-loading-overlay {
    position: absolute;
    inset: 0;
    z-index: 40;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding-top: 4rem;
    background: rgba(248, 249, 255, 0.72);
    backdrop-filter: blur(1px);
}
.nav-loading-box {
    background: #fff;
    border: 1px solid rgba(195, 198, 215, 0.5);
    border-radius: 0.75rem;
    padding: 1.25rem 1.75rem;
    text-align: center;
    min-width: 12rem;
    box-shadow: 0 10px 15px -3px rgba(11, 28, 48, 0.1);
}
</style>
