<?php
require_once __DIR__ . '/config/config.php';

/* ---------- المدخلات ---------- */
$q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$categorySlug = isset($_GET['category']) && is_string($_GET['category']) ? trim($_GET['category']) : '';
$sort = isset($_GET['sort']) && is_string($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;

$allowedSorts = ['newest', 'price_asc', 'price_desc', 'discount'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'newest';
}

$activeCategoryData = null;
if ($categorySlug !== '') {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = ? LIMIT 1');
    $stmt->execute([$categorySlug]);
    $activeCategoryData = $stmt->fetch();

    if (!$activeCategoryData) {
        flash('error', 'القسم الذي تبحث عنه غير موجود.');
        redirect('index.php');
    }
}

/* ---------- بناء الاستعلام ---------- */
$where = ['p.is_active = 1'];
$params = [];

if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}

if ($activeCategoryData) {
    $where[] = 'p.category_id = ?';
    $params[] = (int) $activeCategoryData['id'];
}

if ($sort === 'discount') {
    $where[] = 'p.old_price IS NOT NULL AND p.old_price > p.price';
}

$whereSql = implode(' AND ', $where);

$sortSql = match ($sort) {
    'price_asc'  => 'p.price ASC, p.id DESC',
    'price_desc' => 'p.price DESC, p.id DESC',
    'discount'   => '((p.old_price - p.price) / p.old_price) DESC, p.price ASC',
    default      => 'p.created_at DESC, p.id DESC',
};

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE $whereSql
     ORDER BY $sortSql
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

/* ---------- بيانات إضافية ---------- */
$heroProducts = $pdo->query(
    'SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 AND p.is_featured = 1
     ORDER BY p.created_at DESC LIMIT 3'
)->fetchAll();

$categoryCounts = $pdo->query(
    'SELECT category_id, COUNT(*) AS total FROM products WHERE is_active = 1 GROUP BY category_id'
)->fetchAll(PDO::FETCH_KEY_PAIR);

$queryParams = [];
if ($q !== '') {
    $queryParams['q'] = $q;
}
if ($categorySlug !== '') {
    $queryParams['category'] = $categorySlug;
}
if ($sort !== 'newest') {
    $queryParams['sort'] = $sort;
}

$isHome = ($q === '' && $categorySlug === '' && $sort === 'newest' && $page === 1);

if ($q !== '') {
    $heading = 'نتائج البحث';
    $subheading = 'نتائج البحث عن «' . $q . '»';
} elseif ($activeCategoryData) {
    $heading = $activeCategoryData['name'];
    $subheading = 'كل ما تحتاجه من قسم ' . $activeCategoryData['name'];
} elseif ($sort === 'discount') {
    $heading = 'العروض والتخفيضات';
    $subheading = 'أفضل الأسعار على منتجات مختارة';
} else {
    $heading = 'كل المنتجات';
    $subheading = 'تشكيلة مختارة بعناية تناسب إطلالتك وميزانيتك';
}

$activeCategory = $categorySlug;
$pageTitle = $q !== '' ? 'بحث: ' . $q : $heading;
require __DIR__ . '/includes/header.php';
?>

