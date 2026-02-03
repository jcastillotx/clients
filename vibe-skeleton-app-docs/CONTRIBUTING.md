# Contributing to AI Project Governance Starter

Thank you for your interest in contributing! This document provides guidelines and standards for contributing to this project.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Commit Standards](#commit-standards)
- [Pull Request Process](#pull-request-process)
- [Documentation Requirements](#documentation-requirements)
- [Security Guidelines](#security-guidelines)
- [Review Process](#review-process)

## Code of Conduct

By participating in this project, you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md). Please read it before contributing.

## Getting Started

### Prerequisites

1. Git installed locally
2. Familiarity with the project's [README](README.md)
3. Understanding of the [Development Orchestration](docs/DEVELOPMENT_ORCHESTRATION.md) workflow

### Setting Up Your Environment

```bash
# Fork the repository
# Clone your fork
git clone https://github.com/YOUR_USERNAME/vibe-skeleton-app.git
cd vibe-skeleton-app

# Add upstream remote
git remote add upstream https://github.com/jcastillotx/vibe-skeleton-app.git

# Copy environment template
cp .env.example .env

# Create your feature branch
git checkout -b feature/your-feature-name
```

## Development Workflow

### Branch Naming Convention

| Type | Format | Example |
|------|--------|---------|
| Feature | `feature/description` | `feature/add-auth-module` |
| Bug Fix | `fix/description` | `fix/login-validation` |
| Documentation | `docs/description` | `docs/update-readme` |
| Security | `security/description` | `security/patch-xss-vuln` |
| Refactor | `refactor/description` | `refactor/auth-service` |

### Development Phases

All contributions must follow the project's phased development approach:

1. **Phase 1**: Define the problem/feature clearly
2. **Phase 2**: Document requirements and acceptance criteria
3. **Phase 3**: Design the solution (for significant changes)
4. **Phase 4**: Implement with TDD approach
5. **Phase 5**: Test thoroughly
6. **Phase 6**: Security review (for security-impacting changes)
7. **Phase 7**: Code review
8. **Phase 8**: Merge and deploy

## Commit Standards

### Commit Message Format

We follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

[optional body]

[optional footer(s)]
```

### Types

| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `style` | Code style (formatting, semicolons, etc.) |
| `refactor` | Code change without feature/fix |
| `perf` | Performance improvement |
| `test` | Adding or updating tests |
| `chore` | Build process or auxiliary tools |
| `security` | Security-related changes |

### Examples

```bash
# Feature
feat(auth): add OAuth2 provider support

# Bug fix
fix(api): resolve race condition in user creation

# Documentation
docs(readme): update installation instructions

# Security
security(deps): update lodash to patch CVE-2021-23337
```

## Pull Request Process

### Before Submitting

- [ ] Read the [Documentation Review Policy](docs/DOCUMENTATION_REVIEW_POLICY.md)
- [ ] Ensure your code follows the project's style guidelines
- [ ] Write or update tests as needed
- [ ] Update documentation for any changed functionality
- [ ] Run the full test suite locally
- [ ] Perform self-review of your code
- [ ] Check for security implications

### PR Requirements

1. **Title**: Clear, descriptive title following commit conventions
2. **Description**: Complete the PR template with:
   - Summary of changes
   - Related issue(s)
   - Testing performed
   - Documentation updates
   - Security considerations
3. **Size**: Keep PRs focused and reasonably sized (<500 lines preferred)
4. **Tests**: All tests must pass
5. **Reviews**: Minimum 1 approval required (2 for security changes)

### Review Checklist

Reviewers will evaluate:

- [ ] Code quality and readability
- [ ] Test coverage and quality
- [ ] Documentation completeness
- [ ] Security implications
- [ ] Performance impact
- [ ] Breaking changes

## Documentation Requirements

### What Requires Documentation

| Change Type | Documentation Required |
|-------------|----------------------|
| New feature | README, API docs, user guide |
| API change | API docs, CHANGELOG, migration guide |
| Configuration | .env.example, deployment docs |
| Security | SECURITY.md, security considerations |
| Breaking change | CHANGELOG, migration guide |

### Documentation Standards

1. **Clarity**: Write for your audience (developers, users, operators)
2. **Completeness**: Include examples and edge cases
3. **Currency**: Keep documentation up-to-date with code
4. **Accessibility**: Use clear language, proper formatting

## Security Guidelines

### Security-Sensitive Changes

Changes affecting security require:

1. **Security review** by a designated reviewer
2. **Threat assessment** documented in PR
3. **Testing** for security implications
4. **Two approvals** before merge

### What Constitutes Security-Sensitive

- Authentication/authorization changes
- Cryptographic operations
- Input validation/sanitization
- API security (rate limiting, CORS, etc.)
- Dependency updates with security patches
- Configuration changes affecting security posture

### Reporting Security Issues

See [SECURITY.md](SECURITY.md) for vulnerability reporting procedures.

## Review Process

### Standard Reviews

- 1 approval required
- CI checks must pass
- Documentation updated

### Security Reviews

- 2 approvals required (including 1 security reviewer)
- Security checklist completed
- No high/critical vulnerabilities introduced

### Documentation Reviews

All PRs are subject to documentation review per our [Documentation Review Policy](docs/DOCUMENTATION_REVIEW_POLICY.md).

## Getting Help

- **Questions**: Open a [Discussion](https://github.com/jcastillotx/vibe-skeleton-app/discussions)
- **Bugs**: Open an [Issue](https://github.com/jcastillotx/vibe-skeleton-app/issues)
- **Security**: See [SECURITY.md](SECURITY.md)

## Recognition

Contributors are recognized in:

- Release notes
- Contributors list
- Security acknowledgments (for security researchers)

---

Thank you for contributing to making AI-assisted development more structured and secure!

*Last updated: 2025-01-14*
