# Brand Monitoring Setup Guide - Free APIs

This guide shows you how to set up comprehensive brand monitoring using **FREE API tiers** that rival expensive platforms like Brandwatch ($800-2000/month) or Mention ($300-600/month).

## 💰 Cost Comparison

| Feature | Expensive SaaS | Our Free Solution |
|---------|----------------|-------------------|
| News Monitoring | ✅ Included | ✅ **FREE** (NewsAPI + Google News RSS) |
| Review Monitoring | ✅ Included | ✅ **FREE** (Yelp + Google Places) |
| Social Media Monitoring | ✅ Included | ✅ **FREE** (Reddit, YouTube, Twitter RSS) |
| Web Mentions | ✅ Included | ✅ **FREE** (Google/Bing Search) |
| Sentiment Analysis | ✅ Included | ✅ **$0.01** per 1000 mentions (AI) |
| **Total Monthly Cost** | **$800-2000** | **~$5-15/month** (AI costs only) |

---

## 🚀 Quick Start (30 minutes)

### Step 1: Get Free API Keys

#### 1.1 News Monitoring

**NewsAPI.org** (FREE: 100 requests/day)
1. Visit: https://newsapi.org/register
2. Sign up for free account
3. Copy API key
4. Add to `.env`: `NEWSAPI_API_KEY=your_key_here`

**Google News RSS** (FREE: Unlimited)
- No API key needed! Already works.

#### 1.2 Review Monitoring

**Yelp Fusion API** (FREE: 5,000 requests/day)
1. Visit: https://www.yelp.com/developers/v3/manage_app
2. Create app (name: "Brand Monitor")
3. Copy API Key
4. Add to `.env`: `YELP_API_KEY=your_key_here`

**Google Places API** (FREE: $200 credit/month = ~40,000 searches)
1. Visit: https://console.cloud.google.com
2. Enable "Places API"
3. Create credentials → API Key
4. Restrict key to "Places API"
5. Add to `.env`: `GOOGLE_PLACES_API_KEY=your_key_here`

#### 1.3 Social Media Monitoring

**Reddit API** (FREE: 60 requests/minute)
1. Visit: https://www.reddit.com/prefs/apps
2. Click "create another app"
3. Select "script" type
4. Note Client ID and Secret
5. Add to `.env`:
   ```
   REDDIT_CLIENT_ID=your_client_id
   REDDIT_CLIENT_SECRET=your_secret
   ```

**YouTube Data API** (FREE: 10,000 quota units/day)
1. Visit: https://console.cloud.google.com
2. Enable "YouTube Data API v3"
3. Create credentials → API Key
4. Restrict to "YouTube Data API v3"
5. Add to `.env`: `YOUTUBE_API_KEY=your_key_here`

**Twitter/X RSS** (FREE: Unlimited via Nitter)
- No API key needed! Uses public RSS feeds.

#### 1.4 Web Mention Monitoring

**Google Custom Search** (FREE: 100 searches/day)
1. Visit: https://programmablesearchengine.google.com/
2. Create new search engine
3. Set to search "entire web"
4. Get Search Engine ID
5. Visit: https://console.cloud.google.com
6. Enable "Custom Search API"
7. Create API Key
8. Add to `.env`:
   ```
   GOOGLE_SEARCH_API_KEY=your_key
   GOOGLE_SEARCH_ENGINE_ID=your_cx_id
   ```

**Bing Search API** (FREE: 1,000 searches/month)
1. Visit: https://www.microsoft.com/en-us/bing/apis/bing-web-search-api
2. Sign up for free tier
3. Copy API key
4. Add to `.env`: `BING_SEARCH_API_KEY=your_key`

### Step 2: Enable Scheduled Monitoring

The system automatically monitors all active clients. Make sure your cron is running:

```bash
# Add to your crontab
* * * * * cd /path/to/clients && php artisan schedule:run >> /dev/null 2>&1
```

### Step 3: Test the System

```bash
# Test news monitoring
php artisan tinker
$client = \App\Models\Client::first();
$news = app(\App\Services\BrandMonitoring\NewsMonitoringService::class);
$news->searchNewsAPI($client);

# Test review monitoring
$reviews = app(\App\Services\BrandMonitoring\ReviewMonitoringService::class);
$reviews->getYelpReviews($client);

# Test social monitoring
$social = app(\App\Services\BrandMonitoring\SocialMonitoringService::class);
$social->searchReddit($client);

# Test sentiment analysis
$sentiment = app(\App\Services\BrandMonitoring\SentimentAnalysisService::class);
$sentiment->analyzePendingSentiments();
```

---

## 📊 What You Get

### Automated Monitoring Schedule

| Task | Frequency | Free Limit | Daily Clients |
|------|-----------|------------|---------------|
| News (NewsAPI + Google RSS) | Hourly | 100/day + unlimited | ~4 clients with NewsAPI |
| Reviews (Yelp + Google) | Every 6 hours | 5000/day + $200 credit | All clients |
| Social (Reddit/YouTube/Twitter) | Every 30 min | 60/min + 10k/day | All clients |
| Web Mentions (Google/Bing) | Every 2 hours | 100/day + 1000/month | ~8 clients with Google |
| Sentiment Analysis | Every 30 min | Pay-per-use AI | 50 mentions/batch |

### Data Collected

For each mention, you get:
- ✅ **Platform** (news, yelp, google, reddit, youtube, x, web)
- ✅ **Mention Text** (title + description/body)
- ✅ **Author** (username/source)
- ✅ **URL** (link to original)
- ✅ **Posted Date** (timestamp)
- ✅ **Sentiment** (positive/neutral/negative)
- ✅ **Metadata** (rating, score, keyword, etc.)

---

## 💡 Smart Usage Tips

