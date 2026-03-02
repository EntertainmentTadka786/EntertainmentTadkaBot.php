<?php
// ==============================
// ENTERTAINMENT TADKA BOT - RENDER.COM WEBHOOK VERSION - FIXED
// ==============================
// Optimized for Render.com Docker Web Service
// Fixed: Parse error on line 2146 (setWebhook constant issue)
// Fixed: 301 redirect issue
// Webhook Mode - No Polling
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
        'line' => $exception->getLine()
    ]);
});

// ==================== ENVIRONMENT VARIABLES ====================
$ENV_CONFIG = [
    'BOT_TOKEN' => getenv('BOT_TOKEN') ?: '8315381064:AAGk0FGVGmB8j5SjpBvW3rD3_kQHe_hyOWU',
    'BOT_USERNAME' => getenv('BOT_USERNAME') ?: 'EntertainmentTadkaBot',
    'ADMIN_IDS' => array_map('intval', explode(',', getenv('ADMIN_IDS') ?: '1080317415')),
    
    'PUBLIC_CHANNELS' => [
        ['id' => getenv('PUBLIC_CHANNEL_1_ID') ?: '-1003181705395', 'username' => '@EntertainmentTadka786'],
        ['id' => getenv('PUBLIC_CHANNEL_2_ID') ?: '-1002831605258', 'username' => '@threater_print_movies'],
        ['id' => getenv('PUBLIC_CHANNEL_3_ID') ?: '-1002964109368', 'username' => '@ETBackup']
    ],
    
    'PRIVATE_CHANNELS' => [
        ['id' => getenv('PRIVATE_CHANNEL_1_ID') ?: '-1003251791991', 'username' => ''],
        ['id' => getenv('PRIVATE_CHANNEL_2_ID') ?: '-1002337293281', 'username' => ''],
        ['id' => getenv('PRIVATE_CHANNEL_3_ID') ?: '-1003614546520', 'username' => '']
    ],
    
    'CSV_FILE' => 'movies.csv',
    'USERS_FILE' => 'users.json',
    'STATS_FILE' => 'bot_stats.json',
    'REQUESTS_FILE' => 'requests.json',
    'CACHE_DIR' => 'cache/',
    
    'CACHE_EXPIRY' => 300,
    'ITEMS_PER_PAGE' => 5,
    'MAX_REQUESTS_PER_DAY' => 3,
    'REQUEST_SYSTEM_ENABLED' => true,
    'MAINTENANCE_MODE' => (getenv('MAINTENANCE_MODE') === 'true') ? true : false,
    'RATE_LIMIT_REQUESTS' => 30,
    'RATE_LIMIT_WINDOW' => 60,
    'WEBHOOK_URL' => getenv('WEBHOOK_URL') ?: '' // FIXED: empty string instead of setWebhook
];

if (empty($ENV_CONFIG['BOT_TOKEN'])) {
    http_response_code(500);
    die("❌ Bot Token not configured.");
}

define('BOT_TOKEN', $ENV_CONFIG['BOT_TOKEN']);
define('ADMIN_IDS', $ENV_CONFIG['ADMIN_IDS']);
define('CSV_FILE', $ENV_CONFIG['CSV_FILE']);
define('USERS_FILE', $ENV_CONFIG['USERS_FILE']);
define('STATS_FILE', $ENV_CONFIG['STATS_FILE']);
define('REQUESTS_FILE', $ENV_CONFIG['REQUESTS_FILE']);
define('CACHE_DIR', $ENV_CONFIG['CACHE_DIR']);
define('CACHE_EXPIRY', $ENV_CONFIG['CACHE_EXPIRY']);
define('ITEMS_PER_PAGE', $ENV_CONFIG['ITEMS_PER_PAGE']);
define('MAX_REQUESTS_PER_DAY', $ENV_CONFIG['MAX_REQUESTS_PER_DAY']);
define('REQUEST_SYSTEM_ENABLED', $ENV_CONFIG['REQUEST_SYSTEM_ENABLED']);
define('MAINTENANCE_MODE', $ENV_CONFIG['MAINTENANCE_MODE']);
define('RATE_LIMIT_REQUESTS', $ENV_CONFIG['RATE_LIMIT_REQUESTS']);
define('RATE_LIMIT_WINDOW', $ENV_CONFIG['RATE_LIMIT_WINDOW']);
define('WEBHOOK_URL', $ENV_CONFIG['WEBHOOK_URL']);

define('MAIN_CHANNEL', '@EntertainmentTadka786');
define('THEATER_CHANNEL', '@threater_print_movies');
define('REQUEST_CHANNEL', '@EntertainmentTadka7860');
define('BACKUP_CHANNEL_USERNAME', '@ETBackup');

// ==================== GLOBAL VARIABLES ====================
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

// ==================== RECOMMENDATIONS DATA ====================
$recommendations = [
    'kgf' => ['Salaar', 'Pushpa', 'Vikram', 'Kantara', 'Ugramm'],
    'kgf chapter 1' => ['Salaar', 'Ugramm', 'KGF Chapter 2'],
    'kgf chapter 2' => ['Salaar', 'Pushpa', 'KGF Chapter 3'],
    'pushpa' => ['KGF', 'RRR', 'Baahubali', 'Salaar', 'Jailer'],
    'pushpa 2' => ['KGF 3', 'Salaar 2', 'Devara'],
    'salaar' => ['KGF', 'Pushpa', 'Baahubali', 'Kantara'],
    'rrr' => ['Baahubali', 'Magadheera', 'Eega', 'Pushpa'],
    'baahubali' => ['RRR', 'Magadheera', 'Saaho', 'Salaar'],
    'avengers' => ['Justice League', 'The Batman', 'Spider-Man', 'Deadpool'],
    'avengers endgame' => ['Infinity War', 'Thor Love and Thunder', 'Doctor Strange'],
    'spider-man' => ['The Batman', 'Venom', 'Morbius'],
    'batman' => ['Joker', 'The Dark Knight', 'Superman'],
    'joker' => ['Taxi Driver', 'Fight Club', 'The Batman'],
    'animal' => ['Kabir Singh', 'Arjun Reddy', 'KGF'],
    'kantara' => ['KGF', 'Ugramm', 'Bell Bottom'],
    'stree' => ['Bhediya', 'Roohi', 'Munjya'],
    'stree 2' => ['Stree', 'Bhediya', 'Munjya'],
    'dunki' => ['PK', '3 Idiots', 'Swades'],
    'jawan' => ['Pathaan', 'War', 'Tiger'],
    'pathaan' => ['Jawan', 'War', 'Tiger'],
    'tiger' => ['Pathaan', 'War', 'Ek Tha Tiger'],
    'war' => ['Pathaan', 'Tiger', 'Fighter']
];

// ==================== MOBILE DETECTION ====================
function is_mobile() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $mobile_agents = ['Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'Windows Phone'];
    
    foreach($mobile_agents as $agent) {
        if(stripos($user_agent, $agent) !== false) return true;
    }
    return false;
}

function optimize_for_mobile($text) {
    if(!is_mobile()) return $text;
    if(strlen($text) > 1000) {
        return substr($text, 0, 1000) . "...\n\n[Message truncated for mobile]";
    }
    return $text;
}

