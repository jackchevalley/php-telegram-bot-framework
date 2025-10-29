<?php
if(!defined('MAINSTART')) { die(); }
require_once 'env_loader.php';



// Define if database is enabled
define(
    "DB_ENABLED",
    isset($_ENV['DB_ENABLED']) && $_ENV['DB_ENABLED'] === "true"
);


// Function to connect to the database
function db_connect() {
    try {
        // Check if a global database connection already exists, if so, return it
        if (isset($GLOBALS['db']) and $GLOBALS['db']) {
            return $GLOBALS['db'];
        }

        // Use environment variable for host, fallback to localhost
        $host = $_ENV['DB_HOST'] ?? "127.0.0.1";
        $dbname = $_ENV['DB_NAME'] ?? "bot_database";
        $username = $_ENV['DB_USER'] ?? "bot_user";
        $password = $_ENV['DB_PASS'] ?? "bot_password";

        // Create a new PDO instance
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    }
    catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
        die();
    }
}


// Function to execute queries securely
function secure ($sql, $par = 0, $fc = 0): array | int | null {
    if (!DB_ENABLED) {
        return null;
    }

    global $db;
    if (!isset($db) or !$db) {
        $db = db_connect();
    }

    try {
        $sc = $db->prepare($sql);
        if(isset($par) and $par)
            $sc->execute($par);
        else
            $sc->execute();
    }
    catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
        if (function_exists("sm") && isset($GLOBALS['admin_errors_ID'])) {
            sm(
                $GLOBALS['admin_errors_ID'],
                "Query error encountered\n\n" . $e->getMessage() . "\n\n<b>Query:</b> \n<code>" . $sql . "</code>"
            );
        }

        error_log("PDO Error: " . $e->getMessage() . " | Query: " . $sql . " | Params: " . json_encode($par));
        die();
    }

    // Fetch results
    if(isset($fc) and $fc) {
        switch ($fc) {
            case 1:
                return $sc->fetch(PDO::FETCH_ASSOC); // Fetch first result
            case 2:
                return $sc->rowCount(); // Fetch number of results
            case 3:
                return $sc->fetchAll(); // Fetch all results
            case 4:
                return $db->lastInsertId(); // Fetch last inserted ID
        }
    }

    return null;
}


// Functions for transaction management
function transaction_start($sql, $params = [], bool $fetch_many = false): array | bool | null {
    global $db;
    try {
        $db->beginTransaction();
        $stmt = $db->prepare($sql . " FOR UPDATE");
        $stmt->execute($params);
        return ($fetch_many) ?
            $stmt->fetchAll(PDO::FETCH_ASSOC)
            : $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
        error_log("PDO Transaction Error: " . $e->getMessage() . " | Query: " . $sql . " | Params: " . json_encode($params));
        $db->rollBack();

        die();
    }
    catch (Exception $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
        error_log("General Transaction Error: " . $e->getMessage() . " | Query: " . $sql . " | Params: " . json_encode($params));
        $db->rollBack();

        die();
    }
}


// Commit the transaction
function transaction_commit(): void {
    global $db;
    try {
        $db->commit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
        $db->rollBack();
        die();
    }
}


// Rollback the transaction
function transaction_rollback(): void {
    global $db;
    try {
        $db->rollBack();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
        die();
    }
}


// Close the database connection
function closeDbConnection(): void {
    if (isset($GLOBALS['db']) and $GLOBALS['db']) {
        $GLOBALS['db'] = null;
    }
}
