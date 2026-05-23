<?php
require_once 'config.php';
session_start();
header('Content-Type: text/html; charset=UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$pdo = getPDO();

function saveToCookie($name, $value) {
    setcookie($name, $value, time() + 365 * 24 * 60 * 60, '/');
}

$errors = [];

// 1. ФИО
$fio = trim($_POST['fio'] ?? '');
if (empty($fio)) {
    $errors[] = 'ФИО обязательно';
} elseif (strlen($fio) > 150) {  // ← заменил mb_strlen на strlen
    $errors[] = 'ФИО не длиннее 150 символов';
} elseif (!preg_match('/^[A-Za-zА-Яа-яЁё\s]+$/u', $fio)) {
    $errors[] = 'ФИО может содержать только буквы и пробелы';
} else {
    saveToCookie('fio_value', $fio);
}

// 2. Телефон
$phone = trim($_POST['phone'] ?? '');
if (empty($phone)) {
    $errors[] = 'Телефон обязателен';
} elseif (!preg_match('/^[\+\(\)\d\s-]{10,20}$/', $phone)) {
    $errors[] = 'Неверный формат телефона';
} else {
    saveToCookie('phone_value', $phone);
}

// 3. Email
$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    $errors[] = 'Email обязателен';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Неверный формат email';
} else {
    saveToCookie('email_value', $email);
}

// 4. Дата рождения
$birth_date = $_POST['birth_date'] ?? '';
if (empty($birth_date)) {
    $errors[] = 'Дата рождения обязательна';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
    $errors[] = 'Неверный формат даты';
} else {
    saveToCookie('birth_date_value', $birth_date);
}

// 5. Пол
$gender = $_POST['gender'] ?? '';
if (!in_array($gender, ['male', 'female', 'other'])) {
    $errors[] = 'Выберите пол';
} else {
    saveToCookie('gender_value', $gender);
}

// 6. Языки
$languages = $_POST['languages'] ?? [];
if (empty($languages)) {
    $errors[] = 'Выберите хотя бы один язык';
} else {
    saveToCookie('languages_value', json_encode($languages));
}

// 7. Биография
$biography = trim($_POST['biography'] ?? '');
saveToCookie('biography_value', $biography);

// 8. Контракт
$contract = isset($_POST['contract']) && $_POST['contract'] == 1 ? 1 : 0;
if ($contract != 1) {
    $errors[] = 'Подтвердите согласие с контрактом';
} else {
    saveToCookie('contract_value', $contract);
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: index.php');
    exit;
}

// === СОХРАНЕНИЕ В БД ===
try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO applications 
        (fio, phone, email, birth_date, gender, biography, contract_agreed) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([$fio, $phone, $email, $birth_date, $gender, $biography, $contract]);
    $application_id = $pdo->lastInsertId();
    
    $stmtLang = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($languages as $lang_id) {
        $stmtLang->execute([$application_id, $lang_id]);
    }
    
    $pdo->commit();
    
    header('Location: index.php?success=1');
    exit;
    
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['errors'] = ['Ошибка сохранения: ' . $e->getMessage()];
    header('Location: index.php');
    exit;
}
?>