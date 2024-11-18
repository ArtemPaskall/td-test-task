<?php
// Включаємо налаштування для обробки помилок
ini_set('display_errors', 1);
error_reporting(E_ALL);
sleep(1);

// Встановлюємо заголовки для відповіді
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

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

    // Ініціалізація cURL
    $ch = curl_init($geoApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);  
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

    // Виконання запиту
    $geoResponse = curl_exec($ch);

    // Перевірка на помилки cURL
    if (curl_errno($ch)) {
        // Якщо є помилка при отриманні геоданих, то просто присвоюємо null
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);

    // Повернення результату у вигляді масиву
    return json_decode($geoResponse, true);
}

// Записуємо запит у лог
// Отримання геоданих (замість цього буде присвоєно null, навіть якщо не вдалось отримати геодані)
// $geoData = getGeoData($_SERVER['REMOTE_ADDR']); 
$geoData = getGeoData('8.8.8.8'); 

$requestData = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'body' => $_POST,
    'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'],
    'geoData' => $geoData,
];
logToFile($logFile, ['Request' => $requestData]);

// Перевірка, чи це POST-запит
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $time = trim($_POST['select_service'] ?? '');
    $comments = trim($_POST['comments'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Валідація
    if (empty($firstName)) $errors[] = "First name is required.";
    if (empty($lastName)) $errors[] = "Last name is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (empty($time) || $time === 'selecttime') $errors[] = "Please choose a time.";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (!empty($errors)) {
        $response = ['status' => 'error', 'errors' => $errors];
        logToFile($logFile, ['Response' => $response]);
        echo json_encode($response);
        exit;
    }
   
    // Відповідь
    $response = [
        'status' => 'success',
        'redirectUrl' => 'success_page.php',
    ];
    logToFile($logFile, ['Response' => $response]);
    echo json_encode($response);
    exit;
} else {
    $response = ['status' => 'error', 'message' => 'Invalid request method.'];
    logToFile($logFile, ['Response' => $response]);
    echo json_encode($response);
    exit;
}
?>
