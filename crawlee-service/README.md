# Crawlee Web Scraping Service

A Node.js microservice providing advanced web scraping capabilities using [Crawlee](https://crawlee.dev/), integrated with the Laravel application.

## Features

- **Fast HTML Crawling** - Cheerio-based crawler for static content
- **JavaScript Rendering** - Playwright-based crawler for SPAs and dynamic content
- **Screenshot Capture** - Full page screenshots with customizable options
- **Structured Data Extraction** - Extract data using CSS selectors
- **Async Job Processing** - Background crawl jobs with status polling
- **Bot Protection Avoidance** - Human-like browser fingerprints
- **Proxy Support** - Built-in proxy rotation (configurable)

## Requirements

- Node.js 18.0.0 or higher
- npm or yarn

## Installation

```bash
# Navigate to the service directory
cd crawlee-service

# Install dependencies
npm install

# Install Playwright browsers (required for JS rendering)
npx playwright install chromium

# Copy environment configuration
cp .env.example .env

# Edit .env with your settings
nano .env
```

## Configuration

Edit `.env` to configure the service:

```env
# Server
PORT=3001
HOST=127.0.0.1
NODE_ENV=production

# Security - Set a strong API key
API_KEY=your-secure-api-key-here

# Crawling defaults
DEFAULT_MAX_REQUESTS=50
DEFAULT_TIMEOUT_SECS=60
DEFAULT_CONCURRENCY=5

# Browser settings
HEADLESS=true
```

## Running the Service

```bash
# Development (with auto-reload)
npm run dev

# Production
npm start
```

## API Endpoints

### Health Check

```
GET /health
GET /health/detailed
```

### Crawling

```
POST /api/v1/crawl          # Start async crawl job
POST /api/v1/crawl/sync     # Synchronous crawl (waits for completion)
GET  /api/v1/crawl/:jobId   # Get job status
DELETE /api/v1/crawl/:jobId # Cancel job
GET  /api/v1/jobs           # List all jobs
```

### Scraping

```
POST /api/v1/scrape         # Scrape single page
POST /api/v1/extract        # Extract structured data
POST /api/v1/screenshot     # Take screenshot
```

## Laravel Integration

### Configuration

Add to your Laravel `.env`:

```env
CRAWLEE_ENABLED=true
CRAWLEE_SERVICE_URL=http://127.0.0.1:3001
CRAWLEE_API_KEY=your-secure-api-key-here
```

### Usage Examples

```php
use App\Services\Marketing\Crawling\CrawleeService;

$crawlee = new CrawleeService();

// Check if service is available
if (!$crawlee->isHealthy()) {
    // Fall back to built-in crawler or show error
}

// Crawl a website (fast, no JS)
$results = $crawlee->crawl('https://example.com', [
    'max_requests' => 50,
    'follow_links' => true,
    'same_domain' => true,
]);

// Crawl a JavaScript-heavy SPA
$results = $crawlee->crawlSpa('https://react-app.com', 20, '[data-loaded="true"]');

// Scrape a single page with custom selectors
$data = $crawlee->scrapePage('https://example.com/product', [
    'title' => 'h1.product-title',
    'price' => '.price-tag',
    'description' => '.product-description',
]);

// Scrape JavaScript-rendered content
$data = $crawlee->scrapeJsPage('https://spa-site.com', [
    'items' => '.dynamic-list li',
]);

// Take a screenshot
$screenshot = $crawlee->screenshot('https://example.com', [
    'fullPage' => true,
    'type' => 'png',
]);
// $screenshot['screenshot'] contains base64 encoded image

// Extract structured data
$data = $crawlee->extract('https://example.com/article', [
    'headline' => 'h1',
    'author' => '.author-name',
    'published' => 'time[datetime]',
    'content' => 'article.content',
]);

// Async crawl for large sites
$job = $crawlee->crawlAsync('https://large-site.com', [
    'max_requests' => 500,
]);
$jobId = $job['job_id'];

// Poll for status
$status = $crawlee->getJobStatus($jobId);
if ($status['job']['status'] === 'completed') {
    $results = $status['job']['results'];
}
```

### SEO Audit Integration

```php
// In your SEO audit service
public function auditWebsite(string $url): array
{
    $crawlee = new CrawleeService();

    // Use Crawlee for JavaScript sites, built-in for static
    if ($this->isJavaScriptSite($url) && $crawlee->isHealthy()) {
        $pages = $crawlee->crawlSpa($url, 100);
    } else {
        // Fall back to built-in WebsiteCrawler
        $crawler = new WebsiteCrawler();
        $pages = $crawler->crawl($url);
    }

    return $this->analyzePages($pages);
}
```

## Crawler Types

### Cheerio (Default)

- Fast HTTP-based crawler
- Low resource usage
- No JavaScript rendering
- Best for: Static sites, SEO audits, link crawling

```php
$crawlee->crawl($url, ['crawler_type' => 'cheerio']);
```

### Playwright

- Full browser automation
- JavaScript rendering
- Screenshot support
- Higher resource usage
- Best for: SPAs, React/Vue apps, dynamic content

```php
$crawlee->crawl($url, ['crawler_type' => 'playwright']);
```

## Response Format

### Crawl Results

```json
{
  "success": true,
  "crawler_type": "cheerio",
  "duration_ms": 5432,
  "total_pages": 25,
  "results": [
    {
      "url": "https://example.com/page",
      "status_code": 200,
      "crawled_at": "2024-01-15T10:30:00Z",
      "title": "Page Title",
      "meta_description": "Page description",
      "canonical": "https://example.com/page",
      "headings": {
        "h1": ["Main Heading"],
        "h2": ["Subheading 1", "Subheading 2"],
        "h3": []
      },
      "links": {
        "internal": [{"url": "...", "text": "...", "rel": null}],
        "external": [{"url": "...", "text": "...", "rel": "nofollow"}]
      },
      "images": [
        {"src": "...", "alt": "Image description", "has_alt": true}
      ],
      "word_count": 1500,
      "schema_types": ["Article", "Organization"]
    }
  ]
}
```

## Running as a System Service

### Using PM2

```bash
# Install PM2
npm install -g pm2

# Start the service
pm2 start src/server.js --name crawlee-service

# Save configuration
pm2 save

# Auto-start on boot
pm2 startup
```

### Using Systemd

Create `/etc/systemd/system/crawlee.service`:

```ini
[Unit]
Description=Crawlee Web Scraping Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/crawlee-service
ExecStart=/usr/bin/node src/server.js
Restart=on-failure
Environment=NODE_ENV=production

[Install]
WantedBy=multi-user.target
```

Then:

```bash
sudo systemctl enable crawlee
sudo systemctl start crawlee
```

## Troubleshooting

### Playwright Browser Issues

```bash
# Reinstall browsers
npx playwright install chromium

# Install system dependencies (Ubuntu/Debian)
npx playwright install-deps chromium
```

### Memory Issues

For large crawls, increase Node.js memory:

```bash
NODE_OPTIONS="--max-old-space-size=4096" npm start
```

### Connection Refused

Ensure the service is running and the port is correct:

```bash
curl http://127.0.0.1:3001/health
```

## License

MIT
