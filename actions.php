
<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    exit('Қате сұраныс. Бетті жаңартып көр.');
}

try {
    if (isset($_POST['add_user'])) {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (
            $name === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($password) < 8
        ) {
            exit('Мәліметтерді тексер. Құпиясөз кемінде 8 таңба болсын.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare(
            "INSERT INTO users
            (full_name, email, password_hash, role, status)
            VALUES (?, ?, ?, 'student', 'active')"
        );

        $stmt->execute([$name, $email, $hash]);
        $_SESSION['notice'] = 'Пайдаланушы қосылды!';
    }

    if (isset($_POST['change_role'])) {
        $id = (int)($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? '';

        if (
            $id < 1 ||
            !in_array($role, ['student', 'librarian', 'admin'], true)
        ) {
            exit('Рөл дұрыс таңдалмаған.');
        }

        $stmt = $db->prepare(
            'UPDATE users SET role = ? WHERE id = ?'
        );
        $stmt->execute([$role, $id]);

        $_SESSION['notice'] = 'Рөл өзгертілді!';
    }

    if (isset($_POST['change_status'])) {
        $id = (int)($_POST['user_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (
            $id < 1 ||
            !in_array($status, ['active', 'inactive'], true)
        ) {
            exit('Мәртебе дұрыс таңдалмаған.');
        }

        $stmt = $db->prepare(
            'UPDATE users SET status = ? WHERE id = ?'
        );
        $stmt->execute([$status, $id]);

        $_SESSION['notice'] = 'Мәртебе өзгертілді!';
    }
} catch (PDOException $e) {
    $_SESSION['notice'] = 'Қате: email қайталануы немесе дерекқор құрылымы сәйкес келмеуі мүмкін.';
}

header('Location: index.php');
exit;
?>
