<?php
session_start();
// Включаємо налаштування для обробки помилок
ini_set('display_errors', 1);
error_reporting(E_ALL);
sleep(1);

// Встановлюємо заголовки для відповіді
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// **Додані заголовки для XSS-захисту**
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Content-Security-Policy: script-src 'self' 'unsafe-inline'; object-src 'none';");
header("X-XSS-Protection: 1; mode=block");

// Файл для журналу
$logFile = 'requests.log';

// Функція для запису в журнал
function logToFile($logFile, $data) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Отримання геоданих
function getGeoData($ip) {
    $geoApiUrl = "https://ipinfo.io/$ip?token=7633c71cf1f808";

    $ch = curl_init($geoApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $geoResponse = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return null;
    }
    curl_close($ch);

    return json_decode($geoResponse, true);
}

// Санітизація для XSS-захисту
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Санітизація виводу
function sanitizeOutput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeOutput($value);
        }
        return $data;
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Логування запитів
$geoData = getGeoData('8.8.8.8');
$requestData = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'body' => $_POST,
    'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'],
    'geoData' => $geoData,
];
logToFile($logFile, ['Request' => $requestData]);

// Підключення до бази даних SQLite
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $response = ['status' => 'error', 'message' => 'Database connection failed.'];
    logToFile($logFile, ['Response' => $response]);
    echo json_encode($response);
    exit;
}

// Обробка POST-запитів
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (empty($csrfToken) || $csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit;
    }

    // Оновлюємо токен після успішної перевірки
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $errors = [];
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $time = sanitizeInput($_POST['select_service'] ?? '');
    $comments = sanitizeInput($_POST['comments'] ?? '');
    $price = sanitizeInput($_POST['select_price'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');

    // Додаткова валідація
    if (empty($firstName) || mb_strlen($firstName) > 50) $errors[] = "Invalid first name.";
    if (empty($lastName) || mb_strlen($lastName) > 50) $errors[] = "Invalid last name.";
    if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) $errors[] = "Invalid phone number.";
    if (empty($time) || $time === 'selecttime') $errors[] = "Please choose a time.";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (mb_strlen($comments) > 500) $errors[] = "Comments are too long.";

    if (!empty($errors)) {
        $response = ['status' => 'error', 'errors' => $errors];
        logToFile($logFile, ['Response' => $response]);
        echo json_encode($response);
        exit;
    }

    // Збереження даних у базі даних
    try {
        // Перевіряємо чи існує користувач
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = :phone");
        $stmt->execute(['phone' => $phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Додаємо нового користувача
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone) VALUES (:first_name, :last_name, :email, :phone)");
            $stmt->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
            ]);
            $userId = $pdo->lastInsertId();
        } else {
            $userId = $user['id'];
        }

        // Додаємо повідомлення
        $stmt = $pdo->prepare("INSERT INTO messages (user_id, message, appointment_time, price) VALUES (:user_id, :message, :appointment_time, :price)");
        $stmt->execute([
            'user_id' => $userId,
            'message' => $comments,
            'appointment_time' => $time,
            'price' => $price,
        ]);

        $response = [
            'status' => 'success',
            'redirectUrl' => '../success/index.html',
        ];
        logToFile($logFile, ['Response' => $response]);
        echo json_encode($response);
        exit;
    } catch (PDOException $e) {
        $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        logToFile($logFile, ['Response' => $response]);
        echo json_encode($response);
        exit;
    }
} else {
    $response = ['status' => 'error', 'message' => 'Invalid request method.'];
    logToFile($logFile, ['Response' => $response]);
    echo json_encode($response);
    exit;
}
?>
