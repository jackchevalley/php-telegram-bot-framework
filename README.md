# 🤖 Telegram Bot PHP Framework

A lightweight, modular PHP framework for building Telegram bots with webhook support.
Designed for rapid development with built-in features and resource management.

The framework can be easily customized to fit your bot's specific needs.
I've been using and improving it for years, and almost every bot I create is based on this structure.


## 📑 Table of Contents

- ✨ [Features](#-features)
- 📋 [Requirements](#-requirements)
- 🚀 [Quick Start](#-quick-start)
- 📁 [Project Structure](#-project-structure)
- 🎯 [Core Concepts](#-core-concepts)
- 💾 [Database Usage](#-database-usage)
- 👑 [Admin Commands](#-admin-commands)
- 🎨 [Commands and Input Handling](#-commands-and-input-handling)
- ⏰ [Cron Jobs](#-cron-jobs)
- 🔒 [Security Features](#-security-features)
- 📱 [Handling Different Message Sources](#-handling-different-message-sources)
- 📝 [Best Practices](#-best-practices)
- 💡 [Extra tips and Linux Setup Guides](#-extra-tips)
- 🤝 [Contributing](#-contributing)
- 📄 [License](#-license)
- 💬 [Support](#-support)

---


## ✨ Features

- **Fast webhook-based architecture** - Handles updates and closes the connection with Telegram quickly. Includes a filter to ensure only real Telegram requests are processed
- **Database integration** - MySQL/MariaDB support with prepared functions for secure queries, transactions, and user management. Fully built with PDO
- **Built-in cron system** - Schedule recurring tasks without external dependencies
- **Admin panel** - Separate admin commands and permission system for managing the bot, users, and possible admin groups
- **System metrics** - Monitor CPU, RAM, disk usage, and performance via a simple command accessible from the bot to the admins
- **Security** - IP verification, input sanitization, and secure database queries
- **Easy message handling** - Simplified API functions for sending messages, photos, videos, and more
- **Multi-environment support** - `.env` configuration for different deployments
- **Media and input support** - Handle any input: text, photos, videos, audio, documents, and more
- **User management** - Automatic user registration and profile updates
- **User blocking system** - Built-in blocked users management

---


## 📋 Requirements

- PHP 8.0 or higher (I am currently working with PHP 8.4)
- MySQL/MariaDB (optional, can be disabled)
- Composer
- Web server with HTTPS support (Telegram requires HTTPS for webhooks)
- Linux server (recommended for metrics features)
- You can read the [Linux Setup Guide](extra/linux/setup-ubuntu25.md) for a complete server setup.

---


## 🚀 Quick Start

### 1. Clone the Repository

You should clone this repo in the folder visible to the web. <br>
For the nginx configuration provided, it should be in `/var/www/html/`.

```bash
cd /var/www/html/
git clone https://github.com/jackchevalley/php-telegram-bot-framework.git
mv php-telegram-bot-framework my-project-name
cd my-project-name
```

Since we have called the folder `my-project-name`, the webhook URL will be:
```
https://your-domain.com/my-project-name/index.php
```

### 2. Install Dependencies

```bash
cd public/libs
composer install
cd ../../  # Return to project root
```

### 3. Configure Environment

Copy the example environment file and edit it:

```bash
cp extra/.env.example data/.env
nano data/.env
```

**Remember to edit** `data/.env` with your configuration.
Here you will set up the bot token, database credentials (if used), and other settings.


### 4. Configure Bot Settings

**Edit** `public/configs.php`:

```php
// Bot username
$bot_username = "YourBotUsername_bot";

// Domain URL
$DOMAIN_URL = "https://your-domain.com/";

// Main admin ID (first one is the main admin)
$MAIN_ADMIN = 123456789;
$ADMINS = [
    $MAIN_ADMIN,
    // Add more admin IDs here...
];

// Admin group chats
$GENERIC_ADMIN_CHAT_ID = -100131213121312;
$ADMIN_CHATS = [
    $GENERIC_ADMIN_CHAT_ID,
    // Add more admin chat IDs here...
];
```

These are the main bot settings that should be easily and quickly accessible, which is why they are not in `data/.env`.

### 5. Setup Database (optional)

Create a new database if not existing:

```sql
CREATE DATABASE your_database CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

Import the database structure (the file is located in the `extra/` folder):

```bash
mysql -u your_user -p your_database < extra/basic_database_structure.sql
```

The framework includes two tables:
- `users` - Store user information and temporary states
- `blocked_users` - Manage blocked users

### 6. Set Webhook

Run the webhook setup script:

```bash
php delupdates.php
```

This script will:
1. Delete existing webhook
2. Clear pending updates
3. Set new webhook with your URL

### 7. Remove unwanted files

Clean up unused files for security and organization.
The `extra/` folder contains only files that are not needed at this point and should be entirely removed from your project.

```bash
rm -rf extra/
```

To keep everything clean, you can also remove this README file if you want.

### 8. Test Your Bot

Send `/start` to your bot on Telegram. You should receive a welcome message!

---


## 📁 Project Structure

```
├── index.php                # Main webhook entry point
├── comandi.php              # Command handlers and routing
├── delupdates.php           # Webhook setup script
├── data/
│   └── posts/               # Example data storage folder
├── extra/
│   ├── .env.example         # Example environment file (copy to data/.env)
│   ├── basic_database_structure.sql  # Database schema
│   ├── linux/               # Linux setup guides
│   ├── nginx/               # Nginx configuration files
│   └── php-fpm/             # PHP-FPM optimization guide
├── public/
│   ├── access.php           # IP verification (Telegram servers)
│   ├── configs.php          # Bot configuration
│   ├── database.php         # Database functions
│   ├── env_loader.php       # Environment loader
│   ├── functions.php        # Core bot functions
│   ├── metrics_functions.php # System monitoring
│   └── libs/                # Composer dependencies
├── other/
│   ├── private/
│   │   └── cron/            # Cron job system
│   │       ├── runner.php   # Cron runner
│   │       ├── modules/     # Cron task modules
│   │       └── resources/   # Cron utilities
│   └── sections/
│       ├── admin_commands.php  # Admin command handlers
│       ├── channels.php     # Channel message handlers
│       └── groups.php       # Group message handlers
```

**File Structure Explanation:**

- `index.php`: Main entry point for webhook updates
- `comandi.php`: Main command and input handler
- `delupdates.php`: Webhook setup utility script
- `data/`: Folder to store/write files, such as `.env`, language files, user data, etc.
- `extra/`: Folder with setup guides and example files from the repository, <u>should be deleted</u> after setup
- `public/`: Core bot files and configurations, includes all necessary functions that could be used anywhere
- `other/`: Additional code logic, not functions
- `other/sections/`: Handlers for specific messages such as admin commands, groups/channels messages, and more
- `other/private/`: Private code that should not be accessible from outside, such as cron jobs or any other background tasks

In order to keep it functional as it should be, make sure to set proper permissions:
```bash
# Allow web server to write in data folder
sudo chown -R www-data:www-data data/
chmod -R 755 data/

# Protect private folder from web access
chmod -R 700 other/private/
```
This will allow the bot to write files in the data folder while keeping private files safe from outside access.

---


## 🎯 Core Concepts

### Message Flow

1. **Webhook receives update** → `index.php`
2. **IP verification** → `public/access.php`
3. **Fast response to Telegram** → Connection closed immediately
4. **Parse update data** → Extract message, user info, etc. (`public/functions.php`)
5. **Load classes and connectors** → Environment, Database, HTTP client, Redis (if used)
6. **Route to handler** → `comandi.php`
7. **Process command** → Execute bot logic or redirect to sections
8. **Send response** → Prepare and send message back to user
9. **Close connectors** → Clean up database and HTTP connections

### Variables from the Update

The framework automatically extracts some variables from Telegram updates, you can find all of them in `public/functions.php`:

```php
$update      // Raw update array
$chatID      // Chat ID
$userID      // User ID
$name        // User's first name
$username    // User's username (if available, unset if not)
$msg         // Message text or callback data
$message_id  // Message ID (unset if it is a callback, you'll find it in $cbmid)
$cbid        // Callback query ID
$cbmid       // Callback message ID
$caption     // Media caption (unset if not present or not a media)
$photo       // Photo file_id
$video       // Video file_id
$voice       // Voice file_id
// ... and more
```

### Sending Messages

#### Simple Text Message

```php
sm($chatID, "Hello World!");
```
The `sm()` function sends a message to the specified chat ID.
It accepts strings or arrays (for multi-line messages).
It can be useful for creating big messages easily.
```php
$output_text = [];
$output_text[] = "Hello World!";
$output_text[] = "";
$output_text[] = "This is a multi-line message.";

sm($chatID, $output_text);
```


#### Message with Inline Keyboard

```php
$output_text = [];
$output_text[] = "Hello World!";
$output_text[] = "";
$output_text[] = "This is a multi-line message.";

$inline_menu = [];
$inline_menu[] = [
    ['text' => "Option 1", 'callback_data' => '/option1'],
    ['text' => "Option 2", 'callback_data' => '/option2']
];
$inline_menu[] = [
    ['text' => "Back", 'callback_data' => '/start']
];

sm($chatID, $output_text, $inline_menu);
```

Each element added as `$inline_menu[] = [...]` represents a new row of buttons.
To insert multiple buttons in the same row, add more button arrays inside the same array.


#### Message with Reply Keyboard

```php
$text = "Choose from the keyboard:";

$hard_menu = [
    [['text' => "Button 1"], ['text' => "Button 2"]],
    [['text' => "Button 3"]]
];

sm($chatID, $text, hard_menu: $hard_menu);
```

#### Send and Delete Previous (Clean UI)

This function sends a new message and deletes the previous one sent by the bot in the same chat (if sent using this same function).

```php
// Sends new message and deletes the previous one
smg($chatID, "Updated message", $inline_menu);
```

This can be very useful to keep the chat clean when navigating menus or updating information.
I suggest imagining the bot structure like this:
- **Main section messages**: Use `smg()` to always have only one message per section. This prevents users from clicking old buttons, going back and forth, and potentially creating conflicts, for example with input requests.
- **Subsection messages**: Use normal `sm()` messages to provide additional information without deleting the main section message.

### Available Message Functions

You can find all the functions provided by the framework for sending and managing messages in the `public/functions.php` file.

We have included many functions to handle various tasks, but some of them may not be needed for your specific bot. We suggest cleaning up the unused functions to keep your code light and easy to maintain.

---


## 💾 Database Usage

### Secure Query Execution

The framework provides a `secure()` function for safe database queries.
You don't need to perform manual connection or prepare statements; just call the secure function, and it will take care of the connection, preparing, and executing the query.

```php
// Execute query without results
secure("UPDATE users SET first_name = :first_name, username = :username WHERE user_id = :id", [
    'first_name' => $name,
    'username' => $username ?? null,        // maybe the user does not have a username
    'id' => $userID
]);
```

The secure function accepts three parameters:
1. **SQL Query** - The SQL statement with named placeholders (e.g., `:name`, `:id`)
2. **Parameters Array** - An associative array mapping placeholders to values
3. **Fetch Mode** (optional) - Determines the type of result to return

### Fetch Modes

The `secure()` function supports different fetch modes:
- **0** (default): No result
- **1**: Fetch single row, the first one (associative array)
- **2**: Get row count (number of affected rows), useful to check whether an UPDATE or DELETE affected any rows or for quickly checking the existence of rows
- **3**: Fetch all rows (array of associative arrays)
- **4**: Get last insert ID (for INSERT queries)

```php
// Fetch single row
$user = secure("SELECT * FROM users WHERE user_id = :id", 
    ['id' => $userID], 
    1  // Fetch mode: 1 = single row
);
echo json_encode($user); 
>> {"user_id":"123456789","first_name":"John","username":"john_doe", ...}

// Fetch all rows
$users = secure("SELECT * FROM users WHERE attivo = 1", 
    [], 
    3  // Fetch mode: 3 = all rows
);
echo json_encode($users);
>> [
    {"user_id":"123456789","first_name":"John","username":"john_doe", ...}, 
    {"user_id":"987654321","first_name":"Jacopo","username":"jackchevalley", ...}, 
    {...}, 
]

// Get last insert ID
$last_row_ID = secure("INSERT INTO users (user_id, first_name) VALUES (:id, :name)", 
    ['id' => $userID, 'name' => $name], 
    4  // Fetch mode: 4 = last insert ID
);
echo "New user ID: " . $last_row_ID;
>> New user ID: 42
```

### Transaction Support
Useful for operations that require multiple queries to be executed atomically and for SELECT FOR UPDATE operations.
```php
// Start transaction and lock row
$user = transaction_start(
    "SELECT * FROM users WHERE user_id = :id",
    ['id' => $userID]
);

// Perform operations...
secure("UPDATE users SET balance = balance - 10 WHERE user_id = :id", 
    ['id' => $userID]
);

// Commit transaction
transaction_commit();

// Or rollback on error
transaction_rollback();
```

### User Management

The framework automatically:
- Creates new users on first interaction
- Updates user information (name, username)
- Stores temporary states in `temp` column
- Tracks last message ID for cleanup

The temporary state (`temp` column) can be used to manage multistep interactions with users.
For example, a user clicks a button where it asks for an input, you can set a temporary state to wait for that input.

```php

// When clicking the button that will ask for input
    ...
    temp("waiting_for_name");
}


// Later, when receiving a message from the user we can check if the message is expected
if ($us['temp'] == "waiting_for_name") {
    ...


// When done, clear the temporary state just by calling the function without parameters
temp();
```

---


## 👑 Admin Commands

### Define Admin Section

Admins are configured in `public/configs.php`:

```php
$MAIN_ADMIN = 123456789;
$ADMINS = [
    $MAIN_ADMIN,
    987654321,
    // More admin IDs...
];
```

### Create Admin Commands

Edit `other/sections/admin_commands.php`:

```php
if ($msg == '/admin_stats') {
    $total_users = secure("SELECT COUNT(*) as cnt FROM users", [], 1)['cnt'];
    $active_users = secure("SELECT COUNT(*) as cnt FROM users WHERE attivo = 1", [], 1)['cnt'];
    
    $text = [
        "📊 <b>Bot Statistics</b>",
        "",
        "Total users: $total_users",
        "Active users: $active_users"
    ];
    
    sm($chatID, $text);
    die();
}
```

The structure of the framework allows you to add management groups and different levels of permissions easily by checking the `$chatID` against the configured admin chats.

Take for example the `/status` command that shows system metrics, it's available to admins everywhere and to any user that is in the selected group.

### System Metrics

The built-in `/status` command shows:
- CPU usage and cores
- RAM usage (total, used, available)
- Disk usage
- System uptime
- Load average
- Database performance
- API request latency

The limits for warnings and critical alerts should be edited by you according to your server specifications.

---


## 🎨 Commands and Input Handling

The framework, after initializing variables, routes all commands and messages to `comandi.php`.
Here it will populate some more variables used only in the commands handling *(the `if(true)` is just to collapse the code in IDEs)*.

The code structure splits between messages and media, since messages could be commands but media cannot.

After splitting messages and media, it splits again between commands (starting with `/`) and potential inputs.

If you are using a Reply Keyboard, you can map buttons to commands using the `$COMMANDS_ALIAS` array.

### Basic Command Handler

```php
// In comandi.php

if ($msg == "/start") {
    $text = [];
    $text[] = "<b>Welcome <a href='tg://user?id=$userID'>$name</a>!</b>";
    $text[] = "";
    $text[] = "This is a sample bot built with the Telegram Bot PHP Framework.";
    
    $inline_menu = [
        [
            ['text' => "📋 Menu", 'callback_data' => '/menu'],
            ['text' => "ℹ️ Info", 'callback_data' => '/info']
        ]
    ];
    
    if (isset($cbmid)) {
        // Called from callback, we have to edit the message and answer the callback.
        cb_reply($text, $inline_menu);
    } else {
        // Called from text message, we have to send a new message. Since the /start is a main section message (it has buttons to navigate), we use smg()
        smg($chatID, $text, $inline_menu);
    }
    
    temp(); // Clear temp state. The /start command is a simple way to reset state and cancel inputs.
}
```

### User Inputs - Temporary States

```php
# In the command section 
// Set state
if ($msg == "/setname") {
    sm($chatID, "Please send your new name:");
    temp("waiting_for_name");
    die();
}


# In the input section
// Handle state
if ($us['temp'] == "waiting_for_name" && isset($msg)) {
    secure("UPDATE users SET custom_name = :name WHERE user_id = :id", [
        'name' => $msg,
        'id' => $userID
    ]);
    
    sm($chatID, "Name updated to: $msg");
    temp(); // Clear state
    die();
}
```

### Command Aliases

```php
$COMMANDS_ALIAS = [
    '/menu' => '/start',
    '/help' => '/info',
    'Settings ⚙️' => '/settings',
];
```

### Lock Callback-Only Commands
If a command should be executed only from an inline button (callback query), you can use the `lock_non_callback()` function to enforce this.

It will lock the command so that if a user tries to execute it by sending the text, it will be ignored.
Useful to prevent abuse of commands that should only be triggered by buttons.
```php
if ($msg == "/special_action") {
    lock_non_callback(); // Ensures command is from button, not text
    
    // Process action...
    cb_reply("Action completed!");
}
```

### Suggested Structure for Commands and Temporary States

I find it very useful to give a distinct structure to commands and their relative temporary states.

Imagine your bot having this structure:
- **Main Menu**
- **Settings**
    - Change Name
    - Change Language
- **Support**
    - Info
    - Contact
    - Rules

I suggest structuring your code in sections for better organization and splitting commands from inputs.

Inside the commands section you can have something like this:
```php

// Start command
if ($msg == '/start') {
    
    $text = [];
    $text[] = "<b>Welcome <a href='tg://user?id=$userID'>$name</a>!</b>";
    $text[] = "";
    $text[] = "This is the main menu. Choose an option:";
    
    $inline_menu = [];
    $inline_menu[] = [
        ['text' => "Settings ⚙️", 'callback_data' => '/settings'],
        ['text' => "Support 🆘", 'callback_data' => '/support']
    ];
    
    if (isset($cbmid)) {
        cb_reply($text, $inline_menu);
    } else {
        smg($chatID, $text, $inline_menu);
    }
    
    temp(); // Clear any previous state
}

// Section for settings
elseif (str_starts_with($msg, '/settings')) {

    // Handler panel of the settings
    if ($msg == '/settings') {
        
        $text = [];
        $text[] = "<b>Settings</b>";
        $text[] = "";
        $text[] = "Choose an option:";
        
        $inline_menu = [];
        $inline_menu[] = [
            ['text' => "Change Name", 'callback_data' => '/settings_name'],
            ['text' => "Change Language", 'callback_data' => '/settings_language']
        ];
        $inline_menu[] = [
            ['text' => "Back to Main Menu", 'callback_data' => '/start']
        ];
        
        if (isset($cbmid)) {
            cb_reply($text, $inline_menu);
        } else {
            smg($chatID, $text, $inline_menu);
        }
        
        temp(); // Clear any previous state
    }
    
    // Handler for changing name (request to input)
    elseif ($msg == '/settings_name') {
        lock_non_callback();
        
        cb_reply("Please insert your name now");
        temp("settings_name");
    }
    
    // Handler for changing language (click the language)
    elseif (str_starts_with($msg, '/settings_language')) {
        lock_non_callback();
    
        // Check if a language was selected and save it
        if (str_starts_with($msg, '/settings_language_select')) {
            $selected_language = str_replace('/settings_language_select_', '', $msg);
            
            secure("UPDATE users SET language = :lang WHERE user_id = :id", [
                'lang' => $selected_language,
                'id' => $userID
            ]);
            
            $us['lang'] = $selected_language; // Update local variable
        }
        
        
        $text = [];
        $text[] = "<b>Choose your language</b>";
        $text[] = "";
        $text[] = "Current language: " . $us['lang'];
        
        $inline_menu = [];
        $inline_menu[] = [
            ['text' => "English ". bool_to_value($us['lang'] == 'en'), 'callback_data' => '/settings_language_select_en'],
            ['text' => "Italian ". bool_to_value($us['lang'] == 'it'), 'callback_data' => '/settings_language_select_it']
        ];
        
        $inline_menu[] = [
            ['text' => "Back to Settings", 'callback_data' => '/settings']
        ];
        cb_reply($text, $inline_menu);
        
        temp(); // Clear any previous state for safety
    }
    
}

// Section for support
elseif (str_starts_with($msg, '/support')) {
    
    // Main support panel
    if ($msg == '/support') {
        
        $text = [];
        $text[] = "<b>Support</b>";
        $text[] = "";
        $text[] = "Choose an option:";
        
        $inline_menu = [];
        $inline_menu[] = [
            ['text' => "Info", 'callback_data' => '/support_info'],
            ['text' => "Contact", 'callback_data' => '/support_contact'],
            ['text' => "Rules", 'callback_data' => '/support_rules']
        ];
        $inline_menu[] = [
            ['text' => "Back to Main Menu", 'callback_data' => '/start']
        ];
        
        if (isset($cbmid)) {
            cb_reply($text, $inline_menu);
        } else {
            sm($chatID, $text, $inline_menu);
        }
        
        temp(); // Clear any previous state
    }
    
    // Handler for info page
    elseif ($msg == '/support_info') {
    
        $text = "My bot information...";
        $inline_menu = [];
        $inline_menu[] = [
            ['text' => "Back to Support", 'callback_data' => '/support']
        ];
        
        if (isset($cbmid)) {
            cb_reply($text, $inline_menu);        
        } else {
            // We use sm() instead of smg() since this is a secondary message
            // and buttons don't lead to any input
            sm($chatID, $text, $inline_menu);
        }
    }

    // Add more handlers for /support_contact, /support_rules, etc...
}
```

**Benefits of this structure:**

This approach will help you keep your code organized and easy to maintain, especially as the bot grows in complexity.

Using clear sectioning helps you:
- Avoid conflicts between commands
- Reduce deeply nested if-else statements
- Make the code more readable and maintainable
- Easily find and modify specific features
- Handle complex navigation flows with clarity

---


## ⏰ Cron Jobs

The framework includes a built-in cron system that runs independently.

### Setup Cron Runner

You should set up a Docker container to run the cron runner.
It works in a while (true) loop, so it just needs to stay alive and automatically restart when needed.

If you prefer not to use Docker, you could run it inside a screen session but that’s riskier, since the process could crash or the screen could be lost after a reboot.

If your project does not require heavy cron jobs, you can discard the runner and just execute the modules when needed from a conventional cron.

For example:
```bash
30 * * * * cd /path/to/bot/other/private/cron/modules/ && php module_to_run.php >> /dev/null 2>&1
```

### Configure Cron Tasks

If you need to run multiple cron tasks with different schedules, you can use the built-in runner.
Edit `other/private/cron/runner.php`:

```php
const ONE_MINUTE = 60;
const FIVE_MINUTES = 300;
const ONE_HOUR = 3600;

$FILES_RUN_TIME = [
    'task_every_minute.php'     => ONE_MINUTE,
    'daily_cleanup.php'         => "00:01",        // At 00:01
    'send_reminders.php'        => ["12:00", "18:00"], // Multiple times
    'check_subscriptions.php'   => FIVE_MINUTES,
];
```

### Create Cron Task

Create a file in `other/private/cron/modules/`:

```php
<?php
// other/private/cron/modules/send_daily_report.php

if(!defined('MAINSTART')) { die("<b>The request source has not been recognized. Make sure to execute from the provided entry point</b>"); }

// Your task logic here
$users = secure("SELECT * FROM users WHERE attivo = 1", [], 3);

foreach ($users as $user) {
    sm($user['user_id'], "Daily report: ...");
}

logger("Daily report sent to " . count($users) . " users");
```


### Built-in Broadcast System

We have included a broadcast module that can be used to send messages to all users in the database.
You can find it in `other/private/cron/modules/broadcast.php`.
For now, it's fairly basic and not implemented in the admin commands, but you can easily customize it to your needs.

---


## 🔒 Security Features

### IP Verification

`public/access.php` verifies requests come from Telegram:

```php
// Automatically included in index.php
// Blocks requests not from Telegram's IP ranges
```

### Strict Entry Point

At the very beginning of `index.php`, you’ll find this constant definition:

```php
const MAINSTART = true;
```

This constant acts as a security flag. <br>
Every internal file includes a check like this:

```php
if(!defined('MAINSTART')) { die("<b>The request source has not been recognized. Make sure to execute from the provided entry point</b>"); }
```

If the constant isn’t defined, it means the file was accessed directly rather than through an authorized entry point.
The script will immediately stop and display an error message.

Because your project may have multiple entry points (for example, for cron jobs or other API endpoints),
you must always define this constant at the very top of each of them.
Otherwise, internal includes will fail due to the missing definition.

Finally, remember to include the same initial check in every new internal file you create.
This ensures that no one can access it directly from outside the provided entry points.


### Input Sanitization

User input is automatically sanitized to avoid HTML tags issues while displaying messages or names.

```php
$msg = strip_tags($update["message"]["text"]);
$name = strip_tags($update["message"]["from"]["first_name"]);
```

Always remember to use the `secure()` function to call the database with user parameters. <u>Never</u> put them in the query directly.

### Blocked Users

A simple blocked users system is included.
Just add the user to the `blocked_users` table and the framework will automatically block any interaction from that user.

```php
// Block a user
secure("INSERT INTO blocked_users (user_id, by_user_id) VALUES (:uid, :aid)", [
    'uid' => $blocked_user_id,
    'aid' => $admin_id
]);
// The framework will automatically block any interaction from that user, you can check it out in comandi.php
```

---


## 📱 Handling Different Message Sources

### Group Messages

Handled in `other/sections/groups.php`:

```php
if ($chatID < 0) {
    // It's a group message
    require_once 'other/sections/groups.php';
}
```

### Channel Posts

Handled in `other/sections/channels.php`:

```php
if (!isset($userID)) {
    // It's a channel post
    require_once 'other/sections/channels.php';
}
```

---


## 📝 Best Practices

1. **Always use prepared statements** - Never concatenate SQL queries
2. **Clear temp states** - Call `temp()` after completing actions
3. **Use callback locks** - Prevent command abuse with `lock_non_callback()`
4. **Handle both callbacks and messages** - Check for `$cbmid` in commands
5. **Sanitize input** - Even though framework does it, be careful with user data
6. **Clear Functions** - The framework provides many functions in public/functions.php, clean up the unused functions and keep your code light.

---


## 💡 Extra Tips

Here you can find some additional tips to set up your server and bot for better performance.

### 🐧 Linux Server Setup
I use to run my bots on Ubuntu servers. This framework is very light, you don't need a powerful server.

**Check out the complete guide:** [setup-ubuntu25](extra/linux/setup-ubuntu25.md)

This will help you set up a basic Ubuntu server with all the necessary components to run your bot smoothly.

### 🚀 Nginx Configuration
I suggest using Nginx as a web server for better performance with PHP-FPM.

**Check out the complete guide:** [setup-nginx](extra/nginx/setup-nginx.md)



### ⚡ PHP-FPM Optimization
Use PHP-FPM with a proper configuration for better performance.

**Check out the complete guide:** [php-tips](extra/php-fpm/php-tips.md)

---


## 🤝 Contributing

This framework is designed to be customized for your specific needs. Feel free to:

- Add new features in `public/functions.php`
- Create new sections in `other/sections/`
- Extend database schema in `data/`
- Add cron tasks in `other/private/cron/modules/`


## 📄 License

This framework is provided as is for building Telegram bots.
You are free to use and modify it for both personal and commercial projects.


## 💬 Support

For issues or questions about the framework structure, check:
- Telegram Bot API: https://core.telegram.org/bots/api
- PHP PDO Documentation: https://www.php.net/manual/en/book.pdo.php
- Guzzle HTTP Client: https://docs.guzzlephp.org/
- My Development channel: https://t.me/JacksWork

I hope this will help you create amazing bots or even start your own coding journey.
I'll always be grateful to the open-source framework that helped me when I began programming many years ago.
Today, I enjoy my work thanks to that code and the people who shared it.

---

**Built with ❤️ by [@JackChevalley ☭](https://t.me/JacksWork)**

