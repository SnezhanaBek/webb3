<?php
// Подключение к БД
$host = 'localhost';
$dbname = 'webb3_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$errors = [];

// ФИО
$fio = trim($_POST['fio'] ?? '');
if (empty($fio)) {
    $errors['fio'] = 'ФИО обязательно';
} elseif (mb_strlen($fio) > 150) {
    $errors['fio'] = 'ФИО не длиннее 150 символов';
} elseif (!preg_match('/^[A-Za-zА-Яа-яЁё\s]+$/u', $fio)) {
    $errors['fio'] = 'Только буквы и пробелы';
}

// Телефон
$phone = trim($_POST['phone'] ?? '');
if (empty($phone)) {
    $errors['phone'] = 'Телефон обязателен';
} elseif (!preg_match('/^[\+\(\)\d\s-]{10,20}$/', $phone)) {
    $errors['phone'] = 'Неверный формат телефона';
}

// Email
$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    $errors['email'] = 'Email обязателен';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Неверный формат email';
}

// Дата рождения
$birth_date = $_POST['birth_date'] ?? '';
if (empty($birth_date)) {
    $errors['birth_date'] = 'Дата рождения обязательна';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
    $errors['birth_date'] = 'Неверный формат даты';
}

// Пол
$gender = $_POST['gender'] ?? '';
if (!in_array($gender, ['male', 'female', 'other'])) {
    $errors['gender'] = 'Выберите пол';
}

// Языки
$languages = $_POST['languages'] ?? [];
if (empty($languages)) {
    $errors['languages'] = 'Выберите хотя бы один язык';
}

// Контракт
if (!isset($_POST['contract']) || $_POST['contract'] != 1) {
    $errors['contract'] = 'Подтвердите согласие с контрактом';
}

// Если ошибки — сохраняем в сессию и возвращаем
if (!empty($errors)) {
    session_start();
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $_POST;
    header('Location: index.php');
    exit;
}

// Сохраняем в БД
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO applications 
        (fio, phone, email, birth_date, gender, biography, contract_agreed) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $biography = trim($_POST['biography'] ?? '');
    $contract = isset($_POST['contract']) ? 1 : 0;
    
    $stmt->execute([$fio, $phone, $email, $birth_date, $gender, $biography, $contract]);
    
    $application_id = $pdo->lastInsertId();

    $stmtLang = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($languages as $lang_id) {
        $stmtLang->execute([$application_id, $lang_id]);
    }

    $pdo->commit();
    
    header('Location: index.php?success=1');
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    die("Ошибка при сохранении: " . $e->getMessage());
}
?>