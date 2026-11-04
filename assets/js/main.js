/* ============================================================
   متجر لمسة — سكربت الواجهة
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    /* ---------- إغلاق رسائل التنبيه ---------- */
    document.querySelectorAll('.flash').forEach(function (flash) {
        var closeBtn = flash.querySelector('.flash-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () { flash.remove(); });
        }
        window.setTimeout(function () {
            flash.style.transition = 'opacity .3s ease, transform .3s ease';
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-6px)';
            window.setTimeout(function () { flash.remove(); }, 320);
        }, 7000);
    });

    /* ---------- إظهار / إخفاء كلمة المرور ---------- */
    document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-password-toggle'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.classList.toggle('is-on', show);
            btn.setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
        });
    });

    /* ---------- عدّاد الكمية ---------- */
    var cartSubmitTimer = null;

    function submitCartRow(form) {
        if (!form) return;
        window.clearTimeout(cartSubmitTimer);
        cartSubmitTimer = window.setTimeout(function () {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }, 450);
    }

    document.querySelectorAll('.qty-stepper').forEach(function (stepper) {
        var input = stepper.querySelector('input[type="number"]');
        if (!input) return;

        var max = parseInt(stepper.getAttribute('data-max') || input.getAttribute('max') || '9999', 10);
        if (isNaN(max) || max < 1) max = 1;

        function clamp() {
            var value = parseInt(input.value, 10);
            if (isNaN(value) || value < 1) value = 1;
            if (value > max) value = max;
            input.value = value;
            return value;
        }

        stepper.querySelectorAll('[data-qty]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var delta = parseInt(btn.getAttribute('data-qty'), 10) || 0;
                input.value = (parseInt(input.value, 10) || 1) + delta;
                clamp();
                var form = stepper.closest('.cart-row');
                if (form) submitCartRow(form);
            });
        });

        input.addEventListener('change', function () {
            clamp();
            var form = stepper.closest('.cart-row');
            if (form) submitCartRow(form);
        });
    });

    /* ---------- قائمة الحساب ---------- */
    var accountMenus = document.querySelectorAll('.account-menu');
    document.addEventListener('click', function (event) {
        accountMenus.forEach(function (menu) {
            if (menu.open && !menu.contains(event.target)) {
                menu.open = false;
            }
        });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            accountMenus.forEach(function (menu) { menu.open = false; });
        }
    });

    /* ---------- تأكيد الحذف / الإجراءات الحساسة ---------- */
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var message = form.getAttribute('data-confirm') || 'هل أنت متأكد؟';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    /* ---------- معاينة صورة المنتج في لوحة الإدارة ---------- */
    document.querySelectorAll('[data-image-input]').forEach(function (input) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file || file.type.indexOf('image/') !== 0) return;
            var preview = document.querySelector('[data-image-preview]');
            if (preview) {
                preview.src = URL.createObjectURL(file);
            }
        });
    });

    /* ---------- عارض الصور ---------- */
    var lightbox = document.getElementById('lightbox');
    if (lightbox) {
        var lightboxImage = lightbox.querySelector('img');
        var closeLightbox = function () {
            lightbox.hidden = true;
            lightboxImage.src = '';
            document.body.style.overflow = '';
        };

        document.querySelectorAll('.zoomable[data-full]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                lightboxImage.src = trigger.getAttribute('data-full');
                lightbox.hidden = false;
                document.body.style.overflow = 'hidden';
            });
        });

        var lightboxCloseBtn = lightbox.querySelector('.lightbox-close');
        if (lightboxCloseBtn) {
            lightboxCloseBtn.addEventListener('click', closeLightbox);
        }
        lightbox.addEventListener('click', function (event) {
            if (event.target === lightbox) closeLightbox();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !lightbox.hidden) closeLightbox();
        });
    }

    /* ---------- قائمة لوحة الإدارة على الجوال ---------- */
    document.querySelectorAll('[data-admin-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.body.classList.toggle('admin-sidebar-open');
        });
    });
    document.querySelectorAll('[data-admin-close]').forEach(function (el) {
        el.addEventListener('click', function () {
            document.body.classList.remove('admin-sidebar-open');
        });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.body.classList.remove('admin-sidebar-open');
        }
    });
});