### Maximize Free Limits

**NewsAPI (100/day limit)**:
- Monitor top 4-5 most important clients hourly
- Others use Google News RSS (unlimited)
- Prioritize by client tier

**Google Custom Search (100/day limit)**:
- Run every 2 hours = 12 searches/day
- Monitor 8 clients (12 searches ÷ 1.5 keywords avg)
- Use Bing for additional clients

**Sentiment Analysis Costs**:
- Batch 50 mentions = ~$0.001-0.003 per batch
- 1000 mentions/day = ~$0.06/day = **$1.80/month**
- Much cheaper than SaaS sentiment tools

### Optimize Keywords

Instead of monitoring just "Company Name", use:
```php
$keywords = [
    $client->company_name,
    '@' . $client->twitter_handle, // if available
    $client->domain_name,
];
```

### Handle Rate Limits

The system automatically:
- ✅ Deduplicates by URL (no double-counting)
- ✅ Respects API rate limits
- ✅ Logs failures for review
- ✅ Continues on error (doesn't stop all monitoring)

---

## 🔧 Advanced Configuration

### Adjust Monitoring Frequency

Edit `routes/console.php`:

```php
// Check news every 2 hours instead of hourly
})->everyTwoHours()->name('brand-monitoring-news');

// Check reviews daily instead of every 6 hours
})->daily()->name('brand-monitoring-reviews');
```

### Disable Specific APIs

In `.env`:
```bash
# Disable NewsAPI to save free tier for important clients
NEWSAPI_ENABLED=false

# Disable Twitter RSS if not needed
TWITTER_RSS_ENABLED=false
```

### Custom Sentiment Provider

Use cheaper AI or local models:
```bash
SENTIMENT_AI_PROVIDER=openai
SENTIMENT_AI_MODEL=gpt-4o-mini  # Cheapest option
```

---

## 📈 Scaling Strategy

As you grow:

### 1-10 Clients (FREE)
- Use all free tiers
- Total cost: **$0-5/month** (AI only)

### 10-50 Clients ($5-20/month)
- Upgrade NewsAPI to paid: $449/month for unlimited
- OR rotate free tier across top clients
- Keep everything else free
- Total cost: **$5-20/month** with rotation

### 50+ Clients ($100-300/month)
- Consider paid NewsAPI ($449/month)
- Upgrade Google Search ($5/1000 additional searches)
- Still **80% cheaper** than Brandwatch

---

## 🎯 What This Replaces

| Expensive Platform | Monthly Cost | Our Equivalent | Our Cost |
|-------------------|--------------|----------------|----------|
| Brandwatch | $800-2000 | ✅ All features | $5-15 |
| Mention | $300-600 | ✅ All features | $5-15 |
| Brand24 | $49-399 | ✅ All features | $5-15 |
| Hootsuite Insights | $249-739 | ✅ Social only | $0-5 |
| ReviewTrackers | $99-999 | ✅ Review monitoring | $0 |

---

## 🚨 Troubleshooting

### NewsAPI: "Your API key is invalid or incorrect"

**Common Causes**:
1. **Using Development Key**: NewsAPI free tier keys might be labeled as "developer" keys and have restrictions
2. **Whitespace in Key**: Extra spaces when copying the key from NewsAPI website
3. **Wrong Key Type**: Make sure you're using an API key, not OAuth credentials
4. **Account Not Activated**: Check your email for NewsAPI account activation link

**Solutions**:
1. **Get a Fresh Key**:
   - Visit https://newsapi.org/account
   - Generate a new API key
   - Copy carefully (no extra spaces)
   - Update `.env`: `NEWSAPI_API_KEY=your_key_here`
   - Run: `php artisan config:clear`

2. **Verify Key Format**:
   - NewsAPI keys are typically 32 characters
   - Should look like: `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`

3. **Test Your Key Manually**:
   ```bash
   # Replace YOUR_API_KEY with your actual key
   curl "https://newsapi.org/v2/top-headlines?country=us&apiKey=YOUR_API_KEY"
   ```
   If this fails, the key is definitely invalid - generate a new one.

4. **Check API Status**:
   - Visit https://status.newsapi.org/
   - Make sure NewsAPI service is operational

**Note**: The free tier has limits:
- 100 requests/day
- 1 request per second
- Only 1 month of historical articles

### "No mentions found"

**Check**:
1. API keys are correct in `.env`
2. Client has `is_active = true`
3. Business exists on platform (Yelp, Google Places)
4. Keywords are not too specific

**Debug**:
```bash
php artisan tinker
$client = \App\Models\Client::find(1);
$news = app(\App\Services\BrandMonitoring\NewsMonitoringService::class);
$result = $news->searchNewsAPI($client);
dd($result); // Check for errors
```

### "API quota exceeded"

**Solutions**:
- Reduce monitoring frequency in `console.php`
- Disable less important sources
- Rotate monitoring across clients
- Upgrade to paid tier for that specific API

### "Sentiment not analyzed"

**Check**:
```bash
# See pending mentions
\App\Models\BrandMention::whereNull('sentiment')->count();

# Run manual analysis
$sentiment = app(\App\Services\BrandMonitoring\SentimentAnalysisService::class);
$sentiment->analyzePendingSentiments();
```

---

## 📚 Next Steps

1. **Set up alerts**: Get notified of negative mentions
2. **Create dashboards**: Visualize sentiment trends
3. **Export reports**: Share with clients
4. **Integrate webhooks**: Trigger actions on mentions

---

## 🎉 Success!

You now have **enterprise-level brand monitoring** for the cost of a coffee per month!

**Questions?** Check logs: `storage/logs/laravel.log`

**Need help?** Review service files in `app/Services/BrandMonitoring/`
