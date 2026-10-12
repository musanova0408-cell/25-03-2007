
<?php
function bookCover($title) {
    $title = mb_strtolower($title, 'UTF-8');

    $covers = [
        'атомдық әдеттер' => '9780735211292',
        'atomic habits' => '9780735211292',
        'кішкентай ханзада' => '9780156012195',
        'little prince' => '9780156012195',
        'гэтсби' => '9780743273565',
        'gatsby' => '9780743273565',
        'гарри поттер' => '9780590353427',
        'harry potter' => '9780590353427',
        'алхимик' => '9780061122415',
        'alchemist' => '9780061122415',
        'pride and prejudice' => '9780141439518',
        'тәкаппарлық пен жаңсақтық' => '9780141439518',
        'алиса' => '9780141321073',
        'alice' => '9780141321073'
    ];

    foreach ($covers as $keyword => $isbn) {
        if (mb_strpos($title, $keyword) !== false) {
            return 'https://covers.openlibrary.org/b/isbn/' .
                $isbn . '-M.jpg?default=false';
        }
    }

    return '';
}
session_start();
require_once 'db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$users = $db->query(
    'SELECT id, full_name, email, role, status
     FROM users ORDER BY id DESC'
)->fetchAll();

$total = count($users);

$active = 0;
$librarians = 0;

foreach ($users as $user) {
    if ($user['status'] === 'active') {
        $active++;
    }

    if ($user['role'] === 'librarian') {
        $librarians++;
    }
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$notice = $_SESSION['notice'] ?? '';
unset($_SESSION['notice']);
?>

<!DOCTYPE html>
<html lang="kk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Library Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <header class="header">
        <h1>Library Admin</h1>
        <p>Колледж кітапханасын басқару жүйесі</p>
    </header>

    <?php if ($notice): ?>
        <div class="panel"><?= e($notice) ?></div>
    <?php endif; ?>

    <section class="cards">
        <div class="card">
            <h3>Барлық пайдаланушы</h3>
            <strong><?= $total ?></strong>
        </div>

        <div class="card">
            <h3>Белсенді пайдаланушы</h3>
            <strong><?= $active ?></strong>
        </div>

        <div class="card">
            <h3>Кітапханашылар</h3>
            <strong><?= $librarians ?></strong>
        </div>
    </section>

    <section class="panel">
        <h2>Жаңа пайдаланушы қосу</h2>

        <form action="actions.php" method="post">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION['csrf_token']) ?>">

            <p>
                <input name="full_name" placeholder="Аты-жөні"
                       maxlength="150" required>
            </p>

            <p>
                <input type="email" name="email"
                       placeholder="Email" maxlength="190" required>
            </p>

            <p>
                <input type="password" name="password"
                       placeholder="Құпиясөз (кемінде 8 таңба)"
                       minlength="8" required>
            </p>

            <button type="submit" name="add_user">
                + Пайдаланушы қосу
            </button>
        </form>
    </section>

    <section class="panel">
        <h2>Пайдаланушылар тізімі</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Аты-жөні</th>
                    <th>Email</th>
                    <th>Рөлі</th>
                    <th>Мәртебесі</th>
                    <th>Әрекет</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= (int)$user['id'] ?></td>
                    <td><?= e($user['full_name']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><?= e($user['role']) ?></td>
                    <td><?= e($user['status']) ?></td>

                    <td>
                        <form action="actions.php" method="post">
                            <input type="hidden" name="csrf_token"
                                   value="<?= e($_SESSION['csrf_token']) ?>">

                            <input type="hidden" name="user_id"
                                   value="<?= (int)$user['id'] ?>">

                            <select name="role">
                                <option value="student"
                                    <?= $user['role'] === 'student' ? 'selected' : '' ?>>
                                    Студент
                                </option>
                                <option value="librarian"
                                    <?= $user['role'] === 'librarian' ? 'selected' : '' ?>>
                                    Кітапханашы
                                </option>
                                <option value="admin"
                                    <?= $user['role'] === 'admin' ? 'selected' : '' ?>>
                                    Әкімші
                                </option>
                            </select>

                            <button type="submit" name="change_role">
                                Рөлді сақтау
                            </button>
                        </form>

                        <form action="actions.php" method="post">
                            <input type="hidden" name="csrf_token"
                                   value="<?= e($_SESSION['csrf_token']) ?>">

                            <input type="hidden" name="user_id"
                                   value="<?= (int)$user['id'] ?>">

                            <?php if ($user['status'] === 'active'): ?>
                                <input type="hidden" name="status"
                                       value="inactive">
                                <button type="submit" name="change_status">
                                    Тоқтату
                                </button>
                            <?php else: ?>
                                <input type="hidden" name="status"
                                       value="active">
                                <button type="submit" name="change_status">
                                    Белсендіру
                                </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$users): ?>
                <tr>
                    <td colspan="6">Әзірге пайдаланушылар жоқ.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
