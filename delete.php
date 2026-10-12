
<?php
$db = new PDO(
    "mysql:host=localhost;dbname=college_library;charset=utf8mb4",
    "root", ""
);

$db->exec(
    "UPDATE users SET status='inactive' WHERE id=1"
);

echo "Пайдаланушы өшірілді!";
?>
