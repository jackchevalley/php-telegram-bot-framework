# PHP-FPM Setup and Optimization Guide

This guide explains how to install PHP-FPM 8.4 and optimize its configuration for high-performance Telegram bot webhooks.

## Why Optimize PHP-FPM?

- **Handle high traffic**: Telegram webhooks can receive many concurrent requests
- **Reduce timeouts**: Long-running operations need proper configuration
- **Memory management**: Prevent memory exhaustion
- **Performance**: Faster response times = better user experience

---

## Table of Contents

1. [Installation](#installation)
2. [Understanding PHP-FPM Configuration Files](#understanding-php-fpm-configuration-files)
3. [Optimizing php.ini](#optimizing-phpini)
4. [Optimizing www.conf (PHP-FPM Pool)](#optimizing-wwwconf-php-fpm-pool)
5. [Testing and Monitoring](#testing-and-monitoring)
6. [Troubleshooting](#troubleshooting)

---

## Installation

### Ubuntu/Debian - PHP 8.4

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
sudo apt -y install php8.4-fpm php8.4-cli php8.4-mysql php8.4-curl php8.4-mbstring php8.4-xml php8.4-gmp php8.4-bcmath php8.4-zip php8.4-intl sudo apt install php8.4-json

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

---

## Optimizing php.ini

### Backup Original File

```bash
sudo cp /etc/php/8.4/fpm/php.ini /etc/php/8.4/fpm/php.ini.backup
```

### Edit Configuration

```bash
sudo nano /etc/php/8.4/fpm/php.ini
```

### Recommended Settings for Telegram Bots

Find and modify these values (use `Ctrl+W` to search in nano):

#### **Resource Limits**

```ini
; Maximum memory a script can allocate
; Increase for heavy operations (image processing, large arrays)
memory_limit = 256M

; Maximum file upload size
upload_max_filesize = 64M

; Maximum POST data size (must be >= upload_max_filesize)
post_max_size = 64M

```

#### **Error Handling**

```ini
; Display errors (DISABLE in production!)
display_errors = Off
display_startup_errors = Off

; Log errors to file
log_errors = On
error_log = /var/log/nginx/php-errors.log

; Error reporting level (E_ALL in development, E_ALL & ~E_DEPRECATED in production)
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
```

#### **Performance**

```ini
; OPcache settings (IMPORTANT for performance!)
[opcache]
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
```

## Optimizing www.conf (PHP-FPM Pool)

### Backup Original File

```bash
sudo cp /etc/php/8.4/fpm/pool.d/www.conf /etc/php/8.4/fpm/pool.d/www.conf.backup
```

### Edit Configuration

```bash
sudo nano /etc/php/8.4/fpm/pool.d/www.conf
```

### Understanding Process Management

PHP-FPM uses a pool of worker processes. Three modes available:

1. **static** - Fixed number of workers (predictable, high memory)
2. **dynamic** - Workers spawn/die as needed (flexible, moderate memory)
3. **ondemand** - Workers spawn only when needed (low memory, slower response)

**Recommended for bots**: `dynamic`

### Recommended Settings for Telegram Bots

Find and modify these values:

#### **Process Manager Configuration**

```ini
; Process management mode
pm = dynamic

; Number of child processes (static mode only)
pm.max_children = 50

; Number of processes on startup (dynamic mode)
pm.start_servers = 10

; Minimum idle processes (dynamic mode)
pm.min_spare_servers = 5

; Maximum idle processes (dynamic mode)
pm.max_spare_servers = 20

; Maximum requests before respawn (prevents memory leaks)
pm.max_requests = 500
```

#### **Calculating max_children**

Formula: `(Total RAM - OS RAM - Other Services) / Average PHP Process Size`

Example with 4GB server:
- Total RAM: 4GB (4096MB)
- OS + Services: ~1GB (1024MB)
- Available: 3GB (3072MB)
- Average PHP process: ~50MB
- **max_children**: 3072 / 50 = ~60 (safe: 50)

Check your PHP process size:
```bash
ps aux | grep php-fpm | awk '{sum+=$6; count++} END {print "Average MB:", sum/count/1024}'
```

Check memory usage:
```bash
free -h | awk '/Mem:/ {print $7}'
```

Command for quick estimate, yes it's one whole command that should be executed all at once:
```bash
available=$(free -m | awk '/Mem:/ {print $7}')
avg_php=$(ps aux | grep php-fpm | grep -v grep | awk '{sum+=$6; count++} END {if(count>0) print sum/count/1024; else print 0}')
echo "Available: ${available}MB"
echo "Avg PHP Process: ${avg_php}MB"
if (( $(echo "$avg_php > 0" | bc -l) )); then
  echo "≈ Max PHP-FPM Workers: $(echo "$available / $avg_php" | bc)"
else
  echo "No php-fpm processes running."
fi
```

Based on this output, adjust `pm.max_children` accordingly and the other parameters as follows:
- `pm.start_servers` = 20% of `pm.max_children`
- `pm.min_spare_servers` = 10-20% of `pm.max_children`
- `pm.max_spare_servers` = 40% of `pm.max_children`

---

## Testing and Monitoring

### Restart PHP-FPM

After changing configurations:

```bash
# Test configuration syntax
sudo php-fpm8.4 -t

# Restart service
sudo systemctl restart php8.4-fpm

# Check status
sudo systemctl status php8.4-fpm
```

---

## Troubleshooting

### 1. **502 Bad Gateway (Nginx)**

PHP-FPM is not running or socket path is wrong.

```bash
# Check if running
sudo systemctl status php8.4-fpm

# Check socket exists
ls -la /run/php/php8.4-fpm.sock

# Check Nginx is using correct socket
grep fastcgi_pass /etc/nginx/sites-enabled/*

# Restart both services
sudo systemctl restart php8.4-fpm nginx
```

### 2. **504 Gateway Timeout**

Script execution exceeds timeout.

```bash
# Increase timeouts in php.ini
max_execution_time = 300

# Increase in www.conf
request_terminate_timeout = 300

# Increase in Nginx config
fastcgi_read_timeout 300;

# Restart
sudo systemctl restart php8.4-fpm nginx
```

### 3. **Too Many Connections / Slow Response**

Not enough PHP-FPM workers.

```bash
# Check current workers
ps aux | grep php-fpm | wc -l

# Increase pm.max_children in www.conf
# Then restart
sudo systemctl restart php8.4-fpm
```

### 4. **Out of Memory Errors**

PHP process exceeding memory limit.

```bash
# Increase memory_limit in php.ini
memory_limit = 256M

# Or reduce pm.max_children if server RAM is full
```

### 5. **Permission Denied Errors**

Study how permissions works, spend the time to understand it.<br>
It's common for new developers to misconfigure file permissions and create serious security issues.

Remember that PHP-FPM usually runs under `www-data` user and:
- your bot's `data/` folder should have 755 permission
- your bot's `other/private/` folders should have 600 permission

---

### Quick Optimization Checklist

- [ ] Install PHP 8.4 with all extensions
- [ ] Backup original configs
- [ ] Update `php.ini` with optimized settings
- [ ] Update `www.conf` with proper process management
- [ ] Enable OPcache
- [ ] Test configuration
- [ ] Restart PHP-FPM
- [ ] Check logs for errors

Your bot is now ready to handle high-traffic webhooks! 🚀