<?php if ($isHome): ?>
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-copy">
                <span class="eyebrow"><?= icon('sparkle') ?> وصل حديثاً — تشكيلة الموسم</span>
                <h1>أناقتك اليومية<br><span class="accent-text">تبدأ من لمسة</span></h1>
                <p>أزياء وإلكترونيات ومستلزمات منزل مختارة بعناية، بأسعار تناسب ميزانيتك وتوصيل سريع لباب بيتك.</p>
                <div class="hero-actions">
                    <a class="btn btn-dark btn-lg" href="#products">تسوّق الآن <?= icon('left') ?></a>
                    <a class="btn btn-outline btn-lg" href="<?= url('index.php?sort=discount') ?>">اكتشف العروض</a>
                </div>
                <ul class="hero-trust">
                    <li><?= icon('truck') ?> توصيل 1–3 أيام</li>
                    <li><?= icon('refresh') ?> إرجاع مجاني</li>
                    <li><?= icon('shield') ?> دفع عند الاستلام</li>
                </ul>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <span class="hero-glow"></span>
                <?php foreach ($heroProducts as $index => $heroProduct): ?>
                    <a class="hero-card hero-card--<?= $index + 1 ?>" href="<?= url('product.php?id=' . (int) $heroProduct['id']) ?>" tabindex="-1">
                        <img src="<?= e(media_url($heroProduct['image'])) ?>" alt="">
                        <span class="hero-card-tag"><?= e($heroProduct['category_name']) ?></span>
                    </a>
                <?php endforeach; ?>
                <span class="hero-sticker">
                    <span>خصم يصل إلى</span>
                    <strong>40%</strong>
                </span>
            </div>
        </div>
    </section>

    <div class="container">
        <ul class="perks">
            <li><?= icon('truck') ?><div><strong>توصيل سريع</strong><span>لجميع المحافظات</span></div></li>
            <li><?= icon('refresh') ?><div><strong>إرجاع سهل</strong><span>خلال 14 يوماً</span></div></li>
            <li><?= icon('shield') ?><div><strong>دفع عند الاستلام</strong><span>تأكد من طلبك أولاً</span></div></li>
            <li><?= icon('phone') ?><div><strong>دعم يومي</strong><span>من 9 صباحاً حتى 6 مساءً</span></div></li>
        </ul>
    </div>

    <section class="section container">
        <div class="section-head">
            <div>
                <span class="eyebrow">تسوّق حسب القسم</span>
                <h2>اختر ما يناسبك</h2>
            </div>
            <a class="link-more" href="#products">كل المنتجات <?= icon('left') ?></a>
        </div>
        <div class="cat-grid">
            <?php foreach ($navCategories as $cat): ?>
                <a class="cat-card" href="<?= url('index.php?category=' . urlencode($cat['slug'])) ?>">
                    <span class="cat-card-icon" aria-hidden="true"><?= e($cat['icon']) ?></span>
                    <strong><?= e($cat['name']) ?></strong>
                    <small><?= (int) ($categoryCounts[$cat['id']] ?? 0) ?> منتج</small>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="section container" id="products">
    <div class="section-head section-head--products">
        <div>
            <span class="eyebrow"><?= $q !== '' ? 'بحث' : 'تشكيلتنا' ?></span>
            <h2><?= e($heading) ?></h2>
            <p><?= e($subheading) ?></p>
        </div>

        <div class="section-tools">
            <span class="result-count"><?= $total ?> منتج</span>
            <form method="get" action="<?= url('index.php') ?>" class="sort-form">
                <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
                <?php if ($categorySlug !== ''): ?><input type="hidden" name="category" value="<?= e($categorySlug) ?>"><?php endif; ?>
                <label for="sort" class="sr-only">ترتيب المنتجات</label>
                <select id="sort" name="sort" onchange="this.form.submit()">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>الأحدث أولاً</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>السعر: من الأقل</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>السعر: من الأعلى</option>
                    <option value="discount" <?= $sort === 'discount' ? 'selected' : '' ?>>أعلى نسبة خصم</option>
                </select>
            </form>
        </div>
    </div>

    <?php if (!$products): ?>
        <div class="empty-state">
            <span class="empty-icon"><?= icon('search') ?></span>
            <h3>لا توجد منتجات مطابقة</h3>
            <p>جرّب كلمة بحث أخرى أو تصفّح كل الأقسام.</p>
            <a class="btn btn-dark" href="<?= url('index.php') ?>">عرض كل المنتجات</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <?php require __DIR__ . '/includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($pages, $start + 4);
            $start = max(1, $end - 4);
            ?>
            <nav class="pagination" aria-label="صفحات المنتجات">
                <?php if ($page > 1): ?>
                    <a class="page-btn" href="<?= url('index.php?' . http_build_query($queryParams + ['page' => $page - 1])) ?>" aria-label="الصفحة السابقة"><?= icon('right') ?></a>
                <?php else: ?>
                    <span class="page-btn is-disabled"><?= icon('right') ?></span>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a class="page-btn<?= $i === $page ? ' is-active' : '' ?>"
                       href="<?= url('index.php?' . http_build_query($queryParams + ['page' => $i])) ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                    <a class="page-btn" href="<?= url('index.php?' . http_build_query($queryParams + ['page' => $page + 1])) ?>" aria-label="الصفحة التالية"><?= icon('left') ?></a>
                <?php else: ?>
                    <span class="page-btn is-disabled"><?= icon('left') ?></span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
