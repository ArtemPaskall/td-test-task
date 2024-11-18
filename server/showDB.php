<?php
try {
    // Підключення до бази даних
    $pdo = new PDO('sqlite:database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Вибір даних із таблиці users
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Users Table:</h3>";
    echo "<pre>" . print_r($users, true) . "</pre>";

    // Вибір даних із таблиці messages
    $stmt = $pdo->query("SELECT * FROM messages");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Messages Table:</h3>";
    echo "<pre>" . print_r($messages, true) . "</pre>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
