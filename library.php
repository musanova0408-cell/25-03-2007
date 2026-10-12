
<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$notice = '';
$error = '';
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$audioOnly = isset($_GET['audio']);
function getBookCover($title) {
    $title = mb_strtolower($title);

    $covers = [
        'атомдық әдеттер' => '9780735211292',
        'atomic habits' => '9780735211292',
        'кішкентай ханзада' => '9780156012195',
        'little prince' => '9780156012195',
        'ұлы гэтсби' => '9780743273565',
        'great gatsby' => '9780743273565',
        'гарри поттер' => '9780590353427',
        'harry potter' => '9780590353427',
        'алхимик' => '9780061122415',
        'alchemist' => '9780061122415',
        'тәкаппарлық пен жаңсақтық' => '9780141439518',
        'pride and prejudice' => '9780141439518',
        'алиса' => '9780141321073',
        'alice' => '9780141321073'
    ];

    foreach ($covers as $keyword => $isbn) {
        if (mb_strpos($title, $keyword) !== false) {
            return 'https://covers.openlibrary.org/b/isbn/' .
                   $isbn . '-L.jpg';
        }
    }

    return 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=500&auto=format&fit=crop';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Бетті жаңартып, қайта көріңіз.';
    } elseif (isset($_POST['support'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (
            $name === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            $message === '' ||
            mb_strlen($message) > 3000
        ) {
            $error = 'Барлық өрісті дұрыс толтыр.';
        } else {
            $stmt = $db->prepare(
                'INSERT INTO support_messages (name, email, message)
                 VALUES (?, ?, ?)'
            );
            $stmt->execute([$name, $email, $message]);
            $notice = 'Хабарламаңыз сәтті жіберілді!';
        }
    }
}

$sql = 'SELECT * FROM books WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (title LIKE ? OR author LIKE ? OR description LIKE ?)';
    $term = "%$search%";
    array_push($params, $term, $term, $term);
}

if ($category !== '') {
    $sql .= ' AND category = ?';
    $params[] = $category;
}

if ($audioOnly) {
    $sql .= ' AND is_audio = 1';
}

$sql .= ' ORDER BY id DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = $db->query(
    'SELECT DISTINCT category FROM books ORDER BY category'
)->fetchAll(PDO::FETCH_COLUMN);

$total = (int)$db->query('SELECT COUNT(*) FROM books')->fetchColumn();
$totalAudio = (int)$db->query(
    'SELECT COUNT(*) FROM books WHERE is_audio = 1'
)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="kk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Library Space — Кітапхана</title>
<link rel="stylesheet" href="style.css">

<style>
:root {
    --purple: #7167f9;
    --dark: #151b33;
    --muted: #858ba1;
}

body { background: #f5f6fc; }

.library-nav {
    background: var(--dark);
    color: white;
    padding: 20px max(22px, calc((100% - 1200px)/2));
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
}

.library-nav strong { font-size: 22px; }
.library-nav strong span { color: #aaa2ff; }

.library-nav a {
    color: white;
    text-decoration: none;
    margin-left: 18px;
    font-size: 14px;
}

.wrap { max-width: 1200px; margin: auto; padding: 30px 22px; }

.hero {
    background: linear-gradient(125deg, #20294a, #7167f9);
    border-radius: 22px;
    color: white;
    padding: 42px;
    margin-bottom: 26px;
}

.hero h1 { font-size: clamp(28px, 4vw, 42px); max-width: 600px; }
.hero p { color: #e1e1ff; line-height: 1.7; }

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 26px;
}

.stat-box, .section-box {
    background: white;
    border: 1px solid #eceef5;
    border-radius: 17px;
    padding: 22px;
}

.stat-box strong { display: block; font-size: 28px; margin-top: 10px; }
.stat-box span { color: var(--muted); font-size: 13px; }

.filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin: 20px 0;
}

.filters input { flex: 1; min-width: 180px; }

.book-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}

.book-card {
    background: white;
    border: 1px solid #e9ebf4;
    border-radius: 16px;
    padding: 14px;
    min-width: 0;
    transition: transform .2s, box-shadow .2s;
}

.book-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px #1b234010;
}