function build_compact_keyboard($buttons) {
    if(!is_mobile()) return $buttons;
    
    foreach($buttons['inline_keyboard'] as &$row) {
        foreach($row as &$btn) {
            if(strlen($btn['text']) > 20) {
                $btn['text'] = substr($btn['text'], 0, 18) . '…';
            }
        }
    }
    return $buttons;
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

// ==================== SPELL CHECK FUNCTION ====================
function spell_check($query) {
    $common_mistakes = [
        'kgf' => 'KGF',
        'kgf2' => 'KGF Chapter 2',
        'kgf 2' => 'KGF Chapter 2',
        'kgf chapter2' => 'KGF Chapter 2',
        'pushpa' => 'Pushpa',
        'pushpa2' => 'Pushpa 2 The Rule',
        'pushpa 2' => 'Pushpa 2 The Rule',
        'avengers' => 'Avengers',
        'avengers endgame' => 'Avengers Endgame',
        'endgame' => 'Avengers Endgame',
        'spiderman' => 'Spider-Man',
        'spider man' => 'Spider-Man',
        'batman' => 'The Batman',
        'joker' => 'Joker',
        'stree' => 'Stree',
        'stree2' => 'Stree 2',
        'stree 2' => 'Stree 2',
        'animal' => 'Animal',
        'dunki' => 'Dunki',
        'jawan' => 'Jawan',
        'pathaan' => 'Pathaan',
        'tiger' => 'Tiger',
        'war' => 'War',
        'rrr' => 'RRR',
        'baahubali' => 'Baahubali',
        'bahubali' => 'Baahubali',
        'kalki' => 'Kalki 2898 AD',
        'devara' => 'Devara',
        'salaar' => 'Salaar',
        'kgf chapter 1' => 'KGF Chapter 1',
        'kgf chapter 2' => 'KGF Chapter 2',
        'kgf chapter 3' => 'KGF Chapter 3',
        'kgf3' => 'KGF Chapter 3',
        'kgf 3' => 'KGF Chapter 3'
    ];
    
    $query_lower = strtolower(trim($query));
    
    if(isset($common_mistakes[$query_lower])) {
        return $common_mistakes[$query_lower];
    }
    
    foreach($common_mistakes as $wrong => $correct) {
        if(strpos($query_lower, $wrong) !== false) {
            return $correct;
        }
    }
    
    return $query;
}

// ==================== LANGUAGE DETECTION ====================
function detectUserLanguage($text) {
    $hindi_pattern = '/[\x{0900}-\x{097F}]/u';
    $hindi_words = ['है', 'हूं', 'का', 'की', 'के', 'में', 'से', 'को', 'पर', 'और', 'या', 'यह', 'वह', 'मैं', 'तुम', 'आप', 'क्या', 'क्यों', 'कैसे', 'कब', 'कहां', 'नहीं', 'बहुत', 'अच्छा', 'बुरा', 'था', 'थी', 'थे', 'गया', 'गई', 'गए', 'फिल्म', 'मूवी', 'डाउनलोड', 'चाहिए', 'खोज'];
    
    $tamil_pattern = '/[\x{0B80}-\x{0BFF}]/u';
    $tamil_words = ['தமிழ்', 'படம்', 'மூவி', 'தேடல்', 'வேண்டும்', 'எப்படி', 'எங்கே'];
    
    $telugu_pattern = '/[\x{0C00}-\x{0C7F}]/u';
    $telugu_words = ['తెలుగు', 'సినిమా', 'మూవీ', 'వెతకండి', 'కావాలి', 'ఎలా', 'ఎక్కడ'];
    
    $malayalam_pattern = '/[\x{0D00}-\x{0D7F}]/u';
    $malayalam_words = ['മലയാളം', 'സിനിമ', 'മൂവി', 'തിരയുക', 'വേണം', 'എങ്ങനെ', 'എവിടെ'];
    
    $kannada_pattern = '/[\x{0C80}-\x{0CFF}]/u';
    $kannada_words = ['ಕನ್ನಡ', 'ಚಿತ್ರ', 'ಮೂವಿ', 'ಹುಡುಕಿ', 'ಬೇಕು', 'ಹೇಗೆ', 'ಎಲ್ಲಿ'];
    
    if(preg_match($hindi_pattern, $text)) return 'hindi';
    if(preg_match($tamil_pattern, $text)) return 'tamil';
    if(preg_match($telugu_pattern, $text)) return 'telugu';
    if(preg_match($malayalam_pattern, $text)) return 'malayalam';
    if(preg_match($kannada_pattern, $text)) return 'kannada';
    
    $words = explode(' ', strtolower($text));
    $scores = ['hindi' => 0, 'tamil' => 0, 'telugu' => 0, 'malayalam' => 0, 'kannada' => 0, 'hinglish' => 0, 'english' => 0];
    
    foreach($words as $word) {
        if(in_array($word, $hindi_words)) $scores['hindi'] += 2;
        if(in_array($word, $tamil_words)) $scores['tamil'] += 2;
        if(in_array($word, $telugu_words)) $scores['telugu'] += 2;
        if(in_array($word, $malayalam_words)) $scores['malayalam'] += 2;
        if(in_array($word, $kannada_words)) $scores['kannada'] += 2;
        
        $hinglish_words = ['hai', 'hain', 'ka', 'ki', 'ke', 'mein', 'se', 'ko', 'par', 'aur', 'kya', 'kyun', 'kaise', 'kab', 'kahan', 'nahi', 'bahut', 'acha', 'bura', 'tha', 'the', 'gaya', 'gayi', 'bole', 'bolo', 'kar', 'karo', 'de', 'do', 'le', 'lo'];
        if(in_array($word, $hinglish_words)) $scores['hinglish'] += 1;
        
        $english_words = ['movie', 'film', 'download', 'watch', 'print', 'search', 'find', 'looking', 'want', 'need', 'the', 'and', 'for', 'with', 'from'];
        if(in_array($word, $english_words)) $scores['english'] += 1;
    }
    
    arsort($scores);
    return key($scores);
}

function get_language_response($lang, $key) {
    $responses = [
        'hindi' => [
            'searching' => "🔍 खोज रहा हूँ... थोड़ा इंतज़ार करो",
            'found' => "✅ मिल गई! भेज रहा हूँ...",
            'not_found' => "😔 ये मूवी अभी उपलब्ध नहीं है!"
        ],
        'tamil' => [
            'searching' => "🔍 தேடுகிறேன்... சற்று பொறுங்கள்",
            'found' => "✅ கிடைத்துவிட்டது! அனுப்புகிறேன்...",
            'not_found' => "😔 இந்த படம் இன்னும் கிடைக்கவில்லை!"
        ],
        'telugu' => [
            'searching' => "🔍 వెతుకుతున్నాను... కాస్త ఆగండి",
            'found' => "✅ దొరికింది! పంపిస్తున్నాను...",
            'not_found' => "😔 ఈ సినిమా ఇంకా అందుబాటులో లేదు!"
        ],
        'malayalam' => [
            'searching' => "🔍 തിരയുന്നു... കുറച്ചു നിൽക്കൂ",
            'found' => "✅ കിട്ടി! അയക്കുന്നു...",
            'not_found' => "😔 ഈ സിനിമ ഇതുവരെ ലഭ്യമല്ല!"
        ],
        'kannada' => [
            'searching' => "🔍 ಹುಡುಕುತ್ತಿದ್ದೇನೆ... ಸ್ವಲ್ಪ ಕಾಯಿರಿ",
            'found' => "✅ ಸಿಕ್ಕಿತು! ಕಳುಹಿಸುತ್ತಿದ್ದೇನೆ...",
            'not_found' => "😔 ಈ ಚಿತ್ರ ಇನ್ನೂ ಲಭ್ಯವಿಲ್ಲ!"
        ],
        'hinglish' => [
            'searching' => "🔍 Dhoondh raha hoon... Zara wait karo",
            'found' => "✅ Mil gayi! Bhej raha hoon...",
            'not_found' => "😔 Yeh movie abhi available nahi hai!"
        ],
        'english' => [
            'searching' => "🔍 Searching... Please wait",
            'found' => "✅ Found it! Sending...",
            'not_found' => "😔 This movie isn't available yet!"
        ]
    ];
    
    return $responses[$lang][$key] ?? $responses['english'][$key];
}

// ==================== HINGLISH RESPONSES ====================
function getHinglishResponse($key, $vars = []) {
    $responses = [
        'welcome' => "🎬 <b>Entertainment Tadka mein aapka swagat hai!</b>\n\n" .
                     "📢 <b>Bot kaise use karein:</b>\n" .
                     "• Bus movie ka naam likho\n" .
                     "• English ya Hindi dono mein likh sakte ho\n" .
                     "• 'theater' add karo theater print ke liye\n\n" .
                     "🔍 <b>Examples:</b>\n" .
                     "• Mandala Murders 2025\n" .
                     "• KGF\n" .
                     "• Pushpa\n\n" .
                     "📢 <b>Hamare Channels:</b>\n" .
                     "🍿 Main: @EntertainmentTadka786\n" .
                     "🎭 Theater: @threater_print_movies\n" .
                     "📥 Requests: @EntertainmentTadka7860\n\n" .
                     "🎬 <b>Movie Request System:</b>\n" .
                     "• /request MovieName se request karo\n" .
                     "• Roz sirf " . MAX_REQUESTS_PER_DAY . " requests\n\n" .
                     "💡 <b>Tip:</b> /help se saari commands dekho",
        
        'help' => "🤖 <b>Entertainment Tadka Bot - Madad</b>\n\n" .
                  "📢 <b>Commands:</b>\n" .
                  "/start - Welcome\n" .
                  "/help - Yeh help\n" .
                  "/settings - Personalize settings\n" .
                  "/premium - Premium UI demo\n" .
                  "/search [name] - Movie search\n" .
                  "/request [name] - Request movie\n" .
                  "/myrequests - Apni requests\n" .
                  "/requestlimit - Limit check\n" .
                  "/totalupload - Saari movies\n" .
                  "/checkdate - Upload stats\n" .
                  "/testcsv - Database test\n" .
                  "/checkcsv - CSV data\n" .
                  "/language - Bhasha badlo\n" .
                  "/channel - Join channels\n" .
                  "/remind - Set reminder\n" .
                  "/myreminders - Your reminders\n" .
                  "/calendar - Release calendar\n" .
                  "/recommend - Similar movies\n\n" .
                  "👑 <b>Admin Commands:</b>\n" .
                  "/maintenance - Maintenance mode\n" .
                  "/cleanup - System cleanup\n" .
                  "/sendalert - Send alert\n" .
                  "/pendingrequests - Pending requests",
        
        'search_found' => "🔍 <b>{count} movies mil gaye '{query}' ke liye:</b>\n\n{results}",
        'search_select' => "🚀 <b>Movie select karo:</b>",
        'search_not_found' => "😔 <b>Yeh movie abhi available nahi hai!</b>",
        'request_success' => "✅ <b>Request submit ho gayi!</b>\n\n🎬 Movie: {movie}\n📝 ID: #{id}\n🕒 Status: Pending",
        'request_duplicate' => "⚠️ <b>Yeh movie aap already request kar chuke ho!</b>",
        'request_limit' => "❌ <b>Daily limit reached! Kal try karo.</b>",
        'myrequests_empty' => "📭 <b>Aapne abhi tak koi request nahi ki hai.</b>",
        'stats' => "📊 <b>Bot Statistics</b>\n\n🎬 Total Movies: {movies}\n👥 Total Users: {users}\n🔍 Total Searches: {searches}",
        'error' => "❌ <b>Error:</b> {message}",
        'maintenance' => "🛠️ <b>Bot Under Maintenance</b>\n\nWill be back soon!",
        'language_choose' => "🌐 <b>Choose your language / अपनी भाषा चुनें:</b>"
    ];
    
    $response = isset($responses[$key]) ? $responses[$key] : $key;
    
    foreach ($vars as $var => $value) {
        $response = str_replace('{' . $var . '}', $value, $response);
    }
    
    return $response;
}

// ==================== TELEGRAM API FUNCTIONS ====================
function apiRequest($method, $params = array(), $is_multipart = false) {
    if (!RateLimiter::check('telegram_api', RATE_LIMIT_REQUESTS, RATE_LIMIT_WINDOW)) {
        usleep(100000);
    }
    
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    
    if ($is_multipart) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode != 200) {
            log_error("Telegram API error: HTTP $httpCode", 'API_ERROR', ['method' => $method]);
        }
        return $res;
    } else {
        $options = [
            'http' => [
                'method' => 'POST',
                'content' => http_build_query($params),
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'timeout' => 30,
                'follow_location' => false // Don't follow redirects
            ]
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            log_error("Telegram API request failed", 'API_ERROR', ['method' => $method]);
        }
        return $result;
    }
}

function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = null) {
    $text = optimize_for_mobile($text);
    if($reply_markup) {
        $reply_markup = json_encode(build_compact_keyboard(json_decode($reply_markup, true)));
    }
    
    $data = ['chat_id' => $chat_id, 'text' => $text];
    if ($reply_markup) $data['reply_markup'] = $reply_markup;
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    
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
    if ($text) $data['text'] = $text;
    return apiRequest('answerCallbackQuery', $data);
}

function forwardMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('forwardMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => intval($message_id)
    ]);
}

function copyMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('copyMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => intval($message_id)
    ]);
}

function sendChatAction($chat_id, $action = 'typing') {
    return apiRequest('sendChatAction', ['chat_id' => $chat_id, 'action' => $action]);
}

// ==================== CHANNEL FUNCTIONS ====================
function get_channel_username_link($channel_type) {
    switch ($channel_type) {
        case 'main':
        case 'public':
            return "https://t.me/" . ltrim(MAIN_CHANNEL, '@');
        case 'theater':
            return "https://t.me/" . ltrim(THEATER_CHANNEL, '@');
        default:
            return "https://t.me/EntertainmentTadka786";
    }
}

function get_direct_channel_link($message_id, $channel_id) {
    if (empty($channel_id)) return "Channel ID not available";
    $channel_id_clean = str_replace('-100', '', $channel_id);
    return "https://t.me/c/" . $channel_id_clean . "/" . $message_id;
}

// ==================== CSV MANAGER CLASS ====================
class CSVManager {
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
        if (!file_exists(CACHE_DIR)) {
            @mkdir(CACHE_DIR, 0777, true);
        }
        
        if (!file_exists(CSV_FILE)) {
            $header = "movie_name,message_id,channel_id,quality,size,language,channel_type,date\n";
            @file_put_contents(CSV_FILE, $header);
            @chmod(CSV_FILE, 0666);
        }
        
