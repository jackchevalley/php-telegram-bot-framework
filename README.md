# 🤖 Telegram Bot PHP Framework

A lightweight, modular PHP framework for building Telegram bots with webhook support. 
Designed for rapid development with built-in features and resources management.

The framework can be easily customized to fit your bot's specific needs.
I've been using and improving it for years and almost every bot I create is based on this structure.

## ✨ Features

- **Fast webhook-based architecture** - Handles the update and closes connection with telegram quickly. There is a filter to make sure only real telegram requests are processed.
- **Database integration** - MySQL/MariaDB support with prepared functions for secure queries, transactions and user management. Fully with PDO
- **Built-in cron system** - Schedule recurring tasks without external dependencies
- **Admin panel** - Separate admin commands and permission system for managing the bot, users and possibile admin groups
- **System metrics** - Monitor CPU, RAM, disk usage, and performance over a simple command accessibile from the bot
- **Security** - IP verification, input sanitization, and secure database queries
- **Easy message handling** - Simplified API functions for sending messages, photos, videos, and more
- **Multi-environment support** - `.env` configuration for different deployments
- **Media and Input support** - Handle any input: text inputs, photos, videos, audio, documents...
- **User management** - Automatic user registration and profile updates
- **User blocking system** - Built-in blocked users management

## 📋 Requirements

- PHP 8.0 or higher (I am currently working with PHP8.4)
- MySQL/MariaDB (optional, can be disabled)
- Composer
- Web server with HTTPS support (Telegram requires HTTPS for webhooks)
- Linux server (recommended for metrics features)

## 🚀 Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/jackchevalley/php-telegram-bot-framework.git
```

### 2. Install Dependencies

```bash
cd public/libs
composer install
```

### 3. Configure Environment

Copy the example environment file and edit it:

```bash
cp data/.env.example data/.env
```

<u>Remember to Edit</u> `data/.env` with your configuration.
Here you will set up the token of the bot, database credentials (if used), and other settings.


### 4. Configure Bot Settings

<u>Edit</u> `public/configs.php`:

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
Here you have the main bot settings that must be easily and quickly accessible.

### 5. Setup Database (if you want)

Import the database structure:

```bash
mysql -u your_user -p your_database < data/basic_database_structure.sql
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

### 7. Test Your Bot

Send `/start` to your bot on Telegram. You should receive a welcome message!

## 📁 Project Structure