.book-cover {
    height: 180px;
    border-radius: 12px;
    padding: 18px;
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
}

.book-cover small { opacity: .85; }
.book-cover strong { font-size: 21px; line-height: 1.3; }

.book-card h3 { font-size: 16px; margin: 15px 0 7px; }
.book-card p { color: var(--muted); font-size: 13px; line-height: 1.5; }
.book-category { color: var(--purple) !important; font-weight: 700; }

.fav {
    float: right;
    background: #f0efff;
    color: #665ce7;
    padding: 7px 10px;
}

.audio-player {
    width: 100%;
    margin-top: 10px;
}

.support-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 28px;
}

.support-grid textarea {
    width: 100%;
    min-height: 120px;
    padding: 12px;
    border: 1px solid #dfe2ed;
    border-radius: 10px;
    font: inherit;
}

.support-grid input { width: 100%; }

.msg { background: #e1f8eb; color: #16764e; padding: 14px; border-radius: 10px; margin-bottom: 15px; }
.err { background: #ffe8e8; color: #a52b38; padding: 14px; border-radius: 10px; margin-bottom: 15px; }
.muted { color: var(--muted); font-size: 14px; }
.empty { background: white; padding: 35px; border-radius: 15px; }

@media(max-width: 950px) {
    .book-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media(max-width: 650px) {
    .book-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .stats, .support-grid { grid-template-columns: 1fr; }
    .hero { padding: 25px; }
    .library-nav a { margin-left: 0; margin-right: 12px; }
    .book-cover { height: 150px; }
}

@media(max-width: 380px) {
    .book-grid { grid-template-columns: 1fr; }
}

/* Жаңа кітапхана дизайны */
:root {
    --purple: #8875ed;
    --dark: #22203b;
    --muted: #85829c;
    --surface: #ffffff;
    --page: #f7f5ff;
}

body {
    background:
        radial-gradient(ellipse at top left, #eeebff 0, transparent 42%),
        #f8f7fc;
    color: var(--dark);
    font-family: Inter, "Segoe UI", sans-serif;
}

.library-nav {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(29, 27, 53, .96);
    backdrop-filter: blur(14px);
    box-shadow: 0 8px 28px #25204418;
}

.library-nav strong {
    letter-spacing: -1px;
}

.wrap {
    max-width: 1280px;
}

.hero {
    position: relative;
    overflow: hidden;
    padding: 58px 45px;
    border-radius: 30px;
    background: linear-gradient(125deg, #292548, #7768e8 72%, #b5a7ff);
    box-shadow: 0 22px 55px #7465d62e;
}

.hero::after {
    content: "BOOKS";
    position: absolute;
    right: -15px;
    bottom: -28px;
    color: #ffffff12;
    font-size: clamp(65px, 12vw, 150px);
    font-weight: 900;
    letter-spacing: -8px;
    pointer-events: none;
}

.hero h1 {
    position: relative;
    z-index: 1;
    max-width: 650px;
    font-weight: 800;
    letter-spacing: -1.5px;
}

.hero p {
    position: relative;
    z-index: 1;
    max-width: 540px;
    line-height: 1.9;
}

button {
    border: none;
    border-radius: 12px;
    padding: 12px 19px;
    background: linear-gradient(135deg, #9685f5, #7663e7);
    color: white;
    font-weight: 700;
    cursor: pointer;
    transition: .2s ease;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px #7768e844;
}

input, select {
    padding: 13px 15px;
    border: 1px solid #e5e0f4;
    border-radius: 12px;
    background: white;
    color: #292541;
    font: inherit;
    outline: none;
}

input:focus, select:focus, textarea:focus {
    border-color: #9685f5;
    box-shadow: 0 0 0 4px #9685f526;
}

.stat-box, .section-box {
    border: 1px solid #ece8f8;
    border-radius: 22px;
    box-shadow: 0 8px 30px #32265f08;
}

.stat-box strong {
    color: #7663e7;
    font-size: 34px;
}

.book-grid {
    gap: 22px;
}

.book-card {
    position: relative;
    padding: 13px;
    overflow: hidden;
    border: 1px solid #eeeaf7;
    border-radius: 22px;
    box-shadow: 0 7px 25px #32265f08;
    transition: transform .25s, box-shadow .25s;
}

.book-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 18px 35px #49358d18;
}

.book-image {
    position: relative;
    height: 245px;
    overflow: hidden;
    border-radius: 15px;
    background: linear-gradient(145deg, #f0ebff, #fff4fb);
}

.book-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform .35s;
}

.book-card:hover .book-image img {
    transform: scale(1.04);
}

.image-badge {
    position: absolute;
    left: 10px;
    top: 10px;
    padding: 7px 10px;
    border-radius: 20px;
    background: #ffffffed;
    color: #715dd0;
    font-size: 11px;
    font-weight: 800;
}

.book-card h3 {
    margin-top: 16px;
    line-height: 1.5;
    min-height: 46px;
    font-size: 16px;
}

.book-card p {
    line-height: 1.7;
}

.book-category {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 9px;
    background: #f0edff;
    color: #7663d9 !important;
}

.fav {
    border-radius: 50%;
    width: 39px;
    height: 39px;
    padding: 0;
    margin-top: 12px;
    background: #f0edff;
    color: #7663e7;
    font-size: 20px;
}

.audio-player {
    width: 100%;
    accent-color: #8875ed;
}

.support-grid {
    margin-bottom: 40px;
}

@media (max-width: 650px) {
    .hero {
        padding: 30px 23px;
        border-radius: 22px;
    }

    .book-image {
        height: 190px;
    }

    .book-grid {
        gap: 12px;
    }

    .book-card {
        padding: 10px;
    }
}

@media (max-width: 380px) {
    .book-image {
        height: 230px;
    }
}

</style>
</head>
<body>

<nav class="library-nav">
    <strong>LIBRARY<span> SPACE.</span></strong>
    <div>
        <a href="#catalog">Кітаптар</a>
        <a href="library.php?audio=1">Аудиокітаптар</a>
        <a href="#favorites">Таңдаулылар</a>
        <a href="#support">Қолдау</a>
    </div>
</nav>

<main class="wrap">

    <section class="hero">
        <h1>Өзіңе ұнайтын кітабыңды тап</h1>
        <p>Мыңдаған ой, жаңа білім және қызықты оқиғалар әлеміне қош келдің.</p>
        <a href="#catalog">
            <button type="button">Кітаптарды көру →</button>
        </a>
    </section>

    <?php if ($notice): ?>
        <div class="msg"><?= e($notice) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="err"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat-box">
            <span>Барлық кітап</span>
            <strong><?= $total ?></strong>
        </div>
        <div class="stat-box">
            <span>Аудиокітаптар</span>
            <strong><?= $totalAudio ?></strong>
        </div>
        <div class="stat-box">
            <span>Санаттар</span>
            <strong><?= count($categories) ?></strong>
        </div>
    </section>

    <section id="catalog">
        <h2>Кітаптар каталогы</h2>
        <p class="muted">Кітапты атауы, авторы немесе санаты бойынша ізде.</p>

        <form method="get" class="filters">
            <input name="search" value="<?= e($search) ?>"
                   placeholder="Кітап немесе авторды іздеу...">

            <select name="category">
                <option value="">Барлық санат</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>"
                        <?= $category === $cat ? 'selected' : '' ?>>
                        <?= e($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Іздеу</button>
            <a href="library.php"><button type="button">Тазалау</button></a>
        </form>

        <?php if (!$books): ?>
            <div class="empty">Бұл сұраныс бойынша кітап табылмады.</div>
        <?php else: ?>
            <div class="book-grid">
                <?php foreach ($books as $book): ?>
                    <article class="book-card">
                       <div class="book-image">
    <img
        src="<?= e(getBookCover($book['title'])) ?>"
        alt="<?= e($book['title']) ?>"
        loading="lazy"
        onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=500&auto=format&fit=crop';"
    >

    <span class="image-badge">
        <?= (int)$book['is_audio'] === 1 ? 'Аудиокітап' : 'Кітап' ?>
    </span>
</div>
                        <button class="fav" type="button"
                                data-id="<?= (int)$book['id'] ?>"
                                aria-label="Таңдаулыға қосу">♡</button>

                        <h3><?= e($book['title']) ?></h3>
                        <p><?= e($book['author']) ?></p>
                        <p class="book-category"><?= e($book['category']) ?></p>
                        <p><?= e($book['description']) ?></p>

                        <?php if ((int)$book['is_audio'] === 1): ?>
                            <p><strong>Аудио нұсқасы</strong></p>

                            <?php if (!empty($book['audio_file'])): ?>
                                <audio class="audio-player" controls preload="none">
                                    <source src="<?= e($book['audio_file']) ?>" type="audio/mpeg">
                                    Браузер аудионы ойната алмайды.
                                </audio>
                            <?php else: ?>
                                <p class="muted">Аудиофайл әлі жүктелмеген.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section id="favorites" class="section-box" style="margin-top:28px">
        <h2>Таңдаулылар</h2>
        <p class="muted">Жүрек батырмасын басып, ұнаған кітаптарыңды сақта.</p>
        <div id="favorite-list">Әзірге таңдаулы кітаптар жоқ.</div>
    </section>

    <section id="support" class="support-grid">
        <div class="section-box">
            <h2>Қолдау қызметі</h2>
            <p>Сұрағың бар ма? Бізге хабарлама қалдыр.</p>
            <p class="muted">Хабарлама дерекқорға сақталады.</p>
            <p><strong>Жұмыс тәртібі:</strong> хабарлама жіберу формасы.</p>
        </div>

        <div class="section-box">
            <h2>Бізге жаз</h2>
            <form method="post">
                <input type="hidden" name="csrf_token"
                       value="<?= e($_SESSION['csrf_token']) ?>">

                <p><input name="name" placeholder="Аты-жөнің"
                          maxlength="150" required></p>

                <p><input type="email" name="email"
                          placeholder="Email" maxlength="190" required></p>

                <p><textarea name="message" maxlength="3000"
                              placeholder="Сұрағыңды жаз..." required></textarea></p>

                <button type="submit" name="support">Хабарлама жіберу</button>
            </form>
        </div>
    </section>

</main>

<script>
const favoriteButtons = document.querySelectorAll('.fav');
const favoriteList = document.getElementById('favorite-list');
const favorites = new Set(
    JSON.parse(localStorage.getItem('library_favorites') || '[]').map(String)
);

function renderFavorites() {
    favoriteButtons.forEach(button => {
        const id = button.dataset.id;
        button.textContent = favorites.has(id) ? '♥' : '♡';
    });

    const selected = [...favoriteButtons]
        .filter(button => favorites.has(button.dataset.id))
        .map(button => button.closest('.book-card').querySelector('h3').textContent);

    favoriteList.textContent = selected.length
        ? selected.join(' · ')
        : 'Әзірге таңдаулы кітаптар жоқ.';
}

favoriteButtons.forEach(button => {
    button.addEventListener('click', () => {
        const id = button.dataset.id;

        if (favorites.has(id)) {
            favorites.delete(id);
        } else {
            favorites.add(id);
        }

        localStorage.setItem(
            'library_favorites',
            JSON.stringify([...favorites])
        );

        renderFavorites();
    });
});

renderFavorites();
</script>

</body>
</html>
