<?php
require_once 'config.php';

session_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: text/html; charset=UTF-8');

$pdo = getPDO();

function saveToCookie($name, $value) {
    setcookie($name, $value, time() + 365 * 24 * 60 * 60, '/');
}

$errors = [];

// Валидация
$fio = trim($_POST['fio'] ?? '');
if (empty($fio)) {
    $errors[] = 'ФИО обязательно';
} elseif (mb_strlen($fio) > 150) {
    $errors[] = 'ФИО не длиннее 150 символов';
} elseif (!preg_match('/^[A-Za-zА-Яа-яЁё\s]+$/u', $fio)) {
    $errors[] = 'ФИО может содержать только буквы и пробелы';
} else {
    saveToCookie('fio_value', $fio);
}

$phone = trim($_POST['phone'] ?? '');
if (empty($phone)) {
    $errors[] = 'Телефон обязателен';
} elseif (!preg_match('/^[\+\(\)\d\s-]{10,20}$/', $phone)) {
    $errors[] = 'Неверный формат телефона';
} else {
    saveToCookie('phone_value', $phone);
}

$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    $errors[] = 'Email обязателен';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Неверный формат email';
} else {
    saveToCookie('email_value', $email);
}

$birth_date = $_POST['birth_date'] ?? '';
if (empty($birth_date)) {
    $errors[] = 'Дата рождения обязательна';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
    $errors[] = 'Неверный формат даты';
} else {
    saveToCookie('birth_date_value', $birth_date);
}

$gender = $_POST['gender'] ?? '';
if (!in_array($gender, ['male', 'female', 'other'])) {
    $errors[] = 'Выберите пол';
} else {
    saveToCookie('gender_value', $gender);
}

$languages = $_POST['languages'] ?? [];
if (empty($languages)) {
    $errors[] = 'Выберите хотя бы один язык программирования';
} else {
    saveToCookie('languages_value', json_encode($languages));
}

$biography = trim($_POST['biography'] ?? '');
saveToCookie('biography_value', $biography);

$contract = isset($_POST['contract']) && $_POST['contract'] == 1 ? 1 : 0;
if ($contract != 1) {
    $errors[] = 'Необходимо подтвердить согласие с контрактом';
} else {
    saveToCookie('contract_value', $contract);
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: index.php');
    exit;
}

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
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['errors'] = ['Ошибка сохранения данных'];
    header('Location: index.php');
    exit;
}
?>