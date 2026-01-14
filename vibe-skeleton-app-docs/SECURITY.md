# Security Policy

## Supported Versions

We actively support security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security issue, please report it responsibly.

### How to Report

**DO NOT** create a public GitHub issue for security vulnerabilities.

Instead, please report security vulnerabilities by:

1. **Email**: Send details to [security@yourdomain.com](mailto:security@yourdomain.com)
2. **GitHub Security Advisories**: Use [GitHub's private vulnerability reporting](https://github.com/jcastillotx/vibe-skeleton-app/security/advisories/new)

### What to Include

Please provide as much information as possible:

- **Type of vulnerability** (e.g., XSS, SQL injection, authentication bypass)
- **Affected component(s)** and version(s)
- **Step-by-step reproduction instructions**
- **Proof of concept** (if available)
- **Potential impact** assessment
- **Suggested remediation** (if any)

### Response Timeline

| Action | Timeframe |
|--------|-----------|
| Initial acknowledgment | 48 hours |
| Preliminary assessment | 7 days |
| Patch development | 30 days (critical), 90 days (standard) |
| Public disclosure | After patch release + 30 days |

### Disclosure Policy

We follow a **90-day coordinated disclosure policy**:

1. Reporter notifies us of vulnerability
2. We acknowledge receipt within 48 hours
3. We investigate and develop a fix
4. We release the fix and notify the reporter
5. After 90 days (or upon fix release), details may be publicly disclosed

### Safe Harbor

We consider security research conducted in accordance with this policy to be:

- **Authorized** concerning any applicable anti-hacking laws
- **Authorized** concerning any relevant anti-circumvention laws
- **Exempt** from restrictions in our Terms of Service that would interfere with conducting security research

We will not pursue legal action against researchers who:

- Make a good faith effort to avoid privacy violations and data destruction
- Only interact with accounts they own or have explicit permission to access
- Do not exploit vulnerabilities beyond what is necessary to demonstrate the issue
- Report vulnerabilities promptly and provide reasonable time for remediation

### Recognition

We appreciate responsible disclosure and may:

- Publicly acknowledge reporters (with consent)
- Provide security researcher credit in release notes
- Consider reporters for our security acknowledgments page

### Scope

This policy applies to:

- All repositories under `jcastillotx` organization
- Production infrastructure and services
- Documentation and configuration files

**Out of scope:**

- Third-party services and dependencies (report to respective maintainers)
- Social engineering attacks
- Physical security
- Denial of Service attacks

## Security Best Practices

When using this project, follow these security guidelines:

### Environment Variables

```bash
# NEVER commit .env files
# Use .env.example as template
cp .env.example .env

# Ensure .env is in .gitignore
echo ".env" >> .gitignore
```

### Secrets Management

- Store secrets in environment variables, not code
- Use secret management tools (AWS Secrets Manager, HashiCorp Vault)
- Rotate credentials regularly
- Use least-privilege access principles

### Authentication

- Implement MFA where possible
- Use secure session management
- Enforce strong password policies
- Use OAuth/OIDC for third-party authentication

### Data Protection

- Encrypt sensitive data at rest and in transit
- Use HTTPS for all connections
- Implement proper input validation
- Sanitize user inputs to prevent injection attacks

## Security Review Requirements

See [DOCUMENTATION_REVIEW_POLICY.md](./docs/DOCUMENTATION_REVIEW_POLICY.md) for mandatory security review checkpoints throughout the development lifecycle.

## Contact

- **Security Team**: security@yourdomain.com
- **General Inquiries**: support@yourdomain.com

---

*Last updated: 2025-01-14*
*Review schedule: Quarterly*
