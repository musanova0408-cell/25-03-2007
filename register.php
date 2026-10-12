
<form method="post">
    Аты-жөні: <input name="name" required><br><br>
    Email: <input name="email" type="email" required><br><br>
    Құпиясөз: <input name="password" type="password" required><br><br>
    <button name="save">Қосу</button>
</form>

<?php
if (isset($_POST['save'])) {
    $db = new PDO(
        "mysql:host=localhost;dbname=college_library;charset=utf8mb4",
        "root", ""
    );

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = $db->prepare(
        "INSERT INTO users (full_name, email, password_hash)
         VALUES (?, ?, ?)"
    );

    $sql->execute([$name, $email, $password]);

    echo "Пайдаланушы қосылды!";
}
?>
