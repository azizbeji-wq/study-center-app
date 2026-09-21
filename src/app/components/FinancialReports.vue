<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h2 class="text-2xl text-gray-900">Comptabilité et Rapports</h2>
      <p class="text-sm text-gray-500 mt-1">Vue d'ensemble financière</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white border border-gray-200 rounded-lg p-5">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm text-gray-600">Revenus Mai</p>
          <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
            <DollarSign class="w-4 h-4 text-indigo-600" />
          </div>
        </div>
        <p class="text-2xl text-gray-900">{{ currentMonthRevenue }} TND</p>
        <div class="flex items-center gap-1 mt-2">
          <TrendingUp class="w-4 h-4 text-green-600" />
          <span class="text-sm text-green-600">+{{ growth }}%</span>
        </div>
      </div>

      <div class="bg-white border border-gray-200 rounded-lg p-5">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm text-gray-600">Dépenses</p>
          <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
            <TrendingDown class="w-4 h-4 text-red-600" />
          </div>
        </div>
        <p class="text-2xl text-gray-900">{{ totalExpenses }} TND</p>
        <p class="text-sm text-gray-500 mt-2">Ce mois</p>
      </div>

      <div class="bg-white border border-gray-200 rounded-lg p-5">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm text-gray-600">Profit Net</p>
          <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
            <DollarSign class="w-4 h-4 text-green-600" />
          </div>
        </div>
        <p class="text-2xl text-gray-900">{{ netProfit }} TND</p>
        <p class="text-sm text-gray-500 mt-2">Mai 2026</p>
      </div>

      <div class="bg-white border border-gray-200 rounded-lg p-5">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm text-gray-600">Élèves Actifs</p>
          <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
            <Users class="w-4 h-4 text-blue-600" />
          </div>
        </div>
        <p class="text-2xl text-gray-900">28</p>
        <p class="text-sm text-gray-500 mt-2">Total inscrits</p>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Revenue Trend (Line Chart) -->
      <div class="bg-white border border-gray-200 rounded-lg p-5">
        <h3 class="text-lg text-gray-900 mb-4">Évolution des Revenus</h3>
        <div class="relative w-full h-[250px]">
          <svg class="w-full h-full" viewBox="0 0 500 250" preserveAspectRatio="none">
            <!-- Grid Lines -->
            <line x1="50" y1="50" x2="480" y2="50" stroke="#E5E7EB" stroke-dasharray="3 3" />
            <line x1="50" y1="100" x2="480" y2="100" stroke="#E5E7EB" stroke-dasharray="3 3" />
            <line x1="50" y1="150" x2="480" y2="150" stroke="#E5E7EB" stroke-dasharray="3 3" />
            <line x1="50" y1="200" x2="480" y2="200" stroke="#E5E7EB" stroke-dasharray="3 3" />
            
            <!-- Y Axis Labels -->
            <text x="15" y="54" fill="#6B7280" font-size="12">4k</text>
            <text x="15" y="104" fill="#6B7280" font-size="12">3k</text>
            <text x="15" y="154" fill="#6B7280" font-size="12">2k</text>
            <text x="15" y="204" fill="#6B7280" font-size="12">1k</text>

            <!-- Line Path -->
            <path
              :d="linePathD"
              fill="none"
              stroke="#4F46E5"
              stroke-width="3"
            />

            <!-- Circles and Tooltips -->
            <g v-for="(point, idx) in linePoints" :key="idx" class="group/point cursor-pointer">
              <circle
                :cx="point.x"
                :cy="point.y"
                r="6"
                fill="#4F46E5"
                stroke="#FFF"
                stroke-width="2"
              />
              <circle
                :cx="point.x"
                :cy="point.y"
                r="12"
                fill="transparent"
                class="hover:fill-[#4F46E5]/10 transition-colors"
              />
              <!-- Tooltip on hover -->
              <title>{{ point.label }} : {{ point.value }} TND</title>
            </g>

            <!-- X Axis Labels -->
            <text v-for="(point, idx) in linePoints" :key="'lbl-' + idx" :x="point.x - 10" y="235" fill="#6B7280" font-size="12">{{ point.label }}</text>
          </svg>
        </div>
      </div>

      <!-- Expense Breakdown (Pie/Donut Chart) -->
      <div class="bg-white border border-gray-200 rounded-lg p-5">
        <h3 class="text-lg text-gray-900 mb-4">Répartition des Dépenses</h3>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-6 h-[250px]">
          <!-- Donut chart -->
          <div class="relative w-40 h-40">
            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
              <circle
                v-for="(slice, idx) in donutSlices"
                :key="idx"
                cx="50"
                cy="50"
                r="40"
                fill="transparent"
                :stroke="slice.color"
                stroke-width="15"
                :stroke-dasharray="`${slice.strokeLength} ${100 * Math.PI}`"
                :stroke-dashoffset="slice.strokeOffset"
                class="transition-all duration-500"
              >
                <title>{{ slice.name }} : {{ slice.value }} TND ({{ slice.percentage }}%)</title>
              </circle>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-xs text-gray-500">Total</span>
              <span class="text-sm text-gray-900 font-semibold">{{ totalExpenses }} TND</span>
            </div>
          </div>

          <!-- Legend -->
          <div class="grid grid-cols-2 sm:grid-cols-1 gap-2">
            <div v-for="item in expenseData" :key="item.id" class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: item.color }"></span>
              <span class="text-xs text-gray-600">{{ item.name }}: {{ item.value }} TND</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Monthly Comparison (Bar Chart) -->
    <div class="bg-white border border-gray-200 rounded-lg p-5">
      <h3 class="text-lg text-gray-900 mb-4">Comparaison Mensuelle</h3>
      <div class="relative w-full h-[300px]">
        <svg class="w-full h-full" viewBox="0 0 500 300" preserveAspectRatio="none">
          <!-- Grid Lines -->
          <line x1="50" y1="50" x2="480" y2="50" stroke="#E5E7EB" stroke-dasharray="3 3" />
          <line x1="50" y1="110" x2="480" y2="110" stroke="#E5E7EB" stroke-dasharray="3 3" />
          <line x1="50" y1="170" x2="480" y2="170" stroke="#E5E7EB" stroke-dasharray="3 3" />
          <line x1="50" y1="230" x2="480" y2="230" stroke="#E5E7EB" stroke-dasharray="3 3" />
          
          <!-- Y Axis Labels -->
          <text x="15" y="54" fill="#6B7280" font-size="12">4k</text>
          <text x="15" y="114" fill="#6B7280" font-size="12">3k</text>
          <text x="15" y="174" fill="#6B7280" font-size="12">2k</text>
          <text x="15" y="234" fill="#6B7280" font-size="12">1k</text>

          <!-- Bars -->
          <g v-for="(bar, idx) in barPoints" :key="idx" class="group/bar cursor-pointer">
            <rect
              :x="bar.x"
              :y="bar.y"
              :width="bar.width"
              :height="bar.height"
              fill="#4F46E5"
              rx="4"
              class="hover:fill-[#6366F1] transition-colors"
            />
            <title>{{ bar.label }} : {{ bar.value }} TND</title>
          </g>

          <!-- X Axis Labels -->
          <text v-for="(bar, idx) in barPoints" :key="'lbl-bar-' + idx" :x="bar.x + (bar.width / 2) - 10" y="275" fill="#6B7280" font-size="12">{{ bar.label }}</text>
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { TrendingUp, TrendingDown, DollarSign, Users } from 'lucide-vue-next';
import api from '../utils/api';

