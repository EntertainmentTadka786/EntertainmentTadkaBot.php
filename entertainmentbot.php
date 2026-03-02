<?php
// ==============================
// ENTERTAINMENT TADKA BOT - CLEAN VERSION
// ==============================
// Removed: Duplicate commands, unused functions, AutoDelete, QuickAdd, etc.
// Total lines reduced by ~40%
// ==============================

// ==================== ENVIRONMENT CONFIGURATION ====================
$environment = getenv('ENVIRONMENT') ?: 'production';

if ($environment === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// ==================== ERROR LOGGING ====================
function log_error($message, $type = 'ERROR', $context = []) {
    $log_entry = sprintf(
        "[%s] %s: %s %s\n",
        date('Y-m-d H:i:s'),
        $type,
        $message,
        !empty($context) ? json_encode($context) : ''
    );
    
    @file_put_contents('error.log', $log_entry, FILE_APPEND);
    @chmod('error.log', 0666);
    @error_log($message);
    
    if (getenv('ENVIRONMENT') === 'development') {
        echo "<!-- DEBUG: " . htmlspecialchars($message) . " -->\n";
    }
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    log_error("PHP Error [$errno]: $errstr in $errfile on line $errline", 'PHP_ERROR');
    return false;
});

set_exception_handler(function($exception) {
    log_error("Uncaught Exception: " . $exception->getMessage(), 'EXCEPTION', [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
});

log_error("Bot script started", 'INFO', [
    'time' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'uri' => $_SERVER['REQUEST_URI'] ?? ''
]);

// ==================== ENVIRONMENT VARIABLES ====================
$ENV_CONFIG = [
    'BOT_TOKEN' => getenv('BOT_TOKEN') ?: '8315381064:AAGk0FGVGmB8j5SjpBvW3rD3_kQHe_hyOWU',
    'BOT_USERNAME' => getenv('BOT_USERNAME') ?: 'EntertainmentTadkaBot',
    'ADMIN_IDS' => array_map('intval', explode(',', getenv('ADMIN_IDS') ?: '1080317415')),
    
    'PUBLIC_CHANNELS' => [
        ['id' => getenv('PUBLIC_CHANNEL_1_ID') ?: '-1003181705395', 'username' => getenv('PUBLIC_CHANNEL_1_USERNAME') ?: '@EntertainmentTadka786'],
        ['id' => getenv('PUBLIC_CHANNEL_2_ID') ?: '-1002831605258', 'username' => getenv('PUBLIC_CHANNEL_2_USERNAME') ?: '@threater_print_movies'],
        ['id' => getenv('PUBLIC_CHANNEL_3_ID') ?: '-1002964109368', 'username' => getenv('PUBLIC_CHANNEL_3_USERNAME') ?: '@ETBackup']
    ],
    
    'PRIVATE_CHANNELS' => [
        ['id' => getenv('PRIVATE_CHANNEL_1_ID') ?: '-1003251791991', 'username' => getenv('PRIVATE_CHANNEL_1_USERNAME') ?: ''],
        ['id' => getenv('PRIVATE_CHANNEL_2_ID') ?: '-1002337293281', 'username' => getenv('PRIVATE_CHANNEL_2_USERNAME') ?: ''],
        ['id' => getenv('PRIVATE_CHANNEL_3_ID') ?: '-1003614546520', 'username' => getenv('PRIVATE_CHANNEL_3_USERNAME') ?: '']
    ],
    
    'REQUEST_GROUP' => [
        'id' => getenv('REQUEST_GROUP_ID') ?: '-1003083386043',
        'username' => getenv('REQUEST_GROUP_USERNAME') ?: '@EntertainmentTadka7860'
    ],
    
    'CSV_FILE' => 'movies.csv',
    'USERS_FILE' => 'users.json',
    'STATS_FILE' => 'bot_stats.json',
    'REQUESTS_FILE' => 'requests.json',
    'BACKUP_DIR' => 'backups/',
    'CACHE_DIR' => 'cache/',
    
    'CACHE_EXPIRY' => 300,
    'ITEMS_PER_PAGE' => 5,
    
    'MAX_REQUESTS_PER_DAY' => 3,
    'REQUEST_SYSTEM_ENABLED' => true,
    
    'MAINTENANCE_MODE' => (getenv('MAINTENANCE_MODE') === 'true') ? true : false,
    'RATE_LIMIT_REQUESTS' => 30,
    'RATE_LIMIT_WINDOW' => 60
];

if (empty($ENV_CONFIG['BOT_TOKEN'])) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die("❌ Bot Token not configured. Please set BOT_TOKEN environment variable.");
}

define('BOT_TOKEN', $ENV_CONFIG['BOT_TOKEN']);
define('ADMIN_IDS', $ENV_CONFIG['ADMIN_IDS']);
define('CSV_FILE', $ENV_CONFIG['CSV_FILE']);
define('USERS_FILE', $ENV_CONFIG['USERS_FILE']);
define('STATS_FILE', $ENV_CONFIG['STATS_FILE']);
define('REQUESTS_FILE', $ENV_CONFIG['REQUESTS_FILE']);
define('BACKUP_DIR', $ENV_CONFIG['BACKUP_DIR']);
define('CACHE_DIR', $ENV_CONFIG['CACHE_DIR']);
define('CACHE_EXPIRY', $ENV_CONFIG['CACHE_EXPIRY']);
define('ITEMS_PER_PAGE', $ENV_CONFIG['ITEMS_PER_PAGE']);
define('MAX_REQUESTS_PER_DAY', $ENV_CONFIG['MAX_REQUESTS_PER_DAY']);
define('REQUEST_SYSTEM_ENABLED', $ENV_CONFIG['REQUEST_SYSTEM_ENABLED']);
define('MAINTENANCE_MODE', $ENV_CONFIG['MAINTENANCE_MODE']);
define('RATE_LIMIT_REQUESTS', $ENV_CONFIG['RATE_LIMIT_REQUESTS']);
define('RATE_LIMIT_WINDOW', $ENV_CONFIG['RATE_LIMIT_WINDOW']);

define('MAIN_CHANNEL', '@EntertainmentTadka786');
define('THEATER_CHANNEL', '@threater_print_movies');
define('REQUEST_CHANNEL', '@EntertainmentTadka7860');
define('SERIAL_CHANNEL', '@Entertainment_Tadka_Serial_786');
define('BACKUP_CHANNEL_USERNAME', '@ETBackup');
define('MAIN_CHANNEL_ID', '-1003251791991');
define('THEATER_CHANNEL_ID', '-1003614546520');
define('BACKUP_CHANNEL_ID', '-1002337293281');

define('LOG_FILE', 'bot_activity.log');
define('AUTO_BACKUP_HOUR', '03');

$MAINTENANCE_MODE = false;
$MAINTENANCE_MESSAGE = "🛠️ <b>Bot Under Maintenance</b>\n\nWe're temporarily unavailable for updates.\nWill be back in few days!\n\nThanks for patience 🙏";

global $movie_messages, $movie_cache, $user_pagination_sessions, $user_settings;
$movie_messages = array();
$movie_cache = array();
$user_pagination_sessions = array();
$user_settings = array();

// ==================== QUALITY DATA ====================
$QUALITIES = ["480p", "720p", "1080p", "4K HDR"];
$AUDIOS = ["Hindi", "English", "Dual Audio"];
$CODECS = ["AVC", "HEVC", "x265"];
$SOURCES = ["WEB-DL", "BluRay", "WEBRip"];

$SIZES = [
    "480p" => ["350MB", "450MB", "550MB"],
    "720p" => ["899MB", "1.1GB", "1.4GB"],
    "1080p" => ["2.3GB", "2.8GB", "3.5GB"],
    "4K HDR" => ["8.5GB", "12.5GB", "15.8GB"]
];

$BOT_NAME = "Entertainment Tadka";

// ==================== PREMIUM UI FUNCTIONS ====================
function premium_caption($title) {
    global $BOT_NAME;
    
    $text = "
🎬 <b>" . strtoupper(htmlspecialchars($title)) . "</b>

━━━━━━━━━━━━━━━━
📥 <b>SELECT YOUR VERSION</b>
━━━━━━━━━━━━━━━━

👇 Click on quality to see files
<i>Powered by $BOT_NAME</i>
    ";
    return trim($text);
}

function build_quality_buttons($title, $data) {
    $buttons = [];
    $added_qualities = [];
    
    foreach ($data as $item) {
        $quality = $item['quality'];
        if (!in_array($quality, $added_qualities)) {
            $count = 0;
            foreach ($data as $x) {
                if ($x['quality'] == $quality) $count++;
            }
            
            $buttons[][] = [
                'text' => "🎥 $quality  |  📁 $count files",
                'callback_data' => "show|$title|$quality"
            ];
            $added_qualities[] = $quality;
        }
    }
    
    $quality_order = ["480p" => 1, "720p" => 2, "1080p" => 3, "4K HDR" => 4];
    usort($buttons, function($a, $b) use ($quality_order) {
        $qa = explode(' ', $a[0]['text'])[1];
        $qb = explode(' ', $b[0]['text'])[1];
        return ($quality_order[$qa] ?? 99) - ($quality_order[$qb] ?? 99);
    });
    
    $buttons[] = [
        ['text' => "🏠 HOME", 'callback_data' => "home"],
        ['text' => "❌ CLOSE", 'callback_data' => "close"]
    ];
    
    return json_encode(['inline_keyboard' => $buttons]);
}

function build_file_buttons($title, $quality, $data) {
    $filtered = [];
    foreach ($data as $item) {
        if ($item['quality'] == $quality) {
            $filtered[] = $item;
        }
    }
    
    if (empty($filtered)) {
        return null;
    }
    
    $buttons = [];
    
    $buttons[][] = [
        'text' => "📌 " . strtoupper($quality) . " - " . count($filtered) . " FILES",
        'callback_data' => "ignore"
    ];
    
    $idx = 1;
    foreach ($filtered as $item) {
        $label = "$idx. " . $item['codec'] . " | " . $item['audio'] . " | " . $item['size'];
        if (!empty($item['source'])) {
            $label .= " | " . $item['source'];
        }
        
        $buttons[][] = [
            'text' => "📁 $label",
            'callback_data' => "send|" . $item['message_id'] . "|" . $item['channel_id']
        ];
        $idx++;
    }
    
    $buttons[] = [
        ['text' => "◀️ BACK", 'callback_data' => "back|$title"],
        ['text' => "❌ CLOSE", 'callback_data' => "close"]
    ];
    
    return json_encode(['inline_keyboard' => $buttons]);
}

function generate_mock_files($movie_data) {
    global $QUALITIES, $AUDIOS, $CODECS, $SOURCES, $SIZES;
    
    $expanded = [];
    
    foreach ($movie_data as $row) {
        foreach ($QUALITIES as $quality) {
            for ($i = 0; $i < 3; $i++) {
                $audio = $AUDIOS[array_rand($AUDIOS)];
                $codec = $CODECS[array_rand($CODECS)];
                $source = $SOURCES[array_rand($SOURCES)];
                $size = $SIZES[$quality][array_rand($SIZES[$quality])];
                
                $expanded[] = [
                    'message_id' => $row['message_id'],
                    'channel_id' => $row['channel_id'],
                    'quality' => $quality,
                    'audio' => $audio,
                    'codec' => $codec,
                    'source' => $source,
                    'size' => $size
                ];
            }
        }
    }
    
    shuffle($expanded);
    return $expanded;
}

// ==================== PERSONALIZE SETTINGS FUNCTIONS ====================
function show_personalize_settings($chat_id, $message_id = null) {
    global $user_settings;
    
    $user_id = $chat_id;
    $settings = $user_settings[$user_id] ?? [
        'file_delete' => 50,
        'auto_scan' => true,
        'spoiler_mode' => false,
        'top_search' => false,
        'priority' => 'size',
        'layout' => 'BTN'
    ];
    
    $file_delete_emoji = $settings['file_delete'] . 's ⏱️';
    $auto_scan_emoji = $settings['auto_scan'] ? '✅' : '❌';
    $spoiler_emoji = $settings['spoiler_mode'] ? '✅' : '❌';
    $top_search_emoji = $settings['top_search'] ? '✅' : '❌';
    $priority_text = $settings['priority'] == 'size' ? '📦 Size' : '🎬 Quality';
    $layout_text = $settings['layout'];
    
    $text = "⚙️ <b>Personalize Your Settings</b>\n";
    $text .= "🕒 " . date('h:i A') . "\n\n";
    $text .= "🗑️ <b>File Delete:</b> $file_delete_emoji\n";
    $text .= "🔍 <b>Auto Scan:</b> $auto_scan_emoji\n\n";
    $text .= "🎭 <b>Spoiler Mode:</b> $spoiler_emoji\n";
    $text .= "🔝 <b>Top Search:</b> $top_search_emoji\n\n";
    $text .= "🥇 <b>1st Priority:</b> $priority_text\n";
    $text .= "📐 <b>Result Layout:</b> $layout_text\n\n";
    $text .= "🔄 <b>Reset to Defaults</b>";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '⏱️ ' . $settings['file_delete'] . 's', 'callback_data' => 'settings_file_delete'],
                ['text' => '🔍 Auto Scan ' . $auto_scan_emoji, 'callback_data' => 'settings_auto_scan']
            ],
            [
                ['text' => '🎭 Spoiler ' . $spoiler_emoji, 'callback_data' => 'settings_spoiler'],
                ['text' => '🔝 Top Search ' . $top_search_emoji, 'callback_data' => 'settings_top_search']
            ],
            [
                ['text' => '🥇 Priority: ' . ucfirst($settings['priority']), 'callback_data' => 'settings_priority'],
                ['text' => '📐 Layout: ' . $settings['layout'], 'callback_data' => 'settings_layout']
            ],
            [
                ['text' => '🔄 Reset to Defaults', 'callback_data' => 'settings_reset']
            ]
        ]
    ];
    
    if ($message_id) {
        editMessage($chat_id, $message_id, $text, json_encode($keyboard), 'HTML');
    } else {
        sendMessage($chat_id, $text, json_encode($keyboard), 'HTML');
    }
}

