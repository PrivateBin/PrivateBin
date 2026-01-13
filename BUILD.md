# Build & Release Guide - PrivateBin-PQC

This document explains how to create production-ready builds that exclude development files.

---

## Quick Start

### Local Production Build

```bash
# Create production archives (tar.gz + zip)
make build

# Output: dist/PrivateBin-PQC-3.0.0.tar.gz
#         dist/PrivateBin-PQC-3.0.0.zip
#         dist/PrivateBin-PQC-3.0.0.tar.gz.sha256
#         dist/PrivateBin-PQC-3.0.0.zip.sha256
```

### Automated GitHub Release

```bash
# Tag and push (triggers automated release workflow)
git tag v3.0.0
git push origin v3.0.0

# GitHub Actions will automatically:
# 1. Create production archives
# 2. Generate SHA256 checksums
# 3. Create draft release
# 4. Attach artifacts
```

---

## File Exclusion System

### How It Works

**Production builds exclude development files using `.gitattributes`:**

```bash
# When you run `git archive`, Git reads .gitattributes
# Files marked with `export-ignore` are excluded automatically
```

### Excluded Files

The following files/directories are **NOT included** in production builds:

#### Development Documentation
- `DEPLOYMENT_CHECKLIST.md` - Operational deployment guide
- `PROJECT_COMPLETION_REPORT.md` - Implementation summary
- `BADGES.md` - Badge generation guide
- `CODE_OF_CONDUCT.md` - Community guidelines
- `Makefile` - Build tooling

#### Testing & CI/CD
- `bin/configuration-test-generator` - Test utilities
- `bin/icon-test` - Icon testing tools
- `tst/` - PHP unit tests
- `js/test/` - JavaScript unit tests
- `.github/` - GitHub Actions workflows
- `.travis.yml` - Travis CI config

#### Development Configuration
- `.codeclimate.yml` - Code Climate config
- `.scrutinizer.yml` - Scrutinizer config
- `.styleci.yml` - Style CI config
- `.vscode/` - VS Code settings
- `.devcontainer/` - Dev container config
- `.editorconfig` - Editor configuration
- `.csslintrc` - CSS linting
- `.jshintrc` - JS linting
- `js/eslint.config.js` - ESLint config
- `.php_cs` - PHP CS Fixer

#### Build Artifacts
- `.gitattributes` - Git export rules
- `.gitignore` - Git ignore rules

#### Localization Development
- `crowdin.yml` - Crowdin integration
- `i18n/en.json` - English template (included in production)

### What IS Included

Production builds contain:
- ✅ All PHP source code (`lib/`, `index.php`)
- ✅ JavaScript application code (`js/privatebin.js`, `js/legacy.js`)
- ✅ WASM modules for PQC (`js/mlkem-wasm/`)
- ✅ Templates (`tpl/`)
- ✅ CSS stylesheets (`css/`)
- ✅ Images and icons (`img/`)
- ✅ Configuration samples (`cfg/`)
- ✅ Vendor dependencies (`vendor/`)
- ✅ Documentation (`README.md`, `SECURITY.md`, `CHANGELOG.md`)
- ✅ License files (`LICENSE`, `COPYRIGHT`)
- ✅ **share.html** - QR share gateway (production feature)

---

## Build Methods

### Method 1: Makefile (Recommended for Local Builds)

**Advantages:**
- Simple command (`make build`)
- Automatic checksums
- Respects `.gitattributes` automatically
- Works on any Unix-like system

**Usage:**

```bash
# Build with default version (3.0.0)
make build

# Build with custom version
make build VERSION=3.1.0

# Clean build artifacts
make build-clean
```

**Output Structure:**

```
dist/
├── PrivateBin-PQC-3.0.0.tar.gz
├── PrivateBin-PQC-3.0.0.tar.gz.sha256
├── PrivateBin-PQC-3.0.0.zip
└── PrivateBin-PQC-3.0.0.zip.sha256
```

