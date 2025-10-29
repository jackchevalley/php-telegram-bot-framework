# ⚡ PHP-FPM Configuration and Optimization Tips

Complete guide for optimizing PHP-FPM for Telegram bot hosting with high performance and reliability.

## 📑 Table of Contents

- [What is PHP-FPM](#what-is-php-fpm)
- [Installation](#installation)
- [Understanding PHP-FPM Configuration Files](#understanding-php-fpm-configuration-files)

---

## What is PHP-FPM

**PHP-FPM** (FastCGI Process Manager) is an alternative PHP FastCGI implementation with features especially useful for high-load sites and applications.

**Benefits for Telegram Bots:**
- Better performance than traditional CGI
- Process isolation and management
- Graceful restart without downtime
- Adaptive process spawning
- Advanced logging and monitoring
- Lower memory footprint

---

## Installation

If you followed the [Ubuntu Setup Guide](../linux/setup-ubuntu25.md), PHP-FPM should already be installed. Otherwise:

```bash
# Update system
sudo apt -y upgrade

# Install prerequisites
sudo apt -y install ca-certificates apt-transport-https software-properties-common

# Add Ondřej Surý's PPA (provides latest PHP versions)
sudo add-apt-repository -y ppa:ondrej/php

# Update package list
sudo apt update

# Install PHP 8.4 with common extensions
sudo apt -y install php8.4-fpm php8.4-cli php8.4-mysql php8.4-curl php8.4-mbstring php8.4-xml php8.4-gmp php8.4-bcmath php8.4-zip php8.4-intl php8.4-json

# Start and enable PHP-FPM
sudo systemctl start php8.4-fpm && sudo systemctl enable php8.4-fpm

# Check status
sudo systemctl status php8.4-fpm
```
### Extension Breakdown

- **php8.4-fpm**: FastCGI Process Manager (required)
- **php8.4-cli**: Command-line PHP (for scripts)
- **php8.4-mysql**: MySQL/MariaDB support
- **php8.4-curl**: HTTP requests (Telegram API calls)
- **php8.4-mbstring**: Multi-byte string functions
- **php8.4-xml**: XML parsing
- **php8.4-gmp**: Large number calculations
- **php8.4-bcmath**: Arbitrary precision math
- **php8.4-zip**: ZIP archive handling
- **php8.4-intl**: Internationalization
- **php8.4-json**: JSON encoding/decoding

### Additional: Redis

```bash
# For Redis caching
sudo apt install redis php8.4-redis
```

---

## Understanding PHP-FPM Configuration Files

PHP-FPM has two main configuration files:

### 1. **php.ini** - PHP Language Settings
**Location**: `/etc/php/8.4/fpm/php.ini`

Controls PHP behavior:
- Memory limits
- Execution time
- Error reporting
- File uploads
- Security settings

### 2. **www.conf** - PHP-FPM Pool Configuration
**Location**: `/etc/php/8.4/fpm/pool.d/www.conf`

Controls process management:
- Number of worker processes
- Process lifecycle
- Performance tuning
- Resource limits