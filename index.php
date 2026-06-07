<?php

require_once "auth.php";

/*
========================
СОЗДАНИЕ ЗАКАЗА
========================
*/

if(isset($_POST['create_order']) && isLogged()){

    $sender = trim($_POST['sender']);
    $receiver = trim($_POST['receiver']);
    $tariff = trim($_POST['tariff']);
    $insurance = trim($_POST['insurance']);

    $track = "NK-" .
             strtoupper(
             substr(
             md5(
             uniqid()
             ),0,10));

    $stmt = $conn->prepare("
    INSERT INTO orders(
        user_id,
        sender,
        receiver,
        tariff,
        insurance,
        track_code
    )
    VALUES(
        ?,?,?,?,?,?
    )
    ");

    $stmt->bind_param(
        "isssss",
        $_SESSION['id'],
        $sender,
        $receiver,
        $tariff,
        $insurance,
        $track
    );

    $stmt->execute();

    $_SESSION['success'] =
    "Заказ создан. Трек: ".$track;

    header("Location:index.php");
    exit;
}

/*
========================
ОБНОВЛЕНИЕ СТАТУСА
========================
*/

if(
    isset($_POST['update_status'])
    && isAdmin()
){

    $id = (int)$_POST['order_id'];

    $status =
    trim($_POST['status']);

    $stmt = $conn->prepare("
    UPDATE orders
    SET status=?
    WHERE id=?
    ");

    $stmt->bind_param(
        "si",
        $status,
        $id
    );

    $stmt->execute();

    header("Location:index.php");
    exit;
}

/*
========================
ПОИСК ТРЕКА
========================
*/

$trackResult = null;

if(isset($_GET['track'])){

    $track =
    trim($_GET['track']);

    $stmt = $conn->prepare("
    SELECT *
    FROM orders
    WHERE track_code=?
    ");

    $stmt->bind_param(
        "s",
        $track
    );

    $stmt->execute();

    $trackResult =
    $stmt->get_result()
         ->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>

<meta charset="UTF-8">

<title>
Наёб-Контора Delivery
</title>

<style>

body{
    font-family:Arial;
    background:#111;
    color:white;
    padding:20px;
}

.card{
    background:#222;
    padding:15px;
    margin:10px 0;
    border-radius:10px;
}

input,select{
    width:100%;
    padding:10px;
    margin:5px 0;
}

button{
    padding:10px;
    cursor:pointer;
}

a{
    color:orange;
}

</style>

</head>
<body>

<h1>
🚚 Наёб-Контора Delivery
</h1>

<?php

if(isset($_SESSION['success'])){

    echo "<div class='card'>"
         .$_SESSION['success'].
         "</div>";

    unset($_SESSION['success']);
}

if(isset($_SESSION['error'])){

    echo "<div class='card'>"
         .$_SESSION['error'].
         "</div>";

    unset($_SESSION['error']);
}
?>

<?php if(!isLogged()): ?>

<div class="card">

<h2>Вход</h2>

<form method="post" action="auth.php">

<input
name="username"
placeholder="Логин">

<input
type="password"
name="password"
placeholder="Пароль">

<button name="login">
Войти
</button>

</form>

</div>

<div class="card">

<h2>Регистрация</h2>

<form method="post" action="auth.php">

<input
name="username"
placeholder="Логин">

<input
type="password"
name="password"
placeholder="Пароль">

<button name="register">
Регистрация
</button>

</form>

</div>

<?php else: ?>

<div class="card">

Привет,
<b>
<?=htmlspecialchars(
$_SESSION['username']
)?>
</b>

|

<a href="auth.php?logout=1">
Выйти
</a>

</div>

<div class="card">

<h2>Тарифы</h2>

<ul>
<li>Эконом — 0.25€</li>
<li>Стандарт — 0.5€</li>
<li>Экспресс — 1.8€</li>
<li>VIP — 2€</li>
</ul>

</div>

<div class="card">

<h2>Страхование</h2>

<ul>
<li>Нет страховки</li>
<li>Базовая</li>
<li>Премиум</li>
<li>VIP</li>
</ul>

</div>

<div class="card">

<h2>Создать заказ</h2>

<form method="post">

<input
name="sender"
placeholder="Отправитель"
required>

<input
name="receiver"
placeholder="Получатель"
required>

<select name="tariff">

<option>
Эконом
</option>

<option>
Стандарт
</option>

<option>
Экспресс
</option>

<option>
VIP
</option>

</select>

<select name="insurance">

<option>
Нет
</option>

<option>
Базовая
</option>

<option>
Премиум
</option>

<option>
VIP
</option>

</select>

<button
name="create_order">

Создать заказ

</button>

</form>

</div>

<div class="card">

<h2>Мои заказы</h2>

<?php

$res = $conn->query(
"SELECT * FROM orders
WHERE user_id=".$_SESSION['id']."
ORDER BY id DESC"
);

while(
$order =
$res->fetch_assoc()
){
?>

<div class="card">

Трек:
<b>
<?=$order['track_code']?>
</b>

<br>

Статус:
<?=$order['status']?>

</div>

<?php } ?>

</div>

<?php endif; ?>

<div class="card">

<h2>Отслеживание</h2>

<form method="get">

<input
name="track"
placeholder="Введите трек">

<button>
Найти
</button>

</form>

<?php

if($trackResult){

echo "
<hr>

Трек:
<b>
".$trackResult['track_code']."
</b>

<br><br>

Отправитель:
".$trackResult['sender']."

<br><br>

Получатель:
".$trackResult['receiver']."

<br><br>

Статус:
<b>
".$trackResult['status']."
</b>
";
}
?>

</div>

<?php if(isAdmin()): ?>

<div class="card">

<h2>
Панель администратора
</h2>

<?php

$orders =
$conn->query(
"SELECT * FROM orders
ORDER BY id DESC"
);

while(
$row =
$orders->fetch_assoc()
){
?>

<div class="card">

ID:
<?=$row['id']?>

<br>

Трек:
<?=$row['track_code']?>

<br>

Статус:
<?=$row['status']?>

<form method="post">

<input
type="hidden"
name="order_id"
value="<?=$row['id']?>">

<select
name="status">

<option>
Создан
</option>

<option>
На складе
</option>

<option>
В пути
</option>

<option>
На таможне
</option>

<option>
Доставлен
</option>

</select>

<button
name="update_status">

Обновить

</button>

</form>

</div>

<?php } ?>

</div>

<?php endif; ?>

</body>
</html>
