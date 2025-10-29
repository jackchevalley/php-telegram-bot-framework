<?php
if(!defined('MAINSTART')) { die("<b>The request source has not been recognized. Make sure to execute from the provided entry point</b>"); }

// Load environment variables
require_once "env_loader.php";

$REDIS_BASE_KEY = $_ENV['REDIS_BASE_KEY'] ?? 'project_base_key';


/**
 * Get a global Redis connection
 * @return Redis Redis connection
 * @throws Exception If connection fails
 */
function getRedisConnection(): Redis {
    $REDIS_PASSWORD = $_ENV['REDIS_PASSWORD'] ?? '';
    $REDIS_HOST = $_ENV['REDIS_HOST'] ?? '';


    // Check if a global Redis connection already exists
    if (isset($GLOBALS['redis'])) {
        $redis = $GLOBALS['redis'];

        // Check if connection is alive
        try {
            $redis->ping();
            return $redis;
        }
        catch (RedisException) {
            // If the connection is not alive, create a new one
            $redis = null;
        }
    }

    // Redis connection configuration (localhost by default)
    $redis = new Redis();
    try {
        $redis->connect($REDIS_HOST);
        $redis->auth($REDIS_PASSWORD);
    } catch (RedisException $e) {
        throw new Exception("Unable to connect to Redis: " . $e->getMessage());
    }

    // Store the connection in a global variable
    $GLOBALS['redis'] = $redis;
    return $redis;
}

/**
 * Close the global Redis connection if it exists
 */
function closeRedisConnection(): void {
    if (isset($GLOBALS['redis'])) {
        $redis = $GLOBALS['redis'];
        try {
            $redis->close();
        } catch (RedisException) {
            // Ignore errors on close
        }
        unset($GLOBALS['redis']);
    }
}

/**
 * Generate the Redis key for a user and specific section
 * Format: project_base_key:section:user_id
 * @param string $section Specific section (e.g., 'ratelimit', 'spam_count', etc.)
 * @param int $userID User ID (optional, default 0)
 * @return string Complete Redis key
 */
function getRedisBaseKey(string $section, int $userID = 0, ...$extraParts): string {
    $key = $GLOBALS['REDIS_BASE_KEY'] . ':' . $section;
    if ($userID > 0) {
        $key .= ':' . $userID;
    }
    foreach ($extraParts as $part) {
        $key .= ':' . $part;
    }
    return $key;
}