```
├── index.php                # Main webhook entry point
├── comandi.php              # Command handlers and routing
├── delupdates.php           # Webhook setup script
├── data/
│   ├── .env                 # Environment configuration
│   ├── .env.example         # Example environment file
│   └── basic_database_structure.sql  # Database schema
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

## 🎯 Core Concepts

### Message Flow

1. **Webhook receives update** → `index.php`
2. **IP verification** → `public/access.php`
3. **Fast response to Telegram** → Connection closed immediately
4. **Parse update data** → Extract message, user info, etc.
5. **Route to handler** → `comandi.php`
6. **Process command** → Execute bot logic or redirect to sections
7. **Send response** → Prepare and send message back to user

### Update Variables

The framework automatically extracts these variables from Telegram updates:

```php
$update      // Raw update array
$chatID      // Chat ID
$userID      // User ID
$nome        // User's first name
$username    // User's username (if available)
$msg         // Message text or callback data
$cbid        // Callback query ID
$cbmid       // Callback message ID
$message_id  // Message ID
$photo       // Photo file_id
$video       // Video file_id
$voice       // Voice file_id
$caption     // Media caption
// ... and more
```

### Sending Messages

#### Simple Text Message

```php
sm($chatID, "Hello World!");
```
The `sm()` function sends a message to the specified chat ID.
It accepts strings or arrays (for multi-line messages).
It can be useful to create big messages easily.
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

The inline every element added as `$inline_menu[] = [...]` represents a new row of buttons.
To insert multiple buttons in the same row, just add more button arrays inside the same array.


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
This function sends a new message and deletes the previous one sent by the bot in the same chat (if send using this same function).
```php
// Sends new message and deletes the previous one
smg($chatID, "Updated message", $inline_menu);
```
It can be very useful to keep the chat clean when navigating menus or updating information.
I suggest to imagine the bot structure like this: 
- **Main sections messages**: use `smg()` to always have only one message per section, this will avoid users to click old buttons, going back and forth and potentially creating conflicts.
- **Subsections messages**: this can use normal `sm()` messages to provide additional information without deleting the main section message. 

### Available Message Functions
You can find all the functions provided by the framework for sending and managing messages in the public/functions.php file.

## 💾 Database Usage

### Secure Query Execution

The framework provides a `secure()` function for safe database queries.
You don't need to perform manual connection or prepare statements, just call the secure function and it will take care of connection, preparing and executing the query.

```php
// Execute query without results
secure("UPDATE users SET first_name = :name, username = :username WHERE user_id = :id", [
    'first_name' => $nome,
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
- **2**: Get row count (number of affected rows), useful to check whether an UPDATE or DELETE affected any rows or for fastly checking the existence of rows
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
    0, 
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
    ['id' => $userID, 'name' => $nome], 
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

The temporary state (`temp` column) can be used to manage multi-step interactions with users.
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

## ⏰ Cron Jobs

The framework includes a built-in cron system that runs independently.

### Setup Cron Runner

You should set up a docker container to run the cron runner, it runs in while True so it just need to keep running and reboot when needed.

If your project does not require heavy cron jobs you can discard the runner and just execute the modules when needed from conventional cron.

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

Create file in `other/private/cron/modules/`:

```php
<?php
// other/private/cron/modules/send_daily_report.php

if (!defined('MAINSTART')) { die(); }

// Your task logic here
$users = secure("SELECT * FROM users WHERE attivo = 1", 0, 3);

foreach ($users as $user) {
    sm($user['user_id'], "Daily report: ...");
}

logger("Daily report sent to " . count($users) . " users");
```


### Built in broadcast system
We have included a broadcast module that can be used to send messages to all users in the database.
You can find it in `other/private/cron/modules/broadcast.php`.
For now the it's kinda basic and it's not implemented in the admin commands, but you can easily customize it to your needs.

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
    $total_users = secure("SELECT COUNT(*) as cnt FROM users", 0, 1)['cnt'];
    $active_users = secure("SELECT COUNT(*) as cnt FROM users WHERE attivo = 1", 0, 1)['cnt'];
    
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

## 🎨 Commands and Input Handling

The framework, after initializing variables, routes all commands and messages to `comandi.php`.
Here it will populate some more variables, used only in the commands handling *(the if(true) is just to collapse the code in idles)*. 

The code structure splits between messages and media, since messages could be commands but medias for sure are not. 

After splitting over messages and media, it splits again between commands (starting with /) and potential inputs.

If you are using a Reply Keyboard, you can map buttons to commands using the `$COMMANDS_ALIAS` array.

### Basic Command Handler

```php
// In comandi.php

if ($msg == "/start") {
    $text = []
    $text[] = "<b>Welcome <a href='tg://user?id=$userID'>$nome</a>!</b>";
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
        // Called from text message, we have to send a new message. since the /start is a main section message (it has buttons to navigate), we use smg(
        smg($chatID, $text, $inline_menu);
    }
    
    temp(); // Clear temp state. The /start command is a simple way to reset state and cancel inputs.
}
```

### Using Temporary States

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

## 🔒 Security Features

### IP Verification

`public/access.php` verifies requests come from Telegram:

```php
// Automatically included in index.php
// Blocks requests not from Telegram's IP ranges
```

### Input Sanitization

User input is automatically sanitized to avoid XSS attacks, always remember to use secure( function to call database with user parameters. Never put them in query directly:

```php
$msg = strip_tags($update["message"]["text"]);
$nome = strip_tags($update["message"]["from"]["first_name"]);
```

### Blocked Users
A simple blocked users system is included.
Just add the user to the `blocked_users` table and the framework will automatically block any interaction from that user.
```php
// Block a user
secure("INSERT INTO blocked_users (user_id, by_user_id) VALUES (:uid, :aid)", [
    'uid' => $blocked_user_id,
    'aid' => $admin_id
]);
// the framework will automatically block any interaction from that user, can check out in comandi.php
```


## 📱 Handling Different Message Types

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


## 🐛 Debugging

### Error Reporting

Errors are automatically sent to the main admin when testing out:

You can add error reporting in the index.php to store logs in a selected file.

## 📝 Best Practices

1. **Always use prepared statements** - Never concatenate SQL queries
2. **Clear temp states** - Call `temp()` after completing actions
3. **Use callback locks** - Prevent command abuse with `lock_non_callback()`
4. **Handle both callbacks and messages** - Check for `$cbmid` in commands
5. **Sanitize input** - Even though framework does it, be careful with user data
6. **Clear Functions** - The framework provides many functions in public/functions.php, clean up the unused functions and keep your code light.


## 🤝 Contributing

This framework is designed to be customized for your specific needs. Feel free to:

- Add new features in `public/functions.php`
- Create new sections in `other/sections/`
- Extend database schema in `data/`
- Add cron tasks in `other/private/cron/modules/`

## 📄 License

This framework is provided as-is for building Telegram bots.

## 💬 Support

For issues or questions about the framework structure, check:
- Telegram Bot API: https://core.telegram.org/bots/api
- PHP PDO Documentation: https://www.php.net/manual/en/book.pdo.php
- Guzzle HTTP Client: https://docs.guzzlephp.org/
- My Development channel: https://t.me/JacksWork

---

**Built with ❤️ by [@JackChevalley](https://t.me/JacksWork)**