        if (!file_exists(USERS_FILE)) {
            $users_data = ['users' => [], 'total_requests' => 0];
            @file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
        }
    }
    
    public function readCSV() {
        $data = [];
        
        if (!file_exists(CSV_FILE)) {
            return $data;
        }
        
        $handle = fopen(CSV_FILE, 'r');
        if (!$handle) return $data;
        
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return $data;
        }
        
        while (($row = fgetcsv($handle)) !== FALSE) {
            if (count($row) >= 3 && !empty(trim($row[0]))) {
                $data[] = [
                    'movie_name' => trim($row[0]),
                    'message_id' => isset($row[1]) ? intval(trim($row[1])) : 0,
                    'channel_id' => isset($row[2]) ? trim($row[2]) : '',
                    'quality' => isset($row[3]) ? trim($row[3]) : 'Unknown',
                    'size' => isset($row[4]) ? trim($row[4]) : 'Unknown',
                    'language' => isset($row[5]) ? trim($row[5]) : 'Hindi',
                    'channel_type' => isset($row[6]) ? trim($row[6]) : 'main',
                    'date' => isset($row[7]) ? trim($row[7]) : date('d-m-Y')
                ];
            }
        }
        fclose($handle);
        
        return $data;
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
                return $this->cache_data;
            }
        }
        
        $this->cache_data = $this->readCSV();
        $this->cache_timestamp = time();
        
        @file_put_contents($cache_file, serialize($this->cache_data));
        @chmod($cache_file, 0666);
        
        return $this->cache_data;
    }
    
    public function clearCache() {
        $this->cache_data = null;
        $this->cache_timestamp = 0;
        
        $cache_file = CACHE_DIR . 'movies_cache.ser';
        if (file_exists($cache_file)) {
            @unlink($cache_file);
        }
    }
    
    public function searchMovies($query) {
        $data = $this->getCachedData();
        $query_lower = strtolower(trim($query));
        $results = [];
        
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
                        'items' => [],
                        'qualities' => []
                    ];
                }
                $results[$movie_lower]['count']++;
                $results[$movie_lower]['items'][] = $item;
                if (!in_array($item['quality'], $results[$movie_lower]['qualities'])) {
                    $results[$movie_lower]['qualities'][] = $item['quality'];
                }
            }
        }
        
        uasort($results, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return array_slice($results, 0, 10);
    }
    
    public function getStats() {
        $data = $this->getCachedData();
        return [
            'total_movies' => count($data),
            'last_updated' => date('Y-m-d H:i:s', $this->cache_timestamp)
        ];
    }
    
    public function auto_cleanup() {
        $data = $this->readCSV();
        $unique = [];
        $unique_keys = [];
        $duplicates = 0;
        
        foreach($data as $row) {
            $key = strtolower($row['movie_name']) . '_' . $row['channel_id'] . '_' . $row['message_id'];
            if(!isset($unique_keys[$key])) {
                $unique_keys[$key] = true;
                $unique[] = $row;
            } else {
                $duplicates++;
            }
        }
        
        if($duplicates > 0) {
            $handle = fopen(CSV_FILE, 'w');
            fputcsv($handle, ['movie_name','message_id','channel_id','quality','size','language','channel_type','date']);
            foreach($unique as $row) {
                fputcsv($handle, [
                    $row['movie_name'],
                    $row['message_id'],
                    $row['channel_id'],
                    $row['quality'],
                    $row['size'],
                    $row['language'],
                    $row['channel_type'],
                    $row['date']
                ]);
            }
            fclose($handle);
            $this->clearCache();
            log_error("CSV Cleanup: Removed $duplicates duplicate entries");
        }
        
        return $duplicates;
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
        $movie_name = trim($movie_name);
        
        if (empty($movie_name) || strlen($movie_name) < 2) {
            return ['success' => false, 'message' => 'Please enter a valid movie name'];
        }
        
        $duplicate_check = $this->checkDuplicateRequest($user_id, $movie_name);
        if ($duplicate_check['is_duplicate']) {
            return ['success' => false, 'message' => "You already requested this recently"];
        }
        
        $flood_check = $this->checkFloodControl($user_id);
        if (!$flood_check['allowed']) {
            return ['success' => false, 'message' => "Daily limit of " . MAX_REQUESTS_PER_DAY . " reached"];
        }
        
        $data = $this->loadData();
        $request_id = ++$data['last_request_id'];
        
        $request = [
            'id' => $request_id,
            'user_id' => $user_id,
            'user_name' => $user_name,
            'movie_name' => $movie_name,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'is_notified' => false
        ];
        
        $data['requests'][$request_id] = $request;
        $data['system_stats']['total_requests']++;
        $data['system_stats']['pending']++;
        
        if (!isset($data['user_stats'][$user_id])) {
            $data['user_stats'][$user_id] = [
                'total_requests' => 0,
                'pending' => 0,
                'requests_today' => 0,
                'last_request_date' => date('Y-m-d')
            ];
        }
        
        $data['user_stats'][$user_id]['total_requests']++;
        $data['user_stats'][$user_id]['pending']++;
        
        if ($data['user_stats'][$user_id]['last_request_date'] != date('Y-m-d')) {
            $data['user_stats'][$user_id]['requests_today'] = 0;
            $data['user_stats'][$user_id]['last_request_date'] = date('Y-m-d');
        }
        $data['user_stats'][$user_id]['requests_today']++;
        
        $this->saveData($data);
        
        return [
            'success' => true,
            'request_id' => $request_id,
            'message' => "✅ Request submitted!"
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
                return ['is_duplicate' => true];
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
        
        return [
            'allowed' => $user_stats['requests_today'] < MAX_REQUESTS_PER_DAY,
            'remaining' => max(0, MAX_REQUESTS_PER_DAY - $user_stats['requests_today'])
        ];
    }
    
    public function approveRequest($request_id, $admin_id) {
        if (!in_array($admin_id, ADMIN_IDS)) {
            return ['success' => false, 'message' => 'Unauthorized'];
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
        
        $data['system_stats']['approved']++;
        $data['system_stats']['pending']--;
        
        $user_id = $request['user_id'];
        $data['user_stats'][$user_id]['approved'] = ($data['user_stats'][$user_id]['approved'] ?? 0) + 1;
        $data['user_stats'][$user_id]['pending']--;
        
        $this->saveData($data);
        
        return [
            'success' => true,
            'request' => $data['requests'][$request_id],
            'message' => "✅ Request #$request_id approved!"
        ];
    }
    
    public function rejectRequest($request_id, $admin_id, $reason = '') {
        if (!in_array($admin_id, ADMIN_IDS)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        $data = $this->loadData();
        
        if (!isset($data['requests'][$request_id])) {
            return ['success' => false, 'message' => 'Request not found'];
        }
        
        $request = $data['requests'][$request_id];
        
        if ($request['status'] != 'pending') {
            return ['success' => false, 'message' => "Request is already {$request['status']}"];
        }
        
        $data['requests'][$request_id]['status'] = 'rejected';
        $data['requests'][$request_id]['rejected_at'] = date('Y-m-d H:i:s');
        $data['requests'][$request_id]['rejected_by'] = $admin_id;
        $data['requests'][$request_id]['reason'] = $reason;
        
        $data['system_stats']['rejected']++;
        $data['system_stats']['pending']--;
        
        $user_id = $request['user_id'];
        $data['user_stats'][$user_id]['rejected'] = ($data['user_stats'][$user_id]['rejected'] ?? 0) + 1;
        $data['user_stats'][$user_id]['pending']--;
        
        $this->saveData($data);
        
        return [
            'success' => true,
            'request' => $data['requests'][$request_id],
            'message' => "❌ Request #$request_id rejected!"
        ];
    }
    
    public function getPendingRequests($limit = 10) {
        $data = $this->loadData();
        $pending = [];
        
        foreach ($data['requests'] as $request) {
            if ($request['status'] == 'pending') {
                $pending[] = $request;
            }
        }
        
        usort($pending, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });
        
        return array_slice($pending, 0, $limit);
    }
    
    public function getUserRequests($user_id) {
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
        
        return $user_requests;
    }
    
    public function getRequest($request_id) {
        $data = $this->loadData();
        return $data['requests'][$request_id] ?? null;
    }
    
    public function getStats() {
        $data = $this->loadData();
        return $data['system_stats'];
    }
    
    public function getUserStats($user_id) {
        $data = $this->loadData();
        return $data['user_stats'][$user_id] ?? [
            'total_requests' => 0,
            'pending' => 0,
            'requests_today' => 0
        ];
    }
    
    public function markAsNotified($request_id) {
        $data = $this->loadData();
        if (isset($data['requests'][$request_id])) {
            $data['requests'][$request_id]['is_notified'] = true;
            $this->saveData($data);
        }
    }
}

// ==================== MOVIE DELIVERY ====================
function deliver_item_to_chat($chat_id, $item) {
    $source_channel = $item['channel_id'] ?? '';
    $channel_type = $item['channel_type'] ?? 'main';
    
    sendChatAction($chat_id, 'typing');
    
    if (!empty($item['message_id']) && is_numeric($item['message_id']) && !empty($source_channel)) {
        if ($channel_type === 'public') {
            $result = json_decode(forwardMessage($chat_id, $source_channel, $item['message_id']), true);
        } else {
            $result = json_decode(copyMessage($chat_id, $source_channel, $item['message_id']), true);
        }
        
        if ($result && $result['ok']) {
            return true;
        }
    }
    
    // Fallback with copyable link
    $text = "🎬 <b>" . htmlspecialchars($item['movie_name'] ?? 'Unknown') . "</b>\n\n";
    
    if (!empty($item['message_id']) && is_numeric($item['message_id']) && !empty($source_channel)) {
        $link = get_direct_channel_link($item['message_id'], $source_channel);
        $text .= "🔗 <b>Click to open:</b> " . $link . "\n\n";
        $text .= "📋 <b>Copy this link:</b>\n<code>" . $link . "</code>\n\n";
    }
    
    $text .= "📢 Join channel: " . get_channel_username_link($channel_type);
    
    sendMessage($chat_id, $text, null, 'HTML');
    return false;
}

// ==================== LOADING ANIMATIONS ====================
function show_loading_animation($chat_id, $message_id, $steps = 5) {
    $animations = [
        '🔍 Searching',
        '🔍 Searching.',
        '🔍 Searching..',
        '🔍 Searching...',
        '🔍 Searching....'
    ];
    
    for($i = 0; $i < $steps; $i++) {
        editMessage($chat_id, $message_id, $animations[$i % count($animations)], null, 'HTML');
        usleep(300000);
    }
}

function show_progress_bar($chat_id, $message_id, $current, $total, $action = "Processing") {
    $percentage = round(($current / $total) * 100);
    $filled = round($percentage / 10);
    $empty = 10 - $filled;
    
    $bar = "⏳ " . str_repeat('█', $filled) . str_repeat('░', $empty) . " $percentage%";
    
    editMessage($chat_id, $message_id, "$bar\n\n$action...", null, 'HTML');
}

// ==================== BATCH DOWNLOAD ====================
function batch_download_with_progress($chat_id, $movies, $page_num) {
    $total = count($movies);
    if ($total === 0) return;
    
    $progress_msg = sendMessage($chat_id, "⏳ Initializing...", null, 'HTML');
    $progress_id = $progress_msg['result']['message_id'] ?? 0;
    
    $success = 0;
    $failed = 0;
    
    for ($i = 0; $i < $total; $i++) {
        $movie = $movies[$i];
        
        show_progress_bar($chat_id, $progress_id, $i + 1, $total, "Sending movies");
        
        try {
            $result = deliver_item_to_chat($chat_id, $movie);
            if ($result) $success++; else $failed++;
        } catch (Exception $e) {
            $failed++;
        }
        
        usleep(500000);
    }
    
    for($i = 0; $i < 3; $i++) {
        editMessage($chat_id, $progress_id, "✅ Complete" . str_repeat('.', $i + 1), null, 'HTML');
        usleep(200000);
    }
    
    editMessage($chat_id, $progress_id,
        "✅ <b>Batch Complete</b>\n\n" .
        "📄 Page: {$page_num}\n" .
        "🎬 Total: {$total} movies\n" .
        "✅ Success: {$success}\n" .
        "❌ Failed: {$failed}\n\n" .
        "📊 Success rate: " . round(($success / $total) * 100, 2) . "%", null, 'HTML');
}

// ==================== PAGINATION ====================
function paginate_movies(array $all, int $page): array {
    $total = count($all);
    if ($total === 0) {
        return ['total' => 0, 'total_pages' => 1, 'page' => 1, 'slice' => []];
    }
    
    $total_pages = (int)ceil($total / ITEMS_PER_PAGE);
    $page = max(1, min($page, $total_pages));
    $start = ($page - 1) * ITEMS_PER_PAGE;
    
    return [
        'total' => $total,
        'total_pages' => $total_pages,
        'page' => $page,
        'slice' => array_slice($all, $start, ITEMS_PER_PAGE)
    ];
}

function get_all_movies_list() {
    $csvManager = CSVManager::getInstance();
    return $csvManager->getCachedData();
}

// ==================== SMART SEARCH ====================
function smart_search($query) {
    global $movie_messages;
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
    
    $csvManager = CSVManager::getInstance();
    $search_results = $csvManager->searchMovies($query_lower);
    
    foreach ($search_results as $movie => $data) {
        $score = $data['score'];
        
        $results[$movie] = [
            'score' => $score,
            'count' => $data['count'],
            'qualities' => $data['qualities']
        ];
    }
    
    uasort($results, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    return $results;
}

// ==================== ADVANCED SEARCH ====================
function advanced_search($chat_id, $query, $user_id = null) {
    $corrected = spell_check($query);
    if($corrected != $query) {
        sendMessage($chat_id, "🤔 <b>Did you mean:</b> <i>$corrected</i>?\nSearching for that instead...", null, 'HTML');
        $query = $corrected;
    }
    
    sendChatAction($chat_id, 'typing');
    
    $q = strtolower(trim($query));
    
    if (strlen($q) < 2) {
        sendMessage($chat_id, "❌ Please enter at least 2 characters");
        return;
    }
    
    $found = smart_search($q);
    
    if (!empty($found)) {
        $lang = detectUserLanguage($query);
        sendMessage($chat_id, get_language_response($lang, 'found'), null, 'HTML');
        
        $msg = "🔍 Found " . count($found) . " movies for '$query':\n\n";
        $i = 1;
        foreach ($found as $movie => $data) {
            $quality_info = !empty($data['qualities']) ? implode('/', $data['qualities']) : 'Unknown';
            $msg .= "$i. $movie (" . $data['count'] . " versions, $quality_info)\n";
            $i++;
            if ($i > 10) break;
        }
        
        sendMessage($chat_id, $msg, null, 'HTML');
        
        $keyboard = ['inline_keyboard' => []];
        $top_movies = array_slice(array_keys($found), 0, 5);
        
        foreach ($top_movies as $movie) {
            $keyboard['inline_keyboard'][] = [[ 
                'text' => "🎬 " . ucwords($movie), 
                'callback_data' => 'movie_' . base64_encode($movie)
            ]];
        }
        
        $keyboard['inline_keyboard'][] = [[
            'text' => "📝 Request Different Movie", 
            'callback_data' => 'request_movie'
        ]];
        
        sendMessage($chat_id, "🚀 Top matches:", json_encode($keyboard));
    } else {
        $lang = detectUserLanguage($query);
        sendMessage($chat_id, get_language_response($lang, 'not_found'), null, 'HTML');
        
        $request_keyboard = [
            'inline_keyboard' => [[
                ['text' => '📝 Request This Movie', 'callback_data' => 'auto_request_' . base64_encode($query)]
            ]]
        ];
        
        sendMessage($chat_id, "💡 Click below to request:", json_encode($request_keyboard));
    }
}

// ==================== TOTAL UPLOAD CONTROLLER ====================
function totalupload_controller($chat_id, $page = 1, $session_id = null) {
    $all = get_all_movies_list();
    if (empty($all)) {
        sendMessage($chat_id, "📭 Koi movies nahi mili!");
        return;
    }
    
    if (!$session_id) {
        $session_id = uniqid('sess_', true);
    }
    
    $pg = paginate_movies($all, (int)$page);
    
    $title = "🎬 <b>Movie Browser</b>\n\n";
    $title .= "📊 Total: <b>{$pg['total']}</b>\n";
    $title .= "📄 Page: <b>{$pg['page']}/{$pg['total_pages']}</b>\n\n";
    $title .= "📋 <b>Movies:</b>\n\n";
    
    $i = ($pg['page'] - 1) * ITEMS_PER_PAGE + 1;
    foreach ($pg['slice'] as $movie) {
        $movie_name = htmlspecialchars($movie['movie_name'] ?? 'Unknown');
        $quality = $movie['quality'] ?? 'Unknown';
        $language = $movie['language'] ?? 'Hindi';
        
        $title .= "<b>{$i}.</b> {$movie_name}\n";
        $title .= "   🏷️ {$quality} | 🗣️ {$language}\n\n";
        $i++;
    }
    
    $kb = ['inline_keyboard' => []];
    $nav_row = [];
    
    if ($pg['page'] > 1) {
        $nav_row[] = ['text' => '◀️ Prev', 'callback_data' => 'pag_prev_' . $pg['page'] . '_' . $session_id];
    }
    $nav_row[] = ['text' => "Page {$pg['page']}/{$pg['total_pages']}", 'callback_data' => 'current'];
    if ($pg['page'] < $pg['total_pages']) {
        $nav_row[] = ['text' => 'Next ▶️', 'callback_data' => 'pag_next_' . $pg['page'] . '_' . $session_id];
    }
    $kb['inline_keyboard'][] = $nav_row;
    
    $action_row = [
        ['text' => '📥 Send Page', 'callback_data' => 'send_' . $pg['page'] . '_' . $session_id],
        ['text' => '❌ Close', 'callback_data' => 'close']
    ];
    $kb['inline_keyboard'][] = $action_row;
    
    sendMessage($chat_id, $title, json_encode($kb), 'HTML');
}

// ==================== PREMIUM UI FUNCTIONS ====================
function premium_caption($title) {
    global $BOT_NAME;
    
    return "
🎬 <b>" . strtoupper(htmlspecialchars($title)) . "</b>

━━━━━━━━━━━━━━━━
📥 <b>SELECT YOUR VERSION</b>
━━━━━━━━━━━━━━━━

👇 Click on quality to see files
<i>Powered by $BOT_NAME</i>";
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

function show_premium_movie_list($chat_id, $movie_name, $requester_name, $files, $page = 1, $total_pages = 1) {
    $text = "🎬 <b>" . strtoupper(htmlspecialchars($movie_name)) . "</b>\n";
    $text .= "👑 <i>admin</i>\n\n";
    $text .= "👤 <b>" . htmlspecialchars($requester_name) . "</b>\n";
    $text .= "📌 <b>REQUESTED BY</b>\n\n";
    
    $start = ($page - 1) * 10;
    $page_files = array_slice($files, $start, 10);
    
    foreach ($page_files as $index => $file) {
        $num = $start + $index + 1;
        $text .= "$num. [{$file['size']}] {$file['name']}\n";
    }
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🎬 QUALITY', 'callback_data' => 'filter_quality|' . $movie_name . '|all'],
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
    $nav_row[] = ['text' => "PAGE $page/$total_pages", 'callback_data' => 'current'];
    if ($page < $total_pages) {
        $nav_row[] = ['text' => 'NEXT ➡️', 'callback_data' => 'page_' . ($page + 1) . '_' . $movie_name];
    }
    $keyboard['inline_keyboard'][] = $nav_row;
    
    return ['text' => $text, 'keyboard' => $keyboard];
}

function show_multi_quality_selector($chat_id, $message_id, $movie_name, $current_page = 1) {
    $qualities = ['480p', '720p', '1080p', '4K HDR', 'HDTC', 'WEBRip', 'BluRay'];
    
    $buttons = [];
    $row = [];
    $count = 0;
    
    foreach($qualities as $q) {
        $row[] = ['text' => "🎥 $q", 'callback_data' => "filter_quality|$movie_name|$q"];
        $count++;
        if($count % 3 == 0) {
            $buttons[] = $row;
            $row = [];
        }
    }
    if(!empty($row)) $buttons[] = $row;
    
    $buttons[] = [['text' => "◀️ Back to Movie", 'callback_data' => "movie|$movie_name|$current_page"]];
    
    $text = "🎬 <b>" . strtoupper(htmlspecialchars($movie_name)) . "</b>\n\n";
    $text .= "📥 <b>Select Quality:</b>\n\n";
    $text .= "👇 Click on quality to see available files";
    
    editMessage($chat_id, $message_id, $text, json_encode(['inline_keyboard' => $buttons]), 'HTML');
}

// ==================== SETTINGS FUNCTIONS ====================
function show_personalize_settings($chat_id, $message_id = null) {
    global $user_settings;
    
    $user_id = $chat_id;
    $settings = $user_settings[$user_id] ?? [
        'file_delete' => 50,
        'auto_scan' => true,
        'spoiler_mode' => false,
        'priority' => 'size',
        'layout' => 'BTN',
        'theme' => 'dark'
    ];
    
    $auto_scan_emoji = $settings['auto_scan'] ? '✅' : '❌';
    $spoiler_emoji = $settings['spoiler_mode'] ? '✅' : '❌';
    $priority_text = $settings['priority'] == 'size' ? '📦 Size' : '🎬 Quality';
    $layout_text = $settings['layout'];
    
    $text = "⚙️ <b>Personalize Your Settings</b>\n\n";
    $text .= "🔍 <b>Auto Scan:</b> $auto_scan_emoji\n";
    $text .= "🎭 <b>Spoiler Mode:</b> $spoiler_emoji\n";
    $text .= "🥇 <b>Priority:</b> $priority_text\n";
    $text .= "📐 <b>Layout:</b> $layout_text\n\n";
    $text .= "🎨 <b>Theme:</b> " . get_theme_name($settings['theme']) . "\n\n";
    $text .= "🔄 <b>Reset to Defaults</b>";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🔍 Auto Scan ' . $auto_scan_emoji, 'callback_data' => 'settings_auto_scan'],
                ['text' => '🎭 Spoiler ' . $spoiler_emoji, 'callback_data' => 'settings_spoiler']
            ],
            [
                ['text' => '🥇 Priority: ' . ucfirst($settings['priority']), 'callback_data' => 'settings_priority'],
                ['text' => '📐 Layout: ' . $settings['layout'], 'callback_data' => 'settings_layout']
            ],
            [
                ['text' => '🎨 Choose Theme', 'callback_data' => 'show_themes']
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

function get_theme_name($theme) {
    $names = [
        'dark' => '🌙 Dark Mode',
        'light' => '☀️ Light Mode', 
        'blue' => '🌊 Ocean Blue',
        'green' => '🌲 Forest',
        'purple' => '👑 Royal',
        'orange' => '🌅 Sunset'
    ];
    return $names[$theme] ?? '🌙 Dark Mode';
}

function show_theme_selector($chat_id, $message_id) {
    $themes = [
        'dark' => ['name' => 'Dark Mode', 'icon' => '🌙'],
        'light' => ['name' => 'Light Mode', 'icon' => '☀️'],
        'blue' => ['name' => 'Ocean Blue', 'icon' => '🌊'],
        'green' => ['name' => 'Forest', 'icon' => '🌲'],
        'purple' => ['name' => 'Royal', 'icon' => '👑'],
        'orange' => ['name' => 'Sunset', 'icon' => '🌅']
    ];
    
    $keyboard = ['inline_keyboard' => []];
    $row = [];
    
    foreach($themes as $key => $theme) {
        $row[] = ['text' => $theme['icon'] . ' ' . $theme['name'], 'callback_data' => 'theme_' . $key];
        if(count($row) == 2) {
            $keyboard['inline_keyboard'][] = $row;
            $row = [];
        }
    }
    if(!empty($row)) $keyboard['inline_keyboard'][] = $row;
    $keyboard['inline_keyboard'][] = [['text' => '🔙 Back to Settings', 'callback_data' => 'settings_back']];
    
    editMessage($chat_id, $message_id, "🎨 <b>Choose Your Theme</b>\n\nSelect a theme to customize the bot appearance:", json_encode($keyboard), 'HTML');
}

function set_user_theme($user_id, $theme) {
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    if(!isset($users_data['users'][$user_id])) {
        $users_data['users'][$user_id] = [];
    }
    $users_data['users'][$user_id]['theme'] = $theme;
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
}

function get_user_theme($user_id) {
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    return $users_data['users'][$user_id]['theme'] ?? 'dark';
}

// ==================== REMINDER FUNCTIONS ====================
function check_reminders() {
    $reminders_file = 'reminders.json';
    if(!file_exists($reminders_file)) return;
    
    $reminders = json_decode(file_get_contents($reminders_file), true);
    $today = date('Y-m-d');
    $updated = false;
    
    foreach($reminders as $user_id => $user_reminders) {
        foreach($user_reminders as $movie => $date) {
            if($date == $today) {
                sendMessage($user_id, "🎉 <b>Movie Release Today!</b>\n\n🎬 <b>$movie</b> releases today!\n\n🔍 Search in bot to get it!", null, 'HTML');
                unset($reminders[$user_id][$movie]);
                $updated = true;
            }
        }
        if(empty($reminders[$user_id])) unset($reminders[$user_id]);
    }
    
    if($updated) file_put_contents($reminders_file, json_encode($reminders, JSON_PRETTY_PRINT));
}

// ==================== CHANNEL INFO ====================
function show_channel_info($chat_id) {
    $message = "📢 <b>Join Our Channels</b>\n\n";
    $message .= "🍿 <b>Main Channel:</b> @EntertainmentTadka786\n";
    $message .= "• Latest movie updates\n\n";
    $message .= "📥 <b>Requests Channel:</b> @EntertainmentTadka7860\n";
    $message .= "• Movie requests & support\n\n";
    $message .= "🎭 <b>Theater Prints:</b> @threater_print_movies\n";
    $message .= "• Theater quality prints\n";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🍿 Main', 'url' => 'https://t.me/EntertainmentTadka786'],
                ['text' => '📥 Requests', 'url' => 'https://t.me/EntertainmentTadka7860']
            ],
            [
                ['text' => '🎭 Theater', 'url' => 'https://t.me/threater_print_movies']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, json_encode($keyboard), 'HTML');
}

// ==================== ADMIN COMMANDS ====================
function perform_cleanup($chat_id) {
    if (!in_array($chat_id, ADMIN_IDS)) {
        sendMessage($chat_id, "❌ Access denied.");
        return;
    }
    
    $csvManager = CSVManager::getInstance();
    $duplicates = $csvManager->auto_cleanup();
    $csvManager->clearCache();
    
    sendMessage($chat_id, "🧹 Cleanup completed!\n\n• CSV duplicates removed: $duplicates\n• Cache cleared\n• System optimized", null, 'HTML');
}

function send_alert_to_all($chat_id, $alert_message) {
    if (!in_array($chat_id, ADMIN_IDS)) {
        sendMessage($chat_id, "❌ Access denied.");
        return;
    }
    
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $success_count = 0;
    
    foreach ($users_data['users'] as $user_id => $user) {
        try {
            sendMessage($user_id, "🚨 <b>Alert:</b>\n\n$alert_message", null, 'HTML');
            $success_count++;
            usleep(50000);
        } catch (Exception $e) {}
    }
    
    sendMessage($chat_id, "✅ Alert sent to $success_count users!", null, 'HTML');
}

function toggle_maintenance_mode($chat_id, $mode) {
    if (!in_array($chat_id, ADMIN_IDS)) {
        sendMessage($chat_id, "❌ Access denied.");
        return;
    }
    
    if ($mode == 'on') {
        $GLOBALS['MAINTENANCE_MODE'] = true;
        sendMessage($chat_id, "🔧 Maintenance mode ENABLED", null, 'HTML');
    } elseif ($mode == 'off') {
        $GLOBALS['MAINTENANCE_MODE'] = false;
        sendMessage($chat_id, "✅ Maintenance mode DISABLED", null, 'HTML');
    }
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
                        ['text' => '❓ Help', 'callback_data' => 'help_command']
                    ],
                    [
                        ['text' => '⚙️ Settings', 'callback_data' => 'settings'],
                        ['text' => '🎬 Premium UI', 'callback_data' => 'premium_ui_demo']
                    ]
                ]
            ];
            sendMessage($chat_id, $welcome, json_encode($keyboard), 'HTML');
            break;

        case '/help':
            sendMessage($chat_id, getHinglishResponse('help'), null, 'HTML');
            break;

        case '/search':
            $movie_name = implode(' ', $params);
            if (empty($movie_name)) {
                sendMessage($chat_id, "❌ Usage: /search movie_name", null, 'HTML');
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
                    ],
                    [
                        ['text' => '🇮🇳 தமிழ்', 'callback_data' => 'lang_tamil'],
                        ['text' => '🇮🇳 తెలుగు', 'callback_data' => 'lang_telugu'],
                        ['text' => '🇮🇳 മലയാളം', 'callback_data' => 'lang_malayalam']
                    ],
                    [
                        ['text' => '🇮🇳 ಕನ್ನಡ', 'callback_data' => 'lang_kannada']
                    ]
                ]
            ];
            sendMessage($chat_id, getHinglishResponse('language_choose'), json_encode($keyboard), 'HTML');
            break;

        case '/settings':
            show_personalize_settings($chat_id);
            break;

        case '/premium':
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🎬 Try Premium UI Demo', 'callback_data' => 'premium_ui_demo']
                    ]
                ]
            ];
            sendMessage($chat_id, "🎬 <b>Premium OTT UI</b>\n\nSend any movie name to see the premium interface!", json_encode($keyboard), 'HTML');
            break;

        case '/request':
            if (!REQUEST_SYSTEM_ENABLED) {
                sendMessage($chat_id, "❌ Request system disabled", null, 'HTML');
                return;
            }
            
            if (count($params) == 0) {
                sendMessage($chat_id, "❌ Usage: /request Movie Name\nExample: /request KGF 2", null, 'HTML');
                return;
            }
            
            $movie_name = implode(' ', $params);
            $requestSystem = RequestSystem::getInstance();
            $result = $requestSystem->submitRequest($user_id, $movie_name, '');
            
            if ($result['success']) {
                sendMessage($chat_id, getHinglishResponse('request_success', [
                    'movie' => $movie_name,
                    'id' => $result['request_id']
                ]), null, 'HTML');
            } else {
                if (strpos($result['message'], 'already requested') !== false) {
                    sendMessage($chat_id, getHinglishResponse('request_duplicate'), null, 'HTML');
                } else {
                    sendMessage($chat_id, getHinglishResponse('request_limit', ['limit' => MAX_REQUESTS_PER_DAY]), null, 'HTML');
                }
            }
            break;

        case '/myrequests':
            $requestSystem = RequestSystem::getInstance();
            $requests = $requestSystem->getUserRequests($user_id);
            $stats = $requestSystem->getUserStats($user_id);
            
            if (empty($requests)) {
                sendMessage($chat_id, getHinglishResponse('myrequests_empty'), null, 'HTML');
                return;
            }
            
            $text = "📋 <b>Your Requests</b>\n\n";
            $text .= "📊 Total: {$stats['total_requests']}\n";
            $text .= "⏳ Pending: {$stats['pending']}\n";
            $text .= "✅ Approved: " . ($stats['approved'] ?? 0) . "\n";
            $text .= "❌ Rejected: " . ($stats['rejected'] ?? 0) . "\n\n";
            $text .= "🎬 Recent:\n\n";
            
            $count = 0;
            foreach($requests as $req) {
                if($count++ >= 5) break;
                $status_emoji = $req['status'] == 'approved' ? '✅' : ($req['status'] == 'rejected' ? '❌' : '⏳');
                $text .= "$status_emoji <b>{$req['movie_name']}</b> - {$req['status']}\n";
            }
            
            sendMessage($chat_id, $text, null, 'HTML');
            break;

        case '/requestlimit':
            $requestSystem = RequestSystem::getInstance();
            $stats = $requestSystem->getUserStats($user_id);
            $remaining = MAX_REQUESTS_PER_DAY - ($stats['requests_today'] ?? 0);
            sendMessage($chat_id, "📊 <b>Daily Request Limit</b>\n\n• Used: " . ($stats['requests_today'] ?? 0) . "\n• Remaining: $remaining\n• Total Limit: " . MAX_REQUESTS_PER_DAY, null, 'HTML');
            break;

        case '/testcsv':
            $csvManager = CSVManager::getInstance();
            $data = $csvManager->getCachedData();
            $msg = "📊 <b>CSV Data</b>\n\nTotal entries: " . count($data) . "\n\n";
            $count = 0;
            foreach(array_slice($data, 0, 10) as $row) {
                $msg .= "{$row['movie_name']} | ID: {$row['message_id']}\n";
            }
            sendMessage($chat_id, $msg, null, 'HTML');
            break;

        case '/checkcsv':
            $csvManager = CSVManager::getInstance();
            $stats = $csvManager->getStats();
            sendMessage($chat_id, "📊 <b>CSV Stats</b>\n\nTotal Movies: {$stats['total_movies']}\nLast Updated: {$stats['last_updated']}", null, 'HTML');
            break;

        case '/channel':
            show_channel_info($chat_id);
            break;

        case '/remind':
            if(count($params) < 2) {
                sendMessage($chat_id, "❌ Usage: /remind MovieName YYYY-MM-DD\nExample: /remind \"KGF 3\" 2025-12-25", null, 'HTML');
                break;
            }
            
            $movie_name = implode(' ', array_slice($params, 0, -1));
            $date = end($params);
            
            if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                sendMessage($chat_id, "❌ Invalid date format. Use YYYY-MM-DD", null, 'HTML');
                break;
            }
            
            $reminders_file = 'reminders.json';
            $reminders = file_exists($reminders_file) ? json_decode(file_get_contents($reminders_file), true) : [];
            
            if(!isset($reminders[$user_id])) $reminders[$user_id] = [];
            $reminders[$user_id][$movie_name] = $date;
            
            file_put_contents($reminders_file, json_encode($reminders, JSON_PRETTY_PRINT));
            
            sendMessage($chat_id, "✅ Reminder set for <b>$movie_name</b> on <b>$date</b>\n\nI'll notify you on release day! 🎬", null, 'HTML');
            break;

        case '/myreminders':
            $reminders_file = 'reminders.json';
            $reminders = file_exists($reminders_file) ? json_decode(file_get_contents($reminders_file), true) : [];
            
            if(empty($reminders[$user_id])) {
                sendMessage($chat_id, "📭 No reminders set.\n\nUse /remind MovieName YYYY-MM-DD to set one!", null, 'HTML');
                break;
            }
            
            $text = "🔔 <b>Your Reminders</b>\n\n";
            foreach($reminders[$user_id] as $movie => $date) {
                $days = ceil((strtotime($date) - time()) / 86400);
                $status = $days > 0 ? "📅 $days days left" : "✅ Released";
                $text .= "🎬 <b>$movie</b>\n   📆 $date | $status\n\n";
            }
            
            sendMessage($chat_id, $text, null, 'HTML');
            break;

        case '/calendar':
        case '/releases':
            $month = isset($params[0]) ? intval($params[0]) : intval(date('m'));
            $year = isset($params[1]) ? intval($params[1]) : intval(date('Y'));
            
            if($month < 1 || $month > 12) $month = intval(date('m'));
            
            $releases_file = 'releases.json';
            if(!file_exists($releases_file)) {
                $default_releases = [
                    "2025" => [
                        "1" => [["movie" => "Game Changer", "date" => "2025-01-12", "language" => "Telugu", "star" => "Ram Charan"]],
                        "4" => [["movie" => "KGF 3", "date" => "2025-04-15", "language" => "Kannada", "star" => "Yash"]],
                        "5" => [["movie" => "Pushpa 2", "date" => "2025-05-30", "language" => "Telugu", "star" => "Allu Arjun"]]
                    ]
                ];
                file_put_contents($releases_file, json_encode($default_releases, JSON_PRETTY_PRINT));
            }
            
            $releases = json_decode(file_get_contents($releases_file), true);
            
            $month_names = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            
            $text = "📅 <b>Movie Releases - {$month_names[$month]} $year</b>\n\n";
            
            if(isset($releases[$year][$month])) {
                foreach($releases[$year][$month] as $movie) {
                    $day = date('d', strtotime($movie['date']));
                    $text .= "📆 <b>$day</b> | 🎬 <b>{$movie['movie']}</b>\n";
                    $text .= "   🌐 {$movie['language']} | ⭐ {$movie['star']}\n\n";
                }
            } else {
                $text .= "No releases scheduled for this month.\n\n";
            }
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '◀️ Prev Month', 'callback_data' => 'calendar|' . ($month-1) . "|$year"],
                        ['text' => 'Next Month ▶️', 'callback_data' => 'calendar|' . ($month+1) . "|$year"]
                    ],
                    [
                        ['text' => '📌 Set Reminder', 'callback_data' => 'reminder_help']
                    ]
                ]
            ];
            
            sendMessage($chat_id, $text, json_encode($keyboard), 'HTML');
            break;

        case '/recommend':
        case '/similar':
            $movie_name = implode(' ', $params);
            if(empty($movie_name)) {
                sendMessage($chat_id, "❌ Usage: /recommend MovieName\nExample: /recommend KGF", null, 'HTML');
                break;
            }
            
            $key = strtolower(trim($movie_name));
            global $recommendations;
            
            $found = null;
            foreach($recommendations as $movie => $recs) {
                if(strpos($key, $movie) !== false || strpos($movie, $key) !== false) {
                    $found = $recs;
                    break;
                }
            }
            
            if(!$found) {
                $best_match = null;
                $highest_similarity = 0;
                foreach(array_keys($recommendations) as $movie) {
                    similar_text($key, $movie, $similarity);
                    if($similarity > $highest_similarity && $similarity > 60) {
                        $highest_similarity = $similarity;
                        $best_match = $movie;
                    }
                }
                if($best_match) $found = $recommendations[$best_match];
            }
            
            if($found) {
                $text = "🎯 <b>Movies similar to $movie_name:</b>\n\n";
                foreach($found as $rec) {
                    $text .= "🎬 <b>$rec</b>\n";
                }
                $text .= "\n🔍 Type any name to search!";
                sendMessage($chat_id, $text, null, 'HTML');
            } else {
                $text = "🤔 No recommendations found for '$movie_name'.\n\n";
                $text .= "🔥 <b>Popular Movies:</b>\n";
                $text .= "• KGF Chapter 2\n• Pushpa 2\n• Salaar\n• Animal\n• Stree 2\n• Jawan";
                sendMessage($chat_id, $text, null, 'HTML');
            }
            break;

        // Admin Commands
        case '/maintenance':
            if (in_array($user_id, ADMIN_IDS)) {
                $mode = isset($params[0]) ? strtolower($params[0]) : '';
                toggle_maintenance_mode($chat_id, $mode);
            } else {
                sendMessage($chat_id, "❌ Access denied.");
            }
            break;

        case '/cleanup':
            if (in_array($user_id, ADMIN_IDS)) {
                perform_cleanup($chat_id);
            } else {
                sendMessage($chat_id, "❌ Access denied.");
            }
            break;

        case '/sendalert':
            if (in_array($user_id, ADMIN_IDS)) {
                $alert_message = implode(' ', $params);
                if (empty($alert_message)) {
                    sendMessage($chat_id, "❌ Usage: /sendalert your_alert", null, 'HTML');
                    return;
                }
                send_alert_to_all($chat_id, $alert_message);
            } else {
                sendMessage($chat_id, "❌ Access denied.");
            }
            break;

        case '/pendingrequests':
            if (in_array($user_id, ADMIN_IDS)) {
                $requestSystem = RequestSystem::getInstance();
                $requests = $requestSystem->getPendingRequests(10);
                $stats = $requestSystem->getStats();
                
                if (empty($requests)) {
                    sendMessage($chat_id, "📭 No pending requests", null, 'HTML');
                    return;
                }
                
                $message = "📋 <b>Pending Requests</b>\n\n";
                $message .= "📊 Total: {$stats['total_requests']}\n";
                $message .= "⏳ Pending: {$stats['pending']}\n\n";
                
                $keyboard = ['inline_keyboard' => []];
                
                foreach ($requests as $req) {
                    $message .= "🔸 <b>#" . $req['id'] . ":</b> " . htmlspecialchars($req['movie_name']) . "\n";
                    $message .= "   👤 User: " . ($req['user_name'] ?: "ID: " . $req['user_id']) . "\n\n";
                    
                    $keyboard['inline_keyboard'][] = [
                        [
                            ['text' => '✅ Approve #' . $req['id'], 'callback_data' => 'approve_' . $req['id']],
                            ['text' => '❌ Reject #' . $req['id'], 'callback_data' => 'reject_' . $req['id']]
                        ]
                    ];
                }
                
                sendMessage($chat_id, $message, json_encode($keyboard), 'HTML');
            } else {
                sendMessage($chat_id, "❌ Access denied.");
            }
            break;

        default:
            sendMessage($chat_id, "❌ Unknown command. Use /help", null, 'HTML');
    }
}

