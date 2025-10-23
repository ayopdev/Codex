import { FiArrowRight, FiGlobe } from 'react-icons/fi';
import { LoadingSpinner } from './LoadingSpinner.jsx';

export const ScanForm = ({ url, onUrlChange, onSubmit, loading }) => (
  <form
    onSubmit={(event) => {
      event.preventDefault();
      onSubmit();
    }}
    className="w-full max-w-2xl rounded-3xl border border-slate-200/70 bg-white/80 p-6 shadow-xl backdrop-blur-lg transition dark:border-slate-800 dark:bg-slate-900/70"
  >
    <label htmlFor="url" className="block text-sm font-semibold text-slate-600 dark:text-slate-300">
      Website URL
    </label>
    <div className="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center">
      <div className="relative flex-1">
        <span className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400">
          <FiGlobe />
        </span>
        <input
          id="url"
          type="url"
          required
          inputMode="url"
          placeholder="https://your-landing-page.com"
          value={url}
          onChange={(event) => onUrlChange(event.target.value)}
          className="w-full rounded-2xl border border-slate-200 bg-white px-12 py-3 text-base font-medium text-slate-800 shadow-inner outline-none transition focus:border-primary-400 focus:ring-4 focus:ring-primary-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-100 dark:focus:border-primary-500 dark:focus:ring-primary-900/40"
        />
      </div>
      <button
        type="submit"
        disabled={loading}
        className="group inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-primary-500 via-primary-600 to-primary-700 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-primary-500/30 transition hover:shadow-xl hover:shadow-primary-500/40 disabled:cursor-not-allowed disabled:opacity-70"
      >
        {loading ? (
          <>
            <LoadingSpinner size="1.25rem" color="white" />
            Scanning...
          </>
        ) : (
          <>
            Scan Now
            <FiArrowRight className="text-lg transition group-hover:translate-x-1" />
          </>
        )}
      </button>
    </div>
    <p className="mt-4 text-sm text-slate-500 dark:text-slate-400">
      Paste the landing page URL you want to analyze. We'll run SEO and performance checks in under a minute.
    </p>
  </form>
);
