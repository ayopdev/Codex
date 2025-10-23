import { useMemo, useState } from 'react';
import { FiAlertCircle, FiExternalLink } from 'react-icons/fi';
import { ThemeToggle } from './components/ThemeToggle.jsx';
import { ScanForm } from './components/ScanForm.jsx';
import { ResultTabs } from './components/ResultTabs.jsx';
import { SeoOverview } from './components/SeoOverview.jsx';
import { PerformanceOverview } from './components/PerformanceOverview.jsx';
import { HistoryPanel } from './components/HistoryPanel.jsx';
import { StatusBadge } from './components/StatusBadge.jsx';
import { useLocalStorage } from './hooks/useLocalStorage.js';

const createId = () => {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID();
  }
  return `history-${Date.now()}-${Math.random().toString(16).slice(2)}`;
};

const formatUrl = (value) => {
  try {
    const { hostname } = new URL(value);
    return hostname;
  } catch (error) {
    return value;
  }
};

const buildResultMessage = (seo, performance) => {
  const seoFailed = Boolean(seo?.error);
  const perfFailed = Boolean(performance?.error);

  if (seoFailed && perfFailed) {
    return 'We could not retrieve SEO or performance details for this URL. Check each tab for more context.';
  }
  if (seoFailed) {
    return 'SEO insights were limited for this URL. Review the SEO tab for details about what we could not access.';
  }
  if (perfFailed) {
    return 'Performance metrics were unavailable for this URL. Review the Performance tab for more information.';
  }
  return '';
};

const EmptyState = () => (
  <div className="flex flex-col items-center justify-center gap-4 rounded-3xl border border-dashed border-slate-300 bg-white/60 p-12 text-center dark:border-slate-700 dark:bg-slate-900/40">
    <h3 className="text-xl font-semibold text-slate-700 dark:text-slate-200">Ready when you are</h3>
    <p className="max-w-md text-sm text-slate-500 dark:text-slate-400">
      Enter a landing page URL above to see a full SEO and performance report. We'll highlight the most important wins and quick fixes for you.
    </p>
  </div>
);

export default function App() {
  const [url, setUrl] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [results, setResults] = useState(null);
  const [activeTab, setActiveTab] = useState('seo');
  const [history, setHistory, clearHistory] = useLocalStorage('lps-history', []);

  const heroSubtitle = useMemo(
    () =>
      loading
        ? 'Running Lighthouse audits and crawling metadata...'
        : 'Instant insights into how search-friendly and fast your landing page really is.',
    [loading]
  );

  const handleScan = async () => {
    if (!url) return;

    setLoading(true);
    setError('');

    try {
      const response = await fetch('/api/scan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url }),
      });

      if (!response.ok) {
        const payload = await response.json().catch(() => ({}));
        throw new Error(payload.error || 'Unable to scan the provided URL.');
      }

      const payload = await response.json();
      setResults(payload);
      setActiveTab('seo');

      const newEntry = {
        id: createId(),
        url,
        timestamp: new Date().toISOString(),
        seo: payload.seo,
        performance: payload.performance,
      };

      setHistory((prev = []) => {
        const filtered = prev.filter((item) => item.url !== newEntry.url);
        return [newEntry, ...filtered].slice(0, 8);
      });

      const notice = buildResultMessage(payload.seo, payload.performance);
      if (notice) {
        setError(notice);
      }
    } catch (scanError) {
      setError(scanError.message || 'Something went wrong.');
    } finally {
      setLoading(false);
    }
  };

  const handleHistorySelect = (entry) => {
    setUrl(entry.url);
    setResults({ seo: entry.seo, performance: entry.performance });
    setActiveTab('seo');
    setError(buildResultMessage(entry.seo, entry.performance));
  };

  const seoData = results?.seo ?? null;
  const performanceData = results?.performance ?? null;

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-100 via-white to-slate-200 pb-20 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
      <div className="mx-auto flex w-full max-w-6xl flex-col gap-12 px-6 py-10 lg:py-16">
        <header className="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
          <div className="space-y-3">
            <div className="inline-flex items-center gap-2 rounded-full border border-primary-200/80 bg-primary-50 px-4 py-1 text-xs font-semibold uppercase tracking-wide text-primary-700 shadow-sm dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-300">
              Landing Page Scanner
            </div>
            <h1 className="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl dark:text-white">
              Audit SEO & performance in seconds.
            </h1>
            <p className="max-w-2xl text-base text-slate-600 dark:text-slate-300">{heroSubtitle}</p>
          </div>
          <ThemeToggle />
        </header>

        <ScanForm url={url} onUrlChange={setUrl} onSubmit={handleScan} loading={loading} />

        {error ? (
          <div className="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/70 p-4 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
            <FiAlertCircle className="text-lg" />
            {error}
          </div>
        ) : null}

        <div className="grid grid-cols-1 gap-8 lg:grid-cols-[2fr,1fr]">
          <div className="flex flex-col gap-8">
            {results ? (
              <div className="flex flex-col gap-6">
                <ResultTabs activeTab={activeTab} onChange={setActiveTab} />
                <div className="space-y-6">
                  {activeTab === 'seo' ? <SeoOverview data={seoData} /> : null}
                  {activeTab === 'performance' ? <PerformanceOverview data={performanceData} /> : null}
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white/70 p-5 text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="space-y-1">
                      <p className="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Scanned URL:
                        <a
                          href={results?.seo?.scannedUrl || url}
                          target="_blank"
                          rel="noreferrer"
                          className="ml-2 inline-flex items-center gap-1 text-primary-600 hover:underline dark:text-primary-300"
                        >
                          {formatUrl(results?.seo?.scannedUrl || url)}
                          <FiExternalLink className="text-base" />
                        </a>
                      </p>
                      <p>Use the tabs above to explore SEO metadata or performance metrics in depth.</p>
                    </div>
                    <StatusBadge status={performanceData?.scoreStatus || 'info'}>
                      Performance score: {performanceData?.score ?? '—'}
                    </StatusBadge>
                  </div>
                </div>
              </div>
            ) : (
              <EmptyState />
            )}
          </div>

          <HistoryPanel history={history} onSelect={handleHistorySelect} onClear={clearHistory} />
        </div>

        <footer className="rounded-3xl border border-slate-200 bg-white/70 p-6 text-sm text-slate-500 shadow-inner dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-400">
          <div className="flex flex-col gap-2 text-center sm:flex-row sm:items-center sm:justify-between">
            <p>
              Built with React, Tailwind CSS, and Lighthouse to help marketers ship faster landing pages.
            </p>
            <p>
              Tip: Save reports or share them with your team for quick wins.
            </p>
          </div>
        </footer>
      </div>
    </div>
  );
}
