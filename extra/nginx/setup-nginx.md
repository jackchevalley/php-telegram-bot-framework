# Nginx Setup Guide for Telegram Bot Webhook

This guide explains how to install and configure Nginx with a secure default server that blocks unwanted access and dedicated virtual hosts for your bot webhooks.

Written for Ubuntu/Debian systems but adaptable to others.

## Why This Configuration?

- **Security First**: The default server blocks direct IP access and requests from non-configured domains
- **Multiple Bots**: Easily run multiple bot webhooks on different domains from the same server
- **Protection**: Prevents unauthorized access and scanning attempts

---

## Table of Contents

1. [Installation](#installation)
2. [Configuration Structure](#configuration-structure)
3. [Setup Default Blocking Server](#setup-default-blocking-server)
4. [Setup Bot Webhook Domain](#setup-bot-webhook-domain)
5. [Adding Multiple Domains](#adding-multiple-domains)
6. [SSL/HTTPS Setup](#sslhttps-setup)
7. [Testing and Troubleshooting](#testing-and-troubleshooting)

---

## Installation

### Ubuntu/Debian

```bash
# Update package list
sudo apt update

# Install Nginx
sudo apt install nginx -y

# Allow Nginx through the firewall (if UFW is enabled)
sudo ufw allow 'Nginx HTTP' 
sudo ufw allow 'Nginx HTTPS' 
sudo ufw allow 'Nginx Full'

# Start and enable Nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```


---

## Configuration Structure

Nginx uses two main directories for configurations:

- **`/etc/nginx/sites-available/`** - Store all configuration files here
- **`/etc/nginx/sites-enabled/`** - Symlinks to active configurations

This allows you to enable/disable sites without deleting config files.

---

## Setup Default Blocking Server

The default server catches all requests that don't match configured domains (direct IP access, wrong domains, scanners, etc.).

### Step 1: Remove Default Nginx Page

```bash
# Remove or disable the default welcome page
sudo rm /etc/nginx/sites-enabled/default
sudo rm /etc/nginx/sites-available/default
```

### Step 2: Install the Blocking Configuration

```bash
# Copy the sample-default.conf to sites-available
sudo cp sample-default.conf /etc/nginx/sites-available/default.conf

# Enable it by creating a symlink
sudo ln -s /etc/nginx/sites-available/default.conf /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### What This Does

When someone accesses your server via:
- Direct IP address (e.g., `http://123.45.67.89`)
- Wrong domain name
- Scanning/probing tools

They will receive:
```
HTTP/1.1 403 Forbidden
Access Denied: This resource can be accessible only from selected domains
```

**No code execution occurs** - it's pure blocking at the Nginx level.

---

## Setup Bot Webhook Domain

Now configure a proper domain for your Telegram bot webhook.

### Step 1: Prepare the Configuration

```bash
# Copy the bot-webhook.conf template
sudo cp bot-webhook.conf /etc/nginx/sites-available/bot.yourdomain.com.conf
```

### Step 2: Edit the Configuration

```bash
sudo nano /etc/nginx/sites-available/bot.yourdomain.com.conf
```

**Change these values:**

1. **server_name**: Replace `your-domain.com` with your actual domain
   ```nginx
   server_name bot.yourdomain.com;
   ```

2. **root**: Update the path to your bot directory (if needed, I usually keep html/)
   ```nginx
   root /var/www/html;
   ```

3. **PHP version**: Check your PHP-FPM socket path
   ```bash
   # Find your PHP socket
   ls /run/php/
   ```
   
   Update if needed:
   ```nginx
   fastcgi_pass unix:/run/php/php8.4-fpm.sock;
   ```

### Step 3: Enable the Configuration

```bash
# Create symlink to enable the site
sudo ln -s /etc/nginx/sites-available/bot.yourdomain.com.conf /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

## Adding Multiple Domains

You can run multiple bot instances on different domains from the same server. This is useful for:
- Running multiple bots
- Separate staging/production environments
- Different webhook endpoints

### Method 1: One Config File Per Domain

```bash
# Copy the template for each bot
sudo cp /etc/nginx/sites-available/bot.yourdomain.com.conf /etc/nginx/sites-available/bot2.example.com.conf

# Edit the new config
sudo nano /etc/nginx/sites-available/bot2.example.com.conf
```

Update:
```nginx
server_name bot2.example.com;
root /var/www/bot2-directory;
error_log /var/log/nginx/bot2.error.log warn;
```

Enable it:
```bash
sudo ln -s /etc/nginx/sites-available/bot2.example.com.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Method 2: Multiple Domains, Same Bot

If you want multiple domains pointing to the same bot:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name bot.domain1.com bot.domain2.com bot.domain3.com;
    
    root /var/www/html;
    # ... rest of configuration
}
```

### Example Multi-Bot Setup

```
/etc/nginx/sites-available/
├── default                          # Blocking server
├── mainbot.example.com.conf         # Production bot
├── testbot.example.com.conf         # Testing bot
└── anotherbot.anotherdomain.com.conf  # Different project

/etc/nginx/sites-enabled/
├── default -> ../sites-available/default
├── mainbot.example.com.conf -> ../sites-available/mainbot.example.com.conf
├── testbot.example.com.conf -> ../sites-available/testbot.example.com.conf
└── anotherbot.anotherdomain.com.conf -> ../sites-available/anotherbot.anotherdomain.com.conf
```

**Benefits:**
- Each bot is isolated
- Different PHP configurations possible per bot
- Easy to enable/disable specific bots
- Separate logging per domain
- Different root directories and permissions

---

## SSL/HTTPS Setup

Telegram requires HTTPS for webhooks. The configurations provided are ready for SSL from Cloudflare with certificate between Client and Cloudflare.

---

## Testing and Troubleshooting

### Test Nginx Configuration

```bash
# Check syntax
sudo nginx -t

# Detailed test
sudo nginx -T
```

### Check if Sites are Enabled

```bash
ls -la /etc/nginx/sites-enabled/
```

### View Logs

```bash
# Default blocking server logs
sudo tail -f /var/log/nginx/blocked.access.log
sudo tail -f /var/log/nginx/blocked.error.log

# Bot domain logs
sudo tail -f /var/log/nginx/bot.error.log

# All Nginx errors
sudo tail -f /var/log/nginx/error.log
```


### Common Issues

#### 1. **502 Bad Gateway**

PHP-FPM not running or wrong socket path.

```bash
# Check PHP-FPM status
sudo systemctl status php8.4-fpm

# Start it
sudo systemctl start php8.4-fpm

# Check socket exists
ls -la /run/php/
```

#### 2. **403 Forbidden on Bot Domain**

Permissions issue.

```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/your-bot-directory
sudo chmod -R 755 /var/www/your-bot-directory
```

#### 3. **Configuration Not Loading**

```bash
# Make sure symlink exists
ls -la /etc/nginx/sites-enabled/

# Reload Nginx
sudo systemctl reload nginx
```

#### 4. **Domain Not Resolving**

Check DNS:
```bash
# Check DNS resolution
nslookup bot.yourdomain.com

# Or
dig bot.yourdomain.com
```

---

## Security Best Practices

1. **Always use HTTPS** for production webhooks
2. **Keep the default blocking server** active
3. **Use separate configurations** for each bot/domain
4. **Regularly update** Nginx and PHP
5. **Monitor logs** for suspicious activity
6. **Disable access logs** for bot webhooks (high traffic)
7. **Set proper file permissions**

---

## Quick Reference Commands

```bash
# Test Nginx config
sudo nginx -t

# Reload Nginx (no downtime)
sudo systemctl reload nginx

# Restart Nginx
sudo systemctl restart nginx

# Enable a site
sudo ln -s /etc/nginx/sites-available/mysite.conf /etc/nginx/sites-enabled/

# Disable a site
sudo unlink /etc/nginx/sites-enabled/mysite.conf

# View error logs
sudo tail -f /var/log/nginx/error.log

# View blocked attempts
sudo tail -f /var/log/nginx/blocked.access.log
```
