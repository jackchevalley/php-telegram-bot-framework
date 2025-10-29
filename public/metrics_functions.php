<?php
if(!defined('MAINSTART')) { die("<b>The request source has not been recognized. Make sure to execute from the provided entry point</b>"); }


// Function to get CPU usage
function getCpuUsage(): float {
    $load = sys_getloadavg();
    $cpuCores = getCpuCores();
    $cpuPercent = ($load[0] / $cpuCores) * 100;
    return round($cpuPercent, 2);
}

// Function to get the number of CPU cores
function getCpuCores(): int {
    if (PHP_OS_FAMILY === 'Linux') {
        $output = shell_exec('nproc');
        return (int)trim($output);
    }
    return 1; // Fallback
}

// Function to get RAM info
function getMemoryInfo(): ?array {
    if (PHP_OS_FAMILY === 'Linux') {
        $memInfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $memInfo, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $available);

        $totalMB = round($total[1] / 1024, 2);
        $availableMB = round($available[1] / 1024, 2);
        $usedMB = $totalMB - $availableMB;
        $usedPercent = round(($usedMB / $totalMB) * 100, 2);

        return [
            'total' => $totalMB,
            'used' => $usedMB,
            'available' => $availableMB,
            'percent' => $usedPercent
        ];
    }
    return null;
}

// Function to get system uptime
function getSystemUptime(): string {
    if (PHP_OS_FAMILY === 'Linux') {
        $uptime = shell_exec('uptime -p');
        return trim(str_replace('up ', '', $uptime));
    }
    return 'N/A';
}


// Function to test database performance
function testDatabasePerformance(): array {
    $start = microtime(true);
    try {
        secure('SELECT * FROM users LIMIT 1', 0, 3); // Example query
        $duration = round((microtime(true) - $start) * 1000, 2); // ms
        if ($duration < 10) {
            return ['status' => '✅', 'time' => $duration, 'text' => 'Excellent'];
        } elseif ($duration < 50) {
            return ['status' => '⚠️', 'time' => $duration, 'text' => 'Good'];
        } else {
            return ['status' => '⚠️', 'time' => $duration, 'text' => 'Slow'];
        }
    } catch (Exception) {
        return ['status' => '❌', 'time' => 0, 'text' => 'Error'];
    }
}

// Function to test request performance
function testRequestsPerformance(): array {

    // Perform a simple request to the bot API and measure time
    function test_request(): int {
        $start = microtime(true);
        try {
            $r = request('getMe');
            if (!$r || !isset($r['ok']) || !$r['ok']) {
                throw new Exception('Request failed');
            }
            return round((microtime(true) - $start) * 1000, 2); // ms
        } catch (Exception) {
            return 0;
        }
    }

    // Execute 3 tests and take the average
    $NUMBER_OF_REQUESTS = 3;
    $result = 0;  // Initialize the variable
    for ($i = 0; $i < $NUMBER_OF_REQUESTS; $i++) {
        $result += test_request();
    }

    $average = round($result / $NUMBER_OF_REQUESTS, 2);
    if ($average < 50) {
        return ['status' => '✅', 'time' => $average, 'text' => 'Excellent'];
    }
    elseif ($average < 120) {
        return ['status' => '✅', 'time' => $average, 'text' => 'Good'];
    }
    elseif ($average < 180) {
        return ['status' => '⚠️', 'time' => $average, 'text' => 'Fair'];
    }
    else {
        return ['status' => '⚠️', 'time' => $average, 'text' => 'Slow'];
    }
}


// Function to get Load Average
function getLoadAverage(): array {
    $load = sys_getloadavg();
    return [
        '1min' => round($load[0], 2),
        '5min' => round($load[1], 2),
        '15min' => round($load[2], 2)
    ];
}

// Helper function to format bytes
function formatBytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}

// Function to get disk space
function getDiskUsage(): array {
    $total = disk_total_space('/');
    $free = disk_free_space('/');
    $used = $total - $free;
    $usedPercent = round(($used / $total) * 100, 2);

    return [
        'total' => formatBytes($total),
        'used' => formatBytes($used),
        'free' => formatBytes($free),
        'percent' => $usedPercent
    ];
}
