# Ubuntu 25 Server Setup Guide for Telegram Bot Hosting

Complete step-by-step guide to set up a production-ready Ubuntu 25 server for hosting Telegram bot webhooks with Nginx, PHP-FPM 8.4, and MariaDB.

> **📚 Deep Dive Guides Available:**
> - [Nginx Setup & Multi-Domain Configuration](../nginx/setup-nginx.md) - Detailed Nginx setup with security and multiple bots
> - [PHP-FPM Optimization Guide](../php-fpm/php-tips.md) - In-depth PHP performance tuning and "overclocking"

---

## 🎯 What This Guide Covers

This is a **quick setup guide** with essential commands. We include the fundamentals here, but we **strongly encourage** you to read the dedicated guides above for:
- Understanding **why** each configuration is important
- Learning **advanced optimization** techniques
- Setting up **multiple bot domains** securely
- **Troubleshooting** common issues
- **Security best practices**

---

## 📋 Requirements

- Fresh Ubuntu 25.04 server (also works on Ubuntu 24.04)
- Root or sudo access
- At least 1GB RAM (2GB+ recommended for production)
- Domain name pointing to your server (for HTTPS webhooks)

---

## Table of Contents

1. [System Preparation](#1-system-preparation)
2. [Nginx Installation](#2-nginx-installation)
3. [MariaDB Installation](#3-mariadb-installation)
4. [PHP 8.4 Installation](#4-php-84-installation)
5. [Nginx Configuration](#5-nginx-configuration)
6. [Testing the Setup](#6-testing-the-setup)
7. [phpMyAdmin Installation (Optional)](#7-phpmyadmin-installation-optional)
8. [System Cleanup](#8-system-cleanup)
9. [PHP Optimization ("Overclock")](#9-php-optimization-overclock)
10. [Next Steps](#10-next-steps)

---

## 1. System Preparation

Update the system and install essential tools.

```bash
# Update all packages
sudo apt update && sudo apt -y full-upgrade

# Clean up
sudo apt -y autoremove && sudo apt autoclean && sudo apt clean

# Install essential utilities
sudo apt -y install htop git zip unzip screen wget

# Clear terminal
clear
```

**What we installed:**
- `htop` - System monitoring tool
- `git` - Version control
- `zip/unzip` - Archive utilities
- `screen` - Terminal multiplexer (useful for long-running tasks)
- `wget` - Download utility

---

## 2. Nginx Installation

Nginx will serve as the web server handling webhook requests.

```bash
# Install Nginx
sudo apt -y install nginx

# Configure firewall (if UFW is active)
sudo ufw allow 'Nginx HTTP' && sudo ufw allow 'Nginx HTTPS' && sudo ufw allow 'Nginx Full'

# Enable Nginx to start on boot
sudo systemctl enable nginx

# Check status
sudo systemctl status nginx
```

> **🔐 Important Security Note:**  
> The default Nginx configuration will be replaced later with a **blocking server** that prevents unauthorized access via IP address.  
> See [Nginx Setup Guide](../nginx/setup-nginx.md) for details on multi-domain setup and security.

---

## 3. MariaDB Installation

MariaDB is a MySQL-compatible database for storing bot data.

### Install MariaDB 12.0

```bash
# Update system
sudo apt update

# Install prerequisites
sudo apt install software-properties-common dirmngr ca-certificates apt-transport-https -y

# Add MariaDB repository key
sudo mkdir -p /etc/apt/keyrings
curl -LsS https://mariadb.org/mariadb_release_signing_key.asc | gpg --dearmor | sudo tee /etc/apt/keyrings/mariadb_keyring.gpg > /dev/null

# Add MariaDB repository (Ubuntu 25.04 / 24.04 compatible)
echo "deb [signed-by=/etc/apt/keyrings/mariadb_keyring.gpg] http://mirror.mariadb.org/repo/12.0.2/ubuntu $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/mariadb.list

# Install MariaDB
sudo apt-get update
sudo apt-get install -y mariadb-server mariadb-client

# Start and enable MariaDB
sudo systemctl start mariadb && sudo systemctl enable mariadb
```

### Secure MariaDB Installation

```bash
# Run security script
sudo mariadb-secure-installation
```

**Follow the prompts:**
1. Enter current root password: Press Enter for none.
2. Switch to unix_socket authentication? **Yes**
3. Change root password? **Yes** (set a strong password, 32 characters with alphanumerics and symbols)
4. Remove anonymous users? **Yes**
5. Disallow root login remotely? **Yes**
6. Remove test database? **Yes**
7. Reload privilege tables? **Yes**

### Create Admin User

```bash
# Login to MariaDB
sudo mariadb -u root -p
```

Inside MariaDB console:

```sql
-- Create admin user (replace 'your_secure_password' with actual password)
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'your_secure_password';

-- Grant all privileges
GRANT ALL PRIVILEGES ON *.* TO 'admin'@'localhost' WITH GRANT OPTION;

-- Apply changes
FLUSH PRIVILEGES;

-- Exit
EXIT;
```

---

## 4. PHP 8.4 Installation

PHP-FPM 8.4 provides high-performance PHP processing.

```bash
# Update system
sudo apt update
sudo apt -y upgrade

# Install prerequisites
sudo apt -y install ca-certificates apt-transport-https software-properties-common

# Add Ondřej Surý's PPA (latest PHP versions)
sudo add-apt-repository -y ppa:ondrej/php

# Update package list
sudo apt update

# Install PHP 8.4 with essential extensions
sudo apt -y install php8.4-fpm php8.4-cli php8.4-mysql php8.4-curl php8.4-mbstring php8.4-xml php8.4-gmp php8.4-bcmath php8.4-zip php8.4-intl php8.4-json

# Start and enable PHP-FPM
sudo systemctl start php8.4-fpm && sudo systemctl enable php8.4-fpm

# Verify PHP version
php -v
```
> **📖 Learn how to properly setup PHP-FPM**  
> Read the [PHP-FPM Optimization Guide](../php-fpm/php-tips.md) for detailed configuration tuning and installation details.

---

## 5. Nginx Configuration

Configure Nginx with optimized settings and a basic default server.

### Step 1: Backup and Replace Main Config

```bash
# Backup original config
sudo cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup

# Remove original
sudo rm /etc/nginx/nginx.conf

# Create new optimized config
sudo nano /etc/nginx/nginx.conf
```

**Paste this configuration:**

```nginx
user www-data;
worker_processes auto;
pid /run/nginx.pid;
include /etc/nginx/modules-enabled/*.conf;

events {
    worker_connections 4096;
    multi_accept on;
    use epoll;
}

http {
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    access_log off;

    keepalive_timeout 65;
    types_hash_max_size 2048;

    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';

    # access_log /var/log/nginx/access.log main;
    error_log /var/log/nginx/error.log;

    # MIME
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # Gzip Compression
    gzip on;
    gzip_comp_level 5;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_vary on;
    gzip_types text/plain text/css text/xml application/xml application/json application/javascript application/rss+xml image/svg+xml;
    gzip_disable "MSIE [1-6]\.(?!.*SV1)";
    gzip_disable "msie6";

    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
```

**Save:** `Ctrl+O`, `Enter`, `Ctrl+X`

**Key optimizations:**
- `worker_processes auto` - Uses all CPU cores
- `worker_connections 4096` - Handles up to 4096 concurrent connections per worker
- `multi_accept on` - Accepts multiple connections at once
- `gzip on` - Compresses responses (faster transfer)
- `access_log off` - Reduces disk I/O (enable for debugging)

### Step 2: Create Default Server Configuration

```bash
# Remove default Nginx welcome page
sudo rm /etc/nginx/sites-available/default

# Create new default config
sudo nano /etc/nginx/sites-available/default.conf
```

**Paste the configuration you find at:** 
- [Default conf](../nginx/sample-default.conf) -  `extra/nginx/sample-default.conf`
- [Bot Webhook](../nginx/sample-default.conf) -  `extra/nginx/bot-webhook.conf`

### Step 3: Enable Configuration

```bash
# Remove old default symlink (if exists)
sudo rm /etc/nginx/sites-enabled/default

# Create new symlink
sudo ln -s /etc/nginx/sites-available/default.conf /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/bot-webhook.conf /etc/nginx/sites-enabled/

# Test Nginx configuration
sudo nginx -t

# Restart services
sudo systemctl restart nginx && sudo systemctl restart php8.4-fpm
```

If `nginx -t` shows errors, check your configuration for typos.

---

## 6. Testing the Setup

Create a test PHP file to verify everything works.

```bash
# Create phpinfo test file
sudo nano /var/www/html/index.php
```

**Paste:**

```php
<?php
phpinfo();
?>
```

**Save:** `Ctrl+O`, `Enter`, `Ctrl+X`

### Test in Browser

Visit: `http://your-domain.com/`

You should see the PHP info page showing:
- PHP Version 8.4.x
- Loaded extensions
- Configuration values

> **⚠️ Security Warning:**  
> **Replace this file immediately after testing:**
> ```php
> <?php
>   echo "Index working! Currently: ". date('Y-m-d H:i:s');
> ?>
> ```
> Exposing phpinfo() in production is a security risk.

### System Cleanup After Testing

```bash
# Update and clean
sudo apt update && sudo apt -y full-upgrade
sudo apt -y autoremove && sudo apt autoclean && sudo apt clean
```

---

## 7. phpMyAdmin Installation (Optional)

Web-based database management interface.

```bash
# Update system
sudo apt update

# Download phpMyAdmin 5.2.2
cd /tmp
wget https://files.phpmyadmin.net/phpMyAdmin/5.2.2/phpMyAdmin-5.2.2-all-languages.zip

# Extract
sudo unzip -q phpMyAdmin-5.2.2-all-languages.zip

# Move to system directory
sudo mv phpMyAdmin-5.2.2-all-languages /usr/share/phpmyadmin

# Create temp directory for phpMyAdmin
sudo mkdir /usr/share/phpmyadmin/tmp
sudo chown www-data:www-data /usr/share/phpmyadmin/tmp

# Create symlink to web root
sudo ln -s /usr/share/phpmyadmin /var/www/html
```

### Access phpMyAdmin

Visit: `http://your-server-ip/phpmyadmin`

Login with:
- Username: `admin`
- Password: (the password you set earlier)

---

## 8. System Cleanup

Final cleanup and reboot.

```bash
# Update everything
sudo apt update && sudo apt -y full-upgrade

# Remove unnecessary packages
sudo apt -y autoremove && sudo apt autoclean && sudo apt clean

# Reboot to apply all changes
sudo reboot
```

After reboot, verify services are running:

```bash
sudo systemctl status nginx
sudo systemctl status php8.4-fpm
sudo systemctl status mariadb
```

---

## 9. PHP Optimization ("Overclock")

Now that everything is working, let's **optimize PHP for high performance**.

> **📖 This is a quick overview. For detailed explanations, read:**  
> [PHP-FPM Optimization Guide](../php-fpm/php-tips.md)

---

## 10. Next Steps

Your server is now ready for Telegram bot hosting! 🎉


### Helpful Resources

- [Nginx Setup & Multi-Domain Guide](../nginx/setup-nginx.md) - **Must read for production**
- [PHP-FPM Optimization Guide](../php-fpm/php-tips.md) - **Deep dive into performance**
- [Bot Framework Documentation](../../README.md) - Main bot framework guide

---

**Happy bot hosting! 🤖🚀**

