<?php
// install.php — автоматическая установка таблиц
require_once 'config.php';

function installTables() {
    $pdo = getPDO();
    
    // Проверяем, существует ли таблица applications
    $stmt = $pdo->query("SHOW TABLES LIKE 'applications'");
    if ($stmt->rowCount() > 0) {
        return; // Таблицы уже есть, ничего не делаем
    }
    
    // SQL для создания таблиц
    $sql = "
-- Таблица языков программирования
CREATE TABLE IF NOT EXISTS programming_languages (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    PRIMARY KEY (id)
);

-- Вставка языков
INSERT IGNORE INTO programming_languages (name) VALUES 
('Pascal'), ('C'), ('C++'), ('JavaScript'), ('PHP'), 
('Python'), ('Java'), ('Haskell'), ('Clojure'), 
('Prolog'), ('Scala'), ('Go');

-- Таблица заявок
CREATE TABLE IF NOT EXISTS applications (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    fio VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    birth_date DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    biography TEXT,
    contract_agreed TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Таблица связи заявок и языков
CREATE TABLE IF NOT EXISTS application_languages (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT(10) UNSIGNED NOT NULL,
    language_id INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES programming_languages(id) ON DELETE CASCADE
);
";
    
    // Выполняем SQL
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // Ошибка при создании таблиц
        die("Ошибка установки таблиц: " . $e->getMessage());
    }
}

// Запускаем установку
installTables();
?>