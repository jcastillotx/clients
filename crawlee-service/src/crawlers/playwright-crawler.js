import { PlaywrightCrawler, Configuration } from '@crawlee/playwright';
import { chromium } from 'playwright';
import { logger } from '../utils/logger.js';

/**
 * Playwright-based crawler for JavaScript-rendered pages
 * Best for SPAs, dynamic content, and sites requiring browser interaction
 */
export class PlaywrightCrawlerService {
  constructor() {
    this.defaultOptions = {
      maxRequestsPerCrawl: parseInt(process.env.DEFAULT_MAX_REQUESTS) || 50,
      maxConcurrency: parseInt(process.env.DEFAULT_CONCURRENCY) || 3,
      requestHandlerTimeoutSecs: parseInt(process.env.DEFAULT_TIMEOUT_SECS) || 90,
      navigationTimeoutSecs: 60,
      headless: process.env.HEADLESS !== 'false'
    };
  }

  /**
   * Crawl multiple URLs with full browser rendering
   */
  async crawl(startUrls, options = {}) {
    const results = [];
    const visited = new Set();
    const {
      maxRequestsPerCrawl = this.defaultOptions.maxRequestsPerCrawl,
      maxConcurrency = this.defaultOptions.maxConcurrency,
      followLinks = true,
      sameDomain = true,
      waitForSelector = null,
      waitForTimeout = 2000,
      blockResources = ['image', 'stylesheet', 'font'],
      selectors = {},
      headers = {},
      cookies = [],
      viewport = { width: 1920, height: 1080 },
      onPageCrawled,
      onError
    } = options;

    // Configure Crawlee
    const config = new Configuration({
      persistStorage: false,
      storageClientOptions: {
        localDataDirectory: process.env.STORAGE_DIR || './storage'
      }
    });

    const crawler = new PlaywrightCrawler({
      maxRequestsPerCrawl,
      maxConcurrency,
      requestHandlerTimeoutSecs: this.defaultOptions.requestHandlerTimeoutSecs,
      navigationTimeoutSecs: this.defaultOptions.navigationTimeoutSecs,

      launchContext: {
        launcher: chromium,
        launchOptions: {
          headless: this.defaultOptions.headless,
          args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
          ]
        }
      },

      browserPoolOptions: {
        maxOpenPagesPerBrowser: 3
      },

      // Pre-navigation setup
      preNavigationHooks: [
        async ({ page, request }) => {
          // Set viewport
          await page.setViewportSize(viewport);

          // Set cookies if provided
          if (cookies.length > 0) {
            await page.context().addCookies(cookies);
          }

          // Set custom headers
          await page.setExtraHTTPHeaders({
            'Accept-Language': 'en-US,en;q=0.9',
            ...headers
          });

          // Block unnecessary resources for performance
          if (blockResources.length > 0) {
            await page.route('**/*', (route) => {
              const resourceType = route.request().resourceType();
              if (blockResources.includes(resourceType)) {
                route.abort();
              } else {
                route.continue();
              }
            });
          }
        }
      ],

      async requestHandler({ request, page, enqueueLinks, response }) {
        const url = request.loadedUrl || request.url;

        if (visited.has(url)) return;
        visited.add(url);

        logger.debug(`Crawling (Playwright): ${url}`);

        try {
          // Wait for content to load
          if (waitForSelector) {
            await page.waitForSelector(waitForSelector, { timeout: 10000 }).catch(() => {});
          } else {
            await page.waitForTimeout(waitForTimeout);
          }

          // Wait for network to be idle
          await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

          const pageData = {
            url,
            status_code: response?.status() || 200,
            crawled_at: new Date().toISOString(),

            // Basic metadata (after JS rendering)
            title: await page.title(),
            meta_description: await page.$eval(
              'meta[name="description"]',
              el => el.content
            ).catch(() => null),
            canonical: await page.$eval(
              'link[rel="canonical"]',
              el => el.href
            ).catch(() => null),

            // Open Graph
            og_title: await page.$eval(
              'meta[property="og:title"]',
              el => el.content
            ).catch(() => null),
            og_description: await page.$eval(
              'meta[property="og:description"]',
              el => el.content
            ).catch(() => null),
            og_image: await page.$eval(
              'meta[property="og:image"]',
              el => el.content
            ).catch(() => null),

            // Headings
            headings: {
              h1: await page.$$eval('h1', els => els.map(el => el.textContent.trim())),
              h2: await page.$$eval('h2', els => els.map(el => el.textContent.trim())),
              h3: await page.$$eval('h3', els => els.map(el => el.textContent.trim()))
            },

            // Links
            links: await page.$$eval('a[href]', (anchors, baseUrl) => {
              const internal = [];
              const external = [];

              anchors.forEach(a => {
                const href = a.href;
                if (!href || href.startsWith('javascript:') || href.startsWith('#')) return;

                try {
                  const linkUrl = new URL(href);
                  const base = new URL(baseUrl);
                  const link = {
                    url: href,
                    text: a.textContent?.trim() || null,
                    rel: a.rel || null
                  };

                  if (linkUrl.hostname === base.hostname) {
                    internal.push(link);
                  } else {
                    external.push(link);
                  }
                } catch (e) {}
              });

              return { internal, external };
            }, url),

            // Images
            images: await page.$$eval('img', imgs => imgs.map(img => ({
              src: img.src,
              alt: img.alt || null,
              has_alt: !!img.alt
            }))),

            // Content stats
            word_count: await page.$eval('body', el =>
              el.innerText.replace(/\s+/g, ' ').trim().split(' ').length
            ).catch(() => 0),

            // Schema/Structured data
            schema_types: await page.$$eval(
              'script[type="application/ld+json"]',
              scripts => {
                const types = [];
                scripts.forEach(s => {
                  try {
                    const json = JSON.parse(s.textContent);
                    if (json['@type']) types.push(json['@type']);
                  } catch (e) {}
                });
                return types;
              }
            ),

            // Performance metrics
            performance: await page.evaluate(() => {
              const timing = performance.timing;
              const navStart = timing.navigationStart;
              return {
                ttfb: timing.responseStart - navStart,
                dom_content_loaded: timing.domContentLoadedEventEnd - navStart,
                load_complete: timing.loadEventEnd - navStart
              };
            }).catch(() => null),

            // Response info
            content_type: response?.headers()?.['content-type'] || null,

            // JavaScript rendered indicator
            js_rendered: true
          };

          // Custom selectors extraction
          if (Object.keys(selectors).length > 0) {
            pageData.extracted = {};
            for (const [key, selector] of Object.entries(selectors)) {
              try {
                const elements = await page.$$(selector);
                if (elements.length === 1) {
                  pageData.extracted[key] = await elements[0].textContent();
                } else if (elements.length > 1) {
                  pageData.extracted[key] = await Promise.all(
                    elements.map(el => el.textContent())
                  );
                } else {
                  pageData.extracted[key] = null;
                }
              } catch (e) {
                pageData.extracted[key] = null;
              }
            }
          }

          results.push(pageData);

          if (onPageCrawled) {
            onPageCrawled(pageData);
          }

          // Enqueue links for crawling
          if (followLinks) {
            await enqueueLinks({
              strategy: sameDomain ? 'same-domain' : 'all'
            });
          }
        } catch (error) {
          logger.error(`Error processing ${url}:`, error);
          if (onError) {
            onError(error, url);
          }
        }
      },

      async failedRequestHandler({ request, error }) {
        logger.error(`Request failed: ${request.url}`, error);
        if (onError) {
          onError(error, request.url);
        }
        results.push({
          url: request.url,
          error: error.message,
          crawled_at: new Date().toISOString()
        });
      }
    }, config);