const monthlyRevenue = [
  { month: 'Jan', revenue: 3200 },
  { month: 'Fév', revenue: 3500 },
  { month: 'Mar', revenue: 3800 },
  { month: 'Avr', revenue: 3600 },
  { month: 'Mai', revenue: 4200 },
];

const expenseData = [
  { id: 'salaires', name: 'Salaires', value: 2500, color: '#4F46E5' },
  { id: 'loyer', name: 'Loyer', value: 800, color: '#06B6D4' },
  { id: 'fournitures', name: 'Fournitures', value: 300, color: '#10B981' },
  { id: 'electricite', name: 'Électricité', value: 200, color: '#F59E0B' },
  { id: 'autres', name: 'Autres', value: 300, color: '#EF4444' },
];

const currentMonthRevenue = ref(4200);
const totalStudents = ref(28);

onMounted(async () => {
  try {
    const res = await api.get('/api/stats');
    if (res.data?.stats?.totalRevenue) {
      currentMonthRevenue.value = Number(res.data.stats.totalRevenue) || 4200;
    }
    if (res.data?.stats?.totalStudents) {
      totalStudents.value = Number(res.data.stats.totalStudents);
    }
  } catch (e) {
    console.error('Erreur chargement rapports:', e);
  }
});

const previousMonthRevenue = 3600;
const growth = computed(() => (((currentMonthRevenue.value - previousMonthRevenue) / previousMonthRevenue) * 100).toFixed(1));
const totalExpenses = expenseData.reduce((sum, item) => sum + item.value, 0);
const netProfit = computed(() => currentMonthRevenue.value - totalExpenses);

// Points generation for Line Chart
const linePoints = computed(() => {
  const minX = 60;
  const maxX = 460;
  const minY = 200;
  const maxY = 50;
  const maxVal = 5000;

  return monthlyRevenue.map((d, index) => {
    const x = minX + (index * (maxX - minX)) / (monthlyRevenue.length - 1);
    const y = minY - (d.revenue / maxVal) * (minY - maxY);
    return { x, y, label: d.month, value: d.revenue };
  });
});

const linePathD = computed(() => {
  if (linePoints.value.length === 0) return '';
  return linePoints.value.reduce((acc, p, index) => {
    if (index === 0) return `M ${p.x} ${p.y}`;
    return `${acc} L ${p.x} ${p.y}`;
  }, '');
});

// Donut slices calculations
const donutSlices = computed(() => {
  const total = expenseData.reduce((sum, item) => sum + item.value, 0);
  const circ = 2 * Math.PI * 40;
  let currentOffset = 0;

  return expenseData.map((d) => {
    const percentage = ((d.value / total) * 100).toFixed(1);
    const strokeLength = (d.value / total) * circ;
    const strokeOffset = -currentOffset;
    currentOffset += strokeLength;

    return {
      name: d.name,
      value: d.value,
      color: d.color,
      percentage,
      strokeLength,
      strokeOffset
    };
  });
});

// Points generation for Bar Chart
const barPoints = computed(() => {
  const minX = 60;
  const maxX = 460;
  const minY = 250;
  const maxY = 50;
  const maxVal = 5000;
  const barWidth = 35;

  const step = (maxX - minX) / (monthlyRevenue.length - 1);

  return monthlyRevenue.map((d, index) => {
    const x = minX + index * step - barWidth / 2;
    const height = (d.revenue / maxVal) * (minY - maxY);
    const y = minY - height;
    return { x, y, width: barWidth, height, label: d.month, value: d.revenue };
  });
});
</script>
