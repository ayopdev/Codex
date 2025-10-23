import express from 'express';
import cors from 'cors';
import { analyzeSEO, buildSeoErrorResult } from './utils/seoAnalyzer.js';
import {
  analyzePerformance,
  buildPerformanceErrorResult,
} from './utils/performanceAnalyzer.js';

const app = express();
const PORT = process.env.PORT || 5000;

app.use(cors());
app.use(express.json({ limit: '1mb' }));

const isValidUrl = (value) => {
  try {
    const parsed = new URL(value);
    return parsed.protocol === 'http:' || parsed.protocol === 'https:';
  } catch (error) {
    return false;
  }
};

app.get('/api/health', (_req, res) => {
  res.json({ status: 'ok' });
});

app.post('/api/scan', async (req, res) => {
  const { url } = req.body || {};

  if (!url || !isValidUrl(url)) {
    return res.status(400).json({
      error: 'Please provide a valid URL starting with http:// or https://',
    });
  }

  try {
    const [seoResult, performanceResult] = await Promise.allSettled([
      analyzeSEO(url),
      analyzePerformance(url),
    ]);

    const seo =
      seoResult.status === 'fulfilled'
        ? seoResult.value
        : await buildSeoErrorResult(
            url,
            seoResult.reason instanceof Error
              ? seoResult.reason.message
              : seoResult.reason || 'Unknown error'
          );

    const performance =
      performanceResult.status === 'fulfilled'
        ? performanceResult.value
        : buildPerformanceErrorResult(
            performanceResult.reason instanceof Error
              ? performanceResult.reason.message
              : performanceResult.reason || 'Unknown error'
          );

    res.json({ seo, performance });
  } catch (error) {
    console.error('Scan error:', error);
    res.status(500).json({
      error: 'Unable to scan the requested page. Please try again later.',
      details: process.env.NODE_ENV === 'development' ? error.message : undefined,
    });
  }
});

app.use((req, res) => {
  res.status(404).json({ error: 'Route not found' });
});

app.listen(PORT, () => {
  console.log(`Landing Page Scanner API listening on port ${PORT}`);
});
