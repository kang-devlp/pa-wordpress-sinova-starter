<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    appMeta: { type: Object, default: () => ({ name: 'Portal SINOVA' }) },
    siteBaseUrl: { type: String, default: '' },
    navGroups: { type: Array, default: () => [] },
    modules: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    roadmapGroups: { type: Array, default: () => [] },
    months: { type: Array, default: () => [] },
    years: { type: Array, default: () => [] },
    documents: { type: Array, default: () => [] },
    recentLogs: { type: Array, default: () => [] },
    gatewayStatus: { type: Object, default: () => ({ ok: false }) },
    compliance: { type: Object, default: () => ({ summary: {}, items: [], module_health: {} }) },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || '');
const flashError = computed(() => page.props.flash?.error || '');

const activeTab = ref('pilot'); // 'pilot' | 'roadmap' | 'logs'
const selectedCategory = ref('ALL');
const searchQuery = ref('');
const selectedSlug = ref(props.modules[0]?.slug || 'lra');
const selectedYear = ref(2026);
const modalOpen = ref(false);
const activeSlot = ref({ subGroup: '', periodKey: '', periodLabel: '', existing: null });

const complianceSummary = computed(() => props.compliance?.summary || {});
const complianceItems = computed(() => props.compliance?.items || []);
const moduleHealthMap = computed(() => props.compliance?.module_health || {});
const attentionItemsCount = computed(() =>
    complianceItems.value.filter((i) => i.status !== 'ok').length
);

const complianceFilter = ref('ALL');
const complianceSearch = ref('');
const filteredComplianceItems = computed(() => {
    const q = complianceSearch.value.trim().toLowerCase();
    return complianceItems.value.filter((item) => {
        if (complianceFilter.value === 'berkala' && item.group !== 'berkala') return false;
        if (complianceFilter.value === 'wajib' && item.group !== 'wajib') return false;
        if (complianceFilter.value === 'ok' && item.status !== 'ok') return false;
        if (complianceFilter.value === 'attention' && item.status === 'ok') return false;
        if (q) {
            const hay = `${item.code} ${item.label} ${item.unit} ${item.note} ${item.evidence_url}`.toLowerCase();
            if (!hay.includes(q)) return false;
        }
        return true;
    });
});

const copiedCode = ref('');
const copiedAll = ref(false);
const copyText = async (text) => {
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
        return;
    }
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
};

const copyEvidenceUrl = async (code, url) => {
    if (!url) return;
    try {
        await copyText(url);
        copiedCode.value = code;
        setTimeout(() => {
            if (copiedCode.value === code) copiedCode.value = '';
        }, 2000);
    } catch {
        // Ignore clipboard errors in restricted browsers
    }
};

const copyAllEvidenceUrls = async () => {
    if (!complianceItems.value.length) return;
    const lines = ['Kode\tKategori\tKriteria SINOVA\tStatus\tURL Bukti SINOVA'];
    for (const item of complianceItems.value) {
        lines.push(`${item.code}\t${item.group_label}\t${item.label}\t${item.points} Poin (${item.status})\t${item.evidence_url}`);
    }
    try {
        await copyText(lines.join('\n'));
        copiedAll.value = true;
        setTimeout(() => {
            copiedAll.value = false;
        }, 2500);
    } catch {
        // Ignore clipboard errors
    }
};

const jumpToModule = (slug) => {
    if (!slug) return;
    selectedCategory.value = 'ALL';
    selectModule(slug);
    activeTab.value = 'pilot';
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const complianceStatusClass = (status) => {
    if (status === 'ok') return 'border-emerald-500/35 bg-emerald-500/15 text-emerald-300';
    if (status === 'kurang') return 'border-amber-500/40 bg-amber-500/15 text-amber-300';
    return 'border-rose-500/40 bg-rose-500/15 text-rose-300';
};

const complianceStatusLabel = (status) => {
    if (status === 'ok') return 'Lengkap Â· 10 Poin';
    if (status === 'kurang') return 'Perlu Perhatian Â· 5 Poin';
    return 'Kosong Â· 0 Poin';
};

const moduleDocCounts = computed(() => {
    const counts = {};
    for (const d of props.documents) {
        counts[d.page_slug] = (counts[d.page_slug] || 0) + 1;
    }
    return counts;
});

const categoryCounts = computed(() => {
    const counts = { ALL: props.modules.length };
    for (const m of props.modules) {
        counts[m.category] = (counts[m.category] || 0) + 1;
    }
    return counts;
});

const filteredModules = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    return props.modules.filter((m) => {
        if (selectedCategory.value !== 'ALL' && m.category !== selectedCategory.value) {
            return false;
        }
        if (q) {
            const hay = `${m.title} ${m.short} ${m.unit} ${m.slug}`.toLowerCase();
            if (!hay.includes(q)) return false;
        }
        return true;
    });
});

const selectCategory = (cat) => {
    selectedCategory.value = cat;
    const list = filteredModules.value;
    if (list.length && !list.some((m) => m.slug === selectedSlug.value)) {
        selectModule(list[0].slug);
    }
};