### Method 2: GitHub Actions (Recommended for Releases)

**Advantages:**
- Fully automated
- Consistent build environment
- SLSA provenance generation
- Integrated with GitHub Releases

**Workflow:** `.github/workflows/release.yml`

**Trigger:**

```bash
# Create annotated tag
git tag -a v3.0.0 -m "Release v3.0.0"

# Push tag to GitHub
git push origin v3.0.0
```

**What Happens:**

1. **Build Phase:**
   - Runs on `ubuntu-latest`
   - Uses `git archive` (respects `.gitattributes`)
   - Creates `.tar.gz` and `.zip` archives

2. **Checksum Phase:**
   - Generates SHA256 for each archive
   - Stores in separate `.sha256` files

3. **Release Phase:**
   - Extracts changelog for this version
   - Creates draft GitHub Release
   - Uploads archives and checksums
   - Generates SLSA provenance

4. **Review Phase:**
   - Release remains in draft state
   - Manual review and publish required

### Method 3: Manual Git Archive

**Advantages:**
- No dependencies (just Git)
- Full control over output

**Usage:**

```bash
# TAR.GZ archive
git archive --format=tar --prefix=PrivateBin-PQC-3.0.0/ HEAD | gzip > PrivateBin-PQC-3.0.0.tar.gz

# ZIP archive
git archive --format=zip --prefix=PrivateBin-PQC-3.0.0/ HEAD > PrivateBin-PQC-3.0.0.zip

# Generate checksums
sha256sum PrivateBin-PQC-3.0.0.tar.gz > PrivateBin-PQC-3.0.0.tar.gz.sha256
sha256sum PrivateBin-PQC-3.0.0.zip > PrivateBin-PQC-3.0.0.zip.sha256
```

**Note:** This respects `.gitattributes` automatically. No additional flags needed.

---

## Verification

### Verify Excluded Files

**Test that development files are excluded:**

```bash
# Build archive
make build

# Extract to temporary directory
mkdir /tmp/verify-build
cd /tmp/verify-build
tar xzf ~/path/to/dist/PrivateBin-PQC-3.0.0.tar.gz

# Check for excluded files (should return nothing)
find . -name "DEPLOYMENT_CHECKLIST.md"
find . -name "PROJECT_COMPLETION_REPORT.md"
find . -name ".github"
find . -name "tst"
find . -name "Makefile"

# If any results appear, .gitattributes needs updating
```

### Verify Included Files

**Test that production files are included:**

```bash
cd /tmp/verify-build/PrivateBin-PQC-3.0.0

# Core files (must exist)
ls -lh index.php
ls -lh share.html
ls -lh js/privatebin.js
ls -lh lib/Controller.php

# PQC files (must exist for v3.0.0)
ls -lh lib/FormatV3.php
ls -lh js/pqccrypto.js
ls -lh js/mlkem-wasm/

# Documentation (must exist)
ls -lh README.md
ls -lh SECURITY.md
ls -lh CHANGELOG.md
```

### Verify Checksums

```bash
# Download release archive and checksum
wget https://github.com/zologic/PrivateBin-PQC/releases/download/v3.0.0/PrivateBin-PQC-3.0.0.tar.gz
wget https://github.com/zologic/PrivateBin-PQC/releases/download/v3.0.0/PrivateBin-PQC-3.0.0.tar.gz.sha256

# Verify checksum
sha256sum -c PrivateBin-PQC-3.0.0.tar.gz.sha256
# Expected: PrivateBin-PQC-3.0.0.tar.gz: OK
```

---

## Adding New Files to Exclusion List

### When to Exclude

Exclude files that are:
- ✅ Development-only documentation
- ✅ Testing infrastructure
- ✅ CI/CD configuration
- ✅ Code quality tools
- ✅ Editor/IDE settings
- ✅ Build tooling