// ==================== CHECK DATE FUNCTION ====================
function check_date($chat_id) {
    if (!file_exists(CSV_FILE)) {
        sendMessage($chat_id, "⚠️ No data yet.");
        return;
    }
    
    $csvManager = CSVManager::getInstance();
    $data = $csvManager->getCachedData();
    
    $date_counts = [];
    foreach ($data as $row) {
        $d = $row['date'] ?? 'Unknown';
        if (!isset($date_counts[$d])) $date_counts[$d] = 0;
        $date_counts[$d]++;
    }
    
    krsort($date_counts);
    $msg = "📅 <b>Upload Record</b>\n\n";
    
    foreach (array_slice($date_counts, 0, 10) as $date => $count) {
        $msg .= "➡️ $date: $count movies\n";
    }
    
    sendMessage($chat_id, $msg, null, 'HTML');
}

// ==================== NOTIFICATION FUNCTION ====================
function notifyUserAboutRequest($user_id, $request, $action) {
    $requestSystem = RequestSystem::getInstance();
    $movie_name = htmlspecialchars($request['movie_name']);
    
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
            $message .= "📋 <b>Reason:</b> " . htmlspecialchars($request['reason']) . "\n";
        }
    }
    
    sendMessage($user_id, $message, null, 'HTML');
    $requestSystem->markAsNotified($request['id']);
}

