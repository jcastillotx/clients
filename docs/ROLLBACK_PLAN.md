# Rollback Plan - Next.js Migration

**Emergency Procedures for Reverting to Laravel**

## Rollback Triggers

Execute rollback if ANY of these conditions occur:

### Critical Triggers (Immediate Rollback)

- ❌ **Payment processing failure** affecting >10% of transactions
- ❌ **Data corruption or loss** detected in PostgreSQL
- ❌ **Security vulnerability** exposing client data
- ❌ **Critical bugs** affecting >50% of active users
- ❌ **Complete service outage** lasting >15 minutes

### Warning Triggers (Prepare for Rollback)

- ⚠️ **Performance degradation** >50% slower than Laravel
- ⚠️ **Authentication failures** >5% of login attempts
- ⚠️ **Background jobs failing** at >20% rate
- ⚠️ **Monitoring alerts** showing sustained high error rates

## Pre-Migration Preparation

### Week Before Launch

1. **Full Laravel Backup**

   ```bash
   # MySQL database backup
   mysqldump -u root -p kre8iv_clients > backups/laravel-db-$(date +%Y%m%d).sql

   # Application files backup
   tar -czf backups/laravel-app-$(date +%Y%m%d).tar.gz \
     --exclude='node_modules' \
     --exclude='vendor' \
     /path/to/laravel-app

   # Environment variables
   cp .env backups/.env.backup-$(date +%Y%m%d)
   ```

2. **Document Current State**

   ```bash
   # DNS records
   dig kre8iv.app > backups/dns-records.txt

   # Server configuration
   nginx -T > backups/nginx-config.txt

   # Laravel queue status
   php artisan queue:monitor > backups/queue-status.txt
   ```

3. **Parallel Running Setup**
   - Keep Laravel server running on `old.kre8iv.app`
   - Run Next.js on `beta.kre8iv.app` for 48 hours
   - Monitor both environments side-by-side

## Rollback Procedures

### Phase 1: DNS Revert (5 minutes)

**Objective**: Point domain back to Laravel immediately

```bash
# 1. Update DNS records (Cloudflare example)
curl -X PATCH "https://api.cloudflare.com/client/v4/zones/{zone_id}/dns_records/{dns_id}" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  --data '{
    "type": "A",
    "name": "kre8iv.app",
    "content": "{laravel_server_ip}",
    "ttl": 120,
    "proxied": true
  }'

# 2. Verify DNS propagation
dig kre8iv.app +short
# Should return Laravel server IP

# 3. Clear CDN cache (if using Cloudflare)
curl -X POST "https://api.cloudflare.com/client/v4/zones/{zone_id}/purge_cache" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'
```

**Verification**:

- Visit https://kre8iv.app - should show Laravel version
- Check response headers for Laravel signatures
- Monitor error logs for 404s

**Estimated Time**: 5 minutes
**Impact**: Users immediately routed back to Laravel

---

### Phase 2: Data Sync Back (30 minutes)

**Objective**: Sync data created in Next.js back to MySQL

```bash
# 1. Export data from Supabase PostgreSQL (created during Next.js period)
pg_dump -h db.xxx.supabase.co -U postgres -d postgres \
  --data-only \
  --table=users \
  --table=clients \
  --table=requests \
  --table=invoices \
  > backups/nextjs-new-data-$(date +%Y%m%d-%H%M).sql

# 2. Convert PostgreSQL dump to MySQL format
# (Manual conversion needed - UUIDs to INTs, JSONB to JSON, etc.)
python scripts/pg-to-mysql-converter.py \
  backups/nextjs-new-data-*.sql \
  > backups/nextjs-new-data-mysql.sql

# 3. Import new data into Laravel MySQL
mysql -u root -p kre8iv_clients < backups/nextjs-new-data-mysql.sql

# 4. Verify row counts match
mysql -u root -p -e "
  SELECT 'users' as table_name, COUNT(*) as count FROM kre8iv_clients.users
  UNION ALL
  SELECT 'clients', COUNT(*) FROM kre8iv_clients.clients
  UNION ALL
  SELECT 'requests', COUNT(*) FROM kre8iv_clients.requests
  UNION ALL
  SELECT 'invoices', COUNT(*) FROM kre8iv_clients.invoices;
"
```

