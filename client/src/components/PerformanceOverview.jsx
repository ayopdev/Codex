import {
  FiActivity,
  FiBarChart2,
  FiClock,
  FiDatabase,
  FiTrendingUp,
  FiZap,
} from 'react-icons/fi';
import clsx from 'clsx';
import { StatusBadge } from './StatusBadge.jsx';

const MetricCard = ({ icon: Icon, title, metric }) => (
  <div className="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-md dark:border-slate-800 dark:bg-slate-900/60">
    <div className="flex items-center justify-between">
      <div className="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-200">
        <Icon className="text-lg text-primary-500" />
        {title}
      </div>
      <StatusBadge status={metric.status} />
    </div>
    <p className="text-2xl font-semibold text-slate-900 dark:text-slate-100">{metric.value}</p>
    <p className="text-xs uppercase tracking-wide text-slate-400">{metric.label}</p>
  </div>
);

const Gauge = ({ score, status }) => {
  const normalized = Math.max(0, Math.min(score ?? 0, 100));
  const gradient = `conic-gradient(var(--color) ${normalized * 3.6}deg, rgba(148, 163, 184, 0.15) 0deg)`;
  const colorMap = {
    good: 'var(--gauge-color, #10b981)',
    warning: 'var(--gauge-color, #f59e0b)',
    error: 'var(--gauge-color, #ef4444)',
  };
  const gaugeColor = colorMap[status] || colorMap.warning;

  return (
    <div className="relative flex h-40 w-40 items-center justify-center">
      <div
        className="absolute inset-0 rounded-full"
        style={{
          background: gradient,
          '--color': gaugeColor,
        }}
      />
      <div className="absolute inset-[18%] rounded-full bg-white shadow-inner dark:bg-slate-950" />
      <div className="relative z-10 flex flex-col items-center justify-center gap-1">
        <p className="text-4xl font-bold text-slate-900 dark:text-slate-100">{normalized}</p>
        <span className="text-xs font-semibold uppercase tracking-wide text-slate-400">Performance</span>
      </div>
    </div>
  );
};

export const PerformanceOverview = ({ data }) => {
  if (!data) return null;

  const {
    score,
    scoreStatus,
    metrics: { pageLoadTime, largestContentfulPaint, firstInputDelay, totalBlockingTime },
    imageAudits,
    resourceSummary,
  } = data;

  return (
    <div className="flex flex-col gap-6">
      <div className="flex flex-col items-center justify-between gap-6 rounded-3xl border border-slate-200 bg-gradient-to-r from-primary-50 via-slate-50 to-primary-100 p-6 shadow-lg dark:border-slate-800 dark:from-primary-900/30 dark:via-slate-900 dark:to-primary-900/40 lg:flex-row">
        <div className="flex flex-col items-center gap-4 text-center lg:flex-row lg:items-center lg:gap-8 lg:text-left">
          <Gauge score={score} status={scoreStatus} />
          <div className="max-w-xl space-y-3">
            <StatusBadge status={scoreStatus}>Overall score: {score}</StatusBadge>
            <h3 className="text-2xl font-semibold text-slate-900 dark:text-slate-100">Core performance overview</h3>
            <p className="text-sm text-slate-600 dark:text-slate-300">
              These metrics reflect how fast your landing page loads and becomes interactive. Aim for a score above 90 to ensure a smooth user experience.
            </p>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
        <MetricCard icon={FiClock} title="Speed Index" metric={pageLoadTime} />
        <MetricCard icon={FiTrendingUp} title="Largest Contentful Paint" metric={largestContentfulPaint} />
        <MetricCard icon={FiZap} title="Max Potential FID" metric={firstInputDelay} />
        <MetricCard icon={FiActivity} title="Total Blocking Time" metric={totalBlockingTime} />
      </div>

      <section className="grid grid-cols-1 gap-5 lg:grid-cols-5">
        <div className="rounded-2xl border border-slate-200 bg-white/80 p-5 shadow-md dark:border-slate-800 dark:bg-slate-900/60 lg:col-span-3">
          <header className="flex items-center justify-between">
            <div className="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-200">
              <FiBarChart2 className="text-lg text-primary-500" /> Image optimization opportunities
            </div>
            <StatusBadge status={imageAudits.every((audit) => audit.status === 'good') ? 'good' : 'warning'}>
              {imageAudits.length ? `${imageAudits.filter((audit) => audit.status !== 'good').length} improvements` : 'No data'}
            </StatusBadge>
          </header>
          <div className="mt-4 space-y-4">
            {imageAudits.length ? (
              imageAudits.map((audit) => (
                <div
                  key={audit.id}
                  className={clsx(
                    'rounded-xl border p-4 text-sm transition',
                    audit.status === 'good'
                      ? 'border-emerald-200 bg-emerald-50/60 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200'
                      : 'border-amber-200 bg-amber-50/60 text-amber-700 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200'
                  )}
                >
                  <p className="font-semibold">{audit.title}</p>
                  <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{audit.displayValue || audit.description}</p>
                </div>
              ))
            ) : (
              <p className="text-sm text-slate-500 dark:text-slate-400">No image optimization opportunities detected.</p>
            )}
          </div>
        </div>

        <div className="rounded-2xl border border-slate-200 bg-white/80 p-5 shadow-md dark:border-slate-800 dark:bg-slate-900/60 lg:col-span-2">
          <header className="flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-200">
            <FiDatabase className="text-lg text-primary-500" /> Resource size summary
          </header>
          <div className="mt-4 space-y-3">
            {resourceSummary.length ? (
              resourceSummary.map((resource) => (
                <div key={resource.label} className="flex items-center justify-between rounded-xl border border-slate-200/60 bg-slate-100/70 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-800/50">
                  <div className="flex flex-col">
                    <span className="font-semibold text-slate-700 dark:text-slate-100">{resource.label}</span>
                    <span className="text-xs text-slate-500">{resource.count} requests</span>
                  </div>
                  <span className="font-semibold text-slate-700 dark:text-slate-100">{resource.sizeFormatted}</span>
                </div>
              ))
            ) : (
              <p className="text-sm text-slate-500 dark:text-slate-400">No resource data available.</p>
            )}
          </div>
        </div>
      </section>
    </div>
  );
};