**Do NOT exclude:**
- ❌ Production features (like `share.html`)
- ❌ User-facing documentation (`README.md`, `SECURITY.md`)
- ❌ Core application code
- ❌ Dependencies (`vendor/`, WASM modules)

### How to Exclude

**Edit `.gitattributes`:**

```bash
# Add line with file path and export-ignore attribute
echo "NEW_DEV_FILE.md export-ignore" >> .gitattributes
```

**Example:**

```gitattributes
# Development documentation
DEPLOYMENT_CHECKLIST.md export-ignore
PROJECT_COMPLETION_REPORT.md export-ignore
NEW_ADMIN_GUIDE.md export-ignore

# Testing directories
tst/ export-ignore
js/test/ export-ignore
```

**Test the change:**

```bash
# Create test archive
git archive --format=tar HEAD | tar -t | grep "NEW_DEV_FILE.md"

# If nothing appears, exclusion works correctly
```

---

## Release Checklist

Before creating a production release:

### 1. Update Version Numbers

```bash
# Update version in all files
make increment VERSION=3.1.0

# Files updated:
# - README.md
# - SECURITY.md
# - doc/Installation.md
# - js/package.json
# - lib/Controller.php
# - Makefile
```

### 2. Update CHANGELOG.md

Add new section for this version:

```markdown
## 3.1.0 - YYYY-MM-DD

### Added
- New feature description

### Changed
- Changed feature description

### Fixed
- Bug fix description
```

### 3. Run Tests

```bash
# Run all unit tests
make test

# Expected: All tests pass
```

### 4. Create Local Build

```bash
# Generate production archives
make build VERSION=3.1.0

# Verify output
ls -lh dist/
```

### 5. Tag and Push

```bash
# Create signed tag (recommended)
git tag -s v3.1.0 -m "Release v3.1.0"

# Or create annotated tag
git tag -a v3.1.0 -m "Release v3.1.0"

# Push tag (triggers GitHub Actions)
git push origin v3.1.0
```

### 6. Review GitHub Release

- Go to: https://github.com/zologic/PrivateBin-PQC/releases
- Find draft release for v3.1.0
- Verify changelog content
- Verify attached artifacts (archives + checksums)
- **Publish release**

---

## Deployment from Production Build

### Extract Archive

```bash
# Download release archive
wget https://github.com/zologic/PrivateBin-PQC/releases/download/v3.0.0/PrivateBin-PQC-3.0.0.tar.gz

# Verify checksum
wget https://github.com/zologic/PrivateBin-PQC/releases/download/v3.0.0/PrivateBin-PQC-3.0.0.tar.gz.sha256
sha256sum -c PrivateBin-PQC-3.0.0.tar.gz.sha256

# Extract to web server directory
cd /var/www
tar xzf PrivateBin-PQC-3.0.0.tar.gz
mv PrivateBin-PQC-3.0.0 privatebin-pqc
```

### Post-Extraction Setup

```bash
cd /var/www/privatebin-pqc

# Create data directory (outside web root for security)
mkdir -p ../privatebin-data

# Set permissions
chown -R www-data:www-data ../privatebin-data
chmod 700 ../privatebin-data

# Configure PrivateBin
cp cfg/conf.sample.php cfg/conf.php
nano cfg/conf.php

# Edit configuration:
# - Set data directory path
# - Enable qrcode + qrshare
# - Configure other options as needed
```

### Verify WASM Files

```bash
# Check PQC WASM modules exist
ls -lh js/mlkem-wasm/*.wasm

# Verify web server serves WASM with correct MIME type
curl -I https://yourdomain.com/js/mlkem-wasm/mlkem768.wasm
# Expected: Content-Type: application/wasm
```

---

## Troubleshooting

### Problem: `.gitattributes` not respected

**Symptom:** Development files appear in archives

**Solution:**

```bash
# Verify .gitattributes syntax
cat .gitattributes | grep export-ignore

# Ensure files are committed
git status

# Test exclusion manually
git archive --format=tar HEAD | tar -t | grep "tst/"
# Should return nothing if excluded correctly
```