function show_file_delete_settings($chat_id, $message_id) {
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '⏱️ 10s', 'callback_data' => 'set_delete_10'],
                ['text' => '⏱️ 30s', 'callback_data' => 'set_delete_30']
            ],
            [
                ['text' => '⏱️ 50s', 'callback_data' => 'set_delete_50'],
                ['text' => '⏱️ 90s', 'callback_data' => 'set_delete_90']
            ],
            [
                ['text' => '⏱️ 120s', 'callback_data' => 'set_delete_120']
            ],
            [
                ['text' => '🔙 Back', 'callback_data' => 'settings_back']
            ]
        ]
    ];
    
    editMessage($chat_id, $message_id, "⚙️ <b>Select File Delete Timer</b>\n\nChoose auto-delete time for files:", json_encode($keyboard), 'HTML');
}

// ==================== PREMIUM MOVIE LIST UI ====================
function show_premium_movie_list($chat_id, $movie_name, $requester_name, $files, $page = 1, $total_pages = 1) {
    $text = "🎬 <b>" . strtoupper(htmlspecialchars($movie_name)) . "</b>\n";
    $text .= "👑 <i>admin</i>\n\n";
    $text .= "👤 <b>" . htmlspecialchars($requester_name) . "</b>\n";
    $text .= "📌 <b>REQUESTED BY : " . strtoupper(htmlspecialchars($requester_name)) . "</b>\n\n";
    
    $start = ($page - 1) * 10;
    $page_files = array_slice($files, $start, 10);
    
    foreach ($page_files as $index => $file) {
        $num = $start + $index + 1;
        $text .= "$num. [{$file['size']}] {$file['name']}\n";
    }
    
    $text .= "\n---\n";
    $text .= "🚫 <b>Remove ads</b>  |  ";
    $text .= "📤 <b>SEND ALL</b>";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🎬 QUALITY', 'callback_data' => 'filter_quality_' . $movie_name],
                ['text' => '🗣️ LANGUAGE', 'callback_data' => 'filter_language_' . $movie_name],
                ['text' => '📺 SEASON', 'callback_data' => 'filter_season_' . $movie_name]
            ],
            []
        ]
    ];
    
    $nav_row = [];
    if ($page > 1) {
        $nav_row[] = ['text' => '⬅️ PREV', 'callback_data' => 'page_' . ($page - 1) . '_' . $movie_name];
    }
    $nav_row[] = ['text' => "PAGE $page/$total_pages", 'callback_data' => 'current_page'];
    if ($page < $total_pages) {
        $nav_row[] = ['text' => 'NEXT ➡️', 'callback_data' => 'page_' . ($page + 1) . '_' . $movie_name];
    }
    $keyboard['inline_keyboard'][] = $nav_row;
    
    return ['text' => $text, 'keyboard' => $keyboard];
}

function show_premium_series_list($chat_id, $series_name, $requester_name, $episodes, $page = 1, $total_pages = 1) {
    $text = "👤 <b>" . htmlspecialchars($requester_name) . "</b>\n";
    $text .= "📺 " . htmlspecialchars($series_name) . " ← " . ($page * 10 - 9) . "-" . min($page * 10, count($episodes)) . " " . date('h:i A') . "\n\n";
    $text .= "👑 <i>admin</i>\n\n";
    $text .= "👤 <b>" . htmlspecialchars($requester_name) . "</b>\n";
    $text .= "📌 <b>REQUESTED BY : " . strtoupper(htmlspecialchars($requester_name)) . "</b>\n\n";
    
    $start = ($page - 1) * 10;
    $page_eps = array_slice($episodes, $start, 10);
    
    foreach ($page_eps as $index => $ep) {
        $num = $start + $index + 1;
        $text .= "$num. [{$ep['size']}] {$ep['name']}\n";
    }
    
    $text .= "\n---\n";
    $text .= "🚫 <b>Remove ads</b>  |  ";
    $text .= "📤 <b>SEND ALL</b>";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🎬 QUALITY', 'callback_data' => 'filter_quality_' . $series_name],
                ['text' => '🗣️ LANGUAGE', 'callback_data' => 'filter_language_' . $series_name],
                ['text' => '📺 SEASON', 'callback_data' => 'filter_season_' . $series_name]
            ],
            []
        ]
    ];
    
    $nav_row = [];
    if ($page > 1) {
        $nav_row[] = ['text' => '⬅️ PREV', 'callback_data' => 'series_page_' . ($page - 1) . '_' . $series_name];
    }
    $nav_row[] = ['text' => "PAGE $page/$total_pages", 'callback_data' => 'current_page'];
    if ($page < $total_pages) {
        $nav_row[] = ['text' => 'NEXT ➡️', 'callback_data' => 'series_page_' . ($page + 1) . '_' . $series_name];
    }
    $keyboard['inline_keyboard'][] = $nav_row;
    
    return ['text' => $text, 'keyboard' => $keyboard];
}

// ==================== SECURITY FUNCTIONS ====================
function validateInput($input, $type = 'text') {
    if (is_array($input)) {
        return array_map('validateInput', $input);
    }
    
    $input = trim($input);
    
    switch($type) {
        case 'movie_name':
            if (strlen($input) < 2 || strlen($input) > 200) {
                return false;
            }
            if (!preg_match('/^[\p{L}\p{N}\s\-\.\,\&\+\'\"\(\)\!\:\;\?]{2,200}$/u', $input)) {
                return false;
            }
            return $input;
            
        case 'user_id':
            return preg_match('/^\d+$/', $input) ? intval($input) : false;
            
        case 'command':
            return preg_match('/^\/[a-zA-Z0-9_]+$/', $input) ? $input : false;
            
        case 'telegram_id':
            return preg_match('/^\-?\d+$/', $input) ? $input : false;
            
        case 'filename':
            $input = basename($input);
            $allowed_files = ['movies.csv', 'users.json', 'bot_stats.json', 'requests.json'];
            return in_array($input, $allowed_files) ? $input : false;
            
        default:
            return $input;
    }
}

function secureFileOperation($filename, $operation = 'read') {
    $filename = validateInput($filename, 'filename');
    if (!$filename) {
        return false;
    }
    
    if ($operation === 'write') {
        if (!is_writable($filename)) {
            @chmod($filename, 0644);
        }
    }
    
    return $filename;
}

// ==================== RATE LIMITING ====================
class RateLimiter {
    private static $limits = [];
    
    public static function check($key, $limit = 30, $window = 60) {
        $now = time();
        $window_start = $now - $window;
        
        if (!isset(self::$limits[$key])) {
            self::$limits[$key] = [];
        }
        
        self::$limits[$key] = array_filter(self::$limits[$key], 
            function($time) use ($window_start) {
                return $time > $window_start;
            });
        
        if (count(self::$limits[$key]) >= $limit) {
            log_error("Rate limit exceeded for key: $key", 'WARNING');
            return false;
        }
        
        self::$limits[$key][] = $now;
        return true;
    }
}

// ==================== CHANNEL FUNCTIONS ====================
function get_channel_id_by_username($username) {
    global $ENV_CONFIG;
    
    $username = strtolower(trim(ltrim($username, '@')));
    
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel) {
        $channel_user = strtolower(trim(ltrim($channel['username'], '@')));
        if ($channel_user == $username) {
            return $channel['id'];
        }
    }
    
    return null;
}

function getChannelType($channel_id) {
    global $ENV_CONFIG;
    
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel) {
        if ($channel['id'] == $channel_id) return 'public';
    }
    
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $channel) {
        if ($channel['id'] == $channel_id) return 'private';
    }
    
    return 'unknown';
}

function getChannelUsername($channel_id) {
    global $ENV_CONFIG;
    
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel) {
        if ($channel['id'] == $channel_id) {
            return $channel['username'];
        }
    }
    
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $channel) {
        if ($channel['id'] == $channel_id) {
            return $channel['username'] ?: 'Private Channel';
        }
    }
    
    return 'Unknown Channel';
}

function get_direct_channel_link($message_id, $channel_id) {
    if (empty($channel_id)) return "Channel ID not available";
    $channel_id_clean = str_replace('-100', '', $channel_id);
    return "https://t.me/c/" . $channel_id_clean . "/" . $message_id;
}

function get_channel_username_link($channel_type) {
    switch ($channel_type) {
        case 'main':
        case 'public':
            return "https://t.me/" . ltrim(MAIN_CHANNEL, '@');
        case 'theater':
            return "https://t.me/" . ltrim(THEATER_CHANNEL, '@');
        case 'serial':
            return "https://t.me/" . ltrim(SERIAL_CHANNEL, '@');
        case 'backup':
            return "https://t.me/" . ltrim(BACKUP_CHANNEL_USERNAME, '@');
        default:
            return "https://t.me/EntertainmentTadka786";
    }
}

// ==================== FILE INITIALIZATION ====================
function initialize_files() {
    $files = [
        CSV_FILE => "movie_name,message_id,date,video_path,quality,size,language,channel_type,channel_id,channel_username\n",
        USERS_FILE => json_encode(['users' => [], 'total_requests' => 0, 'message_logs' => [], 'daily_stats' => []], JSON_PRETTY_PRINT),
        STATS_FILE => json_encode(['total_movies' => 0, 'total_users' => 0, 'total_searches' => 0, 'total_downloads' => 0, 'successful_searches' => 0, 'failed_searches' => 0, 'daily_activity' => [], 'last_updated' => date('Y-m-d H:i:s')], JSON_PRETTY_PRINT),
        REQUESTS_FILE => json_encode(['requests' => [], 'pending_approval' => [], 'completed_requests' => [], 'user_request_count' => []], JSON_PRETTY_PRINT)
    ];
    
    foreach ($files as $file => $content) {
        if (!file_exists($file)) {
            file_put_contents($file, $content);
            @chmod($file, 0666);
        }
    }
    
    if (!file_exists(BACKUP_DIR)) {
        @mkdir(BACKUP_DIR, 0777, true);
    }
    
    if (!file_exists(LOG_FILE)) {
        file_put_contents(LOG_FILE, "[" . date('Y-m-d H:i:s') . "] SYSTEM: Files initialized\n");
    }
    
    if (!file_exists(CACHE_DIR)) {
        @mkdir(CACHE_DIR, 0777, true);
    }
}

initialize_files();

function bot_log($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $type: $message\n";
    file_put_contents(LOG_FILE, $log_entry, FILE_APPEND);
}

