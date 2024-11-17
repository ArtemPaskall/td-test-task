<?php
// Включаємо налаштування для обробки помилок
ini_set('display_errors', 1);
error_reporting(E_ALL);
sleep(3);
// Перевірка, чи це POST-запит
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Масив для збереження помилок
    $errors = [];

    // Отримання даних із форми
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $time = trim($_POST['select_service'] ?? '');
    $comments = trim($_POST['comments'] ?? ''); 
    $price = trim($_POST['price'] ?? '');
    $email = trim($_POST['email'] ?? ''); 

    // Валідація обов'язкових полів

    // Ім'я
    if (empty($firstName)) {
        $errors[] = "First name is required.";
    } elseif (strlen($firstName) < 3) {
        $errors[] = "First name must be at least 3 characters long.";
    }

    // Прізвище
    if (empty($lastName)) {
        $errors[] = "Last name is required.";
    } elseif (strlen($lastName) < 3) {
        $errors[] = "Last name must be at least 3 characters long.";
    }

    // Телефон
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match('/^\+?[0-9\s\-\(\)]{11,}$/', $phone)) {
        $errors[] = "Invalid phone number format.";
    }

    // Час (поле `select_service`)
    if (empty($time) || $time === 'selecttime') {
        $errors[] = "Please choose a time.";
    }

    // Валідація необов'язкових полів

    // Email (якщо заповнене, перевіряємо формат)
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Перевірка на помилки
    if (!empty($errors)) {
        echo json_encode([
            'status' => 'error',
            'errors' => $errors, // Масив помилок
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'redirectUrl' => 'success_page.php', 
    ]);
    exit;
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.',
    ]);
    exit;
}
