<?php
require_once dirname(__DIR__) . '/config/config.php';
require_admin();

/* ---------- إجراءات ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($productId > 0 && in_array($action, ['delete', 'toggle'], true)) {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            flash('error', 'المنتج غير موجود.');
            redirect('admin/products.php');
        }

        if ($action === 'delete') {
            if ($product['image'] && str_starts_with($product['image'], 'uploads/products/')) {
                $imageFile = dirname(__DIR__) . '/uploads/products/' . basename($product['image']);
                if (is_file($imageFile)) {
                    @unlink($imageFile);
                }
            }
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$productId]);
            flash('success', 'تم حذف المنتج «' . $product['name'] . '».');
        } else {
            $newStatus = (int) $product['is_active'] === 1 ? 0 : 1;
            $pdo->prepare('UPDATE products SET is_active = ? WHERE id = ?')->execute([$newStatus, $productId]);
            flash('success', $newStatus === 1 ? 'تم إظهار المنتج في المتجر.' : 'تم إخفاء المنتج من المتجر.');
        }
    }

    redirect('admin/products.php');
}

/* ---------- الفلاتر ---------- */
$q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$where = ['1 = 1'];
$params = [];

if ($q !== '') {
    $where[] = 'p.name LIKE ?';
    $params[] = '%' . $q . '%';
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $categoryId;
}
if ($status === 'active') {
    $where[] = 'p.is_active = 1';
} elseif ($status === 'hidden') {
    $where[] = 'p.is_active = 0';
} elseif ($status === 'out') {
    $where[] = 'p.stock = 0';
} elseif ($status === 'low') {
    $where[] = 'p.stock > 0 AND p.stock <= 5';
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE $whereSql
     ORDER BY p.created_at DESC, p.id DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY id ASC')->fetchAll();

$queryParams = [];
if ($q !== '') {
    $queryParams['q'] = $q;
}
if ($categoryId > 0) {
    $queryParams['category'] = $categoryId;
}
if ($status !== '') {
    $queryParams['status'] = $status;
}

$adminTitle = 'المنتجات';
$adminSubtitle = 'إدارة كتالوج المتجر والمخزون';
$adminActive = 'products';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-head">
    <div>
        <span class="result-count"><?= $total ?> منتج</span>
        <?php if ($q !== '' || $categoryId > 0 || $status !== ''): ?>
            <a class="link-more" href="<?= url('admin/products.php') ?>"><?= icon('refresh') ?> مسح الفلاتر</a>
        <?php endif; ?>
    </div>
    <a class="btn btn-accent" href="<?= url('admin/product-form.php') ?>"><?= icon('plus') ?> إضافة منتج جديد</a>
</div>

<form method="get" action="<?= url('admin/products.php') ?>" class="filter-bar">
    <div class="filter-search">
        <?= icon('search') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="ابحث باسم المنتج...">
    </div>

    <select name="category" aria-label="القسم">
        <option value="0">كل الأقسام</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>>
                <?= e($category['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="status" aria-label="الحالة">
        <option value="">كل الحالات</option>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>ظاهر في المتجر</option>
        <option value="hidden" <?= $status === 'hidden' ? 'selected' : '' ?>>مخفي</option>
        <option value="low" <?= $status === 'low' ? 'selected' : '' ?>>مخزون منخفض</option>
        <option value="out" <?= $status === 'out' ? 'selected' : '' ?>>نفد المخزون</option>
    </select>

    <button type="submit" class="btn btn-dark"><?= icon('search') ?> تصفية</button>
</form>

<section class="admin-card">
    <?php if (!$products): ?>
        <div class="admin-empty">
            <p>لا توجد منتجات مطابقة.</p>
            <a class="btn btn-outline btn-sm" href="<?= url('admin/products.php') ?>">عرض كل المنتجات</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table data-table--products">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>القسم</th>
                        <th>السعر</th>
                        <th>المخزون</th>
                        <th>الحالة</th>
                        <th>مميز</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php $stock = (int) $product['stock']; ?>
                        <tr>
                            <td>
                                <div class="cell-product">
                                    <img src="<?= e(media_url($product['image'])) ?>" alt="" loading="lazy">
                                    <div>
                                        <a href="<?= url('admin/product-form.php?id=' . (int) $product['id']) ?>"><?= e($product['name']) ?></a>
                                        <small class="cell-sub" dir="ltr">LM-<?= str_pad((string) $product['id'], 5, '0', STR_PAD_LEFT) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($product['category_name']) ?></td>
                            <td>
                                <strong><?= money($product['price']) ?></strong>
                                <?php if ((float) $product['old_price'] > 0): ?>
                                    <small class="cell-sub"><del><?= money($product['old_price']) ?></del></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="stock-badge <?= $stock === 0 ? 'stock-badge--out' : ($stock <= 5 ? 'stock-badge--low' : 'stock-badge--ok') ?>">
                                    <?= $stock ?> قطعة
                                </span>
                            </td>
                            <td>
                                <?php if ((int) $product['is_active'] === 1): ?>
                                    <span class="status-badge status-delivered">ظاهر</span>
                                <?php else: ?>
                                    <span class="status-badge status-cancelled">مخفي</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) $product['is_featured'] === 1): ?>
                                    <span class="feature-star" title="منتج مميز"><?= icon('star') ?></span>
                                <?php else: ?>
                                    <span class="cell-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="icon-action" href="<?= url('product.php?id=' . (int) $product['id']) ?>" target="_blank" rel="noopener" title="عرض"><?= icon('eye') ?></a>
                                    <a class="icon-action" href="<?= url('admin/product-form.php?id=' . (int) $product['id']) ?>" title="تعديل"><?= icon('edit') ?></a>

                                    <form method="post" action="<?= url('admin/products.php') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                        <button type="submit" class="icon-action" title="<?= (int) $product['is_active'] === 1 ? 'إخفاء' : 'إظهار' ?>">
                                            <?= (int) $product['is_active'] === 1 ? icon('close') : icon('check') ?>
                                        </button>
                                    </form>

                                    <form method="post" action="<?= url('admin/products.php') ?>"
                                          data-confirm="هل تريد حذف المنتج «<?= e($product['name']) ?>» نهائياً؟">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                        <button type="submit" class="icon-action icon-action--danger" title="حذف"><?= icon('trash') ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($pages, $start + 4);
            $start = max(1, $end - 4);
            ?>
            <nav class="pagination" aria-label="صفحات المنتجات">
                <?php if ($page > 1): ?>
                    <a class="page-btn" href="<?= url('admin/products.php?' . http_build_query($queryParams + ['page' => $page - 1])) ?>"><?= icon('right') ?></a>
                <?php endif; ?>
                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a class="page-btn<?= $i === $page ? ' is-active' : '' ?>" href="<?= url('admin/products.php?' . http_build_query($queryParams + ['page' => $i])) ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $pages): ?>
                    <a class="page-btn" href="<?= url('admin/products.php?' . http_build_query($queryParams + ['page' => $page + 1])) ?>"><?= icon('left') ?></a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
