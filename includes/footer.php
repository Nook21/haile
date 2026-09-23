<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-top">
            <div class="footer-brand">
                <span class="footer-wordmark"><?= e(getSetting('site_title','HAILE')) ?></span>
                <span class="footer-tagline"><?= e(getSetting('designer_title','Real Estate Advisor')) ?></span>
                <p class="footer-desc"><?= e(getSetting('site_description','Exceptional Property. Informed Decisions.')) ?></p>
            </div>
            <div class="footer-nav-col">
                <p class="footer-col-label">Navigate</p>
                <a href="<?= BASE_URL ?>/#properties">Properties</a>
                <a href="<?= BASE_URL ?>/#about">About</a>
                <a href="<?= BASE_URL ?>/#services">Services</a>
                <a href="<?= BASE_URL ?>/#insights">Insights</a>
                <a href="<?= BASE_URL ?>/#contact">Contact</a>
            </div>
        </div>
        <div class="footer-bottom">
            <span class="footer-copy"><?= e(getSetting('copyright_text','© 2026 Haile Real Estate Advisor. All Rights Reserved.')) ?></span>
            <div class="footer-social">
                <?php if ($ig = getSetting('social_instagram')): ?>
                <a href="<?= e($ig) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                <?php endif; ?>
                <?php if ($fb = getSetting('social_facebook')): ?>
                <a href="<?= e($fb) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                <?php endif; ?>
                <?php if ($stg = getSetting('social_telegram')): ?>
                <a href="<?= e($stg) ?>" target="_blank" rel="noopener" aria-label="Telegram"><i class="bi bi-telegram"></i></a>
                <?php endif; ?>
                <?php if ($swa = getSetting('social_whatsapp')): ?>
                <a href="<?= e($swa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                <?php endif; ?>
            </div>
            <span class="footer-made">Made with <span class="footer-heart">♥</span></span>
        </div>
        <div class="footer-powered">Powered by <a href="mailto:sayhi@akiyacrm.com" class="footer-powered-link"><i class="bi bi-stars"></i><span>labratsai</span></a></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/assets/js/public.js"></script>
</body>
</html>