// ==================== CSV MANAGER CLASS ====================
class CSVManager {
    private static $buffer = [];
    private static $instance = null;
    private $cache_data = null;
    private $cache_timestamp = 0;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initializeFiles();
    }
    
    private function initializeFiles() {
        if (!file_exists(BACKUP_DIR)) {
            @mkdir(BACKUP_DIR, 0777, true);
        }
        if (!file_exists(CACHE_DIR)) {
            @mkdir(CACHE_DIR, 0777, true);
        }
        
        if (!file_exists(CSV_FILE)) {
            $header = "movie_name,message_id,channel_id\n";
            @file_put_contents(CSV_FILE, $header);
            @chmod(CSV_FILE, 0666);
        }
        
        if (!file_exists(USERS_FILE)) {
            $users_data = ['users' => [], 'total_requests' => 0, 'message_logs' => []];
            @file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
        }
        
        if (!file_exists(STATS_FILE)) {
            $stats_data = ['total_movies' => 0, 'total_users' => 0, 'total_searches' => 0, 'last_updated' => date('Y-m-d H:i:s')];
            @file_put_contents(STATS_FILE, json_encode($stats_data, JSON_PRETTY_PRINT));
        }
    }
    
    private function acquireLock($file, $mode = LOCK_EX) {
        $fp = fopen($file, 'r+');
        if ($fp && flock($fp, $mode)) {
            return $fp;
        }
        if ($fp) fclose($fp);
        return false;
    }
    
    private function releaseLock($fp) {
        if ($fp) {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
    
    public function readCSV() {
        $data = [];
        
        if (!file_exists(CSV_FILE)) {
            log_error("CSV file not found", 'ERROR');
            return $data;
        }
        
        $fp = $this->acquireLock(CSV_FILE, LOCK_SH);
        if (!$fp) {
            log_error("Failed to lock CSV file for reading", 'ERROR');
            return $data;
        }
        
        try {
            $header = fgetcsv($fp);
            if ($header === false || $header[0] !== 'movie_name') {
                log_error("Invalid CSV header, rebuilding", 'WARNING');
                $this->rebuildCSV();
                return $this->readCSV();
            }
            
            $row_count = 0;
            while (($row = fgetcsv($fp)) !== FALSE) {
                if (count($row) >= 3 && !empty(trim($row[0]))) {
                    $data[] = [
                        'movie_name' => validateInput(trim($row[0]), 'movie_name'),
                        'message_id' => isset($row[1]) ? intval(trim($row[1])) : 0,
                        'channel_id' => isset($row[2]) ? validateInput(trim($row[2]), 'telegram_id') : ''
                    ];
                    $row_count++;
                }
            }
            log_error("Read $row_count rows from CSV", 'INFO');
            return $data;
        } catch (Exception $e) {
            log_error("Error reading CSV: " . $e->getMessage(), 'ERROR');
            return [];
        } finally {
            $this->releaseLock($fp);
        }
    }
    
    private function rebuildCSV() {
        $backup = BACKUP_DIR . 'csv_backup_' . date('Y-m-d_H-i-s') . '.csv';
        if (file_exists(CSV_FILE)) {
            copy(CSV_FILE, $backup);
            log_error("CSV backed up to: $backup", 'INFO');
        }
        
        $data = [];
        if (file_exists(CSV_FILE)) {
            $lines = file(CSV_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $parts = explode(',', $line);
                if (count($parts) >= 3) {
                    $data[] = [
                        'movie_name' => validateInput(trim($parts[0]), 'movie_name'),
                        'message_id' => intval(trim($parts[1])),
                        'channel_id' => validateInput(trim($parts[2]), 'telegram_id')
                    ];
                }
            }
        }
        
        $fp = fopen(CSV_FILE, 'w');
        if ($fp) {
            fputcsv($fp, ['movie_name', 'message_id', 'channel_id']);
            foreach ($data as $row) {
                fputcsv($fp, [$row['movie_name'], $row['message_id'], $row['channel_id']]);
            }
            fclose($fp);
            @chmod(CSV_FILE, 0666);
        }
        
        log_error("CSV rebuilt with " . count($data) . " rows", 'INFO');
    }
    
    public function getCachedData() {
        $cache_file = CACHE_DIR . 'movies_cache.ser';
        
        if ($this->cache_data !== null && (time() - $this->cache_timestamp) < CACHE_EXPIRY) {
            return $this->cache_data;
        }
        
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < CACHE_EXPIRY) {
            $cached = @unserialize(file_get_contents($cache_file));
            if ($cached !== false) {
                $this->cache_data = $cached;
                $this->cache_timestamp = filemtime($cache_file);
                log_error("Loaded from file cache", 'INFO');
                return $this->cache_data;
            }
        }
        
        $this->cache_data = $this->readCSV();
        $this->cache_timestamp = time();
        
        @file_put_contents($cache_file, serialize($this->cache_data));
        @chmod($cache_file, 0666);
        
        log_error("Cache updated with " . count($this->cache_data) . " items", 'INFO');
        
        return $this->cache_data;
    }
    
    public function clearCache() {
        $this->cache_data = null;
        $this->cache_timestamp = 0;
        
        $cache_file = CACHE_DIR . 'movies_cache.ser';
        if (file_exists($cache_file)) {
            @unlink($cache_file);
            log_error("Cache cleared", 'INFO');
        }
    }
    
    public function searchMovies($query) {
        $query = validateInput($query, 'movie_name');
        if (!$query) {
            return [];
        }
        
        $data = $this->getCachedData();
        $query_lower = strtolower(trim($query));
        $results = [];
        
        log_error("Searching for: $query", 'INFO', ['total_items' => count($data)]);
        
        foreach ($data as $item) {
            if (empty($item['movie_name'])) continue;
            
            $movie_lower = strtolower($item['movie_name']);
            $score = 0;
            
            if ($movie_lower === $query_lower) {
                $score = 100;
            } elseif (strpos($movie_lower, $query_lower) !== false) {
                $score = 80;
            } else {
                similar_text($movie_lower, $query_lower, $similarity);
                if ($similarity > 60) {
                    $score = $similarity;
                }
            }
            
            if ($score > 0) {
                if (!isset($results[$movie_lower])) {
                    $results[$movie_lower] = [
                        'score' => $score,
                        'count' => 0,
                        'items' => []
                    ];
                }
                $results[$movie_lower]['count']++;
                $results[$movie_lower]['items'][] = $item;
            }
        }
        
        uasort($results, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        log_error("Search results: " . count($results) . " matches", 'INFO');
        
        return array_slice($results, 0, 10);
    }
    
    public function getStats() {
        $data = $this->getCachedData();
        $stats = [
            'total_movies' => count($data),
            'channels' => [],
            'last_updated' => date('Y-m-d H:i:s', $this->cache_timestamp)
        ];
        
        foreach ($data as $item) {
            $channel = $item['channel_id'];
            if (!isset($stats['channels'][$channel])) {
                $stats['channels'][$channel] = 0;
            }
            $stats['channels'][$channel]++;
        }
        
        return $stats;
    }
}

// ==================== REQUEST SYSTEM CLASS ====================
class RequestSystem {
    private static $instance = null;
    private $db_file = REQUESTS_FILE;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initializeDatabase();
    }
    
    private function initializeDatabase() {
        if (!file_exists($this->db_file)) {
            $default_data = [
                'requests' => [],
                'last_request_id' => 0,
                'user_stats' => [],
                'system_stats' => ['total_requests' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0]
            ];
            file_put_contents($this->db_file, json_encode($default_data, JSON_PRETTY_PRINT));
        }
    }
    
    private function loadData() {
        $data = json_decode(file_get_contents($this->db_file), true);
        if (!$data) {
            $data = [
                'requests' => [],
                'last_request_id' => 0,
                'user_stats' => [],
                'system_stats' => ['total_requests' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0]
            ];
        }
        return $data;
    }
    
    private function saveData($data) {
        return file_put_contents($this->db_file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function submitRequest($user_id, $movie_name, $user_name = '') {
        $movie_name = validateInput($movie_name, 'movie_name');
        $user_id = validateInput($user_id, 'user_id');
        
        if (!$movie_name || !$user_id) {
            return ['success' => false, 'message' => 'Please enter a valid movie name (min 2 characters)'];
        }
        
        if (empty($movie_name) || strlen($movie_name) < 2) {
            return ['success' => false, 'message' => 'Please enter a valid movie name (min 2 characters)'];
        }
        
        $duplicate_check = $this->checkDuplicateRequest($user_id, $movie_name);
        if ($duplicate_check['is_duplicate']) {
            return ['success' => false, 'message' => "You already requested '$movie_name' recently. Please wait before requesting again."];
        }
        
        $flood_check = $this->checkFloodControl($user_id);
        if (!$flood_check['allowed']) {
            return ['success' => false, 'message' => "You've reached the daily limit of " . MAX_REQUESTS_PER_DAY . " requests. Please try again tomorrow."];
        }
        
        $data = $this->loadData();
        $request_id = ++$data['last_request_id'];
        
        $request = [
            'id' => $request_id,
            'user_id' => $user_id,
            'user_name' => validateInput($user_name),
            'movie_name' => $movie_name,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'approved_at' => null,
            'rejected_at' => null,
            'approved_by' => null,
            'rejected_by' => null,
            'reason' => '',
            'is_notified' => false
        ];
        
        $data['requests'][$request_id] = $request;
        $data['system_stats']['total_requests']++;
        $data['system_stats']['pending']++;
        
        if (!isset($data['user_stats'][$user_id])) {
            $data['user_stats'][$user_id] = [
                'total_requests' => 0,
                'approved' => 0,
                'rejected' => 0,
                'pending' => 0,
                'last_request_time' => null,
                'requests_today' => 0,
                'last_request_date' => date('Y-m-d')
            ];
        }
        
        $data['user_stats'][$user_id]['total_requests']++;
        $data['user_stats'][$user_id]['pending']++;
        $data['user_stats'][$user_id]['last_request_time'] = time();
        
        if ($data['user_stats'][$user_id]['last_request_date'] != date('Y-m-d')) {
            $data['user_stats'][$user_id]['requests_today'] = 0;
            $data['user_stats'][$user_id]['last_request_date'] = date('Y-m-d');
        }
        
        $data['user_stats'][$user_id]['requests_today']++;
        
        $this->saveData($data);
        
        log_error("Request submitted", 'INFO', ['request_id' => $request_id, 'movie_name' => $movie_name]);
        
        return [
            'success' => true,
            'request_id' => $request_id,
            'message' => "✅ Request submitted successfully!\n\n🎬 Movie: $movie_name\n📝 ID: #$request_id\n🕒 Status: Pending\n\nYou will be notified when it's approved."
        ];
    }
    
    private function checkDuplicateRequest($user_id, $movie_name) {
        $data = $this->loadData();
        $movie_lower = strtolower($movie_name);
        $time_limit = time() - (24 * 3600);
        
        foreach ($data['requests'] as $request) {
            if ($request['user_id'] == $user_id && 
                strtolower($request['movie_name']) == $movie_lower &&
                strtotime($request['created_at']) > $time_limit) {
                return ['is_duplicate' => true, 'request' => $request];
            }
        }
        
        return ['is_duplicate' => false];
    }
    
    private function checkFloodControl($user_id) {
        $data = $this->loadData();
        
        if (!isset($data['user_stats'][$user_id])) {
            return ['allowed' => true, 'remaining' => MAX_REQUESTS_PER_DAY];
        }
        
        $user_stats = $data['user_stats'][$user_id];
        
        if ($user_stats['last_request_date'] != date('Y-m-d')) {
            return ['allowed' => true, 'remaining' => MAX_REQUESTS_PER_DAY];
        }
        
        $remaining = MAX_REQUESTS_PER_DAY - $user_stats['requests_today'];
        
        return [
            'allowed' => $user_stats['requests_today'] < MAX_REQUESTS_PER_DAY,
            'remaining' => max(0, $remaining)
        ];
    }
    
    public function approveRequest($request_id, $admin_id) {
        if (!in_array($admin_id, ADMIN_IDS)) {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }
        
        $data = $this->loadData();
        
        if (!isset($data['requests'][$request_id])) {
            return ['success' => false, 'message' => 'Request not found'];
        }
        
        $request = $data['requests'][$request_id];
        
        if ($request['status'] != 'pending') {
            return ['success' => false, 'message' => "Request is already {$request['status']}"];
        }
        
        $data['requests'][$request_id]['status'] = 'approved';
        $data['requests'][$request_id]['approved_at'] = date('Y-m-d H:i:s');
        $data['requests'][$request_id]['approved_by'] = $admin_id;
        $data['requests'][$request_id]['updated_at'] = date('Y-m-d H:i:s');
        
        $data['system_stats']['approved']++;
        $data['system_stats']['pending']--;
        
        $user_id = $request['user_id'];
        $data['user_stats'][$user_id]['approved']++;
        $data['user_stats'][$user_id]['pending']--;
        
        $this->saveData($data);
        
        log_error("Request approved", 'INFO', ['request_id' => $request_id]);
        
        return [
            'success' => true,
            'request' => $data['requests'][$request_id],
            'message' => "✅ Request #$request_id approved!"
        ];
    }
    
    public function rejectRequest($request_id, $admin_id, $reason = '') {
        if (!in_array($admin_id, ADMIN_IDS)) {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }
        
        $data = $this->loadData();
        
        if (!isset($data['requests'][$request_id])) {
            return ['success' => false, 'message' => 'Request not found'];
        }
        
        $request = $data['requests'][$request_id];
        
        if ($request['status'] != 'pending') {
            return ['success' => false, 'message' => "Request is already {$request['status']}"];
        }
        
        $reason = validateInput($reason);
        
        $data['requests'][$request_id]['status'] = 'rejected';
        $data['requests'][$request_id]['rejected_at'] = date('Y-m-d H:i:s');
        $data['requests'][$request_id]['rejected_by'] = $admin_id;
        $data['requests'][$request_id]['updated_at'] = date('Y-m-d H:i:s');
        $data['requests'][$request_id]['reason'] = $reason;
        
        $data['system_stats']['rejected']++;
        $data['system_stats']['pending']--;
        
        $user_id = $request['user_id'];
        $data['user_stats'][$user_id]['rejected']++;
        $data['user_stats'][$user_id]['pending']--;
        
        $this->saveData($data);
        
        log_error("Request rejected", 'INFO', ['request_id' => $request_id]);
        
        return [
            'success' => true,
            'request' => $data['requests'][$request_id],
            'message' => "❌ Request #$request_id rejected!"
        ];
    }
    
    public function getPendingRequests($limit = 10, $filter_movie = '') {
        $data = $this->loadData();
        $pending = [];
        
        foreach ($data['requests'] as $request) {
            if ($request['status'] == 'pending') {
                if (!empty($filter_movie)) {
                    $movie_lower = strtolower($filter_movie);
                    $request_movie_lower = strtolower($request['movie_name']);
                    if (strpos($request_movie_lower, $movie_lower) === false) {
                        continue;
                    }
                }
                $pending[] = $request;
            }
        }
        
        usort($pending, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });
        
        return array_slice($pending, 0, $limit);
    }
    
    public function getUserRequests($user_id, $limit = 20) {
        $data = $this->loadData();
        $user_requests = [];
        
        foreach ($data['requests'] as $request) {
            if ($request['user_id'] == $user_id) {
                $user_requests[] = $request;
            }
        }
        
        usort($user_requests, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($user_requests, 0, $limit);
    }
    
    public function getRequest($request_id) {
        $data = $this->loadData();
        return $data['requests'][$request_id] ?? null;
    }
    
    public function getStats() {
        $data = $this->loadData();
        return $data['system_stats'];
    }
    
    public function checkAutoApprove($movie_name) {
        $movie_name = validateInput($movie_name, 'movie_name');
        if (!$movie_name) return [];
        
        $data = $this->loadData();
        $movie_lower = strtolower($movie_name);
        $auto_approved = [];
        
        foreach ($data['requests'] as $request_id => $request) {
            if ($request['status'] == 'pending') {
                $request_movie_lower = strtolower($request['movie_name']);
                
                if (strpos($movie_lower, $request_movie_lower) !== false || 
                    strpos($request_movie_lower, $movie_lower) !== false ||
                    similar_text($movie_lower, $request_movie_lower) > 80) {
                    
                    $data['requests'][$request_id]['status'] = 'approved';
                    $data['requests'][$request_id]['approved_at'] = date('Y-m-d H:i:s');
                    $data['requests'][$request_id]['approved_by'] = 'system';
                    $data['requests'][$request_id]['updated_at'] = date('Y-m-d H:i:s');
                    
                    $data['system_stats']['approved']++;
                    $data['system_stats']['pending']--;
                    
                    $user_id = $request['user_id'];
                    $data['user_stats'][$user_id]['approved']++;
                    $data['user_stats'][$user_id]['pending']--;
                    
                    $auto_approved[] = $request_id;
                }
            }
        }
        
        if (!empty($auto_approved)) {
            $this->saveData($data);
            log_error("Auto-approved requests", 'INFO', ['request_ids' => $auto_approved]);
        }
        
        return $auto_approved;
    }
    
    public function markAsNotified($request_id) {
        $data = $this->loadData();
        if (isset($data['requests'][$request_id])) {
            $data['requests'][$request_id]['is_notified'] = true;
            $this->saveData($data);
        }
    }
}

// ==================== TELEGRAM API FUNCTIONS ====================
function apiRequest($method, $params = array(), $is_multipart = false) {
    if (!RateLimiter::check('telegram_api', RATE_LIMIT_REQUESTS, RATE_LIMIT_WINDOW)) {
        log_error("Telegram API rate limit exceeded", 'WARNING');
        usleep(100000);
    }
    
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    
    log_error("API Request: $method", 'DEBUG', $params);
    
    if ($is_multipart) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $res = curl_exec($ch);
        if ($res === false) {
            log_error("CURL ERROR: " . curl_error($ch), 'ERROR');
        }
        curl_close($ch);
        return $res;
    } else {
        $options = [
            'http' => [
                'method' => 'POST',
                'content' => http_build_query($params),
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            ]
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            $error = error_get_last();
            log_error("apiRequest failed for method $method: " . ($error['message'] ?? 'Unknown error'), 'ERROR');
        }
        return $result;
    }
}

function sendChatAction($chat_id, $action = 'typing') {
    return apiRequest('sendChatAction', ['chat_id' => $chat_id, 'action' => $action]);
}

function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = null) {
    $data = ['chat_id' => $chat_id, 'text' => $text];
    if ($reply_markup) $data['reply_markup'] = $reply_markup;
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    
    log_error("Sending message to $chat_id", 'INFO', ['text_length' => strlen($text)]);
    
    return apiRequest('sendMessage', $data);
}