<!-- КІТАПТАР БӨЛІМІ -->
<section class="library-books" id="books">
    <div class="section-heading">
        <span class="section-label">БІЛІМ ӘЛЕМІ</span>
        <h2>Танымал кітаптар</h2>
        <p>Өзіңе ұнайтын кітапты таңда және жаңа әлемді аш.</p>
    </div>

    <div class="book-grid">

        <div class="book-card">
            <img src="https://covers.openlibrary.org/b/id/15237820-M.jpg"
                 alt="Атомдық әдеттер"
                 loading="lazy"
                 onerror="this.onerror=null;this.src='https://covers.openlibrary.org/b/isbn/9780735211292-M.jpg'">
            <span class="book-tag">Өзін-өзі дамыту</span>
            <h3>Атомдық әдеттер</h3>
            <p>Джеймс Клир</p>
            <a href="https://openlibrary.org/search?q=Atomic+Habits" target="_blank" rel="noopener">Кітапты оқу</a>
        </div>

        <div class="book-card">
            <img src="https://covers.openlibrary.org/b/isbn/9780156012195-L.jpg"
                 alt="Кішкентай ханзада" loading="lazy">
            <span class="book-tag">Ертегі</span>
            <h3>Кішкентай ханзада</h3>
            <p>Антуан де Сент-Экзюпери</p>
            <a href="https://openlibrary.org/search?q=The+Little+Prince" target="_blank" rel="noopener">Кітапты оқу</a>
        </div>

        <div class="book-card">
            <img src="https://covers.openlibrary.org/b/id/9367419-M.jpg"
                 alt="Ұлы Гэтсби" loading="lazy">
            <span class="book-tag">Роман</span>
            <h3>Ұлы Гэтсби</h3>
            <p>Фрэнсис Скотт Фицджеральд</p>
            <a href="https://openlibrary.org/search?q=The+Great+Gatsby" target="_blank" rel="noopener">Кітапты оқу</a>
        </div>

        <div class="book-card">
            <img src="https://covers.openlibrary.org/b/isbn/9780590353427-L.jpg"
                 alt="Гарри Поттер" loading="lazy">
            <span class="book-tag">Фэнтези</span>
            <h3>Гарри Поттер</h3>
            <p>Джоан Роулинг</p>
            <a href="https://openlibrary.org/search?q=Harry+Potter" target="_blank" rel="noopener">Кітапты оқу</a>
        </div>

        <div class="book-card">
            <img src="https://covers.openlibrary.org/b/id/15173647-M.jpg"
                 alt="Алхимик" loading="lazy">
            <span class="book-tag">Роман</span>
            <h3>Алхимик</h3>
            <p>Пауло Коэльо</p>
            <a href="https://openlibrary.org/search?q=The+Alchemist+Paulo+Coelho" target="_blank" rel="noopener">Кітапты оқу</a>
        </div>

        <div class="book-card">
            <img src="https://covers.openlibrary.org/b/id/15219808-M.jpg"
                 alt="Тәкаппарлық пен жаңсақтық" loading="lazy">
            <span class="book-tag">Классика</span>
            <h3>Тәкаппарлық пен жаңсақтық</h3>
            <p>Джейн Остин</p>
            <a href="https://openlibrary.org/search?q=Pride+and+Prejudice" target="_blank" rel="noopener">Кітапты оқу</a>
        </div>

        <div class="book-card">
            <img src="https://covers.openlibrary.org/b/isbn/9780141321073-L.jpg"
                 alt="Алиса ғажайыптар елінде" loading="lazy">
            <span class="book-tag">Қиял-ғажайып</span>
            <h3>Алиса ғажайыптар елінде</h3>
            <p>Льюис Кэрролл</p>
            <a href="https://openlibrary.org/search?q=Alice+in+Wonderland" target="_blank" rel="noopener">Кітапты оқу</a>
        </div>

        <div class="book-card">
            <img src="https://covers.openlibrary.org/b/isbn/9780385472579-L.jpg"
                 alt="Әлем әдебиеті" loading="lazy">
            <span class="book-tag">Әдебиет</span>
            <h3>Әлем әдебиетінің таңдаулы шығармалары</h3>
            <p>Классикалық шығармалар</p>
            <a href="https://openlibrary.org/search?q=classic+literature" target="_blank" rel="noopener">Кітапты оқу</a>
        </div>

    </div>
</section>
```

    </div>
</section>
```
</div>
</body>
</html>
