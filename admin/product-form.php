<?php
require_once dirname(__DIR__) . '/config/config.php';
require_admin();

/* ---------- رفع صورة المنتج ---------- */
function save_product_image(array $file): array
{
    $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return [null, 'تعذّر رفع الصورة. حاول مرة أخرى.'];
    }

    if ((int) $file['size'] > 3 * 1024 * 1024) {
        return [null, 'حجم الصورة يجب ألا يتجاوز 3 ميغابايت.'];
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
        return [null, 'صيغة الصورة غير مدعومة. استخدم JPG أو PNG أو WEBP أو GIF.'];
    }

    $uploadDir = dirname(__DIR__) . '/uploads/products';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        return [null, 'تعذّر إنشاء مجلد الصور. تحقق من صلاحيات المجلد.'];
    }

    $fileName = 'product-' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $fileName)) {
        return [null, 'تعذّر حفظ الصورة على السيرفر.'];
    }

    return ['uploads/products/' . $fileName, null];
}

function delete_product_image(?string $path): void
{
    if (!$path || !str_starts_with($path, 'uploads/products/')) {
        return;
    }
    $file = dirname(__DIR__) . '/uploads/products/' . basename($path);
    if (is_file($file)) {
        @unlink($file);
    }
}

/* ---------- بيانات أساسية ---------- */
$categories = $pdo->query('SELECT * FROM categories ORDER BY id ASC')->fetchAll();
$categoryIds = array_map('intval', array_column($categories, 'id'));

if (!$categories) {
    flash('error', 'يجب إضافة قسم واحد على الأقل قبل إضافة المنتجات.');
    redirect('admin/products.php');
}

$productId = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$isEdit = $productId > 0;
$product = null;

if ($isEdit) {
    $product = get_product($productId);
    if (!$product) {
        flash('error', 'المنتج غير موجود.');
        redirect('admin/products.php');
    }
}