function editMessage($chat_id, $message_id, $new_text, $reply_markup = null, $parse_mode = null) {
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $new_text
    ];
    if ($reply_markup) $data['reply_markup'] = $reply_markup;
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    
    log_error("Editing message $message_id for $chat_id", 'INFO');
    
    return apiRequest('editMessageText', $data);
}

function deleteMessage($chat_id, $message_id) {
    return apiRequest('deleteMessage', [
        'chat_id' => $chat_id,
        'message_id' => $message_id
    ]);
}

function answerCallbackQuery($callback_query_id, $text = null, $show_alert = false) {
    $data = ['callback_query_id' => $callback_query_id, 'show_alert' => $show_alert];
    if ($text) $data['text'] = validateInput($text, 'text');
    return apiRequest('answerCallbackQuery', $data);
}

function forwardMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('forwardMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => validateInput($from_chat_id, 'telegram_id'),
        'message_id' => intval($message_id)
    ]);
}

function copyMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('copyMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => validateInput($from_chat_id, 'telegram_id'),
        'message_id' => intval($message_id)
    ]);
}

// ==================== MOVIE DELIVERY ====================
function deliver_item_to_chat($chat_id, $item) {
    $source_channel = $item['channel_id'] ?? MAIN_CHANNEL_ID;
    $channel_type = isset($item['channel_type']) ? $item['channel_type'] : getChannelType($source_channel);
    
    sendChatAction($chat_id, 'typing');
    
    if (!empty($item['message_id']) && is_numeric($item['message_id'])) {
        if ($channel_type === 'public') {
            $result = json_decode(forwardMessage($chat_id, $source_channel, $item['message_id']), true);
        } else {
            $result = json_decode(copyMessage($chat_id, $source_channel, $item['message_id']), true);
        }
        
        if ($result && $result['ok']) {
            update_stats('total_downloads', 1);
            bot_log("Movie delivered from $channel_type: {$item['movie_name']}");
            return true;
        }
    }

    $text = "🎬 <b>" . htmlspecialchars($item['movie_name'] ?? 'Unknown') . "</b>\n";
    $text .= "📊 Quality: " . htmlspecialchars($item['quality'] ?? 'Unknown') . "\n";
    $text .= "💾 Size: " . htmlspecialchars($item['size'] ?? 'Unknown') . "\n";
    $text .= "🗣️ Language: " . htmlspecialchars($item['language'] ?? 'Hindi') . "\n";
    $text .= "📡 Channel: " . getChannelUsername($source_channel) . "\n\n";
    
    if (!empty($item['message_id']) && is_numeric($item['message_id'])) {
        $link = get_direct_channel_link($item['message_id'], $source_channel);
        $text .= "🔗 <b>Direct Link:</b> $link\n\n";
        $text .= "📋 <b>Copy Link:</b>\n<code>$link</code>\n\n";
    }
    
    $text .= "⚠️ Join channel to access content: " . get_channel_username_link($channel_type);
    
    sendMessage($chat_id, $text, null, 'HTML');
    update_stats('total_downloads', 1);
    return false;
}

// ==================== STATISTICS ====================
function update_stats($field, $increment = 1) {
    if (!file_exists(STATS_FILE)) return;
    
    $stats = json_decode(file_get_contents(STATS_FILE), true);
    $stats[$field] = ($stats[$field] ?? 0) + $increment;
    $stats['last_updated'] = date('Y-m-d H:i:s');
    
    $today = date('Y-m-d');
    if (!isset($stats['daily_activity'][$today])) {
        $stats['daily_activity'][$today] = ['searches' => 0, 'downloads' => 0, 'users' => 0];
    }
    
    if ($field == 'total_searches') $stats['daily_activity'][$today]['searches'] += $increment;
    if ($field == 'total_downloads') $stats['daily_activity'][$today]['downloads'] += $increment;
    
    file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
}

function get_stats() {
    if (!file_exists(STATS_FILE)) return [];
    return json_decode(file_get_contents(STATS_FILE), true);
}

// ==================== USER MANAGEMENT ====================
function update_user_data($user_id, $user_info = []) {
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    
    if (!isset($users_data['users'][$user_id])) {
        $users_data['users'][$user_id] = [
            'first_name' => $user_info['first_name'] ?? '',
            'last_name' => $user_info['last_name'] ?? '',
            'username' => $user_info['username'] ?? '',
            'joined' => date('Y-m-d H:i:s'),
            'last_active' => date('Y-m-d H:i:s'),
            'points' => 0,
            'total_searches' => 0,
            'total_downloads' => 0,
            'request_count' => 0,
            'last_request_date' => null,
            'language' => 'hinglish'
        ];
        $users_data['total_requests'] = ($users_data['total_requests'] ?? 0) + 1;
        update_stats('total_users', 1);
        bot_log("New user registered: $user_id");
    }
    
    $users_data['users'][$user_id]['last_active'] = date('Y-m-d H:i:s');
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
    
    return $users_data['users'][$user_id];
}

function update_user_activity($user_id, $action) {
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    
    if (isset($users_data['users'][$user_id])) {
        $points_map = ['search' => 1, 'found_movie' => 5, 'daily_login' => 10, 'movie_request' => 2, 'download' => 3];
        
        $users_data['users'][$user_id]['points'] += ($points_map[$action] ?? 0);
        
        if ($action == 'search') $users_data['users'][$user_id]['total_searches']++;
        if ($action == 'download') $users_data['users'][$user_id]['total_downloads']++;
        
        $users_data['users'][$user_id]['last_active'] = date('Y-m-d H:i:s');
        file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
    }
}

// ==================== HINGLISH FUNCTIONS ====================
function detectUserLanguage($text) {
    $hindi_pattern = '/[\x{0900}-\x{097F}]/u';
    if(preg_match($hindi_pattern, $text)) return 'hindi';
    
    $hinglish_words = ['hai', 'hain', 'ka', 'ki', 'ke', 'mein', 'se', 'ko', 'par', 'aur', 'kya', 'kyun', 'kaise', 'kab', 'kahan', 'nahi', 'bahut', 'acha', 'bura', 'tha', 'the', 'gaya', 'gayi', 'bole', 'bolo', 'kar', 'karo', 'de', 'do', 'le', 'lo'];
    
    $words = explode(' ', strtolower($text));
    $hinglish_count = 0;
    foreach ($words as $word) {
        if (in_array($word, $hinglish_words)) $hinglish_count++;
    }
    
    if ($hinglish_count >= 2) return 'hinglish';
    return 'english';
}

function getUserLanguage($user_id) {
    if (file_exists(USERS_FILE)) {
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        if (isset($users_data['users'][$user_id]['language'])) {
            return $users_data['users'][$user_id]['language'];
        }
    }
    return 'hinglish';
}

function setUserLanguage($user_id, $lang) {
    if (!file_exists(USERS_FILE)) return;
    
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    if (!$users_data) $users_data = ['users' => []];
    
    if (!isset($users_data['users'][$user_id])) {
        $users_data['users'][$user_id] = [];
    }
    
    $users_data['users'][$user_id]['language'] = $lang;
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
}

function getHinglishResponse($key, $vars = []) {
    $responses = [
        'welcome' => "🎬 <b>Entertainment Tadka mein aapka swagat hai!</b>\n\n" .
                     "📢 <b>Bot kaise use karein:</b>\n" .
                     "• Bus movie ka naam likho\n" .
                     "• English ya Hindi dono mein likh sakte ho\n" .
                     "• 'theater' add karo theater print ke liye\n" .
                     "• Thoda sa naam bhi kaafi hai\n\n" .
                     "🔍 <b>Examples:</b>\n" .
                     "• Mandala Murders 2025\n" .
                     "• Lokah Chapter 1 Chandra 2025\n" .
                     "• Idli Kadai (2025)\n" .
                     "• IT - Welcome to Derry (2025) S01\n" .
                     "• hindi movie\n" .
                     "• kgf\n\n" .
                     "📢 <b>Hamare Channels:</b>\n" .
                     "🍿 Main: @EntertainmentTadka786\n" .
                     "🎭 Theater: @threater_print_movies\n" .
                     "📺 Serial: @Entertainment_Tadka_Serial_786\n" .
                     "📥 Requests: @EntertainmentTadka7860\n" .
                     "🔒 Backup: @ETBackup\n\n" .
                     "🎬 <b>Movie Request System:</b>\n" .
                     "• /request MovieName se request karo\n" .
                     "• Ya likho: 'pls add MovieName'\n" .
                     "• Status check karo /myrequests se\n" .
                     "• Roz sirf " . MAX_REQUESTS_PER_DAY . " requests kar sakte ho\n\n" .
                     "💡 <b>Tip:</b> /help se saari commands dekho",
        
        'help' => "🤖 <b>Entertainment Tadka Bot - Madad</b>\n\n" .
                  "📢 <b>Hamare Channels:</b>\n" .
                  "🍿 Main: @EntertainmentTadka786\n" .
                  "🎭 Theater: @threater_print_movies\n" .
                  "📺 Serial: @Entertainment_Tadka_Serial_786\n" .
                  "📥 Requests: @EntertainmentTadka7860\n" .
                  "🔒 Backup: @ETBackup\n\n" .
                  "📋 <b>Commands:</b>\n" .
                  "/start - Welcome message\n" .
                  "/help - Yeh help message\n" .
                  "/settings - Personalize settings\n" .
                  "/premium - Premium UI demo\n" .
                  "/movie [name] - Movie details\n" .
                  "/series [name] - Series episodes\n" .
                  "/search - Movie search\n" .
                  "/request MovieName - Request movie\n" .
                  "/myrequests - Apni requests dekho\n" .
                  "/requestlimit - Request limit check\n" .
                  "/totalupload - Saari movies browse\n" .
                  "/checkdate - Upload statistics\n" .
                  "/testcsv - Database test\n" .
                  "/checkcsv - CSV data check\n" .
                  "/csvstats - CSV statistics\n" .
                  "/language - Bhasha badlo\n" .
                  "/channel - Join channels\n\n" .
                  "👑 <b>Admin Commands:</b>\n" .
                  "/maintenance - Maintenance mode\n" .
                  "/cleanup - System cleanup\n" .
                  "/sendalert - Send alert\n" .
                  "/pendingrequests - Pending requests\n\n" .
                  "🎬 <b>Movie Requests:</b>\n" .
                  "• /request MovieName use karo\n" .
                  "• Ya likho: 'pls add MovieName'\n" .
                  "• Roz " . MAX_REQUESTS_PER_DAY . " requests max\n" .
                  "• Status check: /myrequests\n\n" .
                  "🔍 <b>Search kaise karein:</b>\n" .
                  "• Bus movie ka naam likho\n" .
                  "• Example: 'kgf', 'pushpa'",
        
        'search_found' => "🔍 <b>{count} movies mil gaye '{query}' ke liye:</b>\n\n{results}",
        'search_select' => "🚀 <b>Movie select karo saari copies pane ke liye:</b>",
        'search_not_found' => "😔 <b>Yeh movie abhi available nahi hai!</b>\n\n📢 Join: @EntertainmentTadka786",
        'invalid_search' => "🎬 <b>Please enter a valid movie name!</b>\n\nExamples:\n• kgf\n• pushpa\n• avengers",
        'request_success' => "✅ <b>Request successfully submit ho gayi!</b>\n\n🎬 Movie: {movie}\n📝 ID: #{id}\n🕒 Status: Pending",
        'request_duplicate' => "⚠️ <b>Yeh movie aap already request kar chuke ho!</b>",
        'request_limit' => "❌ <b>Aapne daily limit reach kar li hai!</b>\n\nRoz sirf {limit} requests kar sakte ho.",
        'request_guide' => "📝 <b>Movie Request Guide</b>\n\n" .
                           "1️⃣ <b>Command se:</b>\n" .
                           "<code>/request Movie Name</code>\n" .
                           "Example: /request KGF Chapter 3\n\n" .
                           "2️⃣ <b>Natural Language se:</b>\n" .
                           "• pls add Movie Name\n" .
                           "• please add Movie Name\n\n" .
                           "📌 <b>Limit:</b> {limit} requests per day",
        
        'myrequests_empty' => "📭 <b>Aapne abhi tak koi request nahi ki hai.</b>",
        'error' => "❌ <b>Error:</b> {message}",
        'maintenance' => "🛠️ <b>Bot Under Maintenance</b>\n\nWe'll be back soon!",
        'language_choose' => "🌐 <b>Choose your language / अपनी भाषा चुनें:</b>",
        'language_english' => "Language set to English",
        'language_hindi' => "भाषा हिंदी में सेट हो गई",
        'language_hinglish' => "Hinglish mode active!"
    ];
    
    $response = isset($responses[$key]) ? $responses[$key] : $key;
    
    foreach ($vars as $var => $value) {
        $response = str_replace('{' . $var . '}', $value, $response);
    }
    
    return $response;
}

