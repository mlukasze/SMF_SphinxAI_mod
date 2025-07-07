# 📊 Codecov Integration Setup Guide

This guide explains how to set up Codecov integration for the SMF Sphinx AI Search Plugin to track code coverage across PHP and Python components.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Codecov Account Setup](#codecov-account-setup)
- [GitHub Repository Configuration](#github-repository-configuration)
- [Workflow Configuration](#workflow-configuration)
- [Verification](#verification)
- [Troubleshooting](#troubleshooting)

## Prerequisites

- GitHub repository with admin access
- Active GitHub Actions (enabled by default on public repositories)
- Codecov account (free for open source projects)

## Codecov Account Setup

### 1. Create Codecov Account

1. Go to [codecov.io](https://codecov.io/)
2. Click **"Sign up"**
3. Choose **"Sign up with GitHub"**
4. Authorize Codecov to access your GitHub account

### 2. Add Repository to Codecov

1. Once logged in, click **"Add new repository"**
2. Select your organization/account
3. Find and select `SMF_SphinxAI_mod` (or your repository name)
4. Click **"Setup repo"**

### 3. Get Repository Token

1. In Codecov, navigate to your repository
2. Click **"Settings"** tab
3. Copy the **"Repository Upload Token"**
   ```
   Example: 12345678-1234-1234-1234-123456789abc
   ```

## GitHub Repository Configuration

### 1. Add Codecov Token as GitHub Secret

1. Go to your GitHub repository
2. Click **"Settings"** tab
3. In the left sidebar, click **"Secrets and variables"** → **"Actions"**
4. Click **"New repository secret"**
5. Set the following:
   - **Name**: `CODECOV_TOKEN`
   - **Secret**: Paste the token from Codecov (step 3 above)
6. Click **"Add secret"**

### 2. Verify Secret Configuration

The secret should now appear in your repository secrets list as:
```
CODECOV_TOKEN • Updated now
```

## Workflow Configuration

### 1. Codecov Configuration File

Create `.codecov.yml` in your repository root:

```yaml
# .codecov.yml
coverage:
  status:
    project:
      default:
        target: 80%          # Target coverage percentage
        threshold: 2%        # Allow 2% decrease
        base: auto
    patch:
      default:
        target: 70%          # Target coverage for new code
        threshold: 5%        # Allow 5% decrease for patches

comment:
  layout: "header, diff, flags, components, footer"
  behavior: default
  require_changes: false

flags:
  php:
    paths:
      - php/
    carryforward: true
  python:
    paths:
      - SphinxAI/
    carryforward: true

component_management:
  default_rules:
    flag_regexes:
      - php
      - python
  individual_components:
    - component_id: php-core
      name: PHP Core
      flag_regexes:
        - php
      paths:
        - php/core/
        - php/services/
    - component_id: python-ai
      name: Python AI
      flag_regexes:
        - python
      paths:
        - SphinxAI/handlers/
        - SphinxAI/core/
```

### 2. Workflow File Verification

Ensure your `.github/workflows/main.yml` includes the codecov upload steps:

```yaml
- name: Upload PHP coverage to Codecov
  uses: codecov/codecov-action@v5
  with:
    token: ${{ secrets.CODECOV_TOKEN }}
    file: tests/coverage.xml
    flags: php
    name: php-coverage
    fail_ci_if_error: true

- name: Upload Python coverage to Codecov
  uses: codecov/codecov-action@v5
  with:
    token: ${{ secrets.CODECOV_TOKEN }}
    file: tests/coverage.xml
    flags: python
    name: python-coverage
    fail_ci_if_error: true
```

## Verification

### 1. Trigger Workflow

1. Push changes to trigger the GitHub Actions workflow:
   ```bash
   git add .
   git commit -m "Add Codecov integration"
   git push
   ```

2. Monitor the workflow in GitHub Actions tab

### 2. Check Codecov Dashboard

1. Go to [codecov.io](https://codecov.io/) and navigate to your repository
2. You should see coverage reports after the first successful workflow run
3. Coverage data will be displayed for both PHP and Python components

### 3. Verify Badge

Add the Codecov badge to your README.md:

```markdown
[![Coverage](https://codecov.io/gh/yourusername/SMF_SphinxAI_mod/branch/main/graph/badge.svg)](https://codecov.io/gh/yourusername/SMF_SphinxAI_mod)
```

Replace `yourusername` with your actual GitHub username/organization.

## Troubleshooting

### Common Issues

#### 1. "Token not found" Error

**Problem**: Workflow fails with token authentication error.

**Solution**:
- Verify the secret name is exactly `CODECOV_TOKEN` (case-sensitive)
- Ensure the token was copied correctly from Codecov
- Check that the repository has access to organization secrets (if applicable)

#### 2. No Coverage Data

**Problem**: Workflow succeeds but no coverage appears in Codecov.

**Solution**:
- Verify coverage files are generated (`coverage.xml`)
- Check file paths in the workflow match actual coverage file locations
- Ensure tests are actually running and generating coverage

#### 3. Coverage File Not Found

**Problem**: Codecov action reports "coverage file not found".

**Solution**:
```yaml
# Add debug step to verify file existence
- name: Debug coverage files
  run: |
    find . -name "coverage.xml" -type f
    ls -la tests/
```

#### 4. Permissions Issues

**Problem**: Workflow fails with permission errors.

**Solution**:
- Ensure repository has Actions enabled
- Check that the workflow has necessary permissions:

```yaml
permissions:
  contents: read
  security-events: write
  actions: read
```

### Advanced Configuration

#### Multiple Coverage Reports

For projects with multiple test suites:

```yaml
- name: Upload combined coverage
  uses: codecov/codecov-action@v5
  with:
    token: ${{ secrets.CODECOV_TOKEN }}
    files: ./tests/php-coverage.xml,./tests/python-coverage.xml
    flags: combined
    name: combined-coverage
```

#### Private Repository Setup

For private repositories:

1. Codecov Pro subscription required
2. Use the same token setup process
3. Ensure organization-level access if applicable

## Integration with Pull Requests

Codecov will automatically:
- Comment on pull requests with coverage changes
- Show coverage diff in the PR interface
- Block PRs if coverage drops below threshold (optional)

### PR Configuration

Add to `.codecov.yml`:

```yaml
comment:
  require_changes: true
  layout: "diff, flags, components"
  behavior: default
  
coverage:
  status:
    patch:
      default:
        target: 80%
        if_not_found: success
        only_pulls: true
```

## Security Considerations

- **Token Security**: Never expose the Codecov token in logs or code
- **Access Control**: Limit repository access to necessary personnel
- **Audit**: Regularly review who has access to repository secrets

## Support Resources

- **Codecov Documentation**: [docs.codecov.io](https://docs.codecov.io/)
- **GitHub Actions**: [docs.github.com/actions](https://docs.github.com/en/actions)
- **Codecov Support**: [support@codecov.io](mailto:support@codecov.io)

---

**📝 Note**: This setup provides comprehensive coverage tracking for both PHP and Python components of the SMF Sphinx AI Search Plugin, helping maintain code quality and identify untested areas.
