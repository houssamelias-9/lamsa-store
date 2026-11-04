    </main>

    <div class="container">
        <footer class="site-footer">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a class="brand brand--footer" href="<?= url('index.php') ?>">
                        <span class="brand-mark">ل</span>
                        <span class="brand-copy">
                            <span class="brand-name"><?= e(SITE_NAME) ?><span class="brand-dot">.</span></span>
                            <span class="brand-tagline"><?= e(SITE_TAGLINE) ?></span>
                        </span>
                    </a>
                    <p>متجرك العربي للأزياء والإلكترونيات ومستلزمات المنزل. جودة مختارة بعناية وأسعار تناسب ميزانيتك.</p>
                    <div class="footer-badges">
                        <span class="pill"><?= icon('shield') ?> دفع آمن</span>
                        <span class="pill"><?= icon('refresh') ?> إرجاع خلال 14 يوم</span>
                    </div>
                </div>

                <div class="footer-col">
                    <h3>روابط سريعة</h3>
                    <ul>
                        <li><a href="<?= url('index.php') ?>">الرئيسية</a></li>
                        <li><a href="<?= url('index.php?sort=newest') ?>">وصل حديثاً</a></li>
                        <li><a href="<?= url('index.php?sort=discount') ?>">العروض والتخفيضات</a></li>
                        <li><a href="<?= url('cart.php') ?>">سلة التسوق</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3>حسابك</h3>
                    <ul>
                        <?php if ($me): ?>
                            <li><a href="<?= url('profile.php') ?>">بيانات حسابي</a></li>
                            <li><a href="<?= url('orders.php') ?>">طلباتي</a></li>
                        <?php else: ?>
                            <li><a href="<?= url('login.php') ?>">تسجيل الدخول</a></li>
                            <li><a href="<?= url('register.php') ?>">إنشاء حساب جديد</a></li>
                        <?php endif; ?>
                        <li><a href="<?= url('cart.php') ?>">متابعة التسوق</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3>تواصل معنا</h3>
                    <ul class="footer-contact">
                        <li><?= icon('phone') ?> <span dir="ltr">+962 79 000 0000</span></li>
                        <li><?= icon('mail') ?> <span dir="ltr">hello@lamsa.com</span></li>
                        <li><?= icon('pin') ?> عمّان، الأردن</li>
                        <li><?= icon('clock') ?> السبت – الخميس، 9 ص – 6 م</li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© <?= date('Y') ?> <?= e(SITE_NAME) ?> — جميع الحقوق محفوظة.</span>
                <span class="footer-payments">
                    <span class="pay-chip">الدفع عند الاستلام</span>
                    <span class="pay-chip">تحويل بنكي</span>
                </span>
            </div>
        </footer>
    </div>

<div class="lightbox" id="lightbox" hidden>
    <button type="button" class="lightbox-close" aria-label="إغلاق"><?= icon('close') ?></button>
    <img src="" alt="">
</div>

<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