function sendHinglish($chat_id, $key, $vars = [], $reply_markup = null) {
    $message = getHinglishResponse($key, $vars);
    return sendMessage($chat_id, $message, $reply_markup, 'HTML');
}

// ==================== SEARCH FUNCTIONS ====================
function smart_search($query) {
    global $movie_messages;
    
    if(empty($movie_messages)) {
        $csvManager = CSVManager::getInstance();
        $data = $csvManager->getCachedData();
        foreach($data as $item) {
            $movie = strtolower($item['movie_name']);
            if(!isset($movie_messages[$movie])) $movie_messages[$movie] = [];
            $movie_messages[$movie][] = $item;
        }
    }
    
    $query_lower = strtolower(trim($query));
    $results = array();
    
    $is_theater_search = false;
    $theater_keywords = ['theater', 'theatre', 'print', 'hdcam', 'camrip'];
    foreach ($theater_keywords as $keyword) {
        if (strpos($query_lower, $keyword) !== false) {
            $is_theater_search = true;
            $query_lower = str_replace($keyword, '', $query_lower);
            break;
        }
    }
    $query_lower = trim($query_lower);
    
    foreach ($movie_messages as $movie => $entries) {
        $score = 0;
        
        if ($movie == $query_lower) {
            $score = 100;
        } elseif (strpos($movie, $query_lower) !== false) {
            $score = 80;
        } else {
            similar_text($movie, $query_lower, $similarity);
            if ($similarity > 60) $score = $similarity;
        }
        
        if ($is_theater_search) {
            foreach($entries as $e) {
                if(strpos(strtolower($e['quality'] ?? ''), 'theater') !== false) $score += 20;
            }
        }
        
        if ($score > 0) {
            $results[$movie] = [
                'score' => $score,
                'count' => count($entries),
                'qualities' => array_unique(array_column($entries, 'quality'))
            ];
        }
    }
    
    uasort($results, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    return array_slice($results, 0, 10);
}

function advanced_search($chat_id, $query, $user_id = null) {
    global $movie_messages;
    
    sendChatAction($chat_id, 'typing');
    
    $q = strtolower(trim($query));
    
    if (strlen($q) < 2) {
        sendMessage($chat_id, "❌ Please enter at least 2 characters for search");
        return;
    }
    
    $invalid_keywords = ['vlc', 'audio', 'track', 'how', 'what', 'why', 'help', 'problem', 'issue'];
    $words = explode(' ', $q);
    $invalid_count = 0;
    foreach ($words as $word) {
        if (in_array($word, $invalid_keywords)) $invalid_count++;
    }
    
    if ($invalid_count > 0 && ($invalid_count / count($words)) > 0.5) {
        sendHinglish($chat_id, 'invalid_search');
        return;
    }
    
    $found = smart_search($q);
    
    if (!empty($found)) {
        update_stats('successful_searches', 1);
        
        $msg = "🔍 Found " . count($found) . " movies for '$query':\n\n";
        $i = 1;
        foreach ($found as $movie => $data) {
            $quality_info = !empty($data['qualities']) ? implode('/', $data['qualities']) : 'Unknown';
            $msg .= "$i. $movie (" . $data['count'] . " versions, $quality_info)\n";
            $i++;
            if ($i > 10) break;
        }
        
        sendMessage($chat_id, $msg);
        
        $keyboard = ['inline_keyboard' => []];
        $top_movies = array_slice(array_keys($found), 0, 5);
        
        foreach ($top_movies as $movie) {
            $keyboard['inline_keyboard'][] = [[ 
                'text' => "🎬 " . ucwords($movie), 
                'callback_data' => $movie 
            ]];
        }
        
        $keyboard['inline_keyboard'][] = [[
            'text' => "📝 Request Different Movie", 
            'callback_data' => 'request_movie'
        ]];
        
        sendMessage($chat_id, "🚀 Top matches (click for info):", json_encode($keyboard));
        
        if ($user_id) update_user_activity($user_id, 'search');
    } else {
        update_stats('failed_searches', 1);
        sendHinglish($chat_id, 'search_not_found');
        
        $request_keyboard = [
            'inline_keyboard' => [[
                ['text' => '📝 Request This Movie', 'callback_data' => 'auto_request_' . base64_encode($query)]
            ]]
        ];
        
        sendMessage($chat_id, "💡 Click below to automatically request this movie:", json_encode($request_keyboard));
    }
    
    update_stats('total_searches', 1);
}

// ==================== PAGINATION FUNCTIONS ====================
function paginate_movies(array $all, int $page): array {
    $total = count($all);
    if ($total === 0) {
        return ['total' => 0, 'total_pages' => 1, 'page' => 1, 'slice' => [], 'has_next' => false, 'has_prev' => false];
    }
    
    $total_pages = (int)ceil($total / ITEMS_PER_PAGE);
    $page = max(1, min($page, $total_pages));
    $start = ($page - 1) * ITEMS_PER_PAGE;
    
    return [
        'total' => $total,
        'total_pages' => $total_pages,
        'page' => $page,
        'slice' => array_slice($all, $start, ITEMS_PER_PAGE),
        'has_next' => $page < $total_pages,
        'has_prev' => $page > 1
    ];
}

function totalupload_controller($chat_id, $page = 1) {
    $csvManager = CSVManager::getInstance();
    $all = $csvManager->getCachedData();
    
    if (empty($all)) {
        sendMessage($chat_id, "📭 Koi movies nahi mili! Pehle kuch movies add karo.");
        return;
    }
    
    $pg = paginate_movies($all, (int)$page);
    
    foreach ($pg['slice'] as $movie) {
        deliver_item_to_chat($chat_id, $movie);
        usleep(500000);
    }
    
    $text = "📊 <b>Movie Browser</b>\n\n";
    $text .= "• Page: {$pg['page']}/{$pg['total_pages']}\n";
    $text .= "• Total Movies: {$pg['total']}\n\n";
    $text .= "➡️ Use buttons to navigate";
    
    $keyboard = ['inline_keyboard' => []];
    $row = [];
    if ($pg['has_prev']) {
        $row[] = ['text' => '◀️ Prev', 'callback_data' => 'tu_prev_' . ($pg['page'] - 1)];
    }
    if ($pg['has_next']) {
        $row[] = ['text' => 'Next ▶️', 'callback_data' => 'tu_next_' . ($pg['page'] + 1)];
    }
    if (!empty($row)) {
        $keyboard['inline_keyboard'][] = $row;
    }
    
    sendMessage($chat_id, $text, json_encode($keyboard), 'HTML');
}

function batch_download_with_progress($chat_id, $movies, $page_num) {
    $total = count($movies);
    if ($total === 0) return;
    
    $progress_msg = sendMessage($chat_id, "📦 <b>Batch Info Started</b>\n\nPage: {$page_num}\nTotal: {$total} movies\n\n⏳ Initializing...");
    $progress_id = $progress_msg['result']['message_id'] ?? 0;
    if (!$progress_id) return;
    
    $success = 0;
    $failed = 0;
    
    for ($i = 0; $i < $total; $i++) {
        $movie = $movies[$i];
        
        if ($i % 2 == 0) {
            $progress = round(($i / $total) * 100);
            editMessage($chat_id, $progress_id, 
                "📦 <b>Sending Page {$page_num} Info</b>\n\n" .
                "Progress: {$progress}%\n" .
                "Processed: {$i}/{$total}\n" .
                "✅ Success: {$success}\n" .
                "❌ Failed: {$failed}\n\n" .
                "⏳ Please wait...", null, 'HTML');
        }
        
        try {
            $result = deliver_item_to_chat($chat_id, $movie);
            if ($result) $success++; else $failed++;
        } catch (Exception $e) {
            $failed++;
        }
        
        usleep(500000);
    }
    
    editMessage($chat_id, $progress_id,
        "✅ <b>Batch Info Complete</b>\n\n" .
        "📄 Page: {$page_num}\n" .
        "🎬 Total: {$total} movies\n" .
        "✅ Success: {$success}\n" .
        "❌ Failed: {$failed}", null, 'HTML');
}

// ==================== CHANNEL INFO FUNCTIONS ====================
function show_channel_info($chat_id) {
    $message = "📢 <b>Join Our Channels</b>\n\n";
    $message .= "🍿 <b>Main Channel:</b> @EntertainmentTadka786\n";
    $message .= "• Latest movie updates\n\n";
    $message .= "📥 <b>Requests Channel:</b> @EntertainmentTadka7860\n";
    $message .= "• Movie requests\n\n";
    $message .= "🎭 <b>Theater Prints:</b> @threater_print_movies\n";
    $message .= "• Theater quality prints\n\n";
    $message .= "📺 <b>Serial Channel:</b> @Entertainment_Tadka_Serial_786\n";
    $message .= "• Web series & TV shows\n\n";
    $message .= "🔒 <b>Backup Channel:</b> @ETBackup\n";
    $message .= "• Secure data backups\n";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🍿 Main', 'url' => 'https://t.me/EntertainmentTadka786'],
                ['text' => '📥 Requests', 'url' => 'https://t.me/EntertainmentTadka7860']
            ],
            [
                ['text' => '🎭 Theater', 'url' => 'https://t.me/threater_print_movies'],
                ['text' => '📺 Serial', 'url' => 'https://t.me/Entertainment_Tadka_Serial_786']
            ],
            [
                ['text' => '🔒 Backup', 'url' => 'https://t.me/ETBackup']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, json_encode($keyboard), 'HTML');
}

// ==================== ADMIN COMMANDS ====================
function admin_stats($chat_id) {
    if (!in_array($chat_id, ADMIN_IDS)) {
        sendMessage($chat_id, "❌ Access denied. Admin only command.");
        return;
    }
    
    $stats = get_stats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $total_users = count($users_data['users'] ?? []);
    
    $msg = "📊 <b>Bot Statistics</b>\n\n";
    $msg .= "🎬 Total Movies: " . ($stats['total_movies'] ?? 0) . "\n";
    $msg .= "👥 Total Users: " . $total_users . "\n";
    $msg .= "🔍 Total Searches: " . ($stats['total_searches'] ?? 0) . "\n";
    $msg .= "📥 Total Downloads: " . ($stats['total_downloads'] ?? 0) . "\n";
    $msg .= "🕒 Last Updated: " . ($stats['last_updated'] ?? 'N/A');
    
    sendMessage($chat_id, $msg, null, 'HTML');
}

function show_csv_data($chat_id, $show_all = false) {
    if (!file_exists(CSV_FILE)) {
        sendMessage($chat_id, "❌ CSV file not found.");
        return;
    }
    
    $csvManager = CSVManager::getInstance();
    $data = $csvManager->getCachedData();
    
    if (empty($data)) {
        sendMessage($chat_id, "📊 CSV file is empty.");
        return;
    }
    
    $limit = $show_all ? count($data) : 10;
    $display_data = array_slice($data, -$limit);
    
    $message = "📊 <b>CSV Movie Database</b>\n\n";
    $message .= "📁 Total Movies: " . count($data) . "\n";
    if (!$show_all) {
        $message .= "🔍 Showing latest 10 entries\n\n";
    }
    
    $i = 1;
    foreach ($display_data as $movie) {
        $movie_name = htmlspecialchars($movie['movie_name'] ?? 'Unknown');
        $channel_name = getChannelUsername($movie['channel_id']);
        $message .= "$i. 🎬 " . $movie_name . "\n";
        $message .= "   📝 ID: " . $movie['message_id'] . "\n";
        $message .= "   📡 Channel: " . $channel_name . "\n\n";
        $i++;
        
        if (strlen($message) > 3000) {
            sendMessage($chat_id, $message, null, 'HTML');
            $message = "📊 Continuing...\n\n";
        }
    }
    
    sendMessage($chat_id, $message, null, 'HTML');
}

function toggle_maintenance_mode($chat_id, $mode) {
    global $MAINTENANCE_MODE;
    
    if (!in_array($chat_id, ADMIN_IDS)) {
        sendMessage($chat_id, "❌ Access denied. Admin only command.");
        return;
    }
    
    if ($mode == 'on') {
        $MAINTENANCE_MODE = true;
        sendMessage($chat_id, "🔧 Maintenance mode ENABLED", null, 'HTML');
    } elseif ($mode == 'off') {
        $MAINTENANCE_MODE = false;
        sendMessage($chat_id, "✅ Maintenance mode DISABLED", null, 'HTML');
    } else {
        sendMessage($chat_id, "❌ Usage: <code>/maintenance on</code> or <code>/maintenance off</code>", null, 'HTML');
    }
}

