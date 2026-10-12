
<?php
$db = new PDO(
    "mysql:host=localhost;dbname=college_library;charset=utf8mb4",
    "root", ""
);

$users = $db->query("SELECT * FROM users");

foreach ($users as $user) {
    echo htmlspecialchars($user['full_name']) . " - ";
    echo htmlspecialchars($user['email']) . "<br>";
}
?>