$form = [
    'name'        => $product['name'] ?? '',
    'category_id' => (int) ($product['category_id'] ?? $categories[0]['id']),
    'price'       => $product['price'] ?? '',
    'old_price'   => $product['old_price'] ?? '',
    'stock'       => $product['stock'] ?? 0,
    'description' => $product['description'] ?? '',
    'is_featured' => (int) ($product['is_featured'] ?? 0),
    'is_active'   => (int) ($product['is_active'] ?? 1),
];
$currentImage = $product['image'] ?? null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $form = [
        'name'        => trim((string) ($_POST['name'] ?? '')),
        'category_id' => (int) ($_POST['category_id'] ?? 0),
        'price'       => trim((string) ($_POST['price'] ?? '')),
        'old_price'   => trim((string) ($_POST['old_price'] ?? '')),
        'stock'       => trim((string) ($_POST['stock'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_active'   => isset($_POST['is_active']) ? 1 : 0,
    ];

    $name = $form['name'];
    $description = $form['description'];
    $priceRaw = str_replace(',', '.', $form['price']);
    $oldPriceRaw = str_replace(',', '.', $form['old_price']);
    $stockRaw = $form['stock'];

    if (mb_strlen($name) < 3) {
        $errors['name'] = 'اسم المنتج يجب أن يكون 3 أحرف على الأقل.';
    } elseif (mb_strlen($name) > 160) {
        $errors['name'] = 'اسم المنتج طويل جداً (الحد 160 حرفاً).';
    }

    if (!in_array($form['category_id'], $categoryIds, true)) {
        $errors['category_id'] = 'اختر قسماً صحيحاً.';
    }

    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $priceRaw) || (float) $priceRaw <= 0) {
        $errors['price'] = 'أدخل سعراً صحيحاً (مثال: 29.90).';
    }

    $oldPrice = null;
    if ($oldPriceRaw !== '') {
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $oldPriceRaw) || (float) $oldPriceRaw <= 0) {
            $errors['old_price'] = 'أدخل سعراً قديماً صحيحاً أو اتركه فارغاً.';
        } elseif ((float) $oldPriceRaw <= (float) $priceRaw) {
            $errors['old_price'] = 'السعر القديم يجب أن يكون أعلى من السعر الحالي.';
        } else {
            $oldPrice = (float) $oldPriceRaw;
        }
    }

    if (!preg_match('/^\d{1,6}$/', $stockRaw)) {
        $errors['stock'] = 'أدخل كمية مخزون صحيحة (رقم صحيح).';
    }

    if (mb_strlen($description) > 5000) {
        $errors['description'] = 'الوصف طويل جداً (الحد 5000 حرف).';
    }

    $imagePath = $currentImage;

    if (!$errors && isset($_FILES['image'])) {
        [$newImage, $imageError] = save_product_image($_FILES['image']);
        if ($imageError) {
            $errors['image'] = $imageError;
        } elseif ($newImage) {
            $imagePath = $newImage;
        }
    }

    if (!$errors) {
        try {
            if ($isEdit) {
                $stmt = $pdo->prepare(
                    'UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, old_price = ?,
                        stock = ?, image = ?, is_featured = ?, is_active = ? WHERE id = ?'
                );
                $stmt->execute([
                    $form['category_id'],
                    $name,
                    $description !== '' ? $description : null,
                    (float) $priceRaw,
                    $oldPrice,
                    (int) $stockRaw,
                    $imagePath,
                    $form['is_featured'],
                    $form['is_active'],
                    $productId,
                ]);

                if ($imagePath !== $currentImage) {
                    delete_product_image($currentImage);
                }

                flash('success', 'تم تحديث المنتج «' . $name . '» بنجاح.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO products (category_id, name, slug, description, price, old_price, stock, image, is_featured, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $form['category_id'],
                    $name,
                    'p-' . bin2hex(random_bytes(6)),
                    $description !== '' ? $description : null,
                    (float) $priceRaw,
                    $oldPrice,
                    (int) $stockRaw,
                    $imagePath,
                    $form['is_featured'],
                    $form['is_active'],
                ]);

                flash('success', 'تمت إضافة المنتج «' . $name . '» بنجاح.');
            }

            redirect('admin/products.php');
        } catch (PDOException $e) {
            if ($imagePath !== $currentImage) {
                delete_product_image($imagePath);
            }
            error_log('Product save error: ' . $e->getMessage());
            $errors['general'] = 'حدث خطأ أثناء حفظ المنتج. حاول مرة أخرى.';
        }
    }
}

$adminTitle = $isEdit ? 'تعديل منتج' : 'إضافة منتج';
$adminSubtitle = $isEdit ? 'تحديث بيانات «' . $product['name'] . '»' : 'أضف منتجاً جديداً إلى كتالوج المتجر';
$adminActive = 'products';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-head">
    <a class="link-more" href="<?= url('admin/products.php') ?>"><?= icon('right') ?> العودة إلى المنتجات</a>
</div>