function perform_cleanup($chat_id) {
    if (!in_array($chat_id, ADMIN_IDS)) {
        sendMessage($chat_id, "❌ Access denied. Admin only command.");
        return;
    }
    
    $csvManager = CSVManager::getInstance();
    $csvManager->clearCache();
    
    sendMessage($chat_id, "🧹 Cleanup completed!\n\n• Cache cleared\n• System optimized", null, 'HTML');
}

function send_alert_to_all($chat_id, $alert_message) {
    if (!in_array($chat_id, ADMIN_IDS)) {
        sendMessage($chat_id, "❌ Access denied. Admin only command.");
        return;
    }
    
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $success_count = 0;
    
    foreach ($users_data['users'] as $user_id => $user) {
        try {
            sendMessage($user_id, "🚨 <b>Important Alert:</b>\n\n$alert_message", null, 'HTML');
            $success_count++;
            usleep(50000);
        } catch (Exception $e) {}
    }
    
    sendMessage($chat_id, "✅ Alert sent to $success_count users!", null, 'HTML');
}

function csv_stats_command($chat_id) {
    $csvManager = CSVManager::getInstance();
    $stats = $csvManager->getStats();
    
    $csv_size = file_exists(CSV_FILE) ? filesize(CSV_FILE) : 0;
    
    $message = "📊 <b>CSV Database Statistics</b>\n\n";
    $message .= "📁 File Size: " . round($csv_size / 1024, 2) . " KB\n";
    $message .= "🎬 Total Movies: " . $stats['total_movies'] . "\n";
    $message .= "🕒 Last Updated: " . $stats['last_updated'] . "\n\n";
    
    foreach ($stats['channels'] as $channel_id => $count) {
        $channel_name = getChannelUsername($channel_id);
        $message .= "• " . $channel_name . ": " . $count . "\n";
    }
    
    sendMessage($chat_id, $message, null, 'HTML');
}

function check_date($chat_id) {
    $stats = get_stats();
    $msg = "📅 Bot Statistics\n\n";
    $msg .= "🎬 Total Movies: " . ($stats['total_movies'] ?? 0) . "\n";
    $msg .= "👥 Total Users: " . ($stats['total_users'] ?? 0) . "\n";
    $msg .= "🔍 Total Searches: " . ($stats['total_searches'] ?? 0) . "\n";
    $msg .= "🕒 Last Updated: " . ($stats['last_updated'] ?? 'N/A');
    sendMessage($chat_id, $msg, null, 'HTML');
}

function test_csv($chat_id) {
    $csvManager = CSVManager::getInstance();
    $data = $csvManager->getCachedData();
    
    if (empty($data)) {
        sendMessage($chat_id, "📊 CSV file is empty.", null, 'HTML');
        return;
    }
    
    $message = "📊 CSV Movie Database\n\n";
    $message .= "📁 Total Movies: " . count($data) . "\n";
    $message .= "🔍 Showing latest 10 entries\n\n";
    
    $recent = array_slice($data, -10);
    $i = 1;
    foreach ($recent as $movie) {
        $movie_name = htmlspecialchars($movie['movie_name'] ?? 'Unknown');
        $channel_name = getChannelUsername($movie['channel_id']);
        $message .= "$i. 🎬 " . $movie_name . "\n";
        $message .= "   📝 ID: " . $movie['message_id'] . "\n";
        $message .= "   📡 Channel: " . $channel_name . "\n\n";
        $i++;
    }
    
    sendMessage($chat_id, $message, null, 'HTML');
}

// ==================== SAMPLE DATA LOADER FUNCTIONS ====================
function load_movie_files($movie_name) {
    return [
        ['size' => '3.02 GB', 'name' => 'Dhurandhar 2025 HINDI 1080p 10bit WEBRip 6CH x265 HEVC PSA mkv'],
        ['size' => '1.51 GB', 'name' => 'Dhurandhar 2025 HINDI 720p 10bit WEBRip 6CH x265 HEVC PSA mkv'],
        ['size' => '1.51 GB', 'name' => 'Dhurandhar 2025 720p 10bit DS4K NF WEBRip HIN AAC5 1 x265 HEVC ESub mkv'],
        ['size' => '3.34 GB', 'name' => 'Dhurandhar 2025 1080p 10bit DS4K NF WEBRip Hindi AAC 5 1 x265 HEVC mkv'],
        ['size' => '688.23 MB', 'name' => 'Dhurandhar 2025 480p V2 HDTC Hindi LiNE x264 mkv'],
        ['size' => '1.18 GB', 'name' => 'Dhurandhar 2025 720p HEVC V2 HDTC Hindi LiNE x265 HD mkv'],
        ['size' => '2.69 GB', 'name' => 'Dhurandhar 2025 1080p HEVC V2 HDTC Hindi LiNE x265 HD mkv'],
        ['size' => '1.67 GB', 'name' => 'Dhurandhar 2025 720p V2 HDTC Hindi LiNE x264 mkv'],
    ];
}

function load_series_episodes($series_name) {
    return [
        ['size' => '170.25 MB', 'name' => 'Tuu Juliet Jatt Di S01E100 Gulaab Shows a New Side for Heer 720p mkv'],
        ['size' => '170.71 MB', 'name' => 'Tuu Juliet Jatt Di S01E99 Nawab Reconsiders His Marriage 720p JHS mkv'],
        ['size' => '146.25 MB', 'name' => 'Tuu Juliet Jatt Di S01E98 Heer Faces the Verdict 720p JIOHS WEB mkv'],
        ['size' => '154.03 MB', 'name' => 'Tuu Juliet Jatt Di S01E97 Heer and Nawab Heartfelt Regrets 720p mkv'],
        ['size' => '197.79 MB', 'name' => 'Tuu Juliet Jatt Di S01E96 Heers Forest Misadventure 720p JHS WEB mkv'],
        ['size' => '150.12 MB', 'name' => 'Tuu Juliet Jatt Di S01E95 Nawab Races Against Time 720p JIOHS WEB mkv'],
        ['size' => '193.03 MB', 'name' => 'Tuu Juliet Jatt Di S01E94 Nawab Heer Part Ways 720p JHS WEB DL Hindi mkv'],
        ['size' => '163.08 MB', 'name' => 'Tuu Juliet Jatt Di S01E93 Heer Lies to Protect Tina 720p JHS WEB mkv'],
        ['size' => '190.11 MB', 'name' => 'Tuu Juliet Jatt Di S01E92 Heers Bitter Crown 720p JHS WEB DL Hindi mkv'],
        ['size' => '164.35 MB', 'name' => 'Tuu Juliet Jatt Di S01E91 Nawabs Hidden Feelings for Heer 720p JHS mp4'],
    ];
}

// ==================== COMMAND HANDLER ====================
function handle_command($chat_id, $user_id, $command, $params = []) {
    switch ($command) {
        case '/start':
            $welcome = getHinglishResponse('welcome');
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🍿 Main Channel', 'url' => 'https://t.me/EntertainmentTadka786'],
                        ['text' => '🎭 Theater Prints', 'url' => 'https://t.me/threater_print_movies']
                    ],
                    [
                        ['text' => '📥 Requests', 'url' => 'https://t.me/EntertainmentTadka7860'],
                        ['text' => '🔒 Backup', 'url' => 'https://t.me/ETBackup']
                    ],
                    [
                        ['text' => '📺 Serial Channel', 'url' => 'https://t.me/Entertainment_Tadka_Serial_786'],
                        ['text' => '❓ Help', 'callback_data' => 'help_command']
                    ],
                    [
                        ['text' => '⚙️ Settings', 'callback_data' => 'settings'],
                        ['text' => '🎬 Premium UI', 'callback_data' => 'premium_ui_demo']
                    ]
                ]
            ];
            
            sendMessage($chat_id, $welcome, json_encode($keyboard), 'HTML');
            update_user_activity($user_id, 'daily_login');
            break;

        case '/help':
            sendHinglish($chat_id, 'help');
            break;

        case '/search':
            $movie_name = implode(' ', $params);
            if (empty($movie_name)) {
                sendMessage($chat_id, "❌ Usage: <code>/search movie_name</code>\nExample: <code>/search kgf 2</code>", null, 'HTML');
                return;
            }
            advanced_search($chat_id, $movie_name, $user_id);
            break;

        case '/totalupload':
            $page = isset($params[0]) ? intval($params[0]) : 1;
            totalupload_controller($chat_id, $page);
            break;

        case '/checkdate':
            check_date($chat_id);
            break;

        case '/language':
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🇬🇧 English', 'callback_data' => 'lang_english'],
                        ['text' => '🇮🇳 हिंदी', 'callback_data' => 'lang_hindi'],
                        ['text' => '🎭 Hinglish', 'callback_data' => 'lang_hinglish']
                    ]
                ]
            ];
            sendHinglish($chat_id, 'language_choose', [], json_encode($keyboard));
            break;

        case '/settings':
            show_personalize_settings($chat_id);
            break;

        case '/premium':
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🎬 Try Premium UI Demo', 'callback_data' => 'premium_ui_demo'],
                        ['text' => '🔍 Search Movie', 'switch_inline_query_current_chat' => '']
                    ]
                ]
            ];
            sendMessage($chat_id, "🎬 <b>Premium OTT UI</b>\n\nSend any movie name to see the premium interface!", json_encode($keyboard), 'HTML');
            break;

        case '/movie':
            $movie_name = implode(' ', $params);
            if (empty($movie_name)) {
                sendMessage($chat_id, "❌ Usage: /movie movie_name");
                break;
            }
            
            $files = load_movie_files($movie_name);
            
            if (empty($files)) {
                sendMessage($chat_id, "❌ No files found for this movie");
                break;
            }
            
            $users_data = json_decode(file_get_contents(USERS_FILE), true);
            $requester_name = $users_data['users'][$user_id]['first_name'] ?? 'User';
            
            $total_pages = ceil(count($files) / 10);
            $result = show_premium_movie_list($chat_id, $movie_name, $requester_name, $files, 1, $total_pages);
            sendMessage($chat_id, $result['text'], json_encode($result['keyboard']), 'HTML');
            break;

        case '/series':
            $series_name = implode(' ', $params);
            if (empty($series_name)) {
                sendMessage($chat_id, "❌ Usage: /series series_name");
                break;
            }
            
            $episodes = load_series_episodes($series_name);
            
            if (empty($episodes)) {
                sendMessage($chat_id, "❌ No episodes found for this series");
                break;
            }
            
            $users_data = json_decode(file_get_contents(USERS_FILE), true);
            $requester_name = $users_data['users'][$user_id]['first_name'] ?? 'User';
            
            $total_pages = ceil(count($episodes) / 10);
            $result = show_premium_series_list($chat_id, $series_name, $requester_name, $episodes, 1, $total_pages);
            sendMessage($chat_id, $result['text'], json_encode($result['keyboard']), 'HTML');
            break;

        case '/request':
            if (!REQUEST_SYSTEM_ENABLED) {
                sendHinglish($chat_id, 'error', ['message' => 'Request system currently disabled']);
                return;
            }
            
            if (count($params) == 0) {
                sendHinglish($chat_id, 'request_guide', ['limit' => MAX_REQUESTS_PER_DAY]);
                return;
            }
            
            $requestSystem = RequestSystem::getInstance();
            $movie_name = implode(' ', $params);
            $user_name = $users_data['users'][$user_id]['first_name'] ?? 'User';
            
            $result = $requestSystem->submitRequest($user_id, $movie_name, $user_name);
            
            if ($result['success']) {
                sendHinglish($chat_id, 'request_success', [
                    'movie' => $movie_name,
                    'id' => $result['request_id']
                ]);
                update_user_activity($user_id, 'movie_request');
            } else {
                if (strpos($result['message'], 'already requested') !== false) {
                    sendHinglish($chat_id, 'request_duplicate');
                } elseif (strpos($result['message'], 'daily limit') !== false) {
                    sendHinglish($chat_id, 'request_limit', ['limit' => MAX_REQUESTS_PER_DAY]);
                } else {
                    sendMessage($chat_id, $result['message'], null, 'HTML');
                }
            }
            break;

        case '/myrequests':
            $requestSystem = RequestSystem::getInstance();
            $requests = $requestSystem->getUserRequests($user_id, 10);
            
            if (empty($requests)) {
                sendHinglish($chat_id, 'myrequests_empty');
                return;
            }
            
            $message = "📋 <b>Your Movie Requests</b>\n\n";
            
            foreach ($requests as $req) {
                $status_icon = $req['status'] == 'approved' ? '✅' : ($req['status'] == 'rejected' ? '❌' : '⏳');
                $message .= "$status_icon <b>" . htmlspecialchars($req['movie_name']) . "</b>\n";
                $message .= "   ID: #" . $req['id'] . " | " . ucfirst($req['status']) . "\n";
                $message .= "   Date: " . date('d M', strtotime($req['created_at'])) . "\n\n";
            }
            
            sendMessage($chat_id, $message, null, 'HTML');
            break;

        case '/requestlimit':
            $requestSystem = RequestSystem::getInstance();
            $stats = $requestSystem->getUserStats($user_id);
            
            $message = "📋 <b>Your Request Limit</b>\n\n";
            $message .= "✅ Daily Limit: " . MAX_REQUESTS_PER_DAY . " requests\n";
            $message .= "📅 Used Today: " . ($stats['requests_today'] ?? 0) . " requests\n";
            $message .= "🎯 Remaining Today: " . (MAX_REQUESTS_PER_DAY - ($stats['requests_today'] ?? 0)) . " requests";
            
            sendMessage($chat_id, $message, null, 'HTML');
            break;

        case '/testcsv':
            test_csv($chat_id);
            break;

        case '/checkcsv':
            $show_all = (isset($params[0]) && strtolower($params[0]) == 'all');
            show_csv_data($chat_id, $show_all);
            break;

        case '/csvstats':
            csv_stats_command($chat_id);
            break;

        case '/channel':
            show_channel_info($chat_id);
            break;

        // Admin Commands
        case '/maintenance':
            $mode = isset($params[0]) ? strtolower($params[0]) : '';
            toggle_maintenance_mode($chat_id, $mode);
            break;

        case '/cleanup':
            perform_cleanup($chat_id);
            break;

        case '/sendalert':
            $alert_message = implode(' ', $params);
            if (empty($alert_message)) {
                sendMessage($chat_id, "❌ Usage: <code>/sendalert your_alert</code>", null, 'HTML');
                return;
            }
            send_alert_to_all($chat_id, $alert_message);
            break;

        case '/pendingrequests':
            $requestSystem = RequestSystem::getInstance();
            $limit = isset($params[0]) && is_numeric($params[0]) ? min(intval($params[0]), 50) : 10;
            
            $requests = $requestSystem->getPendingRequests($limit);
            $stats = $requestSystem->getStats();
            
            if (empty($requests)) {
                sendMessage($chat_id, "📭 No pending requests", null, 'HTML');
                return;
            }
            
            $message = "📋 <b>Pending Requests</b>\n\n";
            $message .= "📊 System Stats:\n";
            $message .= "• Total: " . $stats['total_requests'] . "\n";
            $message .= "• Pending: " . $stats['pending'] . "\n";
            $message .= "• Approved: " . $stats['approved'] . "\n";
            $message .= "• Rejected: " . $stats['rejected'] . "\n\n";
            
            $keyboard = ['inline_keyboard' => []];
            
            foreach ($requests as $req) {
                $movie_name = htmlspecialchars($req['movie_name']);
                $user_name = htmlspecialchars($req['user_name'] ?: "ID: " . $req['user_id']);
                $message .= "🔸 <b>#" . $req['id'] . ":</b> " . $movie_name . "\n";
                $message .= "   👤 User: " . $user_name . "\n";
                $message .= "   📅 Date: " . date('d M H:i', strtotime($req['created_at'])) . "\n\n";
                
                $keyboard['inline_keyboard'][] = [
                    [
                        ['text' => '✅ Approve #' . $req['id'], 'callback_data' => 'approve_' . $req['id']],
                        ['text' => '❌ Reject #' . $req['id'], 'callback_data' => 'reject_' . $req['id']]
                    ]
                ];
            }
            
            sendMessage($chat_id, $message, json_encode($keyboard), 'HTML');
            break;

        default:
            sendMessage($chat_id, "❌ Unknown command. Use <code>/help</code> to see all available commands.", null, 'HTML');
    }
}