**Data Sync Script** (`scripts/pg-to-mysql-converter.py`):

```python
#!/usr/bin/env python3
import sys
import re

def convert_pg_to_mysql(pg_dump_file):
    with open(pg_dump_file, 'r') as f:
        content = f.read()

    # Convert UUIDs to INTs (use mappings from migration)
    # Convert JSONB to JSON
    # Convert TIMESTAMPTZ to DATETIME
    # ... conversion logic ...

    return converted_content

if __name__ == '__main__':
    converted = convert_pg_to_mysql(sys.argv[1])
    print(converted)
```

**Estimated Time**: 30 minutes
**Impact**: Data created during Next.js period preserved

---

### Phase 3: Communication (15 minutes)

**Objective**: Notify stakeholders and users

1. **Internal Team**

   ```
   Subject: URGENT - Migration Rollback Initiated

   We've rolled back to the Laravel application due to [SPECIFIC ISSUE].

   Current Status:
   - DNS: Reverted to Laravel (COMPLETE)
   - Data Sync: In Progress
   - User Impact: [DESCRIBE]

   Action Items:
   - [Team Member]: Monitor error logs
   - [Team Member]: Verify payment processing
   - [Team Member]: Test critical user flows

   Next Update: 30 minutes
   ```

2. **End Users** (if service was impacted)

   ```
   Subject: Service Update

   We experienced technical difficulties during our platform upgrade
   and have temporarily reverted to our previous version.

   - All your data is safe and secure
   - Full functionality has been restored
   - We apologize for any inconvenience

   We'll share an update on the upgrade timeline soon.
   ```

3. **Status Page Update**
   - Update https://status.kre8iv.app
   - Post incident report (brief, transparent)

**Estimated Time**: 15 minutes
**Impact**: Stakeholders informed, trust maintained

---

## Post-Rollback Analysis

### Day 1: Incident Report

**Template**:

```markdown
# Migration Rollback Incident Report

Date: [Date]
Duration: [Start Time] - [End Time]
Severity: [Critical/High/Medium]

## Summary

Brief description of what went wrong and why we rolled back.

## Timeline

- [Time]: Migration launched
- [Time]: First signs of issues
- [Time]: Rollback decision made
- [Time]: DNS reverted
- [Time]: Data sync complete
- [Time]: Service fully restored

## Root Cause

Detailed analysis of what caused the failure.

## Impact

- Users affected: [Number/Percentage]
- Data loss: [None/Description]
- Revenue impact: [Amount]
- Downtime: [Duration]

## Contributing Factors

- What we missed in testing
- What changed from staging to production
- External factors

## Action Items

1. [Fix root cause]
2. [Improve testing coverage]
3. [Update migration plan]
4. [Schedule retry date]

## Lessons Learned

- What worked well in rollback
- What could be improved
- Changes to migration strategy
```

### Week 1: Analysis & Planning

1. **Technical Review**
   - Deep dive into failure root cause
   - Gap analysis: What testing missed this issue?
   - Code review: Were there warning signs?

2. **Process Review**
   - Was rollback plan adequate?
   - Was communication effective?
   - Were monitoring/alerts sufficient?

3. **Migration Plan Updates**
   - Incorporate learnings
   - Add additional safeguards
   - Extend testing coverage
   - Consider phased rollout instead of big bang

### Month 1: Retry Planning

**Criteria for Retry**:

- ✅ Root cause fully resolved and tested
- ✅ Additional safeguards in place
- ✅ Team confidence high
- ✅ Stakeholder buy-in
- ✅ Rollback plan enhanced with lessons learned

---

## Rollback Testing

**Practice Rollback BEFORE Launch**

### Week -2: Rollback Drill