    await crawler.run(startUrls);

    return results;
  }

  /**
   * Scrape a single page with custom selectors
   */
  async scrapePage(url, selectors = {}, options = {}) {
    const results = await this.crawl([url], {
      ...options,
      maxRequestsPerCrawl: 1,
      followLinks: false,
      selectors
    });

    return results[0] || null;
  }

  /**
   * Take a screenshot of a page
   */
  async takeScreenshot(url, options = {}) {
    const {
      fullPage = true,
      type = 'png',
      quality = 80,
      viewport = { width: 1920, height: 1080 },
      waitForSelector = null,
      waitForTimeout = 2000
    } = options;

    const browser = await chromium.launch({
      headless: this.defaultOptions.headless,
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
      const page = await browser.newPage();
      await page.setViewportSize(viewport);
      await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });

      if (waitForSelector) {
        await page.waitForSelector(waitForSelector, { timeout: 10000 }).catch(() => {});
      } else {
        await page.waitForTimeout(waitForTimeout);
      }

      const screenshotOptions = {
        fullPage,
        type
      };

      if (type === 'jpeg') {
        screenshotOptions.quality = quality;
      }

      return await page.screenshot(screenshotOptions);
    } finally {
      await browser.close();
    }
  }

  /**
   * Extract structured data based on schema
   */
  async extractData(url, schema, options = {}) {
    const results = await this.crawl([url], {
      ...options,
      maxRequestsPerCrawl: 1,
      followLinks: false,
      selectors: schema
    });

    const page = results[0];
    if (!page || page.error) {
      throw new Error(page?.error || 'Failed to fetch page');
    }

    return {
      url: page.url,
      extracted: page.extracted || {},
      metadata: {
        title: page.title,
        description: page.meta_description
      },
      js_rendered: true
    };
  }

  /**
   * Execute custom JavaScript on a page
   */
  async executeScript(url, script, options = {}) {
    const {
      viewport = { width: 1920, height: 1080 },
      waitForSelector = null,
      waitForTimeout = 2000
    } = options;

    const browser = await chromium.launch({
      headless: this.defaultOptions.headless,
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
      const page = await browser.newPage();
      await page.setViewportSize(viewport);
      await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });

      if (waitForSelector) {
        await page.waitForSelector(waitForSelector, { timeout: 10000 }).catch(() => {});
      } else {
        await page.waitForTimeout(waitForTimeout);
      }

      const result = await page.evaluate(script);

      return {
        url,
        result,
        executed_at: new Date().toISOString()
      };
    } finally {
      await browser.close();
    }
  }
}

export default PlaywrightCrawlerService;