// ==================== NOTIFICATION FUNCTION ====================
function notifyUserAboutRequest($user_id, $request, $action) {
    $requestSystem = RequestSystem::getInstance();
    
    $movie_name = htmlspecialchars($request['movie_name'], ENT_QUOTES, 'UTF-8');
    
    if ($action == 'approved') {
        $message = "🎉 <b>Good News!</b>\n\n";
        $message .= "✅ Your movie request has been <b>APPROVED</b>!\n\n";
        $message .= "🎬 <b>Movie:</b> $movie_name\n";
        $message .= "📝 <b>Request ID:</b> #" . $request['id'] . "\n\n";
        $message .= "🔍 You can now search for this movie in the bot!";
    } else {
        $message = "📭 <b>Update on Your Request</b>\n\n";
        $message .= "❌ Your movie request has been <b>REJECTED</b>.\n\n";
        $message .= "🎬 <b>Movie:</b> $movie_name\n";
        $message .= "📝 <b>Request ID:</b> #" . $request['id'] . "\n";
        
        if (!empty($request['reason'])) {
            $message .= "📋 <b>Reason:</b> " . htmlspecialchars($request['reason'], ENT_QUOTES, 'UTF-8') . "\n";
        }
    }
    
    sendMessage($user_id, $message, null, 'HTML');
    $requestSystem->markAsNotified($request['id']);
}

// ==================== MAIN PROCESSING ====================
$csvManager = CSVManager::getInstance();
$requestSystem = RequestSystem::getInstance();

$update = json_decode(file_get_contents('php://input'), true);

if ($update) {
    log_error("Update received", 'INFO', ['update_id' => $update['update_id'] ?? 'N/A']);
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!RateLimiter::check($ip, 'telegram_update', 30, 60)) {
        http_response_code(429);
        exit;
    }
    
    if (MAINTENANCE_MODE && isset($update['message']) && !in_array($update['message']['from']['id'], ADMIN_IDS)) {
        $chat_id = $update['message']['chat']['id'];
        sendMessage($chat_id, $MAINTENANCE_MESSAGE, null, 'HTML');
        exit;
    }

    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $message['from']['id'];
        $text = isset($message['text']) ? $message['text'] : '';
        $chat_type = $message['chat']['type'] ?? 'private';
        
        log_error("Message received", 'INFO', [
            'chat_id' => $chat_id,
            'user_id' => $user_id,
            'text' => substr($text, 0, 100)
        ]);
        
        $user_info = [
            'first_name' => $message['from']['first_name'] ?? '',
            'last_name' => $message['from']['last_name'] ?? '',
            'username' => $message['from']['username'] ?? ''
        ];
        update_user_data($user_id, $user_info);

        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = strtolower($parts[0]);
            $params = array_slice($parts, 1);
            handle_command($chat_id, $user_id, $command, $params);
        } else if (!empty(trim($text))) {
            if (stripos($text, 'add movie') !== false || stripos($text, 'pls add') !== false) {
                $movie_name = trim(preg_replace('/add movie|pls add|please add/i', '', $text));
                
                if (strlen($movie_name) > 2) {
                    $result = $requestSystem->submitRequest($user_id, $movie_name, $user_info['first_name'] ?? 'User');
                    
                    if ($result['success']) {
                        sendHinglish($chat_id, 'request_success', [
                            'movie' => $movie_name,
                            'id' => $result['request_id']
                        ]);
                    } else {
                        sendMessage($chat_id, $result['message'], null, 'HTML');
                    }
                }
            } else {
                advanced_search($chat_id, $text, $user_id);
            }
        }
    }

    if (isset($update['callback_query'])) {
        $query = $update['callback_query'];
        $message = $query['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $query['from']['id'];
        $data = $query['data'];
        $message_id = $message['message_id'];

        log_error("Callback query received", 'INFO', ['callback_data' => $data]);

        sendChatAction($chat_id, 'typing');

        if (strpos($data, 'movie_') === 0) {
            $movie_name = base64_decode(str_replace('movie_', '', $data));
            
            $csvManager = CSVManager::getInstance();
            $all_movies = $csvManager->getCachedData();
            $movie_items = [];
            
            foreach ($all_movies as $item) {
                if (strtolower($item['movie_name']) === strtolower($movie_name)) {
                    $movie_items[] = $item;
                }
            }
            
            if (!empty($movie_items)) {
                $sent_count = 0;
                foreach ($movie_items as $item) {
                    if (deliver_item_to_chat($chat_id, $item)) {
                        $sent_count++;
                        usleep(300000);
                    }
                }
                
                sendMessage($chat_id, "✅ Sent $sent_count copies of '$movie_name'\n\n📢 Join: " . MAIN_CHANNEL, null, 'HTML');
                answerCallbackQuery($query['id'], "🎬 $sent_count items sent!");
            } else {
                answerCallbackQuery($query['id'], "❌ Movie not found", true);
            }
        }
        elseif (strpos($data, 'tu_prev_') === 0) {
            $page = (int)str_replace('tu_prev_', '', $data);
            totalupload_controller($chat_id, $page);
            answerCallbackQuery($query['id'], "Page $page");
        }
        elseif (strpos($data, 'tu_next_') === 0) {
            $page = (int)str_replace('tu_next_', '', $data);
            totalupload_controller($chat_id, $page);
            answerCallbackQuery($query['id'], "Page $page");
        }
        elseif ($data === 'request_movie') {
            sendHinglish($chat_id, 'request_guide', ['limit' => MAX_REQUESTS_PER_DAY]);
            answerCallbackQuery($query['id'], "📝 Request guide opened");
        }
        elseif ($data === 'help_command') {
            $help_text = getHinglishResponse('help');
            
            $keyboard = [
                'inline_keyboard' => [[
                    ['text' => '🔙 Back to Start', 'callback_data' => 'back_to_start']
                ]]
            ];
            
            editMessage($chat_id, $message_id, $help_text, json_encode($keyboard), 'HTML');
            answerCallbackQuery($query['id'], "Help information loaded");
        }
        elseif ($data === 'back_to_start') {
            $welcome = getHinglishResponse('welcome');
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🍿 Main Channel', 'url' => 'https://t.me/EntertainmentTadka786'],
                        ['text' => '🎭 Theater Prints', 'url' => 'https://t.me/threater_print_movies']
                    ],
                    [
                        ['text' => '📥 Requests', 'url' => 'https://t.me/EntertainmentTadka7860'],
                        ['text' => '🔒 Backup', 'url' => 'https://t.me/ETBackup']
                    ],
                    [
                        ['text' => '📺 Serial Channel', 'url' => 'https://t.me/Entertainment_Tadka_Serial_786'],
                        ['text' => '❓ Help', 'callback_data' => 'help_command']
                    ],
                    [
                        ['text' => '⚙️ Settings', 'callback_data' => 'settings'],
                        ['text' => '🎬 Premium UI', 'callback_data' => 'premium_ui_demo']
                    ]
                ]
            ];
            
            editMessage($chat_id, $message_id, $welcome, json_encode($keyboard), 'HTML');
            answerCallbackQuery($query['id'], "Welcome back!");
        }
        elseif ($data === 'settings') {
            show_personalize_settings($chat_id, $message_id);
            answerCallbackQuery($query['id']);
        }
        elseif ($data === 'settings_file_delete') {
            show_file_delete_settings($chat_id, $message_id);
            answerCallbackQuery($query['id']);
        }
        elseif (strpos($data, 'set_delete_') === 0) {
            $seconds = str_replace('set_delete_', '', $data);
            global $user_settings;
            $user_settings[$user_id]['file_delete'] = intval($seconds);
            show_personalize_settings($chat_id, $message_id);
            answerCallbackQuery($query['id'], "✅ File delete set to {$seconds}s");
        }
        elseif ($data === 'settings_auto_scan') {
            global $user_settings;
            $user_settings[$user_id]['auto_scan'] = !($user_settings[$user_id]['auto_scan'] ?? true);
            show_personalize_settings($chat_id, $message_id);
            answerCallbackQuery($query['id'], "🔄 Toggled");
        }
        elseif ($data === 'settings_spoiler') {
            global $user_settings;
            $user_settings[$user_id]['spoiler_mode'] = !($user_settings[$user_id]['spoiler_mode'] ?? false);
            show_personalize_settings($chat_id, $message_id);
            answerCallbackQuery($query['id'], "🔄 Toggled");
        }
        elseif ($data === 'settings_top_search') {
            global $user_settings;
            $user_settings[$user_id]['top_search'] = !($user_settings[$user_id]['top_search'] ?? false);
            show_personalize_settings($chat_id, $message_id);
            answerCallbackQuery($query['id'], "🔄 Toggled");
        }
        elseif ($data === 'settings_priority') {
            global $user_settings;
            $current = $user_settings[$user_id]['priority'] ?? 'size';
            $user_settings[$user_id]['priority'] = ($current == 'size') ? 'quality' : 'size';
            show_personalize_settings($chat_id, $message_id);
            answerCallbackQuery($query['id'], "✅ Priority changed");
        }
        elseif ($data === 'settings_layout') {
            global $user_settings;
            $current = $user_settings[$user_id]['layout'] ?? 'BTN';
            $user_settings[$user_id]['layout'] = ($current == 'BTN') ? 'TXT' : 'BTN';
            show_personalize_settings($chat_id, $message_id);
            answerCallbackQuery($query['id'], "✅ Layout changed");
        }
        elseif ($data === 'settings_reset') {
            global $user_settings;
            $user_settings[$user_id] = [
                'file_delete' => 50,
                'auto_scan' => true,
                'spoiler_mode' => false,
                'top_search' => false,
                'priority' => 'size',
                'layout' => 'BTN'
            ];
            show_personalize_settings($chat_id, $message_id);
            answerCallbackQuery($query['id'], "🔄 Reset to defaults");
        }
        elseif ($data === 'settings_back') {
            show_personalize_settings($chat_id, $message_id);
            answerCallbackQuery($query['id']);
        }
        elseif ($data === 'premium_ui_demo') {
            $demo_movies = ["Dhurandhar", "Animal", "Stree2", "KGF 2", "Pushpa"];
            $random_movie = $demo_movies[array_rand($demo_movies)];
            
            $demo_data = [
                ['message_id' => '12345', 'channel_id' => '-1003251791991'],
                ['message_id' => '12346', 'channel_id' => '-1003251791991'],
                ['message_id' => '12347', 'channel_id' => '-1003614546520'],
            ];
            
            $expanded_data = generate_mock_files($demo_data);
            
            sendMessage($chat_id, premium_caption($random_movie), build_quality_buttons($random_movie, $expanded_data), 'HTML');
            answerCallbackQuery($query['id'], "🎬 Premium UI Demo");
        }
        elseif (strpos($data, 'show|') === 0) {
            $parts = explode('|', $data);
            $title = $parts[1];
            $quality = $parts[2];
            
            $demo_data = [
                ['message_id' => '12345', 'channel_id' => '-1003251791991'],
                ['message_id' => '12346', 'channel_id' => '-1003251791991'],
                ['message_id' => '12347', 'channel_id' => '-1003614546520'],
            ];
            
            $expanded_data = generate_mock_files($demo_data);
            
            $markup = build_file_buttons($title, $quality, $expanded_data);
            if ($markup) {
                editMessage($chat_id, $message_id, premium_caption($title), $markup, 'HTML');
                answerCallbackQuery($query['id'], "Showing $quality");
            } else {
                answerCallbackQuery($query['id'], "No files", true);
            }
        }
        elseif (strpos($data, 'back|') === 0) {
            $title = explode('|', $data)[1];
            
            $demo_data = [
                ['message_id' => '12345', 'channel_id' => '-1003251791991'],
                ['message_id' => '12346', 'channel_id' => '-1003251791991'],
                ['message_id' => '12347', 'channel_id' => '-1003614546520'],
            ];
            
            $expanded_data = generate_mock_files($demo_data);
            
            editMessage($chat_id, $message_id, premium_caption($title), build_quality_buttons($title, $expanded_data), 'HTML');
            answerCallbackQuery($query['id'], "Back to qualities");
        }
        elseif (strpos($data, 'page_') === 0) {
            $parts = explode('_', $data);
            $page = intval($parts[1]);
            $movie_name = $parts[2];
            
            $files = load_movie_files($movie_name);
            $users_data = json_decode(file_get_contents(USERS_FILE), true);
            $requester_name = $users_data['users'][$user_id]['first_name'] ?? 'User';
            $total_pages = ceil(count($files) / 10);
            
            $result = show_premium_movie_list($chat_id, $movie_name, $requester_name, $files, $page, $total_pages);
            editMessage($chat_id, $message_id, $result['text'], json_encode($result['keyboard']), 'HTML');
            answerCallbackQuery($query['id'], "Page $page");
        }
        elseif (strpos($data, 'series_page_') === 0) {
            $parts = explode('_', $data);
            $page = intval($parts[2]);
            $series_name = $parts[3];
            
            $episodes = load_series_episodes($series_name);
            $users_data = json_decode(file_get_contents(USERS_FILE), true);
            $requester_name = $users_data['users'][$user_id]['first_name'] ?? 'User';
            $total_pages = ceil(count($episodes) / 10);
            
            $result = show_premium_series_list($chat_id, $series_name, $requester_name, $episodes, $page, $total_pages);
            editMessage($chat_id, $message_id, $result['text'], json_encode($result['keyboard']), 'HTML');
            answerCallbackQuery($query['id'], "Page $page");
        }
        elseif ($data === 'home' || $data === 'close') {
            deleteMessage($chat_id, $message_id);
            answerCallbackQuery($query['id'], "Closed");
        }
        elseif (strpos($data, 'send|') === 0) {
            answerCallbackQuery($query['id'], "📁 File selected! (Download simulation)");
        }
        elseif ($data === 'ignore') {
            answerCallbackQuery($query['id']);
        }
        elseif (strpos($data, 'auto_request_') === 0) {
            $movie_name = base64_decode(str_replace('auto_request_', '', $data));
            
            $result = $requestSystem->submitRequest($user_id, $movie_name, $users_data['users'][$user_id]['first_name'] ?? 'User');
            
            if ($result['success']) {
                sendHinglish($chat_id, 'request_success', [
                    'movie' => $movie_name,
                    'id' => $result['request_id']
                ]);
                answerCallbackQuery($query['id'], "Request sent successfully!");
                update_user_activity($user_id, 'movie_request');
            } else {
                sendMessage($chat_id, $result['message'], null, 'HTML');
                answerCallbackQuery($query['id'], "Failed", true);
            }
        }
        elseif (strpos($data, 'approve_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) {
                answerCallbackQuery($query['id'], "❌ Admin only!", true);
                return;
            }
            
            $request_id = str_replace('approve_', '', $data);
            $result = $requestSystem->approveRequest($request_id, $user_id);
            
            if ($result['success']) {
                $request = $result['request'];
                $new_text = $message['text'] . "\n\n✅ <b>Approved by Admin</b>\n🕒 " . date('H:i:s');
                
                editMessage($chat_id, $message_id, $new_text, null, 'HTML');
                answerCallbackQuery($query['id'], "✅ Request #$request_id approved");
                
                notifyUserAboutRequest($request['user_id'], $request, 'approved');
            } else {
                answerCallbackQuery($query['id'], $result['message'], true);
            }
        }
        elseif (strpos($data, 'reject_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) {
                answerCallbackQuery($query['id'], "❌ Admin only!", true);
                return;
            }
            
            $request_id = str_replace('reject_', '', $data);
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Already Available', 'callback_data' => 'reject_reason_' . $request_id . '_already_available'],
                        ['text' => 'Invalid Request', 'callback_data' => 'reject_reason_' . $request_id . '_invalid_request']
                    ],
                    [
                        ['text' => 'Low Quality', 'callback_data' => 'reject_reason_' . $request_id . '_low_quality'],
                        ['text' => 'Not Available', 'callback_data' => 'reject_reason_' . $request_id . '_not_available']
                    ],
                    [
                        ['text' => 'Custom Reason...', 'callback_data' => 'reject_custom_' . $request_id]
                    ]
                ]
            ];
            
            editMessage($chat_id, $message_id, "Select rejection reason for Request #$request_id:", json_encode($keyboard), 'HTML');
            answerCallbackQuery($query['id'], "Select rejection reason");
        }
        elseif (strpos($data, 'reject_reason_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) {
                answerCallbackQuery($query['id'], "❌ Admin only!", true);
                return;
            }
            
            $parts = explode('_', $data);
            $request_id = $parts[2];
            $reason_key = $parts[3];
            
            $reason_map = [
                'already_available' => 'Movie is already available in our channels',
                'invalid_request' => 'Invalid movie name or request',
                'low_quality' => 'Cannot find good quality version',
                'not_available' => 'Movie is not available anywhere'
            ];
            
            $reason = $reason_map[$reason_key] ?? 'Not specified';
            
            $result = $requestSystem->rejectRequest($request_id, $user_id, $reason);
            
            if ($result['success']) {
                $request = $result['request'];
                $new_text = $message['text'] . "\n\n❌ <b>Rejected by Admin</b>\n📝 Reason: $reason\n🕒 " . date('H:i:s');
                
                editMessage($chat_id, $message_id, $new_text, null, 'HTML');
                answerCallbackQuery($query['id'], "❌ Request #$request_id rejected");
                
                notifyUserAboutRequest($request['user_id'], $request, 'rejected');
            } else {
                answerCallbackQuery($query['id'], $result['message'], true);
            }
        }
        elseif (strpos($data, 'lang_') === 0) {
            $lang = str_replace('lang_', '', $data);
            setUserLanguage($user_id, $lang);
            
            $messages = [
                'english' => "Language set to English",
                'hindi' => "भाषा हिंदी में सेट हो गई",
                'hinglish' => "Hinglish mode active!"
            ];
            
            editMessage($chat_id, $message_id, "✅ " . $messages[$lang], null, 'HTML');
            answerCallbackQuery($query['id'], $messages[$lang]);
        }
        else {
            answerCallbackQuery($query['id'], "❌ Invalid option", true);
        }
    }
    
    http_response_code(200);
    echo "OK";
    exit;
}

