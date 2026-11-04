<?php
/* ============================================================
   دوال مساعدة عامة للموقع
   ============================================================ */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function money($amount): string
{
    return number_format((float) $amount, 2) . ' <span class="cur">' . e(CURRENCY) . '</span>';
}

function format_date(?string $date, string $format = 'd/m/Y'): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '';
}

function old(string $key, string $default = ''): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : $default;
}

/* ---------- رموز SVG ---------- */

function icon(string $name, string $class = 'icon'): string
{
    $icons = [
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/>',
        'user'     => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4"/>',
        'cart'     => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M3 3h2l2.4 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L22 8H6"/>',
        'trash'    => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/>',
        'right'    => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'left'     => '<path d="M19 12H5"/><path d="m11 6-6 6 6 6"/>',
        'up'       => '<path d="M12 19V5"/><path d="m6 11 6-6 6 6"/>',
        'check'    => '<path d="m20 6-11 11-5-5"/>',
        'close'    => '<path d="M18 6 6 18M6 6l12 12"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'menu'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'truck'    => '<path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="18" cy="18" r="1.6"/>',
        'shield'   => '<path d="M12 22s8-3.5 8-10V5l-8-3-8 3v7c0 6.5 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'refresh'  => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>',
        'heart'    => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
        'star'     => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'minus'    => '<path d="M5 12h14"/>',
        'edit'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'eye'      => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'box'      => '<path d="m21 8-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
        'chart'    => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>',
        'dashboard'=> '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'tag'      => '<path d="M20.6 13.4 12 22l-9-9V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'phone'    => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
        'mail'     => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'pin'      => '<path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'list'     => '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/>',
        'sparkle'  => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4"/><path d="m6.3 6.3 2.8 2.8M14.9 14.9l2.8 2.8M17.7 6.3l-2.8 2.8M9.1 14.9l-2.8 2.8"/>',
    ];

    $body = $icons[$name] ?? '';

    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . $body . '</svg>';
}

/* ---------- الحماية من CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        echo '<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8">'
            . '<title>انتهت الجلسة</title>'
            . '<body style="font-family:Tahoma,Arial,sans-serif;display:grid;place-items:center;min-height:100vh;background:#f7f5f2;color:#14151a">'
            . '<div style="text-align:center;background:#fff;padding:32px 40px;border-radius:16px;border:1px solid #e8e3dc">'
            . '<h1 style="font-size:20px;margin:0 0 10px">انتهت صلاحية الجلسة</h1>'
            . '<p style="color:#6b6b73;margin:0 0 18px">رجاءً حدّث الصفحة وحاول مرة أخرى.</p>'
            . '<a href="' . e(url('index.php')) . '" style="display:inline-block;background:#14151a;color:#fff;text-decoration:none;padding:10px 20px;border-radius:10px">العودة للرئيسية</a>'
            . '</div></body></html>';
        exit;
    }
}

/* ---------- الرسائل السريعة ---------- */

function flash(string $type, string $message): void
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($flashes) ? $flashes : [];
}

/* ---------- المستخدم ---------- */

function current_user(): ?array
{
    static $cachedUser = false;

    if ($cachedUser !== false) {
        return $cachedUser;
    }

    if (empty($_SESSION['user_id'])) {
        $cachedUser = null;
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        unset($_SESSION['user_id']);
        $user = null;
    }

    $cachedUser = $user ?: null;
    return $cachedUser;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'admin';
}

function require_login(string $next = ''): void
{
    if (is_logged_in()) {
        return;
    }

    if ($next === '') {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
        $query = $_SERVER['QUERY_STRING'] ?? '';
        $next = $script . ($query !== '' ? '?' . $query : '');
    }

    flash('info', 'سجّل دخولك أولاً لإكمال هذه الخطوة.');
    redirect('login.php?next=' . urlencode($next));
}

function require_admin(): void
{
    if (!is_admin()) {
        if (is_logged_in()) {
            flash('error', 'هذه الصفحة مخصصة لإدارة المتجر فقط.');
        } else {
            flash('info', 'سجّل دخولك بحساب الإدارة للمتابعة.');
        }
        redirect('login.php?next=' . urlencode('admin/index.php'));
    }
}

