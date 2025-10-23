import { FiArrowUpRight, FiClock, FiTrash2 } from 'react-icons/fi';

const formatDate = (value) => {
  try {
    return new Intl.DateTimeFormat(undefined, {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(new Date(value));
  } catch (error) {
    return value;
  }
};

export const HistoryPanel = ({ history, onSelect, onClear }) => {
  if (!history?.length) return null;

  return (
    <aside className="rounded-3xl border border-slate-200 bg-white/80 p-5 shadow-lg dark:border-slate-800 dark:bg-slate-900/60">
      <header className="flex items-center justify-between">
        <div className="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-200">
          <FiClock className="text-lg text-primary-500" /> Recent scans
        </div>
        <button
          type="button"
          onClick={() => onClear?.()}
          className="inline-flex items-center gap-1 rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-500 transition hover:border-rose-300 hover:text-rose-500 dark:border-slate-700 dark:text-slate-400 dark:hover:border-rose-500 dark:hover:text-rose-300"
        >
          <FiTrash2 /> Clear
        </button>
      </header>
      <ul className="mt-4 space-y-3">
        {history.map((item) => (
          <li key={item.id}>
            <button
              type="button"
              onClick={() => onSelect?.(item)}
              className="group flex w-full items-center justify-between rounded-2xl border border-slate-200/60 bg-slate-100/70 px-4 py-3 text-left text-sm transition hover:border-primary-300 hover:bg-white hover:text-primary-600 dark:border-slate-700 dark:bg-slate-800/40 dark:hover:border-primary-500 dark:hover:bg-slate-800"
            >
              <div className="flex flex-col gap-1">
                <span className="font-semibold text-slate-700 dark:text-slate-100">{item.url}</span>
                <span className="text-xs text-slate-500 dark:text-slate-400">{formatDate(item.timestamp)}</span>
              </div>
              <FiArrowUpRight className="text-lg text-primary-500 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
            </button>
          </li>
        ))}
      </ul>
    </aside>
  );
};