<?php if ($errors): ?>
    <div class="form-errors" role="alert">
        <?= icon('close') ?>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('admin/product-form.php') ?>" enctype="multipart/form-data" class="product-form">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $productId ?>"><?php endif; ?>

    <div class="product-form-grid">
        <div class="product-form-main">
            <section class="admin-card">
                <div class="admin-card-head">
                    <div>
                        <h2>بيانات المنتج</h2>
                        <p>المعلومات الأساسية التي تظهر للعميل</p>
                    </div>
                </div>

                <div class="form">
                    <div class="field">
                        <label for="name">اسم المنتج</label>
                        <input id="name" type="text" name="name" value="<?= e($form['name']) ?>"
                               placeholder="مثال: فستان صيفي بنقشة زهور" maxlength="160" required>
                        <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
                    </div>

                    <div class="form-grid">
                        <div class="field">
                            <label for="category_id">القسم</label>
                            <select id="category_id" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>"
                                        <?= (int) $form['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                                        <?= e($category['icon'] . ' ' . $category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?><span class="field-error"><?= e($errors['category_id']) ?></span><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="stock">الكمية في المخزون</label>
                            <input id="stock" type="number" name="stock" value="<?= e($form['stock']) ?>" min="0" max="999999" required>
                            <?php if (isset($errors['stock'])): ?><span class="field-error"><?= e($errors['stock']) ?></span><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="price">السعر الحالي (<?= e(CURRENCY) ?>)</label>
                            <input id="price" type="text" name="price" value="<?= e($form['price']) ?>"
                                   inputmode="decimal" placeholder="29.90" required>
                            <?php if (isset($errors['price'])): ?><span class="field-error"><?= e($errors['price']) ?></span><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="old_price">السعر قبل الخصم <span class="optional">(اختياري)</span></label>
                            <input id="old_price" type="text" name="old_price" value="<?= e($form['old_price']) ?>"
                                   inputmode="decimal" placeholder="45.00">
                            <?php if (isset($errors['old_price'])): ?><span class="field-error"><?= e($errors['old_price']) ?></span><?php endif; ?>
                        </div>
                    </div>

                    <div class="field">
                        <label for="description">وصف المنتج</label>
                        <textarea id="description" name="description" rows="6" maxlength="5000"
                                  placeholder="اكتب وصفاً جذاباً يوضح ميزات المنتج..."><?= e($form['description']) ?></textarea>
                        <?php if (isset($errors['description'])): ?><span class="field-error"><?= e($errors['description']) ?></span><?php endif; ?>
                    </div>
                </div>
            </section>
        </div>

        <div class="product-form-side">
            <section class="admin-card">
                <div class="admin-card-head">
                    <div>
                        <h2>صورة المنتج</h2>
                        <p>المقاس المفضل 600×750</p>
                    </div>
                </div>

                <div class="image-field">
                    <div class="image-preview">
                        <img src="<?= e(media_url($currentImage)) ?>" alt="معاينة الصورة" data-image-preview>
                    </div>
                    <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"
                           data-image-input hidden>
                    <label for="image" class="file-drop">
                        <?= icon('plus') ?>
                        <strong>اختيار صورة</strong>
                        <span>JPG · PNG · WEBP · GIF — حتى 3MB</span>
                    </label>
                    <span class="field-hint">اترك الحقل فارغاً للإبقاء على الصورة الحالية.</span>
                    <?php if (isset($errors['image'])): ?><span class="field-error"><?= e($errors['image']) ?></span><?php endif; ?>
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card-head">
                    <div>
                        <h2>خيارات العرض</h2>
                    </div>
                </div>

                <div class="switch-list">
                    <label class="switch">
                        <input type="checkbox" name="is_active" value="1" <?= (int) $form['is_active'] === 1 ? 'checked' : '' ?>>
                        <span class="switch-track"></span>
                        <span class="switch-text"><strong>ظاهر في المتجر</strong><small>يستطيع العملاء رؤية المنتج وشراؤه</small></span>
                    </label>

                    <label class="switch">
                        <input type="checkbox" name="is_featured" value="1" <?= (int) $form['is_featured'] === 1 ? 'checked' : '' ?>>
                        <span class="switch-track"></span>
                        <span class="switch-text"><strong>منتج مميز</strong><small>يظهر في قسم «وصل حديثاً» بالرئيسية</small></span>
                    </label>
                </div>
            </section>

            <div class="product-form-actions">
                <button type="submit" class="btn btn-accent btn-lg btn-block"><?= icon('check') ?> <?= $isEdit ? 'حفظ التعديلات' : 'إضافة المنتج' ?></button>
                <a class="btn btn-ghost btn-block" href="<?= url('admin/products.php') ?>">إلغاء</a>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
