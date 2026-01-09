import { CheerioCrawler, Configuration, Dataset } from '@crawlee/cheerio';
import { logger } from '../utils/logger.js';

/**
 * Cheerio-based crawler for fast HTML scraping
 * Best for static websites that don't require JavaScript rendering
 */
export class CheerioCrawlerService {
  constructor() {
    this.defaultOptions = {
      maxRequestsPerCrawl: parseInt(process.env.DEFAULT_MAX_REQUESTS) || 50,
      maxConcurrency: parseInt(process.env.DEFAULT_CONCURRENCY) || 5,
      requestHandlerTimeoutSecs: parseInt(process.env.DEFAULT_TIMEOUT_SECS) || 60,
      navigationTimeoutSecs: 30
    };
  }

  /**
   * Crawl multiple URLs and follow links
   */
  async crawl(startUrls, options = {}) {
    const results = [];
    const visited = new Set();
    const {
      maxRequestsPerCrawl = this.defaultOptions.maxRequestsPerCrawl,
      maxConcurrency = this.defaultOptions.maxConcurrency,
      followLinks = true,
      sameDomain = true,
      respectRobotsTxt = true,
      extractData = true,
      selectors = {},
      headers = {},
      onPageCrawled,
      onError
    } = options;

    // Configure Crawlee to use in-memory storage
    const config = new Configuration({
      persistStorage: false,
      storageClientOptions: {
        localDataDirectory: process.env.STORAGE_DIR || './storage'
      }
    });

    Configuration.set('defaultStorageId', `crawl-${Date.now()}`);

    const crawler = new CheerioCrawler({
      maxRequestsPerCrawl,
      maxConcurrency,
      requestHandlerTimeoutSecs: this.defaultOptions.requestHandlerTimeoutSecs,
      navigationTimeoutSecs: this.defaultOptions.navigationTimeoutSecs,
      ignoreSslErrors: true,
      additionalMimeTypes: ['application/xml', 'text/xml'],

      // Set custom headers
      preNavigationHooks: [
        async ({ request }) => {
          request.headers = {
            ...request.headers,
            'User-Agent': headers['User-Agent'] ||
              'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            ...headers
          };
        }
      ],

      async requestHandler({ request, $, enqueueLinks, response }) {
        const url = request.loadedUrl || request.url;

        if (visited.has(url)) return;
        visited.add(url);

        logger.debug(`Crawling: ${url}`);

        try {
          const pageData = {
            url,
            status_code: response?.statusCode || 200,
            crawled_at: new Date().toISOString(),
            // Basic metadata
            title: $('title').text().trim() || null,
            meta_description: $('meta[name="description"]').attr('content') || null,
            meta_keywords: $('meta[name="keywords"]').attr('content') || null,
            canonical: $('link[rel="canonical"]').attr('href') || null,
            // Open Graph
            og_title: $('meta[property="og:title"]').attr('content') || null,
            og_description: $('meta[property="og:description"]').attr('content') || null,
            og_image: $('meta[property="og:image"]').attr('content') || null,
            // Headings
            headings: {
              h1: $('h1').map((_, el) => $(el).text().trim()).get(),
              h2: $('h2').map((_, el) => $(el).text().trim()).get(),
              h3: $('h3').map((_, el) => $(el).text().trim()).get()
            },
            // Links
            links: {
              internal: [],
              external: []
            },
            // Images
            images: $('img').map((_, el) => ({
              src: $(el).attr('src'),
              alt: $(el).attr('alt') || null,
              has_alt: !!$(el).attr('alt')
            })).get(),
            // Content stats
            word_count: $('body').text().replace(/\s+/g, ' ').trim().split(' ').length,
            // Schema/Structured data
            schema_types: [],
            // Response info
            response_headers: response?.headers || {},
            content_type: response?.headers?.['content-type'] || null
          };

          // Extract links
          $('a[href]').each((_, el) => {
            const href = $(el).attr('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

            try {
              const absoluteUrl = new URL(href, url).href;
              const isInternal = new URL(absoluteUrl).hostname === new URL(url).hostname;

              if (isInternal) {
                pageData.links.internal.push({
                  url: absoluteUrl,
                  text: $(el).text().trim() || null,
                  rel: $(el).attr('rel') || null
                });
              } else {
                pageData.links.external.push({
                  url: absoluteUrl,
                  text: $(el).text().trim() || null,
                  rel: $(el).attr('rel') || null
                });
              }
            } catch (e) {
              // Invalid URL, skip
            }
          });

          // Extract structured data (JSON-LD)
          $('script[type="application/ld+json"]').each((_, el) => {
            try {
              const jsonLd = JSON.parse($(el).html());
              if (jsonLd['@type']) {
                pageData.schema_types.push(jsonLd['@type']);
              }
            } catch (e) {
              // Invalid JSON, skip
            }
          });

          // Custom selectors extraction
          if (Object.keys(selectors).length > 0) {
            pageData.extracted = {};
            for (const [key, selector] of Object.entries(selectors)) {
              const elements = $(selector);
              if (elements.length === 1) {
                pageData.extracted[key] = elements.text().trim();
              } else if (elements.length > 1) {
                pageData.extracted[key] = elements.map((_, el) => $(el).text().trim()).get();
              } else {
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
      }
    };
  }
}

export default CheerioCrawlerService;