// ==================== SAMPLE DATA LOADER ====================
function load_movie_files($movie_name) {
    return [
        ['size' => '3.02 GB', 'name' => $movie_name . ' 2025 HINDI 1080p WEBRip x265 mkv'],
        ['size' => '1.51 GB', 'name' => $movie_name . ' 2025 HINDI 720p WEBRip x265 mkv'],
        ['size' => '688 MB', 'name' => $movie_name . ' 2025 480p HDTC Hindi x264 mkv']
    ];
}

function load_series_episodes($series_name) {
    $eps = [];
    for($i = 1; $i <= 10; $i++) {
        $eps[] = ['size' => '170 MB', 'name' => $series_name . ' S01E' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 720p mkv'];
    }
    return $eps;
}

// ==================== MAIN PROCESSING - RENDER.COM WEBHOOK VERSION ====================

// Initialize managers
$csvManager = CSVManager::getInstance();
$requestSystem = RequestSystem::getInstance();

// Webhook setup page
if (isset($_GET['setup'])) {
    $webhook_url = (WEBHOOK_URL ?: "https://" . $_SERVER['HTTP_HOST']) . "/";
    $result = apiRequest('setWebhook', ['url' => $webhook_url]);
    $result_data = json_decode($result, true);
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>🎬 Bot Webhook Setup</title>
        <style>
            body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
            .container { max-width: 800px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 20px; }
            h1 { text-align: center; }
            .success { background: #4CAF50; padding: 10px; border-radius: 10px; }
            .error { background: #f44336; padding: 10px; border-radius: 10px; }
            .btn { display: inline-block; padding: 10px 20px; margin: 10px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🎬 Entertainment Tadka Bot</h1>";
    
    if ($result_data && isset($result_data['ok']) && $result_data['ok']) {
        echo "<div class='success'>✅ Webhook successfully set to: $webhook_url</div>";
    } else {
        echo "<div class='error'>❌ Failed to set webhook: " . htmlspecialchars($result) . "</div>";
    }
    
    echo "<div style='text-align: center; margin-top: 20px;'>
            <a href='?test=1' class='btn'>🧪 Test Bot</a>
            <a href='?deletehook=1' class='btn'>🗑️ Delete Webhook</a>
            <a href='/' class='btn'>🏠 Home</a>
          </div>
        </div>
    </body>
    </html>";
    exit;
}

// Webhook delete page
if (isset($_GET['deletehook'])) {
    $result = apiRequest('deleteWebhook');
    $result_data = json_decode($result, true);
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>🎬 Bot Webhook Deletion</title>
        <style>
            body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
            .container { max-width: 800px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 20px; }
            h1 { text-align: center; }
            .success { background: #4CAF50; padding: 10px; border-radius: 10px; }
            .btn { display: inline-block; padding: 10px 20px; margin: 10px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🎬 Entertainment Tadka Bot</h1>";
    
    if ($result_data && isset($result_data['ok']) && $result_data['ok']) {
        echo "<div class='success'>✅ Webhook successfully deleted!</div>";
    } else {
        echo "<div class='success'>⚠️ Webhook deletion attempted: " . htmlspecialchars($result) . "</div>";
    }
    
    echo "<div style='text-align: center; margin-top: 20px;'>
            <a href='?setup=1' class='btn'>🔗 Set Webhook</a>
            <a href='?test=1' class='btn'>🧪 Test Bot</a>
            <a href='/' class='btn'>🏠 Home</a>
          </div>
        </div>
    </body>
    </html>";
    exit;
}

// Test page
if (isset($_GET['test'])) {
    header('Content-Type: text/html; charset=utf-8');
    
    $csvManager = CSVManager::getInstance();
    $stats = $csvManager->getStats();
    $users_data = json_decode(@file_get_contents(USERS_FILE), true);
    $request_stats = $requestSystem->getStats();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>🎬 Bot Test Page</title>
        <style>
            body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
            .container { max-width: 800px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 20px; }
            h1, h3 { text-align: center; }
            .stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 30px 0; }
            .stat-card { background: rgba(255,255,255,0.2); padding: 20px; border-radius: 10px; text-align: center; }
            .stat-value { font-size: 2em; font-weight: bold; }
            .stat-label { font-size: 0.9em; opacity: 0.8; }
            .btn { display: inline-block; padding: 10px 20px; margin: 10px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
            .btn-danger { background: #f44336; }
            .info { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🎬 Entertainment Tadka Bot - Test Page</h1>
            <div class='success' style='background: #4CAF50; padding: 10px; border-radius: 10px; text-align: center;'>
                ✅ Bot Status: <strong>RUNNING</strong> (Webhook Mode)
            </div>
            
            <div class='stats'>
                <div class='stat-card'>
                    <div class='stat-value'>" . $stats['total_movies'] . "</div>
                    <div class='stat-label'>Total Movies</div>
                </div>
                <div class='stat-card'>
                    <div class='stat-value'>" . count($users_data['users'] ?? []) . "</div>
                    <div class='stat-label'>Total Users</div>
                </div>
                <div class='stat-card'>
                    <div class='stat-value'>" . ($request_stats['total_requests'] ?? 0) . "</div>
                    <div class='stat-label'>Total Requests</div>
                </div>
                <div class='stat-card'>
                    <div class='stat-value'>" . ($request_stats['pending'] ?? 0) . "</div>
                    <div class='stat-label'>Pending Requests</div>
                </div>
            </div>
            
            <div class='info'>
                <h3>📁 File Status:</h3>
                <ul>
                    <li>movies.csv: " . (file_exists(CSV_FILE) ? '✅ Exists' : '❌ Missing') . "</li>
                    <li>users.json: " . (file_exists(USERS_FILE) ? '✅ Exists' : '❌ Missing') . "</li>
                    <li>error.log: " . (file_exists('error.log') ? '✅ Exists' : '⚠️ Will be created') . "</li>
                    <li>cache/ directory: " . (is_dir(CACHE_DIR) ? '✅ Exists' : '⚠️ Will be created') . "</li>
                </ul>
            </div>
            
            <div class='info'>
                <h3>⚙️ Configuration:</h3>
                <ul>
                    <li>Bot Token: " . substr(BOT_TOKEN, 0, 10) . "..." . substr(BOT_TOKEN, -5) . "</li>
                    <li>Environment: " . $environment . "</li>
                    <li>Maintenance Mode: " . (MAINTENANCE_MODE ? '🔧 ON' : '✅ OFF') . "</li>
                    <li>Request System: " . (REQUEST_SYSTEM_ENABLED ? '✅ Enabled' : '❌ Disabled') . "</li>
                    <li>Items Per Page: " . ITEMS_PER_PAGE . "</li>
                    <li>Max Requests/Day: " . MAX_REQUESTS_PER_DAY . "</li>
                </ul>
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='?setup=1' class='btn'>🔗 Set Webhook</a>
                <a href='?deletehook=1' class='btn btn-danger'>🗑️ Delete Webhook</a>
                <a href='/' class='btn'>🏠 Home</a>
            </div>
            
            <p style='text-align: center;'>© " . date('Y') . " - Clean Version | Optimized for Render.com</p>
        </div>
    </body>
    </html>";
    exit;
}

// Health check endpoint for Render
if (isset($_GET['health']) || isset($_GET['ping'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'timestamp' => time(),
        'bot' => 'Entertainment Tadka',
        'mode' => 'webhook'
    ]);
    exit;
}

// Default home page
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['setup']) && !isset($_GET['deletehook']) && !isset($_GET['test']) && !isset($_GET['health']) && !isset($_GET['ping'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>🎬 Entertainment Tadka Bot</title>
        <style>
            body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
            .container { max-width: 800px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 20px; }
            h1 { text-align: center; }
            .status { background: #4CAF50; padding: 10px; border-radius: 10px; text-align: center; }
            .btn { display: inline-block; padding: 10px 20px; margin: 10px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
            .feature-list { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin: 30px 0; }
            .feature-item { background: rgba(255,255,255,0.2); padding: 10px; border-radius: 5px; text-align: center; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🎬 Entertainment Tadka Bot</h1>
            <div class='status'>✅ Bot is Running - Webhook Mode for Render.com</div>
            
            <p style='text-align: center; margin: 20px 0;'>
                Telegram Bot for movie searches | Optimized for Render.com Docker Web Service
            </p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='?setup=1' class='btn'>🔗 Set Webhook</a>
                <a href='?test=1' class='btn'>🧪 Test Bot</a>
                <a href='?deletehook=1' class='btn'>🗑️ Delete Webhook</a>
            </div>
            
            <h3>✨ Features:</h3>
            <div class='feature-list'>
                <div class='feature-item'>✅ Movie Search with Spell Check</div>
                <div class='feature-item'>✅ Premium OTT UI</div>
                <div class='feature-item'>✅ Multi-Language (7 languages)</div>
                <div class='feature-item'>✅ Movie Recommendations</div>
                <div class='feature-item'>✅ Release Calendar</div>
                <div class='feature-item'>✅ Reminder System</div>
                <div class='feature-item'>✅ Theme Selector</div>
                <div class='feature-item'>✅ Mobile Optimized</div>
                <div class='feature-item'>✅ Request System</div>
                <div class='feature-item'>✅ Admin Controls</div>
                <div class='feature-item'>✅ Webhook Mode</div>
                <div class='feature-item'>✅ Render.com Optimized</div>
            </div>
            
            <p style='text-align: center;'>© " . date('Y') . " - Clean Version | Optimized for Render.com</p>
        </div>
    </body>
    </html>";
    exit;
}

// ==================== WEBHOOK HANDLER ====================
// This is the main entry point for Telegram updates
$update = json_decode(file_get_contents('php://input'), true);

if ($update) {
    log_error("Update received", 'INFO', ['update_id' => $update['update_id'] ?? 'N/A']);
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!RateLimiter::check($ip, 'telegram_update', 30, 60)) {
        http_response_code(429);
        exit;
    }
    
    if (MAINTENANCE_MODE && isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        sendMessage($chat_id, getHinglishResponse('maintenance'), null, 'HTML');
        exit;
    }

    // Process channel posts
    if (isset($update['channel_post'])) {
        // Channel posts handled silently
    }

    // Process user messages
    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $message['from']['id'];
        $message_id = $message['message_id'];
        $text = isset($message['text']) ? $message['text'] : '';
        $chat_type = $message['chat']['type'] ?? 'private';
        
        log_error("Message received", 'INFO', ['chat_id' => $chat_id, 'text' => substr($text, 0, 50)]);
        
        $user_info = [
            'first_name' => $message['from']['first_name'] ?? '',
            'last_name' => $message['from']['last_name'] ?? '',
            'username' => $message['from']['username'] ?? ''
        ];
        
        // Update user in users.json
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        if (!isset($users_data['users'][$user_id])) {
            $users_data['users'][$user_id] = [
                'first_name' => $user_info['first_name'],
                'last_name' => $user_info['last_name'],
                'username' => $user_info['username'],
                'joined' => date('Y-m-d H:i:s'),
                'last_active' => date('Y-m-d H:i:s')
            ];
            file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
        } else {
            $users_data['users'][$user_id]['last_active'] = date('Y-m-d H:i:s');
            file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
        }

        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = strtolower($parts[0]);
            $params = array_slice($parts, 1);
            handle_command($chat_id, $user_id, $command, $params);
        } else if (!empty(trim($text))) {
            if (stripos($text, 'add movie') !== false || 
                stripos($text, 'please add') !== false || 
                stripos($text, 'pls add') !== false ||
                stripos($text, 'request movie') !== false) {
                
                if (!REQUEST_SYSTEM_ENABLED) {
                    sendMessage($chat_id, "❌ Request system disabled", null, 'HTML');
                    exit;
                }
                
                $patterns = [
                    '/add movie (.+)/i',
                    '/please add (.+)/i',
                    '/pls add (.+)/i',
                    '/request movie (.+)/i'
                ];
                
                $movie_name = '';
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $text, $matches)) {
                        $movie_name = trim($matches[1]);
                        break;
                    }
                }
                
                if (empty($movie_name)) {
                    $clean_text = preg_replace('/add movie|please add|pls add|movie|add|request/i', '', $text);
                    $movie_name = trim($clean_text);
                }
                
                if (strlen($movie_name) < 2) {
                    sendMessage($chat_id, "❌ Please enter a valid movie name", null, 'HTML');
                    exit;
                }
                
                $result = $requestSystem->submitRequest($user_id, $movie_name, '');
                
                if ($result['success']) {
                    sendMessage($chat_id, getHinglishResponse('request_success', [
                        'movie' => $movie_name,
                        'id' => $result['request_id']
                    ]), null, 'HTML');
                } else {
                    if (strpos($result['message'], 'already requested') !== false) {
                        sendMessage($chat_id, getHinglishResponse('request_duplicate'), null, 'HTML');
                    } else {
                        sendMessage($chat_id, getHinglishResponse('request_limit', ['limit' => MAX_REQUESTS_PER_DAY]), null, 'HTML');
                    }
                }
            } else {
                $lang = detectUserLanguage($text);
                sendMessage($chat_id, get_language_response($lang, 'searching'), null, 'HTML');
                advanced_search($chat_id, $text, $user_id);
            }
        }
    }

    // Process callback queries
    if (isset($update['callback_query'])) {
        $query = $update['callback_query'];
        $message = $query['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $query['from']['id'];
        $data = $query['data'];
        $message_id = $message['message_id'];

        log_error("Callback query", 'INFO', ['data' => $data]);

        if (strpos($data, 'movie_') === 0) {
            $movie_name_encoded = str_replace('movie_', '', $data);
            $movie_name = base64_decode($movie_name_encoded);
            
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
                sendMessage($chat_id, "✅ Sent $sent_count copies of '$movie_name'", null, 'HTML');
                answerCallbackQuery($query['id'], "🎬 $sent_count items sent!");
            } else {
                answerCallbackQuery($query['id'], "❌ Movie not found", true);
            }
        }
        elseif (strpos($data, 'pag_prev_') === 0) {
            $parts = explode('_', $data);
            $current_page = intval($parts[2]);
            $session_id = $parts[3] ?? '';
            totalupload_controller($chat_id, max(1, $current_page - 1), $session_id);
            answerCallbackQuery($query['id'], "Previous page");
        }
        elseif (strpos($data, 'pag_next_') === 0) {
            $parts = explode('_', $data);
            $current_page = intval($parts[2]);
            $session_id = $parts[3] ?? '';
            $all = get_all_movies_list();
            $total_pages = ceil(count($all) / ITEMS_PER_PAGE);
            totalupload_controller($chat_id, min($total_pages, $current_page + 1), $session_id);
            answerCallbackQuery($query['id'], "Next page");
        }
        elseif (strpos($data, 'send_') === 0) {
            $parts = explode('_', $data);
            $page_num = intval($parts[1]);
            $session_id = $parts[2] ?? '';
            
            $all = get_all_movies_list();
            $pg = paginate_movies($all, $page_num);
            batch_download_with_progress($chat_id, $pg['slice'], $page_num);
            answerCallbackQuery($query['id'], "📦 Batch started!");
        }
        elseif ($data === 'current') {
            answerCallbackQuery($query['id'], "Current page");
        }
        elseif ($data === 'request_movie') {
            sendMessage($chat_id, "📝 Use /request MovieName to request a movie", null, 'HTML');
            answerCallbackQuery($query['id'], "Request guide");
        }
        elseif ($data === 'help_command') {
            $help_text = getHinglishResponse('help');
            $keyboard = [
                'inline_keyboard' => [[
                    ['text' => '🔙 Back', 'callback_data' => 'back_to_start']
                ]]
            ];
            editMessage($chat_id, $message_id, $help_text, json_encode($keyboard), 'HTML');
            answerCallbackQuery($query['id'], "Help");
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
                'auto_scan' => true,
                'spoiler_mode' => false,
                'priority' => 'size',
                'layout' => 'BTN',
                'theme' => 'dark'
            ];
            show_personalize_settings($chat_id, $message_id);
            answerCallbackQuery($query['id'], "🔄 Reset to defaults");
        }
        elseif ($data === 'settings_back') {
            show_personalize_settings($chat_id, $message_id);
            answerCallbackQuery($query['id']);
        }
        elseif ($data === 'show_themes') {
            show_theme_selector($chat_id, $message_id);
            answerCallbackQuery($query['id']);
        }
        elseif (strpos($data, 'theme_') === 0) {
            $theme = str_replace('theme_', '', $data);
            set_user_theme($user_id, $theme);
            global $user_settings;
            $user_settings[$user_id]['theme'] = $theme;
            
            $theme_names = [
                'dark' => '🌙 Dark Mode',
                'light' => '☀️ Light Mode', 
                'blue' => '🌊 Ocean Blue',
                'green' => '🌲 Forest',
                'purple' => '👑 Royal',
                'orange' => '🌅 Sunset'
            ];
            
            show_personalize_settings($chat_id, $message_id);
            answerCallbackQuery($query['id'], "✅ Theme set to " . $theme_names[$theme]);
        }
        elseif ($data === 'premium_ui_demo') {
            $demo_movies = ["KGF 2", "Animal", "Stree 2", "Pushpa", "Salaar"];
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
        elseif (strpos($data, 'filter_quality|') === 0) {
            $parts = explode('|', $data);
            $movie_name = $parts[1];
            $quality = $parts[2];
            
            $files = load_movie_files($movie_name);
            $filtered = array_filter($files, function($f) use ($quality) {
                return strpos($f['name'], $quality) !== false;
            });
            
            if(empty($filtered)) {
                answerCallbackQuery($query['id'], "❌ No $quality files found", true);
                return;
            }
            
            $result = show_premium_movie_list($chat_id, $movie_name . " - $quality", "User", array_values($filtered), 1, ceil(count($filtered)/10));
            editMessage($chat_id, $message_id, $result['text'], json_encode($result['keyboard']), 'HTML');
            answerCallbackQuery($query['id'], "Showing $quality files");
        }
        elseif (strpos($data, 'page_') === 0) {
            $parts = explode('_', $data);
            $page = intval($parts[1]);
            $movie_name = $parts[2];
            
            $files = load_movie_files($movie_name);
            $total_pages = ceil(count($files) / 10);
            
            $result = show_premium_movie_list($chat_id, $movie_name, "User", $files, $page, $total_pages);
            editMessage($chat_id, $message_id, $result['text'], json_encode($result['keyboard']), 'HTML');
            answerCallbackQuery($query['id'], "Page $page");
        }
        elseif (strpos($data, 'movie|') === 0) {
            $parts = explode('|', $data);
            $movie_name = $parts[1];
            $page = isset($parts[2]) ? intval($parts[2]) : 1;
            
            $files = load_movie_files($movie_name);
            $total_pages = ceil(count($files) / 10);
            
            $result = show_premium_movie_list($chat_id, $movie_name, "User", $files, $page, $total_pages);
            editMessage($chat_id, $message_id, $result['text'], json_encode($result['keyboard']), 'HTML');
            answerCallbackQuery($query['id'], "Movie menu");
        }
        elseif ($data === 'home') {
            deleteMessage($chat_id, $message_id);
            answerCallbackQuery($query['id'], "Home");
        }
        elseif ($data === 'close') {
            deleteMessage($chat_id, $message_id);
            answerCallbackQuery($query['id'], "Closed");
        }
        elseif (strpos($data, 'send|') === 0) {
            answerCallbackQuery($query['id'], "📁 File selected!");
        }
        elseif ($data === 'ignore') {
            answerCallbackQuery($query['id']);
        }
        elseif (strpos($data, 'auto_request_') === 0) {
            $movie_name = base64_decode(str_replace('auto_request_', '', $data));
            
            $requestSystem = RequestSystem::getInstance();
            $result = $requestSystem->submitRequest($user_id, $movie_name, '');
            
            if ($result['success']) {
                sendMessage($chat_id, getHinglishResponse('request_success', [
                    'movie' => $movie_name,
                    'id' => $result['request_id']
                ]), null, 'HTML');
                answerCallbackQuery($query['id'], "Request sent!");
            } else {
                sendMessage($chat_id, getHinglishResponse('request_limit', ['limit' => MAX_REQUESTS_PER_DAY]), null, 'HTML');
                answerCallbackQuery($query['id'], "Daily limit reached!", true);
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
                $new_text = $message['text'] . "\n\n✅ <b>Approved by Admin</b>";
                
                editMessage($chat_id, $message_id, $new_text, null, 'HTML');
                answerCallbackQuery($query['id'], "✅ Approved");
                
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
            answerCallbackQuery($query['id'], "Select reason");
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
                'already_available' => 'Movie is already available',
                'invalid_request' => 'Invalid movie name',
                'low_quality' => 'Cannot find good quality',
                'not_available' => 'Movie not available'
            ];
            
            $reason = $reason_map[$reason_key] ?? 'Not specified';
            
            $result = $requestSystem->rejectRequest($request_id, $user_id, $reason);
            
            if ($result['success']) {
                $request = $result['request'];
                $new_text = $message['text'] . "\n\n❌ <b>Rejected</b>\n📝 Reason: $reason";
                
                editMessage($chat_id, $message_id, $new_text, null, 'HTML');
                answerCallbackQuery($query['id'], "❌ Rejected");
                
                notifyUserAboutRequest($request['user_id'], $request, 'rejected');
            } else {
                answerCallbackQuery($query['id'], $result['message'], true);
            }
        }
        elseif (strpos($data, 'reject_custom_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) {
                answerCallbackQuery($query['id'], "❌ Admin only!", true);
                return;
            }
            
            $request_id = str_replace('reject_custom_', '', $data);
            
            sendMessage($chat_id, "Please send the custom rejection reason for Request #$request_id:", null, 'HTML');
            answerCallbackQuery($query['id'], "Type reason");
            
            $pending_file = 'pending_rejection.json';
            $pending_data = [
                'request_id' => $request_id,
                'admin_id' => $user_id,
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'timestamp' => time()
            ];
            file_put_contents($pending_file, json_encode($pending_data, JSON_PRETTY_PRINT));
        }
        elseif (strpos($data, 'calendar|') === 0) {
            $parts = explode('|', $data);
            $month = intval($parts[1]);
            $year = intval($parts[2]);
            
            if($month < 1) { $month = 12; $year--; }
            if($month > 12) { $month = 1; $year++; }
            
            $releases_file = 'releases.json';
            $releases = json_decode(file_get_contents($releases_file), true);
            
            $month_names = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            
            $text = "📅 <b>Movie Releases - {$month_names[$month]} $year</b>\n\n";
            
            if(isset($releases[$year][$month])) {
                foreach($releases[$year][$month] as $movie) {
                    $day = date('d', strtotime($movie['date']));
                    $text .= "📆 <b>$day</b> | 🎬 <b>{$movie['movie']}</b>\n";
                    $text .= "   🌐 {$movie['language']} | ⭐ {$movie['star']}\n\n";
                }
            } else {
                $text .= "No releases scheduled for this month.\n\n";
            }
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '◀️ Prev Month', 'callback_data' => 'calendar|' . ($month-1) . "|$year"],
                        ['text' => 'Next Month ▶️', 'callback_data' => 'calendar|' . ($month+1) . "|$year"]
                    ],
                    [
                        ['text' => '📌 Set Reminder', 'callback_data' => 'reminder_help']
                    ]
                ]
            ];
            
            editMessage($chat_id, $message_id, $text, json_encode($keyboard), 'HTML');
            answerCallbackQuery($query['id'], "Loading $month $year...");
        }
        elseif ($data === 'reminder_help') {
            sendMessage($chat_id, "📌 <b>Set Reminder</b>\n\nUse /remind \"Movie Name\" YYYY-MM-DD\n\nExample: /remind \"KGF 3\" 2025-04-15", null, 'HTML');
            answerCallbackQuery($query['id'], "Reminder help");
        }
        elseif (strpos($data, 'lang_') === 0) {
            $lang = str_replace('lang_', '', $data);
            $lang_names = [
                'english' => '🇬🇧 English',
                'hindi' => '🇮🇳 हिंदी',
                'hinglish' => '🎭 Hinglish',
                'tamil' => '🇮🇳 தமிழ்',
                'telugu' => '🇮🇳 తెలుగు',
                'malayalam' => '🇮🇳 മലയാളം',
                'kannada' => '🇮🇳 ಕನ್ನಡ'
            ];
            
            editMessage($chat_id, $message_id, "✅ Language set to " . ($lang_names[$lang] ?? $lang), null, 'HTML');
            answerCallbackQuery($query['id'], "Language changed");
        }
        else {
            answerCallbackQuery($query['id'], "❌ Not available");
        }
    }
    
    // Check for pending rejection responses
    $pending_file = 'pending_rejection.json';
    if (isset($update['message']) && file_exists($pending_file)) {
        $pending_data = json_decode(file_get_contents($pending_file), true);
        if ($pending_data && $pending_data['admin_id'] == $user_id) {
            $request_id = $pending_data['request_id'];
            $reason = $text;
            
            $result = $requestSystem->rejectRequest($request_id, $user_id, $reason);
            
            if ($result['success']) {
                $request = $result['request'];
                
                editMessage($pending_data['chat_id'], $pending_data['message_id'], $message['text'] . "\n\n❌ <b>Rejected</b>\n📝 Reason: $reason", null, 'HTML');
                
                sendMessage($chat_id, "✅ Request #$request_id rejected.", null, 'HTML');
                notifyUserAboutRequest($request['user_id'], $request, 'rejected');
            }
            
            unlink($pending_file);
        }
    }
    
    // Daily maintenance check (webhook version - we'll use a lightweight check)
    static $last_maintenance_check = 0;
    if (time() - $last_maintenance_check >= 86400) { // Once per day
        if (date('H') == '03') { // 3 AM
            $csvManager->auto_cleanup();
            log_error("Daily maintenance completed", 'INFO');
        }
        $last_maintenance_check = time();
    }
    
    // Process inline queries
    if (isset($update['inline_query'])) {
        $inline_query = $update['inline_query'];
        $query_id = $inline_query['id'];
        $user_id = $inline_query['from']['id'];
        $query = $inline_query['query'];
        
        $results = [];
        
        if(!empty($query)) {
            $csvManager = CSVManager::getInstance();
            $movies = $csvManager->searchMovies($query);
            
            $i = 0;
            foreach($movies as $movie_name => $data) {
                if($i >= 20) break;
                
                $results[] = [
                    'type' => 'article',
                    'id' => (string)$i,
                    'title' => ucwords($movie_name),
                    'description' => $data['count'] . ' versions',
                    'input_message_content' => [
                        'message_text' => "🎬 <b>" . ucwords($movie_name) . "</b>\n\nSearch in bot for all versions!",
                        'parse_mode' => 'HTML'
                    ],
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                ['text' => '🔍 Search in Bot', 'url' => 'https://t.me/EntertainmentTadkaBot']
                            ]
                        ]
                    ]
                ];
                $i++;
            }
        }
        
        if(empty($results)) {
            $results[] = [
                'type' => 'article',
                'id' => '0',
                'title' => 'No movies found',
                'description' => 'Type a movie name to search',
                'input_message_content' => [
                    'message_text' => "🔍 Type a movie name to search in @EntertainmentTadkaBot",
                    'parse_mode' => 'HTML'
                ]
            ];
        }
        
        apiRequest('answerInlineQuery', [
            'inline_query_id' => $query_id,
            'results' => json_encode($results),
            'cache_time' => 1
        ]);
        
        exit;
    }
    
    http_response_code(200);
    echo "OK";
    exit;
}

// If no update received, show a simple message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(200);
    echo "OK";
} else {
    // Already handled by home page above
}
?>
