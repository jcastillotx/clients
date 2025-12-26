# Troubleshooting Guide

## 500 errors after deploy
- Ensure `.env` exists and `APP_KEY` is set.
- Clear + rebuild caches:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Storage permission issues
- Ensure writable:

```bash
chmod -R 775 storage bootstrap/cache
```

## Queue not processing jobs
- Verify worker is running (Supervisor recommended).
- Check `storage/logs/worker.log` (if configured).
- Ensure `QUEUE_CONNECTION` matches your infrastructure.

## Scheduled tasks not running
- Confirm cron entry runs every minute:

```cron
* * * * * cd /home/USERNAME/apps/client-portal/current && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

## Stripe payments failing
- Confirm `services.stripe.secret` and `services.stripe.key` are set (via env).
- Confirm the client has `stripe_customer_id` where required.
- Check Stripe dashboard logs for PaymentIntent errors.

## Webhooks not delivering
- Confirm endpoint is active in Admin → Webhooks.
- Inspect delivery history (status + HTTP response).
- Verify receiver checks the HMAC signature:
  - `sha256=HMAC(secret, timestamp + "." + rawBody)`