function safe_next(?string $next): string
{
    if (!is_string($next) || $next === '') {
        return 'index.php';
    }
    if (preg_match(
        '~^(?:[a-z0-9_\-]+\.php|admin/(?:index|products|product-form|orders|order)\.php)(?:\?[a-z0-9_\-=&%+]+)?$~i',
        $next
    )) {
        return $next;
    }
    return 'index.php';
}

/* ---------- المنتجات ---------- */

function media_url(?string $path): string
{
    if (!$path) {
        return url('assets/img/products/placeholder.svg');
    }
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    return url(ltrim($path, '/'));
}

function discount_percent($price, $oldPrice): int
{
    $price = (float) $price;
    $oldPrice = (float) $oldPrice;
    if ($oldPrice <= 0 || $oldPrice <= $price) {
        return 0;
    }
    return (int) round((1 - ($price / $oldPrice)) * 100);
}

function get_product(int $id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p '
        . 'LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    return $product ?: null;
}

/* ---------- السلة (محفوظة في الجلسة) ---------- */

function cart(): array
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function cart_add(int $productId, int $qty = 1): bool
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product || (int) $product['stock'] < 1) {
        return false;
    }

    $cart = cart();
    $current = (int) ($cart[$productId] ?? 0);
    $quantity = $current + max(1, $qty);
    $quantity = min($quantity, (int) $product['stock']);

    $_SESSION['cart'][$productId] = $quantity;
    return true;
}

function cart_set_quantity(int $productId, int $qty): void
{
    if ($qty <= 0) {
        unset($_SESSION['cart'][$productId]);
        return;
    }
    $_SESSION['cart'][$productId] = $qty;
}

function cart_remove(int $productId): void
{
    unset($_SESSION['cart'][$productId]);
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

function cart_count(): int
{
    $cart = cart();
    return array_sum(array_map('intval', $cart));
}

function cart_items(): array
{
    global $pdo;

    $cart = cart();
    if (!$cart) {
        return [];
    }

    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p "
        . "LEFT JOIN categories c ON c.id = p.category_id "
        . "WHERE p.id IN ($placeholders) AND p.is_active = 1");
    $stmt->execute($ids);

    $items = [];
    $found = [];

    foreach ($stmt->fetchAll() as $product) {
        $id = (int) $product['id'];
        $found[$id] = true;
        $qty = (int) ($cart[$id] ?? 0);
        $qty = min($qty, (int) $product['stock']);

        if ($qty < 1) {
            unset($_SESSION['cart'][$id]);
            continue;
        }

        if ($qty !== (int) ($cart[$id] ?? 0)) {
            $_SESSION['cart'][$id] = $qty;
        }

        $items[] = [
            'product'    => $product,
            'qty'        => $qty,
            'line_total' => round((float) $product['price'] * $qty, 2),
        ];
    }

    foreach (array_keys($cart) as $id) {
        if (!isset($found[(int) $id])) {
            unset($_SESSION['cart'][(int) $id]);
        }
    }

    return $items;
}

function cart_subtotal(): float
{
    $total = 0.0;
    foreach (cart_items() as $item) {
        $total += (float) $item['line_total'];
    }
    return round($total, 2);
}

function shipping_cost(float $subtotal): float
{
    if ($subtotal <= 0 || $subtotal >= FREE_SHIPPING_FROM) {
        return 0.0;
    }
    return (float) SHIPPING_FEE;
}

/* ---------- الطلبات ---------- */

function order_statuses(): array
{
    return [
        'pending'    => 'قيد المراجعة',
        'processing' => 'قيد التجهيز',
        'shipped'    => 'تم الشحن',
        'delivered'  => 'تم التسليم',
        'cancelled'  => 'ملغي',
    ];
}

function order_status_label(string $status): string
{
    $statuses = order_statuses();
    return $statuses[$status] ?? $status;
}

function order_status_class(string $status): string
{
    $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    return in_array($status, $allowed, true) ? 'status-' . $status : 'status-pending';
}

/* ---------- محافظات التوصيل ---------- */

function cities(): array
{
    return [
        'عمّان', 'إربد', 'الزرقاء', 'العقبة', 'السلط', 'مادبا', 'جرش',
        'عجلون', 'الكرك', 'معان', 'الطفيلة', 'المفرق',
    ];
}
