<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { BarChart3 } from 'lucide-vue-next';
import {
  RechartsBarChartController,
  type StudentRevenueChartPoint,
} from '../../utils/renderStudentRevenueRechart';

const props = withDefaults(
  defineProps<{
    data: StudentRevenueChartPoint[];
    title?: string;
    subtitle?: string;
    height?: number;
    showAverage?: boolean;
    allowRangeToggle?: boolean;
    rangeMonths?: 6 | 12;
  }>(),
  {
    title: 'Revenus Mensuels — Paiements Élèves',
    subtitle: 'Tendances mensuelles des encaissements élèves réglés',
    height: 280,
    showAverage: true,
    allowRangeToggle: false,
    rangeMonths: 6,
  }
);

const emit = defineEmits<{
  (e: 'update:rangeMonths', value: 6 | 12): void;
}>();

const chartContainerRef = ref<HTMLElement | null>(null);
const controller = new RechartsBarChartController();

const totalRevenue = computed(() =>
  Math.round(
    props.data.reduce((sum, item) => sum + Number(item.students || 0), 0) * 100
  ) / 100
);

const activeMonthsCount = computed(
  () => props.data.filter((item) => Number(item.students || 0) > 0).length
);

const averageMonthlyRevenue = computed(() => {
  if (activeMonthsCount.value === 0) return 0;
  return Math.round((totalRevenue.value / activeMonthsCount.value) * 100) / 100;
});

const peakMonth = computed<StudentRevenueChartPoint | null>(() => {
  if (props.data.length === 0) return null;
  return props.data.reduce<StudentRevenueChartPoint | null>((best, current) => {
    if (!best || Number(current.students || 0) > Number(best.students || 0)) {
      return current;
    }
    return best;
  }, null);
});

const hasAnyRevenue = computed(() => totalRevenue.value > 0);

function renderChart() {
  if (!chartContainerRef.value) return;
  if (!props.data.length) {
    controller.unmount();
    return;
  }
  controller.mount(chartContainerRef.value, {
    data: props.data,
    height: props.height,
    showAverage: props.showAverage,
    averageValue: averageMonthlyRevenue.value,
  });
}

onMounted(() => {
  renderChart();
});

watch(
  () => [props.data, props.height, props.showAverage, averageMonthlyRevenue.value],
  () => {
    renderChart();
  },
  { deep: true }
);

onBeforeUnmount(() => {
  controller.unmount();
});

function formatCurrency(val: number): string {
  return Number(val || 0).toLocaleString('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
}
</script>

<template>
  <section class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5">
      <div class="min-w-0">
        <div class="flex items-center gap-2">
          <BarChart3 class="w-4 h-4 text-indigo-600 shrink-0" />
          <h3 class="text-base sm:text-lg font-bold text-gray-900 truncate">{{ title }}</h3>
        </div>
        <p class="text-xs text-gray-500 mt-0.5">{{ subtitle }}</p>
      </div>

      <div class="flex flex-wrap items-center gap-5 sm:gap-6">
        <!-- Summary Metrics (tabular-nums, unboxed clean typography) -->
        <div class="flex items-center gap-5 text-xs tabular-nums">
          <div>
            <p class="text-gray-400 font-medium">Total période</p>
            <p class="text-sm font-bold text-gray-900">{{ formatCurrency(totalRevenue) }} DT</p>
          </div>
          <div class="h-7 w-px bg-gray-200"></div>
          <div>
            <p class="text-gray-400 font-medium">Moyenne / mois actif</p>
            <p class="text-sm font-bold text-indigo-700">{{ formatCurrency(averageMonthlyRevenue) }} DT</p>
          </div>
          <template v-if="peakMonth && Number(peakMonth.students || 0) > 0">
            <div class="hidden sm:block h-7 w-px bg-gray-200"></div>
            <div class="hidden sm:block">
              <p class="text-gray-400 font-medium">Pic ({{ peakMonth.label }})</p>
              <p class="text-sm font-bold text-emerald-700">{{ formatCurrency(peakMonth.students) }} DT</p>
            </div>
          </template>
        </div>

        <!-- Optional 6/12 Months Segmented Filter -->
        <div
          v-if="allowRangeToggle"
          class="inline-flex items-center gap-1 p-1 bg-gray-100 rounded-lg shrink-0"
          role="group"
          aria-label="Période du graphique"
        >
          <button
            type="button"
            @click="emit('update:rangeMonths', 6)"
            :class="[
              'px-3 py-1.5 text-xs font-semibold rounded-md transition-colors whitespace-nowrap shrink-0 cursor-pointer',
              rangeMonths === 6
                ? 'bg-white text-gray-900 shadow-xs'
                : 'text-gray-600 hover:text-gray-900',
            ]"
          >
            6 mois
          </button>
          <button
            type="button"
            @click="emit('update:rangeMonths', 12)"
            :class="[
              'px-3 py-1.5 text-xs font-semibold rounded-md transition-colors whitespace-nowrap shrink-0 cursor-pointer',
              rangeMonths === 12
                ? 'bg-white text-gray-900 shadow-xs'
                : 'text-gray-600 hover:text-gray-900',
            ]"
          >
            12 mois
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="data.length === 0 || !hasAnyRevenue"
      class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-gray-50/70 px-4 py-12 text-center"
      :style="{ minHeight: `${height}px` }"
    >
      <BarChart3 class="w-8 h-8 text-gray-300 mb-2" />
      <p class="text-sm font-semibold text-gray-700">Aucun paiement élève encaissé sur cette période</p>
      <p class="text-xs text-gray-500 mt-1 max-w-sm">
        Les tendances mensuelles apparaîtront automatiquement dès qu'un paiement élève réglé sera enregistré.
      </p>
    </div>

    <!-- Recharts Mount Target -->
    <div
      v-show="data.length > 0 && hasAnyRevenue"
      ref="chartContainerRef"
      class="w-full"
      :style="{ minHeight: `${height}px` }"
    ></div>

    <!-- Clean Inline Legend -->
    <div
      v-if="data.length > 0 && hasAnyRevenue"
      class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500"
    >
      <div class="flex flex-wrap items-center gap-4">
        <span class="inline-flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-xs bg-indigo-600 inline-block"></span>
          <span>Paiements élèves encaissés</span>
        </span>
        <span class="inline-flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-xs bg-indigo-800 inline-block"></span>
          <span>Mois record</span>
        </span>
        <span v-if="showAverage && averageMonthlyRevenue > 0" class="inline-flex items-center gap-1.5">
          <span class="w-4 border-t-2 border-dashed border-slate-400 inline-block"></span>
          <span>Moyenne mensuelle</span>
        </span>
      </div>
      <span class="tabular-nums">{{ data.length }} mois affichés</span>
    </div>
  </section>
</template>
