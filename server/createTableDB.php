<?php
try {
    // Connect to SQLite (database is created automatically if it doesn't exist)
    $pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Database connected successfully!<br>";

    // SQL to create the `users` table with a UNIQUE constraint on the `phone` field
    $createUsersTable = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            email TEXT,
            phone TEXT NOT NULL UNIQUE
        );
    ";
    $pdo->exec($createUsersTable);
    echo "Table `users` created successfully!<br>";

    // SQL to create the `messages` table
    $createMessagesTable = "
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            message TEXT,
            appointment_time TEXT,
            price TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id)
        );
    ";
    $pdo->exec($createMessagesTable);
    echo "Table `messages` created successfully!<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
