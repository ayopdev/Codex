import lighthouse from 'lighthouse';
import puppeteer from 'puppeteer';

const launchBrowser = async () =>
  puppeteer.launch({
    headless: 'new',
    args: ['--no-sandbox', '--disable-gpu'],
  });

const scoreToStatus = (score) => {
  if (score === null || score === undefined) return 'warning';
  if (score >= 0.9) return 'good';
  if (score >= 0.5) return 'warning';
  return 'error';
};

const formatMs = (value) =>
  typeof value === 'number' ? `${Math.round(value)} ms` : 'Not available';

const formatSeconds = (value) =>
  typeof value === 'number' ? `${(value / 1000).toFixed(2)} s` : 'Not available';

export const analyzePerformance = async (url) => {
  const browser = await launchBrowser();

  try {
    const endpoint = browser.wsEndpoint();
    const endpointUrl = new URL(endpoint);
    const port = Number(endpointUrl.port);
    const options = {
      logLevel: 'info',
      output: 'json',
      onlyCategories: ['performance'],
      port,
    };

    const runnerResult = await lighthouse(url, options);
    const { lhr } = runnerResult;
    const { audits, categories } = lhr;

    const resourceItems = audits['resource-summary']?.details?.items ?? [];
    const resourceSummary = resourceItems.map((item) => ({
      label: item.resourceType,
      transferSize: item.transferSize,
      sizeFormatted: item.transferSize
        ? `${(item.transferSize / 1024).toFixed(1)} KB`
        : '0 KB',
      count: item.requestCount,
    }));

    const imageAuditKeys = [
      'uses-optimized-images',
      'uses-responsive-images',
      'offscreen-images',
      'modern-image-formats',
    ];

    const imageAudits = imageAuditKeys
      .map((key) => audits[key])
      .filter(Boolean)
      .map((audit) => ({
        id: audit.id,
        title: audit.title,
        description: audit.description,
        score: audit.score,
        status: scoreToStatus(audit.score),
        displayValue: audit.displayValue,
      }));

    const perfScore = categories.performance?.score ?? null;

    return {
      score: perfScore !== null ? Math.round(perfScore * 100) : null,
      scoreStatus: scoreToStatus(perfScore),
      metrics: {
        pageLoadTime: {
          label: 'Speed Index',
          value: formatSeconds(audits['speed-index']?.numericValue),
          status: scoreToStatus(audits['speed-index']?.score),
        },
        largestContentfulPaint: {
          label: 'Largest Contentful Paint',
          value: formatSeconds(audits['largest-contentful-paint']?.numericValue),
          status: scoreToStatus(audits['largest-contentful-paint']?.score),
        },
        firstInputDelay: {
          label: 'Max Potential FID',
          value: formatMs(audits['max-potential-fid']?.numericValue),
          status: scoreToStatus(audits['max-potential-fid']?.score),
        },
        totalBlockingTime: {
          label: 'Total Blocking Time',
          value: formatMs(audits['total-blocking-time']?.numericValue),
          status: scoreToStatus(audits['total-blocking-time']?.score),
        },
      },
      imageAudits,
      resourceSummary,
      raw: {
        lcp: audits['largest-contentful-paint']?.displayValue,
        fid: audits['max-potential-fid']?.displayValue,
        tbt: audits['total-blocking-time']?.displayValue,
        loadTime: audits['speed-index']?.displayValue,
      },
    };
  } catch (error) {
    throw new Error(`Performance analysis failed: ${error.message}`);
  } finally {
    if (browser) {
      await browser.close().catch(() => undefined);
    }
  }
};