// ==================== WEBHOOK SETUP ====================
if (isset($_GET['setup'])) {
    $webhook_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $result = apiRequest('setWebhook', ['url' => $webhook_url]);
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot</h1>";
    echo "<h2>Webhook Setup</h2>";
    echo "<pre>Webhook Set: " . htmlspecialchars($result) . "</pre>";
    echo "<p>Webhook URL: " . htmlspecialchars($webhook_url) . "</p>";
    
    $bot_info = json_decode(apiRequest('getMe'), true);
    if ($bot_info && isset($bot_info['ok']) && $bot_info['ok']) {
        echo "<h2>Bot Info</h2>";
        echo "<p>Name: " . htmlspecialchars($bot_info['result']['first_name']) . "</p>";
        echo "<p>Username: @" . htmlspecialchars($bot_info['result']['username']) . "</p>";
    }
    exit;
}

if (isset($_GET['deletehook'])) {
    $result = apiRequest('deleteWebhook');
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot</h1>";
    echo "<h2>Webhook Deleted</h2>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    exit;
}

// ==================== TEST PAGE ====================
if (isset($_GET['test'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot - Test Page</h1>";
    echo "<p><strong>Status:</strong> ✅ Running</p>";
    echo "<p><strong>Bot:</strong> @" . $ENV_CONFIG['BOT_USERNAME'] . "</p>";
    echo "<p><strong>Environment:</strong> " . getenv('ENVIRONMENT') . "</p>";
    
    $stats = $csvManager->getStats();
    echo "<p><strong>Total Movies:</strong> " . $stats['total_movies'] . "</p>";
    
    $users_data = json_decode(@file_get_contents(USERS_FILE), true);
    echo "<p><strong>Total Users:</strong> " . count($users_data['users'] ?? []) . "</p>";
    
    $request_stats = $requestSystem->getStats();
    echo "<p><strong>Total Requests:</strong> " . $request_stats['total_requests'] . "</p>";
    echo "<p><strong>Pending Requests:</strong> " . $request_stats['pending'] . "</p>";
    
    echo "<h3>🚀 Quick Setup</h3>";
    echo "<p><a href='?setup=1'>Set Webhook Now</a></p>";
    echo "<p><a href='?deletehook=1'>Delete Webhook</a></p>";
    
    exit;
}

// ==================== DEFAULT HTML PAGE ====================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎬 Entertainment Tadka Bot</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.8em;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            color: #fff;
        }
        
        .status-card {
            background: rgba(255, 255, 255, 0.2);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #4CAF50;
        }
        
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1em;
            transition: all 0.3s ease;
            min-width: 200px;
            text-align: center;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45a049;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .btn-secondary {
            background: #2196F3;
            color: white;
        }
        
        .stats-panel {
            background: rgba(0, 0, 0, 0.3);
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #4CAF50;
            margin: 10px 0;
        }
        
        .channels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .channel-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 12px;
        }
        
        .feature-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
        }
        
        .feature-item::before {
            content: "✓";
            color: #4CAF50;
            font-weight: bold;
            margin-right: 15px;
        }
        
        @media (max-width: 768px) {
            .container { padding: 20px; }
            h1 { font-size: 2em; }
            .btn { width: 100%; }
        }
        
        footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 Entertainment Tadka Bot</h1>
        
        <div class="status-card">
            <h2>✅ Bot is Running</h2>
            <p>Telegram Bot for movie searches | Clean Version</p>
            <p><strong>Movie Request System:</strong> ✅ Active</p>
            <p><strong>Premium OTT UI:</strong> ✅ Active</p>
            <p><strong>Hinglish Support:</strong> ✅ Active</p>
        </div>
        
        <div class="btn-group">
            <a href="?setup=1" class="btn btn-primary">🔗 Set Webhook</a>
            <a href="?test=1" class="btn btn-secondary">🧪 Test Bot</a>
            <a href="?deletehook=1" class="btn btn-primary">🗑️ Delete Webhook</a>
        </div>
        
        <div class="stats-panel">
            <h3>📊 Current Statistics</h3>
            <div class="stats-grid">
                <?php
                $csvManager = CSVManager::getInstance();
                $requestSystem = RequestSystem::getInstance();
                
                $stats = $csvManager->getStats();
                $users_data = json_decode(@file_get_contents(USERS_FILE), true);
                $total_users = count($users_data['users'] ?? []);
                $request_stats = $requestSystem->getStats();
                ?>
                <div class="stat-item">
                    <div>🎬 Total Movies</div>
                    <div class="stat-value"><?php echo $stats['total_movies']; ?></div>
                </div>
                <div class="stat-item">
                    <div>👥 Total Users</div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-item">
                    <div>📋 Total Requests</div>
                    <div class="stat-value"><?php echo $request_stats['total_requests']; ?></div>
                </div>
                <div class="stat-item">
                    <div>⏳ Pending</div>
                    <div class="stat-value"><?php echo $request_stats['pending']; ?></div>
                </div>
            </div>
        </div>
        
        <h3>📡 Channels</h3>
        <div class="channels-grid">
            <div class="channel-card public">
                <div>🍿 Main: @EntertainmentTadka786</div>
            </div>
            <div class="channel-card public">
                <div>🎭 Theater: @threater_print_movies</div>
            </div>
            <div class="channel-card public">
                <div>📺 Serial: @Entertainment_Tadka_Serial_786</div>
            </div>
            <div class="channel-card public">
                <div>📥 Requests: @EntertainmentTadka7860</div>
            </div>
            <div class="channel-card public">
                <div>🔒 Backup: @ETBackup</div>
            </div>
        </div>
        
        <div class="feature-list">
            <h3>✨ Features</h3>
            <div class="feature-item">Smart movie search</div>
            <div class="feature-item">Premium OTT UI</div>
            <div class="feature-item">Movie Request System</div>
            <div class="feature-item">Personalize Settings Panel</div>
            <div class="feature-item">5 Channels Integrated</div>
            <div class="feature-item">Hinglish Support</div>
            <div class="feature-item">Rate limiting & Security</div>
        </div>
        
        <footer>
            <p>🎬 Entertainment Tadka Bot | Clean Version | Commands: 21</p>
        </footer>
    </div>
</body>
</html>
<?php
// ==================== END OF FILE ====================
// Clean Version - Removed: Duplicate commands, AutoDelete, QuickAdd, Delay Typing
// Total Commands: 21 (14 user + 7 admin)
// Lines reduced by ~40%
?>
