<?php

session_start();

/*
========================
НАСТРОЙКИ БД
========================
*/

$host = "localhost";
$user = "root";
$pass = "";
$db   = "delivery";

$conn = new mysqli($host,$user,$pass,$db);

if($conn->connect_error){
    die("Ошибка подключения к БД");
}

/*
========================
СОЗДАНИЕ ТАБЛИЦ
========================
*/

$conn->query("
CREATE TABLE IF NOT EXISTS users(
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

$conn->query("
CREATE TABLE IF NOT EXISTS orders(
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    sender VARCHAR(255),
    receiver VARCHAR(255),
    tariff VARCHAR(100),
    insurance VARCHAR(100),
    track_code VARCHAR(50),
    status VARCHAR(100) DEFAULT 'Создан',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

/*
========================
СОЗДАНИЕ АДМИНА
========================
*/

$checkAdmin = $conn->query("
SELECT id FROM users
WHERE username='admin'
");

if($checkAdmin->num_rows == 0){

    $adminPass = password_hash(
        "Admin12345",
        PASSWORD_DEFAULT
    );

    $stmt = $conn->prepare("
    INSERT INTO users(
        username,
        password,
        role
    )
    VALUES(
        'admin',
        ?,
        'admin'
    )
    ");

    $stmt->bind_param(
        "s",
        $adminPass
    );

    $stmt->execute();
}

/*
========================
РЕГИСТРАЦИЯ
========================
*/

if(isset($_POST['register'])){

    $username = trim(
        $_POST['username']
    );

    $password = trim(
        $_POST['password']
    );

    if(empty($username) ||
       empty($password))
    {
        die("Заполните все поля");
    }

    if(strtolower($username) == "admin"){
        die("Логин admin зарезервирован");
    }

    $check = $conn->prepare("
    SELECT id
    FROM users
    WHERE username=?
    ");

    $check->bind_param(
        "s",
        $username
    );

    $check->execute();

    $result = $check->get_result();

    if($result->num_rows > 0){
        die("Логин уже занят");
    }

    $hash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $stmt = $conn->prepare("
    INSERT INTO users(
        username,
        password,
        role
    )
    VALUES(
        ?,
        ?,
        'user'
    )
    ");

    $stmt->bind_param(
        "ss",
        $username,
        $hash
    );

    if($stmt->execute()){

        $_SESSION['success'] =
        "Регистрация успешна";

    }else{

        $_SESSION['error'] =
        "Ошибка регистрации";

    }

    header("Location:index.php");
    exit;
}

/*
========================
ВХОД
========================
*/

if(isset($_POST['login'])){

    $username = trim(
        $_POST['username']
    );

    $password = trim(
        $_POST['password']
    );

    $stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE username=?
    ");

    $stmt->bind_param(
        "s",
        $username
    );

    $stmt->execute();

    $userData =
    $stmt->get_result()
         ->fetch_assoc();

    if(
        $userData &&
        password_verify(
            $password,
            $userData['password']
        )
    ){

        $_SESSION['id'] =
        $userData['id'];

        $_SESSION['username'] =
        $userData['username'];

        $_SESSION['role'] =
        $userData['role'];

        header("Location:index.php");
        exit;

    }else{

        $_SESSION['error'] =
        "Неверный логин или пароль";

        header("Location:index.php");
        exit;
    }
}

/*
========================
ВЫХОД
========================
*/

if(isset($_GET['logout'])){

    session_destroy();

    header("Location:index.php");
    exit;
}

/*
========================
ФУНКЦИИ
========================
*/

function isLogged(){

    return isset(
        $_SESSION['id']
    );
}

function isAdmin(){

    return isset(
        $_SESSION['role']
    ) &&
    $_SESSION['role'] == 'admin';
}

?>