```bash
# 1. Deploy Next.js to staging
vercel deploy --env=staging

# 2. Run for 24 hours
# 3. Trigger rollback
./scripts/execute-rollback.sh staging

# 4. Verify:
#   - DNS reverted
#   - Data synced back
#   - All features working
#   - No data loss

# 5. Time each phase
#   - DNS revert: [Actual time]
#   - Data sync: [Actual time]
#   - Communication: [Actual time]

# 6. Identify bottlenecks and optimize
```

**Success Criteria**:

- Complete rollback in <60 minutes
- Zero data loss
- All team members trained
- Runbook validated

---

## Rollback Decision Matrix

| Issue                           | Severity | Rollback Decision                                   |
| ------------------------------- | -------- | --------------------------------------------------- |
| Payment failures >10%           | Critical | Immediate rollback                                  |
| Authentication failures >5%     | Critical | Immediate rollback                                  |
| Data corruption detected        | Critical | Immediate rollback                                  |
| Performance >50% slower         | High     | Rollback if not resolved in 1 hour                  |
| Background jobs failing >20%    | High     | Rollback if not resolved in 2 hours                 |
| UI bugs affecting key workflows | Medium   | Fix in production, rollback if unfixable in 4 hours |
| Minor visual glitches           | Low      | Fix in production, no rollback                      |

---

## Rollback Contacts

| Role            | Name   | Phone   | Email   | Slack     |
| --------------- | ------ | ------- | ------- | --------- |
| CEO             | [Name] | [Phone] | [Email] | @ceo      |
| CTO             | [Name] | [Phone] | [Email] | @cto      |
| Lead Developer  | [Name] | [Phone] | [Email] | @lead-dev |
| DevOps          | [Name] | [Phone] | [Email] | @devops   |
| Support Manager | [Name] | [Phone] | [Email] | @support  |

**Escalation Path**:

1. Lead Developer identifies issue → Assesses severity
2. If critical → Notifies CTO + CEO immediately
3. CTO makes rollback decision
4. DevOps executes rollback
5. Support Manager handles user communication

---

## Files & Resources

### Pre-Migration Checklist

- [ ] MySQL backup created and verified
- [ ] Laravel application files backed up
- [ ] DNS records documented
- [ ] Environment variables backed up
- [ ] Rollback scripts tested
- [ ] Team trained on rollback procedures
- [ ] Monitoring alerts configured
- [ ] Status page prepared
- [ ] Communication templates ready
- [ ] Rollback drill completed successfully

### Rollback Scripts Location

```
/migration/rollback/
├── execute-rollback.sh          # Main rollback orchestrator
├── revert-dns.sh                # DNS revert script
├── sync-data-back.sh            # PostgreSQL → MySQL sync
├── pg-to-mysql-converter.py     # Data format converter
├── verify-rollback.sh           # Post-rollback verification
└── templates/
    ├── internal-email.md
    ├── user-email.md
    └── incident-report.md
```

---

## Prevention Strategies

**To Avoid Needing Rollback**:

1. **Extensive Testing**
   - Load testing with production-scale data
   - Chaos engineering (simulate failures)
   - Penetration testing
   - Accessibility audit
   - Mobile device testing

2. **Gradual Rollout**
   - Option: 10% of users → 50% → 100%
   - Requires feature flags in Next.js
   - Monitor metrics at each stage

3. **Parallel Running**
   - Run Next.js and Laravel simultaneously for 1 week
   - Sync data bidirectionally
   - Compare outputs for consistency
   - Higher cost but lowest risk

4. **Canary Deployments**
   - Deploy to single region first
   - Monitor for 48 hours
   - Roll out to remaining regions

5. **Automated Monitoring**
   - Sentry for error tracking
   - Vercel Analytics for performance
   - Custom dashboards for business metrics
   - Automated alerts for anomalies

---

**Last Updated**: Migration planning phase
**Next Review**: 1 week before launch
**Owner**: Lead Developer + DevOps