const currentModule = computed(() =>
    props.modules.find((m) => m.slug === selectedSlug.value) || props.modules[0]
);

const activePeriods = computed(() =>
    currentModule.value?.periods?.length ? currentModule.value.periods : props.months
);

const availableYears = computed(() => {
    const start = currentModule.value?.start_year || 2020;
    const end = currentModule.value?.end_year || 2027;
    return props.years.filter((y) => y >= start && y <= end);
});

const selectModule = (slug) => {
    selectedSlug.value = slug;
    if (!availableYears.value.includes(selectedYear.value)) {
        selectedYear.value = availableYears.value.includes(2026)
            ? 2026
            : availableYears.value[0];
    }
};

const docMap = computed(() => {
    const map = {};
    for (const d of props.documents) {
        if (d.page_slug !== selectedSlug.value) continue;
        map[`${d.year}|${d.sub_group}|${d.period_key}`] = d;
    }
    return map;
});

const getDoc = (year, subGroup, periodKey) =>
    docMap.value[`${year}|${subGroup}|${periodKey}`] || null;

const yearStats = (year) => {
    const subs = currentModule.value?.sub_groups || [];
    const periods = activePeriods.value;
    let filled = 0;
    const total = subs.length * periods.length;
    for (const sg of subs) {
        for (const p of periods) {
            if (getDoc(year, sg, p.key)) filled++;
        }
    }
    return { filled, total };
};

const form = useForm({
    page_slug: '',
    year: 2026,
    sub_group: '',
    period_key: '',
    source_type: 'wp_pdf',
    doc_url: '',
    pdf_file: null,
    title: '',
});

const openSlotModal = (subGroup, period) => {
    const existing = getDoc(selectedYear.value, subGroup, period.key);
    activeSlot.value = {
        subGroup,
        periodKey: period.key,
        periodLabel: period.label,
        existing,
    };
    form.reset();
    form.clearErrors();
    form.page_slug = selectedSlug.value;
    form.year = selectedYear.value;
    form.sub_group = subGroup;
    form.period_key = period.key;
    form.source_type = existing?.source_type === 'gdrive' || existing?.source_type === 'external' ? existing.source_type : 'wp_pdf';
    form.doc_url = existing?.source_type !== 'wp_pdf' ? (existing?.doc_url || '') : '';
    form.title = existing?.title || `${subGroup} ${period.label} ${selectedYear.value}`;
    form.pdf_file = null;
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
};

const handleFileChange = (e) => {
    const files = e.target.files;
    form.pdf_file = files && files[0] ? files[0] : null;
};

const submitSlot = () => {
    form.post(route('website-sinova.save'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            modalOpen.value = false;
        },
    });
};

const deleteSlot = () => {
    if (!activeSlot.value.existing) return;
    if (!confirm(`Hapus dokumen ${activeSlot.value.subGroup} ${activeSlot.value.periodLabel} ${selectedYear.value} dari website?`)) return;

    router.post(
        route('website-sinova.delete'),
        {
            page_slug: selectedSlug.value,
            year: selectedYear.value,
            sub_group: activeSlot.value.subGroup,
            period_key: activeSlot.value.periodKey,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                modalOpen.value = false;
            },
        }
    );
};

const pulling = ref(false);
const pullFromWebsite = (syncAll = false) => {
    pulling.value = true;
    router.post(
        route('website-sinova.pull'),
        syncAll ? {} : { page_slug: selectedSlug.value },
        {
            preserveScroll: true,
            onFinish: () => { pulling.value = false; },
        }
    );
};

const checkingHealth = ref(false);
const runHealthCheck = () => {
    checkingHealth.value = true;
    router.post(
        route('website-sinova.health-check'),
        { page_slug: selectedSlug.value, year: selectedYear.value },
        {
            preserveScroll: true,
            onFinish: () => { checkingHealth.value = false; },
        }
    );
};

const healthBadgeClass = (doc) => {
    if (!doc || !doc.link_http_status) return 'border-slate-500/30 text-slate-400 bg-slate-500/10';
    if (doc.link_http_status >= 200 && doc.link_http_status < 400) {
        return 'border-emerald-500/30 text-emerald-400 bg-emerald-500/10';
    }
    return 'border-rose-500/30 text-rose-400 bg-rose-500/10';
};

const sourceLabel = (sourceType) => {
    if (sourceType === 'wp_pdf') return 'PDF Web';
    if (sourceType === 'gdrive') return 'Drive';
    return 'Tautan';
};
</script>

