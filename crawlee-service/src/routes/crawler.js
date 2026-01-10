import { Router } from 'express';
import { v4 as uuidv4 } from 'uuid';
import { CheerioCrawlerService } from '../crawlers/cheerio-crawler.js';
import { PlaywrightCrawlerService } from '../crawlers/playwright-crawler.js';
import { logger } from '../utils/logger.js';

const router = Router();

// Store for active/completed crawl jobs
const crawlJobs = new Map();

/**
 * Start a new crawl job
 * POST /api/v1/crawl
 */
router.post('/crawl', async (req, res) => {
  const {
    url,
    urls,
    crawler_type = 'cheerio',
    options = {}
  } = req.body;

  // Validate input
  const startUrls = urls || (url ? [url] : []);
  if (startUrls.length === 0) {
    return res.status(400).json({
      success: false,
      error: 'At least one URL is required (url or urls parameter)'
    });
  }

  // Validate crawler type
  const validCrawlers = ['cheerio', 'playwright', 'puppeteer'];
  if (!validCrawlers.includes(crawler_type)) {
    return res.status(400).json({
      success: false,
      error: `Invalid crawler_type. Must be one of: ${validCrawlers.join(', ')}`
    });
  }

  const jobId = uuidv4();
  const job = {
    id: jobId,
    status: 'running',
    crawler_type,
    start_urls: startUrls,
    options,
    started_at: new Date().toISOString(),
    completed_at: null,
    results: [],
    errors: [],
    stats: {}
  };

  crawlJobs.set(jobId, job);

  // Run crawl asynchronously
  runCrawl(jobId, startUrls, crawler_type, options);

  res.status(202).json({
    success: true,
    message: 'Crawl job started',
    job_id: jobId,
    status_url: `/api/v1/crawl/${jobId}`
  });
});

/**
 * Start a synchronous crawl (waits for completion)
 * POST /api/v1/crawl/sync
 */
router.post('/crawl/sync', async (req, res) => {
  const {
    url,
    urls,
    crawler_type = 'cheerio',
    options = {}
  } = req.body;

  const startUrls = urls || (url ? [url] : []);
  if (startUrls.length === 0) {
    return res.status(400).json({
      success: false,
      error: 'At least one URL is required'
    });
  }

  try {
    const crawler = createCrawler(crawler_type);
    const startTime = Date.now();

    const results = await crawler.crawl(startUrls, options);

    res.json({
      success: true,
      crawler_type,
      duration_ms: Date.now() - startTime,
      total_pages: results.length,
      results
    });
  } catch (error) {
    logger.error('Sync crawl failed:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * Get crawl job status
 * GET /api/v1/crawl/:jobId
 */
router.get('/crawl/:jobId', (req, res) => {
  const { jobId } = req.params;
  const job = crawlJobs.get(jobId);

  if (!job) {
    return res.status(404).json({
      success: false,
      error: 'Job not found'
    });
  }

  res.json({
    success: true,
    job
  });
});

/**
 * Cancel a running crawl job
 * DELETE /api/v1/crawl/:jobId
 */
router.delete('/crawl/:jobId', (req, res) => {
  const { jobId } = req.params;
  const job = crawlJobs.get(jobId);

  if (!job) {
    return res.status(404).json({
      success: false,
      error: 'Job not found'
    });
  }

  if (job.status === 'running') {
    job.status = 'cancelled';
    job.completed_at = new Date().toISOString();
  }

  res.json({
    success: true,
    message: 'Job cancelled',
    job
  });
});

/**
 * Scrape a single page
 * POST /api/v1/scrape
 */
router.post('/scrape', async (req, res) => {
  const {
    url,
    crawler_type = 'cheerio',
    selectors = {},
    options = {}
  } = req.body;

  if (!url) {
    return res.status(400).json({
      success: false,
      error: 'URL is required'
    });
  }

  try {
    const crawler = createCrawler(crawler_type);
    const startTime = Date.now();

    const result = await crawler.scrapePage(url, selectors, options);

    res.json({
      success: true,
      crawler_type,
      duration_ms: Date.now() - startTime,
      data: result
    });
  } catch (error) {
    logger.error('Scrape failed:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * Take a screenshot of a page (Playwright only)
 * POST /api/v1/screenshot
 */
router.post('/screenshot', async (req, res) => {
  const {
    url,
    options = {}
  } = req.body;

  if (!url) {
    return res.status(400).json({
      success: false,
      error: 'URL is required'
    });
  }

  try {
    const crawler = new PlaywrightCrawlerService();
    const startTime = Date.now();

    const screenshot = await crawler.takeScreenshot(url, options);

    res.json({
      success: true,
      duration_ms: Date.now() - startTime,
      screenshot: screenshot.toString('base64'),
      format: options.type || 'png'
    });
  } catch (error) {
    logger.error('Screenshot failed:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * Extract structured data from a page
 * POST /api/v1/extract
 */
router.post('/extract', async (req, res) => {
  const {
    url,
    schema,
    crawler_type = 'cheerio',
    options = {}
  } = req.body;

  if (!url) {
    return res.status(400).json({
      success: false,
      error: 'URL is required'
    });
  }

  if (!schema || Object.keys(schema).length === 0) {
    return res.status(400).json({
      success: false,
      error: 'Extraction schema is required'
    });
  }

  try {
    const crawler = createCrawler(crawler_type);
    const startTime = Date.now();

    const result = await crawler.extractData(url, schema, options);

    res.json({
      success: true,
      crawler_type,
      duration_ms: Date.now() - startTime,
      data: result
    });
  } catch (error) {
    logger.error('Extract failed:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

/**
 * List all crawl jobs
 * GET /api/v1/jobs
 */
router.get('/jobs', (req, res) => {
  const { status, limit = 50 } = req.query;

  let jobs = Array.from(crawlJobs.values());

  if (status) {
    jobs = jobs.filter(job => job.status === status);
  }

  jobs = jobs
    .sort((a, b) => new Date(b.started_at) - new Date(a.started_at))
    .slice(0, parseInt(limit));

  res.json({
    success: true,
    total: jobs.length,
    jobs: jobs.map(({ results, ...job }) => ({
      ...job,
      result_count: results.length
    }))
  });
});

/**
 * Helper: Create crawler instance based on type
 */
function createCrawler(type) {
  switch (type) {
    case 'playwright':
    case 'puppeteer':
      return new PlaywrightCrawlerService();
    case 'cheerio':
    default:
      return new CheerioCrawlerService();
  }
}

/**
 * Helper: Run crawl job asynchronously
 */
async function runCrawl(jobId, urls, crawlerType, options) {
  const job = crawlJobs.get(jobId);

  try {
    const crawler = createCrawler(crawlerType);

    logger.info(`Starting crawl job ${jobId} with ${urls.length} URLs`);

    const results = await crawler.crawl(urls, {
      ...options,
      onPageCrawled: (pageData) => {
        job.results.push(pageData);
      },
      onError: (error, url) => {
        job.errors.push({ url, error: error.message });
      }
    });

    job.status = 'completed';
    job.results = results;
    job.stats = {
      total_pages: results.length,
      total_errors: job.errors.length,
      successful_pages: results.filter(r => !r.error).length
    };

    logger.info(`Crawl job ${jobId} completed with ${results.length} pages`);
  } catch (error) {
    logger.error(`Crawl job ${jobId} failed:`, error);
    job.status = 'failed';
    job.errors.push({ error: error.message });
  } finally {
    job.completed_at = new Date().toISOString();
  }
}

export default router;
