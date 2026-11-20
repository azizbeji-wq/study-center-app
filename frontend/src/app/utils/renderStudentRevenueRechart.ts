import * as React from 'react';
import { createRoot, type Root } from 'react-dom/client';
import {
  ResponsiveContainer,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ReferenceLine,
  Cell,
} from 'recharts';

export interface StudentRevenueChartPoint {
  month: string;
  label: string;
  students: number;
  paymentCount?: number;
  daycare?: number;
}

export interface RenderStudentRevenueOptions {
  data: StudentRevenueChartPoint[];
  height?: number;
  showAverage?: boolean;
  averageValue?: number;
}

function formatTnd(value: number): string {
  return `${Math.round((value + Number.EPSILON) * 100) / 100} DT`;
}

function formatTndLocale(value: number): string {
  return `${Number(value || 0).toLocaleString('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })} DT`;
}

function formatYAxisTick(value: number): string {
  if (value >= 1000) {
    const k = value / 1000;
    return `${Number.isInteger(k) ? k : k.toFixed(1)}k`;
  }
  return `${value}`;
}

interface CustomTooltipProps {
  active?: boolean;
  payload?: ReadonlyArray<{
    value?: unknown;
    payload?: StudentRevenueChartPoint;
  }>;
  label?: string | number;
}

function RevenueTooltipContent(props: CustomTooltipProps) {
  if (!props.active || !props.payload || props.payload.length === 0) {
    return null;
  }

  const point = props.payload[0]?.payload;
  if (!point) return null;

  const amount = Number(point.students || 0);
  const count = point.paymentCount;

  return React.createElement(
    'div',
    {
      style: {
        backgroundColor: '#0f172a',
        color: '#f8fafc',
        borderRadius: '10px',
        padding: '10px 12px',
        boxShadow: '0 10px 25px -5px rgba(15, 23, 42, 0.28)',
        border: '1px solid rgba(148, 163, 184, 0.2)',
        minWidth: '170px',
        fontVariantNumeric: 'tabular-nums',
      },
    },
    React.createElement(
      'div',
      {
        style: {
          fontSize: '12px',
          fontWeight: 700,
          color: '#e2e8f0',
          marginBottom: '6px',
        },
      },
      point.label
    ),
    React.createElement(
      'div',
      {
        style: {
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          gap: '12px',
          fontSize: '13px',
        },
      },
      React.createElement(
        'span',
        { style: { color: '#94a3b8', fontWeight: 500 } },
        'Paiements élèves'
      ),
      React.createElement(
        'span',
        { style: { color: '#ffffff', fontWeight: 800 } },
        formatTndLocale(amount)
      )
    ),
    typeof count === 'number'
      ? React.createElement(
          'div',
          {
            style: {
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              gap: '12px',
              fontSize: '11px',
              marginTop: '4px',
              paddingTop: '4px',
              borderTop: '1px solid rgba(148, 163, 184, 0.16)',
              color: '#cbd5e1',
            },
          },
          React.createElement('span', null, 'Transactions réglées'),
          React.createElement(
            'span',
            { style: { fontWeight: 700 } },
            `${count} paiement${count > 1 ? 's' : ''}`
          )
        )
      : null
  );
}

function StudentRevenueChartElement(options: RenderStudentRevenueOptions) {
  const { data, height = 280, showAverage = true, averageValue = 0 } = options;

  const maxAmount = data.reduce((max, item) => Math.max(max, Number(item.students || 0)), 0);
  const lastIndex = data.length - 1;

  const barChartElement = React.createElement(
    BarChart,
    {
      data,
      margin: { top: 14, right: 12, left: -6, bottom: 4 },
      barCategoryGap: '26%',
    },
    React.createElement(CartesianGrid, {
      strokeDasharray: '3 3',
      vertical: false,
      stroke: '#e2e8f0',
    }),
    React.createElement(XAxis, {
      dataKey: 'label',
      axisLine: false,
      tickLine: false,
      tick: { fill: '#64748b', fontSize: 11, fontWeight: 500 },
      dy: 6,
    }),
    React.createElement(YAxis, {
      axisLine: false,
      tickLine: false,
      width: 52,
      tickFormatter: formatYAxisTick,
      tick: { fill: '#64748b', fontSize: 11, fontWeight: 500 },
      unit: ' DT',
    }),
    React.createElement(Tooltip, {
      cursor: { fill: 'rgba(79, 70, 229, 0.06)' },
      content: React.createElement(RevenueTooltipContent),
    }),
    showAverage && averageValue > 0
      ? React.createElement(ReferenceLine, {
          y: averageValue,
          stroke: '#94a3b8',
          strokeDasharray: '4 4',
          strokeWidth: 1.5,
          label: {
            value: `Moy. ${formatTnd(averageValue)}`,
            position: 'insideTopRight',
            fill: '#64748b',
            fontSize: 10,
            fontWeight: 600,
          },
        })
      : null,
    React.createElement(
      Bar,
      {
        dataKey: 'students',
        name: 'Revenus élèves',
        radius: [6, 6, 0, 0],
        maxBarSize: 44,
        animationDuration: 350,
      },
      data.map((entry, idx) => {
        const val = Number(entry.students || 0);
        const isPeak = maxAmount > 0 && val === maxAmount;
        const isCurrent = idx === lastIndex;
        let fill = '#6366f1';
        if (isPeak) fill = '#4338ca';
        else if (isCurrent && val > 0) fill = '#4f46e5';
        else if (val === 0) fill = '#e2e8f0';

        return React.createElement(Cell, {
          key: `bar-cell-${entry.month || idx}`,
          fill,
        });
      })
    )
  );

  return React.createElement(ResponsiveContainer, {
    width: '100%',
    height,
    children: barChartElement,
  });
}

export class RechartsBarChartController {
  private root: Root | null = null;

  mount(container: HTMLElement, options: RenderStudentRevenueOptions): void {
    if (!this.root) {
      this.root = createRoot(container);
    }
    this.root.render(React.createElement(StudentRevenueChartElement, options));
  }

  update(options: RenderStudentRevenueOptions): void {
    if (!this.root) return;
    this.root.render(React.createElement(StudentRevenueChartElement, options));
  }

  unmount(): void {
    if (this.root) {
      this.root.unmount();
      this.root = null;
    }
  }
}
