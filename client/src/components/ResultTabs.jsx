import clsx from 'clsx';
import { FiTrendingUp, FiZap } from 'react-icons/fi';

const TABS = [
  {
    id: 'seo',
    label: 'SEO Analysis',
    description: 'Metadata, headings, structured signals',
    icon: FiTrendingUp,
  },
  {
    id: 'performance',
    label: 'Performance Analysis',
    description: 'Loading experience and Core Web Vitals',
    icon: FiZap,
  },
];

export const ResultTabs = ({ activeTab, onChange }) => (
  <div className="flex flex-wrap items-center justify-center gap-3 rounded-2xl bg-white/70 p-3 shadow-lg shadow-slate-200/50 ring-1 ring-slate-200 dark:bg-slate-900/70 dark:shadow-none dark:ring-slate-800">
    {TABS.map((tab) => {
      const Icon = tab.icon;
      const isActive = activeTab === tab.id;
      return (
        <button
          key={tab.id}
          type="button"
          onClick={() => onChange(tab.id)}
          className={clsx(
            'group inline-flex flex-1 min-w-[200px] flex-col items-start gap-1 rounded-xl border px-4 py-3 text-left transition focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-200 dark:focus-visible:ring-primary-900/40',
            isActive
              ? 'border-transparent bg-gradient-to-r from-primary-500/90 to-primary-600 text-white shadow-lg shadow-primary-500/30'
              : 'border-slate-200 bg-white text-slate-600 hover:border-primary-200 hover:text-primary-600 dark:border-slate-700 dark:bg-slate-950/80 dark:text-slate-300 dark:hover:border-primary-500 dark:hover:text-primary-300'
          )}
        >
          <span className="inline-flex items-center gap-2 text-sm font-semibold">
            <Icon className={clsx('text-lg', isActive ? 'text-white' : 'text-primary-500')} />
            {tab.label}
          </span>
          <span
            className={clsx(
              'text-xs transition',
              isActive ? 'text-slate-100/80' : 'text-slate-400 dark:text-slate-500'
            )}
          >
            {tab.description}
          </span>
        </button>
      );
    })}
  </div>
);
