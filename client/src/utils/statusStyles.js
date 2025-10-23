export const STATUS_STYLES = {
  good: 'bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:border-emerald-500/30',
  warning:
    'bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:border-amber-500/30',
  error:
    'bg-rose-100 text-rose-700 border border-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:border-rose-500/30',
  info: 'bg-sky-100 text-sky-700 border border-sky-200 dark:bg-sky-500/10 dark:text-sky-200 dark:border-sky-500/30',
};

export const statusLabel = {
  good: 'Good',
  warning: 'Needs attention',
  error: 'Requires action',
  info: 'Information',
};

export const getStatusClasses = (status = 'info') => STATUS_STYLES[status] || STATUS_STYLES.info;