<template>
    <Head title="Update Website (SINOVA)" />

    <div class="min-h-screen bg-slate-950 text-slate-100 p-4 sm:p-6 lg:p-8"><div class="mx-auto max-w-7xl">
        <div class="space-y-6">
            <!-- Header & Gateway Status -->
            <section class="rounded-[1.75rem] border border-[#334155] bg-[#0f172a] p-6 shadow-xl">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-[11px] font-black uppercase tracking-wider text-emerald-400">
                                SK Dirjen Badilag 2722/2026
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-bold"
                                :class="gatewayStatus.ok ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' : 'border-rose-500/30 bg-rose-500/10 text-rose-400'"
                            >
                                {{ gatewayStatus.ok ? `â— Terhubung ke website satker (${modules.length} Modul SINOVA)` : 'â— Gateway Terputus: ' + (gatewayStatus.error || 'Periksa kunci') }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-[11px] font-bold text-amber-300">
                                Total Arsip Tersinkron: {{ documents.length }} Dokumen
                            </span>
                        </div>
                        <h1 class="text-2xl font-black tracking-tight text-[#f8fafc]">
                            Pusat Update Website &amp; Kesiapan SINOVA
                        </h1>
                        <p class="max-w-3xl text-sm text-[#94a3b8]">
                            Kelola <strong>{{ modules.length }} modul keterbukaan informasi SINOVA</strong> dengan slot permanen hingga <strong>Tahun 2027</strong>. Unggah PDF resmi langsung ke hosting website atau tautkan URL eksternal dengan verifikasi publik otomatis.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <button
                            type="button"
                            @click="pullFromWebsite(false)"
                            :disabled="pulling"
                            class="inline-flex items-center gap-2 rounded-xl border border-[#334155] bg-[#1e293b] px-4 py-2.5 text-xs font-bold text-[#f8fafc] transition hover:border-emerald-500/50 disabled:opacity-50"
                        >
                            <span>{{ pulling ? 'Menarik Dataâ€¦' : `Tarik ${currentModule?.short || 'Modul'} dari Web` }}</span>
                        </button>
                        <button
                            type="button"
                            @click="pullFromWebsite(true)"
                            :disabled="pulling"
                            class="inline-flex items-center gap-2 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-2.5 text-xs font-bold text-amber-300 transition hover:bg-amber-500/20 disabled:opacity-50"
                        >
                            <span>{{ pulling ? 'Menyinkronkanâ€¦' : `Tarik Semua ${modules.length} Modul` }}</span>
                        </button>
                        <button
                            type="button"
                            @click="runHealthCheck"
                            :disabled="checkingHealth"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg transition hover:bg-emerald-500 disabled:opacity-50"
                        >
                            <span>{{ checkingHealth ? 'Memeriksa Aksesâ€¦' : 'Cek Akses Link Tahun ' + selectedYear }}</span>
                        </button>
                    </div>
                </div>

                <!-- 4-Pillar SINOVA Scorecard Strip -->
                <div class="mt-5 grid gap-3 border-t border-[#334155] pt-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] font-black uppercase tracking-wider text-emerald-300">Estimasi Skor SINOVA</span>
                            <span class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[11px] font-black text-emerald-200">
                                {{ complianceSummary.ok_count ?? 0 }}/{{ complianceSummary.total_criteria ?? 35 }} Lengkap
                            </span>
                        </div>
                        <div class="mt-1.5 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-white">{{ complianceSummary.estimated_total_score ?? 0 }}%</span>
                            <span v-if="attentionItemsCount" class="text-[11px] font-semibold text-amber-300">
                                Â· {{ attentionItemsCount }} perlu perhatian
                            </span>
                            <span v-else class="text-[11px] font-semibold text-emerald-300">Â· Siap Penilaian Penuh</span>
                        </div>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-black/30">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-amber-300 transition-all"
                                :style="{ width: `${Math.min(100, complianceSummary.estimated_total_score ?? 0)}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-[#334155] bg-[#1e293b]/70 p-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#94a3b8]">A.1 Info Berkala (40%)</span>
                            <span class="text-sm font-black text-emerald-400">{{ complianceSummary.berkala_pct ?? 0 }}%</span>
                        </div>
                        <p class="mt-1.5 text-xs text-[#cbd5e1]">24 kriteria bulanan, triwulanan, &amp; SAKIP 5 tahunan.</p>
                    </div>

                    <div class="rounded-2xl border border-[#334155] bg-[#1e293b]/70 p-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#94a3b8]">A.2 Info Wajib (20%)</span>
                            <span class="text-sm font-black text-emerald-400">{{ complianceSummary.wajib_pct ?? 0 }}%</span>
                        </div>
                        <p class="mt-1.5 text-xs text-[#cbd5e1]">11 kriteria profil, layanan perkara, biaya &amp; standar layanan.</p>
                    </div>

                    <div class="rounded-2xl border border-[#334155] bg-[#1e293b]/70 p-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#94a3b8]">B &amp; C UI/UX Â· Keamanan (40%)</span>
                            <span class="text-sm font-black text-amber-300">{{ complianceSummary.ui_ux_pct ?? 100 }}%</span>
                        </div>
                        <p class="mt-1.5 text-xs text-[#cbd5e1]">Tema Hijau-Gold, cache ~0,03s, WCAG AAA, Turnstile/SSL.</p>
                    </div>
                </div>

                <!-- Navigation Sub-Tabs -->
                <div class="mt-4 flex flex-wrap gap-2 border-t border-[#334155] pt-4">
                    <button
                        type="button"
                        @click="activeTab = 'pilot'"
                        class="rounded-xl px-4 py-2 text-xs font-bold transition"
                        :class="activeTab === 'pilot' ? 'bg-emerald-600 text-white' : 'bg-[#1e293b] text-[#cbd5e1] hover:text-[#f8fafc]'"
                    >
                        {{ modules.length }} Modul SINOVA (Rumah s.d. 2027)
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'roadmap'"
                        class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold transition"
                        :class="activeTab === 'roadmap' ? 'bg-emerald-600 text-white' : 'bg-[#1e293b] text-[#cbd5e1] hover:text-[#f8fafc]'"
                    >
                        <span>Dashboard 35 Kriteria (B1.1â€“W4.1)</span>
                        <span
                            v-if="attentionItemsCount"
                            class="rounded-full bg-amber-400/20 px-1.5 py-0.5 text-[10px] font-black text-amber-300"
                        >
                            {{ attentionItemsCount }}
                        </span>
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'logs'"
                        class="rounded-xl px-4 py-2 text-xs font-bold transition"
                        :class="activeTab === 'logs' ? 'bg-emerald-600 text-white' : 'bg-[#1e293b] text-[#cbd5e1] hover:text-[#f8fafc]'"
                    >
                        Log Sinkronisasi ({{ recentLogs.length }})
                    </button>
                </div>
            </section>

            <!-- Flash Messages -->
            <div v-if="flashSuccess" role="status" class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-5 py-3.5 text-sm font-semibold text-emerald-300">
                {{ flashSuccess }}
            </div>
            <div v-if="flashError" role="alert" class="rounded-2xl border border-rose-500/40 bg-rose-500/10 px-5 py-3.5 text-sm font-semibold text-rose-300">
                {{ flashError }}
            </div>

            <!-- TAB 1: 22 MODUL SINOVA -->
            <template v-if="activeTab === 'pilot'">
                <!-- Category Filter + Quick Search -->
                <section class="rounded-2xl border border-[#334155] bg-[#0f172a] p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="selectCategory('ALL')"
                                class="rounded-xl border px-3 py-1.5 text-xs font-bold transition"
                                :class="selectedCategory === 'ALL'
                                    ? 'border-emerald-500 bg-emerald-600 text-white'
                                    : 'border-[#334155] bg-[#1e293b] text-[#cbd5e1] hover:text-[#f8fafc]'"
                            >
                                Semua ({{ categoryCounts.ALL || 0 }})
                            </button>
                            <button
                                v-for="cat in categories"
                                :key="cat"
                                type="button"
                                @click="selectCategory(cat)"
                                class="rounded-xl border px-3 py-1.5 text-xs font-bold transition"
                                :class="selectedCategory === cat
                                    ? 'border-emerald-500 bg-emerald-600 text-white'
                                    : 'border-[#334155] bg-[#1e293b] text-[#cbd5e1] hover:text-[#f8fafc]'"
                            >
                                {{ cat }} ({{ categoryCounts[cat] || 0 }})
                            </button>
                        </div>
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Cari modul (contoh: LIPA, DIPA, Renstra, MoU)â€¦"
                            class="w-full rounded-xl border border-[#334155] bg-[#1e293b] px-3.5 py-2 text-xs text-[#f8fafc] lg:w-72"
                        />
                    </div>

                    <!-- Module Cards -->
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <button
                            v-for="mod in filteredModules"
                            :key="mod.slug"
                            type="button"
                            @click="selectModule(mod.slug)"
                            class="flex flex-col justify-between rounded-2xl border p-3.5 text-left transition"
                            :class="selectedSlug === mod.slug
                                ? 'border-emerald-500 bg-emerald-500/10 shadow-lg'
                                : 'border-[#334155] bg-[#1e293b]/60 hover:border-emerald-500/40'"
                        >
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-400">{{ mod.unit }}</span>
                                    <a
                                        :href="mod.public_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        @click.stop
                                        class="rounded-lg border border-[#334155] px-2 py-0.5 text-[10px] font-semibold text-[#cbd5e1] hover:text-emerald-400"
                                    >
                                        Web â†—
                                    </a>
                                </div>
                                <p class="mt-1.5 text-sm font-extrabold leading-snug text-[#f8fafc]">{{ mod.title }}</p>
                            </div>
                            <div class="mt-2.5 flex flex-wrap items-center justify-between gap-1.5 text-[11px]">
                                <span
                                    v-if="moduleHealthMap[mod.slug]"
                                    class="rounded-md border px-1.5 py-0.5 text-[10px] font-bold"
                                    :class="complianceStatusClass(moduleHealthMap[mod.slug].status)"
                                >
                                    {{ moduleHealthMap[mod.slug].badge }}
                                </span>
                                <span v-else class="text-[#94a3b8]">Rumah: {{ mod.start_year }}â€“{{ mod.end_year }}</span>
                                <span class="rounded-md bg-emerald-500/15 px-1.5 py-0.5 font-bold text-emerald-300">
                                    {{ moduleDocCounts[mod.slug] || 0 }} dok
                                </span>
                            </div>
                        </button>
                    </div>
                </section>

                <!-- Year Selector & Slot Matrix -->
                <section class="rounded-[1.5rem] border border-[#334155] bg-[#0f172a] p-5">
                    <div
                        v-if="currentModule && moduleHealthMap[currentModule.slug]"
                        class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-xl border px-3.5 py-2.5 text-xs"
                        :class="complianceStatusClass(moduleHealthMap[currentModule.slug].status)"
                    >
                        <div>
                            <strong class="uppercase tracking-wide">{{ currentModule.short }}:</strong>
                            {{ moduleHealthMap[currentModule.slug].note }}
                        </div>
                        <button
                            type="button"
                            @click="copyEvidenceUrl(currentModule.slug, currentModule.public_url)"
                            class="rounded-lg border border-current/30 bg-black/20 px-2.5 py-1 text-[11px] font-bold hover:bg-black/30"
                        >
                            {{ copiedCode === currentModule.slug ? 'âœ“ URL Tersalin!' : 'Salin URL Halaman Web' }}
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="yr in availableYears"
                                :key="yr"
                                type="button"
                                @click="selectedYear = yr"
                                class="rounded-xl border px-3.5 py-2 text-xs font-extrabold transition"
                                :class="selectedYear === yr
                                    ? 'border-amber-400 bg-emerald-600 text-white'
                                    : 'border-[#334155] bg-[#1e293b] text-[#cbd5e1] hover:text-[#f8fafc]'"
                            >
                                {{ yr }}
                                <span class="ml-1.5 rounded bg-black/20 px-1.5 py-0.5 text-[10px]">
                                    {{ yearStats(yr).filled }}/{{ yearStats(yr).total }}
                                </span>
                                <span v-if="yr === 2027" class="ml-1 text-[10px] text-amber-300">â˜… Rumah</span>
                            </button>
                        </div>
                        <a
                            v-if="currentModule"
                            :href="currentModule.public_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-xs font-bold text-emerald-400 hover:underline"
                        >
                            Lihat Halaman Live ({{ currentModule.short }}) â†—
                        </a>
                    </div>

                    <!-- Period Matrix per Sub-Group -->
                    <div class="mt-6 space-y-6" v-if="currentModule">
                        <div v-for="subGroup in currentModule.sub_groups" :key="subGroup" class="space-y-3">
                            <h2 class="border-b border-[#334155] pb-2 text-sm font-black uppercase tracking-wider text-emerald-400">
                                {{ subGroup }} â€” Tahun {{ selectedYear }}
                            </h2>
                            <div
                                class="grid gap-3"
                                :class="activePeriods.length <= 4
                                    ? 'sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4'
                                    : 'sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6'"
                            >
                                <div
                                    v-for="m in activePeriods"
                                    :key="m.key"
                                    class="flex flex-col justify-between rounded-xl border p-3 transition"
                                    :class="getDoc(selectedYear, subGroup, m.key)
                                        ? 'border-emerald-500/40 bg-emerald-500/5'
                                        : 'border-dashed border-[#334155] bg-[#1e293b]/60'"
                                >
                                    <div>
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="text-sm font-extrabold text-[#f8fafc]">{{ m.label }}</span>
                                            <span
                                                v-if="getDoc(selectedYear, subGroup, m.key)"
                                                class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase"
                                                :class="getDoc(selectedYear, subGroup, m.key).source_type === 'wp_pdf'
                                                    ? 'bg-emerald-500/20 text-emerald-300'
                                                    : 'bg-sky-500/20 text-sky-300'"
                                            >
                                                {{ sourceLabel(getDoc(selectedYear, subGroup, m.key).source_type) }}
                                            </span>
                                            <span v-else class="text-[10px] font-semibold text-[#94a3b8]">Kosong</span>
                                        </div>
                                        <p
                                            v-if="getDoc(selectedYear, subGroup, m.key)?.title && activePeriods.length <= 4"
                                            class="mt-1 line-clamp-2 text-[11px] text-[#cbd5e1]"
                                        >
                                            {{ getDoc(selectedYear, subGroup, m.key).title }}
                                        </p>
                                        <p
                                            v-if="getDoc(selectedYear, subGroup, m.key)?.link_health_note"
                                            class="mt-1.5 inline-block rounded border px-1.5 py-0.5 text-[10px] font-semibold"
                                            :class="healthBadgeClass(getDoc(selectedYear, subGroup, m.key))"
                                        >
                                            {{ getDoc(selectedYear, subGroup, m.key).link_health_note }}
                                        </p>
                                    </div>

                                    <div class="mt-3 flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openSlotModal(subGroup, m)"
                                            class="flex-1 rounded-lg px-2.5 py-1.5 text-[11px] font-bold transition"
                                            :class="getDoc(selectedYear, subGroup, m.key)
                                                ? 'border border-[#334155] bg-[#0f172a] text-[#f8fafc] hover:border-emerald-500'
                                                : 'bg-emerald-600 text-white hover:bg-emerald-500'"
                                        >
                                            {{ getDoc(selectedYear, subGroup, m.key) ? 'Ubah' : '+ Isi' }}
                                        </button>
                                        <a
                                            v-if="getDoc(selectedYear, subGroup, m.key)"
                                            :href="getDoc(selectedYear, subGroup, m.key).doc_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="rounded-lg border border-[#334155] px-2 py-1.5 text-[11px] font-bold text-emerald-400 hover:bg-emerald-500/10"
                                            title="Buka dokumen"
                                        >
                                            â†—
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </template>

            <!-- TAB 2: DASHBOARD 35 KRITERIA LAMPIRAN II SINOVA (B1.1 - W4.1) -->
            <template v-else-if="activeTab === 'roadmap'">
                <section class="rounded-2xl border border-[#334155] bg-[#0f172a] p-5 space-y-4">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <h2 class="text-lg font-black text-[#f8fafc]">
                                Checklist Otomatis 35 Kriteria Lampiran II SK 2722/2026
                            </h2>
                            <p class="text-xs text-[#94a3b8]">
                                Evaluasi otomatis kelengkapan bulan berjalan, arsip 5 tahunan SAKIP, serta tombol salin URL kanonik untuk pengisian SINOVA Badilag.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="copyAllEvidenceUrls"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-400/50 bg-amber-500/15 px-4 py-2 text-xs font-extrabold text-amber-200 transition hover:bg-amber-500/25"
                        >
                            <span>{{ copiedAll ? 'âœ“ Seluruh 35 URL Tersalin ke Clipboard!' : 'Salin Semua 35 URL Bukti SINOVA' }}</span>
                        </button>
                    </div>

                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="flt in [
                                    { k: 'ALL', l: `Semua (${complianceSummary.total_criteria || 35})` },
                                    { k: 'berkala', l: 'A.1 Berkala (24)' },
                                    { k: 'wajib', l: 'A.2 Wajib (11)' },
                                    { k: 'ok', l: `Lengkap (${complianceSummary.ok_count || 0})` },
                                    { k: 'attention', l: `Perlu Perhatian (${attentionItemsCount})` },
                                ]"
                                :key="flt.k"
                                type="button"
                                @click="complianceFilter = flt.k"
                                class="rounded-xl border px-3 py-1.5 text-xs font-bold transition"
                                :class="complianceFilter === flt.k
                                    ? 'border-emerald-500 bg-emerald-600 text-white'
                                    : 'border-[#334155] bg-[#1e293b] text-[#cbd5e1] hover:text-[#f8fafc]'"
                            >
                                {{ flt.l }}
                            </button>
                        </div>
                        <input
                            v-model="complianceSearch"
                            type="search"
                            placeholder="Cari kode (B2.5, W1.1), kriteria, atau unitâ€¦"
                            class="w-full rounded-xl border border-[#334155] bg-[#1e293b] px-3.5 py-2 text-xs text-[#f8fafc] lg:w-72"
                        />
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-[#334155]">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-[#1e293b] text-[11px] uppercase tracking-wider text-[#94a3b8]">
                                <tr>
                                    <th class="px-3.5 py-3">Kode</th>
                                    <th class="px-3.5 py-3">Kriteria &amp; Diagnostik Otomatis</th>
                                    <th class="px-3.5 py-3">Status &amp; Poin</th>
                                    <th class="px-3.5 py-3">URL Bukti SINOVA</th>
                                    <th class="px-3.5 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#334155]">
                                <tr
                                    v-for="item in filteredComplianceItems"
                                    :key="item.code"
                                    class="hover:bg-[#1e293b]/40 transition"
                                >
                                    <td class="px-3.5 py-3 align-top whitespace-nowrap">
                                        <span class="inline-block rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-2 py-1 font-black text-emerald-300">
                                            {{ item.code }}
                                        </span>
                                        <p class="mt-1 text-[10px] font-semibold text-[#94a3b8]">
                                            {{ item.group === 'berkala' ? 'Berkala 40%' : 'Wajib 20%' }}
                                        </p>
                                    </td>
                                    <td class="px-3.5 py-3 align-top">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-extrabold text-[#f8fafc]">{{ item.label }}</span>
                                            <span class="rounded bg-[#1e293b] px-1.5 py-0.5 text-[10px] font-bold text-[#94a3b8]">
                                                {{ item.unit }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-[11px] text-[#cbd5e1]">{{ item.note }}</p>
                                        <div v-if="item.years_covered?.length" class="mt-1.5 flex flex-wrap gap-1">
                                            <span
                                                v-for="yr in item.years_covered"
                                                :key="yr"
                                                class="rounded bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-300"
                                            >
                                                {{ yr }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3.5 py-3 align-top whitespace-nowrap">
                                        <span
                                            class="inline-block rounded-full border px-2.5 py-1 text-[10px] font-black uppercase"
                                            :class="complianceStatusClass(item.status)"
                                        >
                                            {{ complianceStatusLabel(item.status) }}
                                        </span>
                                    </td>
                                    <td class="px-3.5 py-3 align-top">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="copyEvidenceUrl(item.code, item.evidence_url)"
                                                class="rounded-lg border px-2.5 py-1.5 text-[11px] font-bold transition"
                                                :class="copiedCode === item.code
                                                    ? 'border-emerald-400 bg-emerald-600 text-white'
                                                    : 'border-[#334155] bg-[#1e293b] text-[#f8fafc] hover:border-emerald-500'"
                                                :title="item.evidence_url"
                                            >
                                                {{ copiedCode === item.code ? 'âœ“ Tersalin!' : 'Salin URL' }}
                                            </button>
                                            <a
                                                :href="item.evidence_url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="rounded-lg border border-[#334155] px-2 py-1.5 text-[11px] font-bold text-emerald-400 hover:bg-emerald-500/10"
                                            >
                                                Buka â†—
                                            </a>
                                        </div>
                                        <p class="mt-1 max-w-[260px] truncate text-[10px] text-[#94a3b8]" :title="item.evidence_url">
                                            {{ item.evidence_url }}
                                        </p>
                                    </td>
                                    <td class="px-3.5 py-3 align-top text-right whitespace-nowrap">
                                        <button
                                            v-if="item.module_slug"
                                            type="button"
                                            @click="jumpToModule(item.module_slug)"
                                            class="rounded-lg px-3 py-1.5 text-[11px] font-bold transition"
                                            :class="item.status === 'ok'
                                                ? 'border border-[#334155] bg-[#1e293b] text-[#f8fafc] hover:border-emerald-500'
                                                : 'bg-amber-500 text-slate-950 hover:bg-amber-400'"
                                        >
                                            {{ item.status === 'ok' ? 'Kelola Slot' : 'Lengkapi Slot' }}
                                        </button>
                                        <span v-else class="text-[10px] font-semibold text-[#94a3b8]">
                                            Halaman #{{ item.post_id }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!filteredComplianceItems.length">
                                    <td colspan="5" class="py-6 text-center text-[#94a3b8]">
                                        Tidak ada kriteria yang cocok dengan filter pencarian.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <details class="rounded-xl border border-[#334155] bg-[#1e293b]/50 p-4">
                        <summary class="cursor-pointer text-xs font-extrabold text-[#f8fafc]">
                            Lihat Pemetaan Pengelola per Bagian (4 Subbag / Kepaniteraan Â· {{ modules.length }} Modul + Portal Khusus)
                        </summary>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <section
                                v-for="grp in roadmapGroups"
                                :key="grp.category"
                                class="rounded-xl border border-[#334155] bg-[#0f172a] p-4"
                            >
                                <div class="border-b border-[#334155] pb-2">
                                    <h3 class="text-sm font-extrabold text-[#f8fafc]">{{ grp.category }}</h3>
                                    <p class="text-[11px] text-[#94a3b8]">Penanggung Jawab: {{ grp.owner }}</p>
                                </div>
                                <ul class="mt-2.5 space-y-1.5">
                                    <li
                                        v-for="ritem in grp.items"
                                        :key="ritem.slug"
                                        class="flex items-center justify-between gap-2 rounded-lg border border-[#334155] bg-[#1e293b] px-3 py-2 text-[11px]"
                                    >
                                        <span class="font-bold text-[#f8fafc]">{{ ritem.label }} <span class="text-[#94a3b8]">(#{{ ritem.post_id }})</span></span>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase"
                                            :class="ritem.status.includes('Aktif') ? 'bg-emerald-500/20 text-emerald-300' : 'bg-sky-500/20 text-sky-300'"
                                        >
                                            {{ ritem.status }}
                                        </span>
                                    </li>
                                </ul>
                            </section>
                        </div>
                    </details>
                </section>
            </template>

            <!-- TAB 3: AUDIT LOG -->
            <template v-else>
                <section class="rounded-2xl border border-[#334155] bg-[#0f172a] p-5 overflow-x-auto">
                    <h2 class="mb-3 text-base font-extrabold text-[#f8fafc]">Riwayat Sinkronisasi Website</h2>
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#334155] text-[#94a3b8]">
                                <th class="py-2 pr-3">Waktu</th>
                                <th class="py-2 pr-3">Petugas</th>
                                <th class="py-2 pr-3">Aksi</th>
                                <th class="py-2 pr-3">Target</th>
                                <th class="py-2 pr-3">Sumber</th>
                                <th class="py-2">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in recentLogs" :key="log.id" class="border-b border-[#334155]/50">
                                <td class="py-2.5 pr-3 text-[#94a3b8]">{{ log.created_at }}</td>
                                <td class="py-2.5 pr-3 font-semibold text-[#f8fafc]">{{ log.user_name }}</td>
                                <td class="py-2.5 pr-3">
                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-bold" :class="log.sync_ok ? 'bg-emerald-500/15 text-emerald-300' : 'bg-rose-500/15 text-rose-300'">
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="py-2.5 pr-3 text-[#cbd5e1]">
                                    {{ log.page_slug }} {{ log.year || '' }} {{ log.sub_group || '' }} {{ log.period_key || '' }}
                                </td>
                                <td class="py-2.5 pr-3 text-[#94a3b8]">{{ log.source_type || '-' }}</td>
                                <td class="py-2.5 text-[#cbd5e1]">{{ log.message }}</td>
                            </tr>
                            <tr v-if="!recentLogs.length">
                                <td colspan="6" class="py-6 text-center text-[#94a3b8]">Belum ada riwayat perubahan dari Portal Internal.</td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </template>
        </div>

        <!-- Modal Upload PDF / Paste Link -->
        <div
            v-if="modalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
            @click.self="closeModal"
        >
            <div class="w-full max-w-lg rounded-2xl border border-[#334155] bg-[#0f172a] p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wider text-emerald-400">
                            {{ currentModule?.short }} Â· {{ activeSlot.subGroup }}
                        </p>
                        <h3 class="mt-1 text-lg font-extrabold text-[#f8fafc]">
                            {{ activeSlot.periodLabel }} {{ selectedYear }}
                        </h3>
                    </div>
                    <button type="button" @click="closeModal" class="rounded-lg border border-[#334155] px-2.5 py-1 text-xs font-bold text-[#94a3b8]">âœ•</button>
                </div>

                <form @submit.prevent="submitSlot" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#cbd5e1] mb-1.5">Metode Publikasi</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="form.source_type = 'wp_pdf'"
                                class="rounded-xl border px-3 py-2 text-xs font-bold transition"
                                :class="form.source_type === 'wp_pdf' ? 'border-emerald-500 bg-emerald-500/15 text-emerald-300' : 'border-[#334155] text-[#cbd5e1]'"
                            >
                                Upload PDF Langsung (Disarankan)
                            </button>
                            <button
                                type="button"
                                @click="form.source_type = 'gdrive'"
                                class="rounded-xl border px-3 py-2 text-xs font-bold transition"
                                :class="form.source_type !== 'wp_pdf' ? 'border-sky-500 bg-sky-500/15 text-sky-300' : 'border-[#334155] text-[#cbd5e1]'"
                            >
                                Tautan Google Drive / URL
                            </button>
                        </div>
                    </div>

                    <div v-if="form.source_type === 'wp_pdf'">
                        <label class="block text-xs font-bold text-[#cbd5e1] mb-1.5">File PDF (Maks. 10 MB)</label>
                        <input
                            type="file"
                            accept="application/pdf,.pdf"
                            @change="handleFileChange"
                            class="block w-full rounded-xl border border-[#334155] bg-[#1e293b] p-2.5 text-xs text-[#f8fafc]"
                            required
                        />
                        <p class="mt-1 text-[11px] text-[#94a3b8]">
                            File otomatis tersimpan aman di hosting <code>website satker</code> sehingga bebas error izin Google Drive.
                        </p>
                        <p v-if="form.errors.pdf_file" class="mt-1 text-xs text-rose-400">{{ form.errors.pdf_file }}</p>
                    </div>

                    <div v-else>
                        <label class="block text-xs font-bold text-[#cbd5e1] mb-1.5">URL Google Drive / Tautan Eksternal (HTTPS)</label>
                        <input
                            v-model="form.doc_url"
                            type="url"
                            placeholder="https://drive.google.com/file/d/.../view?usp=sharing"
                            class="w-full rounded-xl border border-[#334155] bg-[#1e293b] px-3 py-2 text-xs text-[#f8fafc]"
                            required
                        />
                        <p class="mt-1 text-[11px] text-[#94a3b8]">
                            Pastikan akses Google Drive disetel <em>Anyone with the link</em>. Sistem otomatis mengecek akses publik setelah disimpan.
                        </p>
                        <p v-if="form.errors.doc_url" class="mt-1 text-xs text-rose-400">{{ form.errors.doc_url }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#cbd5e1] mb-1.5">Label / Judul Dokumen</label>
                        <input
                            v-model="form.title"
                            type="text"
                            maxlength="255"
                            class="w-full rounded-xl border border-[#334155] bg-[#1e293b] px-3 py-2 text-xs text-[#f8fafc]"
                        />
                    </div>

                    <div class="flex items-center justify-between gap-2 border-t border-[#334155] pt-4">
                        <button
                            v-if="activeSlot.existing"
                            type="button"
                            @click="deleteSlot"
                            class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-3.5 py-2 text-xs font-bold text-rose-300 hover:bg-rose-500/20"
                        >
                            Hapus Slot
                        </button>
                        <span v-else></span>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="closeModal" class="rounded-xl border border-[#334155] px-4 py-2 text-xs font-bold text-[#cbd5e1]">
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-500 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Menyimpan & Sinkronâ€¦' : 'Simpan & Tayangkan ke Website' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div></div>
</template>
