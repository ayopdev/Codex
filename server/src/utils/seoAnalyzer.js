import fetch from 'node-fetch';
import cheerio from 'cheerio';

const USER_AGENT =
  'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36 LandingPageScanner/1.0';

const titleStatus = (length) => {
  if (!length) return 'error';
  if (length >= 50 && length <= 60) return 'good';
  if (length < 30 || length > 65) return 'warning';
  return 'info';
};

const descriptionStatus = (length) => {
  if (!length) return 'error';
  if (length >= 120 && length <= 160) return 'good';
  if (length < 80 || length > 180) return 'warning';
  return 'info';
};

const fetchText = async (targetUrl, options = {}) => {
  const response = await fetch(targetUrl, {
    headers: { 'User-Agent': USER_AGENT },
    ...options,
  });
  if (!response.ok) {
    throw new Error(`Request to ${targetUrl} failed with status ${response.status}`);
  }
  return response.text();
};

const checkPresence = async (targetUrl) => {
  try {
    const head = await fetch(targetUrl, {
      method: 'HEAD',
      headers: { 'User-Agent': USER_AGENT },
    });
    if (head.ok) return true;
    if (head.status === 405 || head.status === 403) {
      const getRes = await fetch(targetUrl, {
        method: 'GET',
        headers: { 'User-Agent': USER_AGENT },
      });
      return getRes.ok;
    }
    return false;
  } catch (error) {
    return false;
  }
};

const createDefaultSeoResult = (url) => {
  const origin = new URL(url).origin;
  const robotsUrl = new URL('/robots.txt', origin).toString();
  const sitemapUrl = new URL('/sitemap.xml', origin).toString();

  return {
    scannedUrl: url,
    title: {
      value: '',
      length: 0,
      status: 'error',
    },
    metaDescription: {
      value: '',
      length: 0,
      status: 'error',
    },
    headings: {
      h1: [],
      h2: [],
      status: 'error',
    },
    imageAlt: {
      total: 0,
      missing: 0,
      status: 'error',
    },
    canonical: {
      value: '',
      status: 'warning',
    },
    robots: {
      url: robotsUrl,
      present: false,
      status: 'warning',
    },
    sitemap: {
      url: sitemapUrl,
      present: false,
      status: 'warning',
    },
    error: false,
    errorMessage: null,
  };
};

export const analyzeSEO = async (url) => {
  const baseResult = createDefaultSeoResult(url);

  try {
    const html = await fetchText(url);
    const $ = cheerio.load(html);

    const title = $('title').first().text().trim();
    const metaDescription = $(
      'meta[name="description"], meta[name="Description"], meta[property="og:description"]'
    )
      .first()
      .attr('content');
    const canonicalRaw = $('link[rel="canonical"]').first().attr('href');
    const canonical = canonicalRaw ? new URL(canonicalRaw, url).toString() : '';

    const h1 = $('h1')
      .map((_, element) => $(element).text().trim())
      .get()
      .filter(Boolean);
    const h2 = $('h2')
      .map((_, element) => $(element).text().trim())
      .get()
      .filter(Boolean);

    const images = $('img');
    const totalImages = images.length;
    let missingAlt = 0;
    images.each((_, element) => {
      const alt = $(element).attr('alt');
      if (!alt || !alt.trim()) {
        missingAlt += 1;
      }
    });

    const [robotsPresent, sitemapPresent] = await Promise.all([
      checkPresence(baseResult.robots.url),
      checkPresence(baseResult.sitemap.url),
    ]);

    return {
      ...baseResult,
      title: {
        value: title,
        length: title.length,
        status: titleStatus(title.length),
      },
      metaDescription: {
        value: metaDescription || '',
        length: metaDescription ? metaDescription.length : 0,
        status: descriptionStatus(metaDescription ? metaDescription.length : 0),
      },
      headings: {
        h1,
        h2,
        status: h1.length ? 'good' : 'warning',
      },
      imageAlt: {
        total: totalImages,
        missing: missingAlt,
        status: missingAlt === 0 ? 'good' : missingAlt > totalImages / 2 ? 'error' : 'warning',
      },
      canonical: {
        value: canonical,
        status: canonical ? 'good' : 'warning',
      },
      robots: {
        url: baseResult.robots.url,
        present: robotsPresent,
        status: robotsPresent ? 'good' : 'warning',
      },
      sitemap: {
        url: baseResult.sitemap.url,
        present: sitemapPresent,
        status: sitemapPresent ? 'good' : 'warning',
      },
    };
  } catch (error) {
    console.error('SEO analysis error:', error);

    const [robotsPresent, sitemapPresent] = await Promise.all([
      checkPresence(baseResult.robots.url),
      checkPresence(baseResult.sitemap.url),
    ]);

    return {
      ...baseResult,
      robots: {
        url: baseResult.robots.url,
        present: robotsPresent,
        status: robotsPresent ? 'good' : 'warning',
      },
      sitemap: {
        url: baseResult.sitemap.url,
        present: sitemapPresent,
        status: sitemapPresent ? 'good' : 'warning',
      },
      error: true,
      errorMessage: `SEO scan failed: ${error.message}`,
    };
  }
};
