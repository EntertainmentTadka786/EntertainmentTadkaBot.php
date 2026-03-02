<?php
// upload_csv.php - Browser se CSV upload karne ke liye
$message = '';
$csv_content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $destination = __DIR__ . '/movies.csv';
        
        // Backup purani file ka
        if (file_exists($destination)) {
            copy($destination, __DIR__ . '/movies.csv.backup_' . date('YmdHis'));
        }
        
        // Upload ki gayi file ko copy karo
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            chmod($destination, 0666);
            $message = '<div style="color: green; background: #e8f5e8; padding: 10px; border-radius: 5px;">✅ CSV file successfully uploaded and permissions set!</div>';
            
            // Cache clear karne ki koshish
            if (file_exists(__DIR__ . '/index.php')) {
                require_once __DIR__ . '/index.php';
                if (class_exists('CSVManager')) {
                    CSVManager::getInstance()->clearCache();
                    $message .= '<div style="color: blue; background: #e8f0fe; padding: 10px; border-radius: 5px; margin-top: 10px;">🧹 Cache cleared!</div>';
                }
            }
            
            // Preview show karo
            $handle = fopen($destination, 'r');
            $header = fgetcsv($handle);
            $csv_content = '<h3>📊 Uploaded CSV Preview (First 10 rows):</h3>';
            $csv_content .= '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
            $csv_content .= '<tr style="background: #f2f2f2;">';
            foreach ($header as $col) {
                $csv_content .= '<th>' . htmlspecialchars($col) . '</th>';
            }
            $csv_content .= '</tr>';
            
            $count = 0;
            while (($row = fgetcsv($handle)) !== FALSE && $count < 10) {
                $csv_content .= '<tr>';
                foreach ($row as $cell) {
                    $csv_content .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
                $csv_content .= '</tr>';
                $count++;
            }
            $csv_content .= '</table>';
            if ($count == 0) {
                $csv_content .= '<p>⚠️ No data rows found!</p>';
            } else {
                $csv_content .= '<p>Total ' . $count . ' rows shown (and more...)</p>';
            }
            fclose($handle);
            
        } else {
            $message = '<div style="color: red; background: #fee; padding: 10px; border-radius: 5px;">❌ Failed to upload file!</div>';
        }
    } else {
        $message = '<div style="color: red; background: #fee; padding: 10px; border-radius: 5px;">❌ Upload error: ' . $file['error'] . '</div>';
    }
}

// Current file check
$current_file = file_exists(__DIR__ . '/movies.csv') ? '✅ Exists' : '❌ Not found';
$current_size = $current_file === '✅ Exists' ? filesize(__DIR__ . '/movies.csv') . ' bytes' : 'N/A';
$current_rows = 0;

if ($current_file === '✅ Exists') {
    $handle = fopen(__DIR__ . '/movies.csv', 'r');
    if ($handle) {
        fgetcsv($handle); // skip header
        while (fgetcsv($handle) !== FALSE) {
            $current_rows++;
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>🎬 CSV Uploader</title>
    <style>
        body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 20px; }
        h1, h2 { text-align: center; }
        .status { background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .btn, input[type=submit] { display: inline-block; padding: 12px 24px; margin: 10px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; font-size: 16px; }
        input[type=file] { padding: 10px; background: white; border-radius: 5px; width: 300px; }
        table { background: white; color: black; border-radius: 5px; }
        th { background: #4CAF50; color: white; }
        td { padding: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 Entertainment Tadka Bot</h1>
        <h2>CSV File Upload Manager</h2>
        
        <div class="status">
            <h3>📊 Current CSV Status:</h3>
            <p><strong>File:</strong> <?php echo $current_file; ?></p>
            <p><strong>Size:</strong> <?php echo $current_size; ?></p>
            <p><strong>Movies Count:</strong> <?php echo $current_rows; ?></p>
            <p><strong>Location:</strong> <?php echo __DIR__; ?>/movies.csv</p>
        </div>
        
        <?php if ($message) echo $message; ?>
        
        <form method="post" enctype="multipart/form-data" style="text-align: center; margin: 30px 0;">
            <h3>📤 Upload New movies.csv</h3>
            <input type="file" name="csv_file" accept=".csv" required>
            <br><br>
            <input type="submit" value="📥 Upload and Replace" class="btn">
        </form>
        
        <?php if ($csv_content) echo $csv_content; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <p><strong>CSV Format Required:</strong></p>
            <code style="background: #333; padding: 10px; display: block; text-align: left;">
                movie_name,message_id,channel_id,quality,size,language,channel_type,date
            </code>
            <p style="margin-top: 20px;">
                <a href="?test=1" class="btn">🔍 Test Bot</a>
                <a href="?setup=1" class="btn">🔗 Set Webhook</a>
                <a href="/" class="btn">🏠 Home</a>
            </p>
        </div>
    </div>
</body>
</html>