### Problem: `make build` fails

**Symptom:** `make: *** No rule to make target 'build'`

**Solution:**

```bash
# Ensure Makefile has been updated
grep "^build:" Makefile

# If missing, pull latest changes
git pull origin master

# Or manually add build target (see Makefile section above)
```

### Problem: GitHub Actions release not triggered

**Symptom:** No draft release created after pushing tag

**Solution:**

```bash
# Check tag format (must start with 'v')
git tag -l

# Verify workflow file exists
ls -lh .github/workflows/release.yml

# Check workflow runs
# Go to: https://github.com/zologic/PrivateBin-PQC/actions
```

### Problem: WASM files missing in production build

**Symptom:** PQC pastes fail to decrypt

**Solution:**

```bash
# Verify WASM files are NOT in .gitattributes
grep "wasm" .gitattributes
# Should return nothing (WASM must be included)

# Check WASM files exist in repository
ls -lh js/mlkem-wasm/

# Rebuild archive
make build
```

---

## Advanced Topics

### Custom Archive Prefix

```bash
# Change directory name inside archive
git archive --format=tar --prefix=custom-name/ HEAD | gzip > build.tar.gz

# Extract will create "custom-name/" directory
```

### Splitting Archives by Component

```bash
# Backend only
git archive --format=tar HEAD lib/ cfg/ vendor/ index.php | gzip > backend.tar.gz

# Frontend only
git archive --format=tar HEAD js/ css/ tpl/ share.html | gzip > frontend.tar.gz
```

### Creating Signed Archives

```bash
# Create archive
make build

# Sign with GPG
gpg --detach-sign --armor dist/PrivateBin-PQC-3.0.0.tar.gz

# Verify signature
gpg --verify dist/PrivateBin-PQC-3.0.0.tar.gz.asc dist/PrivateBin-PQC-3.0.0.tar.gz
```

---

## File Size Comparison

**Typical archive sizes:**

| Archive Type | Size (v3.0.0) | Files Included |
|--------------|---------------|----------------|
| Full repository | ~25 MB | All files including tests, docs, CI |
| Production build | ~8 MB | Core application + dependencies |
| Minimal (no vendor) | ~1 MB | Core application only |

**Size breakdown:**
- `vendor/` directory: ~6 MB (Composer dependencies)
- `js/` directory: ~800 KB (includes WASM modules)
- `tst/` directory: ~500 KB (excluded from production)
- Development docs: ~200 KB (excluded from production)

---

## Security Considerations

### Checksum Verification

**Always verify checksums before deployment:**

```bash
sha256sum -c PrivateBin-PQC-3.0.0.tar.gz.sha256
```

**Why it matters:** Prevents deployment of tampered archives.

### SLSA Provenance

GitHub Actions generates SLSA provenance for releases:
- Proves archive was built by GitHub Actions
- Verifies build inputs and environment
- Detects tampering in build process

**Verify provenance:**

```bash
# Install slsa-verifier
go install github.com/slsa-framework/slsa-verifier/v2/cli/slsa-verifier@latest

# Verify artifact
slsa-verifier verify-artifact \
    --provenance-path provenance.intoto.jsonl \
    --source-uri github.com/zologic/PrivateBin-PQC \
    PrivateBin-PQC-3.0.0.tar.gz
```

### Reproducible Builds

Production builds are reproducible:
- `git archive` always produces identical output for same commit
- No timestamps or random data in archives
- Same commit = same archive hash

**Verify reproducibility:**

```bash
# Build twice
make build
sha256sum dist/PrivateBin-PQC-3.0.0.tar.gz

make build-clean
make build
sha256sum dist/PrivateBin-PQC-3.0.0.tar.gz

# Hashes should match exactly
```

---

**For deployment procedures after extraction, see `DEPLOYMENT_CHECKLIST.md`.**
