?<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$contactSuccess = false;
$contactErrors  = [];
$contactData    = ['name'=>'','phone'=>'','email'=>'','interest'=>'','message'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
    verifyCsrf();
    $contactData['name']     = trim($_POST['name']    ?? '');
    $contactData['phone']    = trim($_POST['phone']   ?? '');
    $contactData['email']    = trim($_POST['email']   ?? '');
    $contactData['interest'] = trim($_POST['interest']?? '');
    $contactData['message']  = trim($_POST['message'] ?? '');

    if (strlen($contactData['name']) < 2)                          $contactErrors[] = 'Please enter your name.';
    if (!filter_var($contactData['email'], FILTER_VALIDATE_EMAIL)) $contactErrors[] = 'Please enter a valid email.';
    if (strlen($contactData['message']) < 10)                      $contactErrors[] = 'Please enter a message (at least 10 characters).';

    if (!$contactErrors) {
        $toEmail     = getSetting('contact_email');
        $fromName    = getSetting('smtp_from_name', 'Haile Estate');
        $fromEmail   = getSetting('smtp_from_email', 'noreply@haile.local');
        $smtpEnabled = getSetting('smtp_enabled', '0') === '1';
        $subject     = 'Property Enquiry from ' . $contactData['name'];
        $plainBody   = "Name: {$contactData['name']}\nPhone: {$contactData['phone']}\nEmail: {$contactData['email']}\nInterest: {$contactData['interest']}\n\nMessage:\n{$contactData['message']}";
        $sent = false;
        if ($toEmail) {
            if ($smtpEnabled) {
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = getSetting('smtp_host');
                    $mail->SMTPAuth   = true;
                    $mail->Username   = getSetting('smtp_username');
                    $mail->Password   = getSetting('smtp_password');
                    $enc = getSetting('smtp_encryption', 'tls');
                    $mail->SMTPSecure = $enc === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = (int) getSetting('smtp_port', '587');
                    $mail->CharSet    = 'UTF-8';
                    $mail->setFrom($fromEmail, $fromName);
                    $mail->addAddress($toEmail);
                    $mail->addReplyTo($contactData['email'], $contactData['name']);
                    $mail->Subject = $subject;
                    $mail->Body    = $plainBody;
                    $mail->send();
                    $sent = true;
                } catch (Exception $e) {
                    error_log('Contact form mail error: ' . $e->getMessage());
                }
            } else {
                $headers = "From: {$fromName} <{$fromEmail}>\r\nReply-To: {$contactData['email']}\r\n";
                $sent = mail($toEmail, $subject, $plainBody, $headers);
            }
        } else {
            $sent = true;
        }
        if ($sent) {
            $contactSuccess = true;
            $contactData    = ['name'=>'','phone'=>'','email'=>'','interest'=>'','message'=>''];
        } else {
            $contactErrors[] = 'Sorry, your message could not be sent. Please try again.';
        }
    }
}

$projects      = getPublishedProjects();
$categories    = getCategories();
$services      = getActiveServices();
$about         = getAbout();
$insights      = getPublishedInsights(3);
$testimonials  = getActiveTestimonials();

$heroTitle    = getSetting('hero_title',    'HAILE');
$heroSubtitle = getSetting('hero_subtitle', "Where exceptional property meets informed decisions. Personalized consultation and investment guidance across Ethiopia's finest residential and commercial real estate.");
$heroCtaText  = getSetting('hero_cta_text', 'View Properties');
$heroEyebrow  = getSetting('hero_eyebrow',  'Senior Property Consultant / Managing Director');
$heroBg       = getSetting('hero_bg_image', '');

$pageTitle = getSetting('site_title');
include __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="hero" id="top">
    <?php if ($heroBg): ?>
    <img src="<?= e(assetUrl($heroBg)) ?>" alt="Hero background" class="hero-bg-img" loading="eager" fetchpriority="high" decoding="async">
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-body">
        <div class="hero-inner">
            <p class="hero-eyebrow hero-rise" style="animation-delay:0.2s"><?= e($heroEyebrow) ?></p>
            <h1 class="hero-title hero-rise" style="animation-delay:0.35s">
                <span class="hero-title-typed" id="heroTyped"></span><span class="hero-title-cursor" id="heroCursor">|</span><span class="hero-title-sub"><?= e(getSetting('designer_title','REAL ESTATE ADVISOR')) ?></span>
            </h1>
            <script>window.HERO_TITLE = <?= json_encode(getSetting('site_title','HAILE')) ?>;</script>
            <p class="hero-sub hero-rise" style="animation-delay:0.5s"><?= e($heroSubtitle) ?></p>
            <div class="hero-actions hero-rise" style="animation-delay:0.65s">
                <a href="#properties" class="btn-hero-primary"><?= e($heroCtaText) ?></a>
                <a href="#contact" class="btn-hero-ghost">Book a Consultation</a>
            </div>
            <div class="hero-scroll-hint hero-rise" style="animation-delay:0.9s">
                <span class="hero-scroll-text">Scroll to discover</span>
                <span class="hero-scroll-arrow">&#8595;</span>
            </div>
        </div>
    </div>
</section>

<!-- PROPERTIES -->
<section class="section properties-section" id="properties">
    <div class="section-inner">
        <div class="section-head">
            <div class="section-head-left">
                <p class="section-eyebrow">Selected Properties</p>
                <h2 class="section-title">Find your<br><em>next address.</em></h2>
                <p class="section-sub">Exceptional spaces. Carefully selected opportunities.</p>
            </div>
            <?php if ($categories): ?>
            <div class="filter-bar" id="filterBar">
                <button class="filter-btn active" data-cat="all">All</button>
                <?php foreach ($categories as $cat): ?>
                <button class="filter-btn" data-cat="<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($projects): ?>
        <div class="prop-grid" id="propGrid">
            <?php foreach ($projects as $i => $p): ?>
            <article class="prop-card fade-up" data-cat="<?= e($p['category_slug'] ?? '') ?>" data-index="<?= $i ?>" style="transition-delay:<?= min($i*0.08,0.4) ?>s">
                <a href="<?= BASE_URL ?>/property?slug=<?= e($p['slug']) ?>" class="prop-card-link">
                    <div class="prop-card-img">
                        <?php if ($p['cover_image']): ?>
                        <img src="<?= e(assetUrl($p['cover_image'])) ?>" alt="<?= e($p['title']) ?>" <?= $i<2?'loading="eager"':'loading="lazy"' ?>>
                        <?php else: ?>
                        <div class="prop-card-img-placeholder"><i class="bi bi-building"></i></div>
                        <?php endif; ?>
                        <span class="prop-card-num"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></span>
                        <?php if ($p['category_name']): ?>
                        <span class="prop-card-badge"><?= e($p['category_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="prop-card-body">
                        <div class="prop-card-header-row">
                            <span class="prop-card-index"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?> &middot; <?= e($p['category_name'] ?? 'Property') ?></span>
                        </div>
                        <h3 class="prop-card-title"><?= e($p['title']) ?></h3>
                        <?php if (!empty($p['location'])): ?>
                        <p class="prop-card-location"><i class="bi bi-geo-alt"></i> <?= e($p['location']) ?></p>
                        <?php endif; ?>
                        <?php if ($p['short_description']): ?>
                        <p class="prop-card-desc"><?= e($p['short_description']) ?></p>
                        <?php endif; ?>
                        <div class="prop-card-footer">
                            <?php if (!empty($p['bedrooms']) || !empty($p['area'])): ?>
                            <div class="prop-card-specs-row">
                                <span class="prop-spec-label">Details</span>
                                <span class="prop-spec-val">
                                    <?php $specs = []; ?>
                                    <?php if (!empty($p['bedrooms'])): $specs[] = $p['bedrooms'].' Bedrooms'; endif; ?>
                                    <?php if (!empty($p['bathrooms'])): $specs[] = $p['bathrooms'].' Bath'; endif; ?>
                                    <?php if (!empty($p['area'])): $specs[] = $p['area']; endif; ?>
                                    <?= implode(' &middot; ', array_map('e', $specs)) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <div class="prop-card-specs-row">
                                <span class="prop-spec-label">Price</span>
                                <span class="prop-spec-val prop-spec-price"><?= !empty($p['price']) ? e($p['price']) : 'Price on request' ?></span>
                            </div>
                            <span class="prop-card-cta">View Property <i class="bi bi-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
        </div>
        <div class="prop-view-more-wrap" id="propViewMoreWrap">
            <button class="prop-view-more-btn" id="propViewMoreBtn">View More <i class="bi bi-arrow-down"></i></button>
        </div>
        <?php else: ?>
        <p class="no-content">No properties published yet.</p>
        <?php endif; ?>
    </div>
</section>

<!-- PROPERTY CATEGORIES -->
<?php if ($categories): ?>
<section class="section categories-section">
    <div class="section-inner">
        <div class="section-head">
            <div class="section-head-left">
                <p class="section-eyebrow">Property Categories</p>
                <h2 class="section-title">Explore by <em>opportunity.</em></h2>
            </div>
        </div>
        <div class="cat-grid">
            <?php foreach ($categories as $i => $cat): ?>
            <div class="cat-tile fade-up" style="transition-delay:<?= min($i*0.08,0.32) ?>s" onclick="filterByCategory('<?= e($cat['slug']) ?>')"
                 role="button" tabindex="0" aria-label="Filter by <?= e($cat['name']) ?>">
                <div class="cat-tile-img-wrap">
                    <?php if (!empty($cat['image'])): ?>
                    <img src="<?= e(assetUrl($cat['image'])) ?>" alt="<?= e($cat['name']) ?>" class="cat-tile-bg" loading="lazy">
                    <?php endif; ?>
                    <div class="cat-tile-overlay"></div>
                </div>
                <div class="cat-tile-content">
                    <span class="cat-tile-count"><?= $cat['project_count'] ?? 0 ?> <?= ($cat['project_count'] ?? 0) == 1 ? 'property' : 'properties' ?></span>
                    <h3 class="cat-tile-name"><?= e($cat['name']) ?></h3>
                    <?php if (!empty($cat['description'])): ?>
                    <p class="cat-tile-desc"><?= e($cat['description']) ?></p>
                    <?php endif; ?>
                    <span class="cat-tile-cta">Explore <i class="bi bi-arrow-right"></i></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ABOUT -->
<?php if ($about): ?>
<section class="section about-section" id="about">
    <div class="section-inner">
        <div class="about-grid">
            <div class="about-img-wrap" id="aboutImg">
                <?php $profileImg = $about['profile_image'] ?: getSetting('profile_image'); ?>
                <?php if ($profileImg): ?>
                <img src="<?= e(assetUrl($profileImg)) ?>" alt="<?= e($about['name']) ?>" loading="lazy">
                <?php else: ?>
                <div class="about-img-placeholder"><i class="bi bi-person"></i></div>
                <?php endif; ?>
                <div class="about-img-label">
                    <span><?= e($about['title'] ?? 'Senior Property Consultant') ?></span>
                </div>
            </div>
            <div class="about-content" id="aboutContent">
                <p class="section-eyebrow">The advisor behind the property</p>
                <h2 class="section-title"><?= e($about['name']) ?></h2>
                <p class="about-role"><?= e($about['title'] ?? 'Senior Property Consultant / Managing Director') ?></p>
                <?php if ($about['biography']): ?>
                <div class="about-bio">
                    <?php foreach (array_filter(explode("\n", $about['biography'])) as $para): ?>
                    <p class="about-bio-p"><?= e(trim($para)) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="about-tags">
                    <span>Property consultation</span>
                    <span>Real estate investment</span>
                    <span>Property sourcing</span>
                    <span>Client representation</span>
                    <span>Market guidance</span>
                    <span>Residential &amp; commercial</span>
                </div>
                <a href="#contact" class="about-meet-link">Meet <?= e(explode(' ', $about['name'])[0]) ?> <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- SERVICES -->
<?php if ($services): ?>
<section class="section services-section" id="services">
    <div class="section-inner">
        <div class="section-head section-head--center">
            <p class="section-eyebrow">Services</p>
            <h2 class="section-title">Considered advice <em>at every stage.</em></h2>
        </div>
        <div class="services-grid">
            <?php foreach ($services as $i => $svc): ?>
            <div class="service-card fade-up" style="transition-delay:<?= min($i*0.07,0.35) ?>s">
                <div class="service-card-icon"><i class="bi <?= e($svc['icon'] ?: 'bi-building') ?>"></i></div>
                <div class="service-card-num"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></div>
                <h3 class="service-card-title"><?= e($svc['title']) ?></h3>
                <?php if ($svc['description']): ?>
                <p class="service-card-desc"><?= e($svc['description']) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- WHY WORK WITH HAILE -->
<section class="why-section">
    <div class="why-inner">
        <p class="why-eyebrow">Why work with <?= e(explode(' ', getSetting('site_title','Haile'))[0]) ?></p>
        <h2 class="why-heading">More than property.<br><em>It's about the right decision.</em></h2>
        <div class="why-rule"></div>
        <div class="why-grid">
            <div class="why-item fade-up" style="transition-delay:0s">
                <span class="why-num">01</span>
                <h3 class="why-title">Personalized Service</h3>
                <p class="why-desc">Every client receives tailored property guidance.</p>
            </div>
            <div class="why-item fade-up" style="transition-delay:0.08s">
                <span class="why-num">02</span>
                <h3 class="why-title">Market Insight</h3>
                <p class="why-desc">Understand opportunities before making major property decisions.</p>
            </div>
            <div class="why-item fade-up" style="transition-delay:0.16s">
                <span class="why-num">03</span>
                <h3 class="why-title">Trust &amp; Transparency</h3>
                <p class="why-desc">Clear communication throughout the consultation and transaction process.</p>
            </div>
            <div class="why-item fade-up" style="transition-delay:0.24s">
                <span class="why-num">04</span>
                <h3 class="why-title">Strategic Thinking</h3>
                <p class="why-desc">Property recommendations based on long-term objectives, not simply listings.</p>
            </div>
        </div>
    </div>
</section>

<!-- INVESTMENT -->
<section class="section investment-section" id="investment">
    <?php $invBg = getSetting('investment_bg_image',''); ?>
    <?php if ($invBg): ?>
    <img src="<?= e(assetUrl($invBg)) ?>" alt="Investment background" class="investment-bg-img">
    <?php endif; ?>
    <div class="investment-overlay"></div>
    <div class="section-inner investment-inner">
        <div class="investment-content">
            <p class="section-eyebrow investment-eyebrow">Investment</p>
            <h2 class="section-title investment-title">Invest with<br><em>perspective.</em></h2>
            <p class="investment-body"><?= nl2br(e(getSetting('investment_section_body', "Real estate decisions require more than finding a property. They require understanding location, opportunity, value and long-term potential.\n\nHaile Real Estate Advisor helps clients approach property decisions with a strategic perspective."))) ?></p>
            <a href="#contact" class="btn-hero-ghost investment-cta">Discuss an Investment</a>
        </div>
    </div>
</section>

<!-- CLIENT JOURNEY -->
<section class="journey-section">
    <div class="journey-container">
        <p class="journey-eyebrow fade-up">The client journey</p>
        <h2 class="journey-heading fade-up">Four steps, clearly led.</h2>
        <div class="journey-steps">
            <div class="journey-step fade-up" style="transition-delay:0s">
                <div class="journey-step-top">
                    <span class="journey-num">01</span>
                    <span class="journey-connector"></span>
                </div>
                <h3 class="journey-title">Discover</h3>
                <p class="journey-desc">Understand your requirements and objectives.</p>
            </div>
            <div class="journey-step fade-up" style="transition-delay:0.08s">
                <div class="journey-step-top">
                    <span class="journey-num">02</span>
                    <span class="journey-connector"></span>
                </div>
                <h3 class="journey-title">Select</h3>
                <p class="journey-desc">Identify properties aligned with your goals.</p>
            </div>
            <div class="journey-step fade-up" style="transition-delay:0.16s">
                <div class="journey-step-top">
                    <span class="journey-num">03</span>
                    <span class="journey-connector"></span>
                </div>
                <h3 class="journey-title">Evaluate</h3>
                <p class="journey-desc">Review property details and investment considerations.</p>
            </div>
            <div class="journey-step fade-up" style="transition-delay:0.24s">
                <div class="journey-step-top">
                    <span class="journey-num">04</span>
                </div>
                <h3 class="journey-title">Decide</h3>
                <p class="journey-desc">Move forward with greater confidence.</p>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<?php if ($testimonials): ?>
<section class="testimonials-section">
    <div class="testimonials-container">
        <p class="testimonials-eyebrow fade-up">Trusted by clients</p>
        <div class="testimonials-rule fade-up"></div>
    </div>
    <div class="testimonials-track-wrap">
        <div class="testimonials-track" id="testimonialsTrack">
            <?php foreach (array_merge($testimonials, $testimonials) as $t): ?>
            <figure class="testimonial-card">
                <span class="testimonial-stars"><?php for ($s=0;$s<($t['rating']??5);$s++): ?>&#9733;<?php endfor; ?></span>
                <blockquote class="testimonial-body">&#8220;<?= e($t['body']) ?>&#8221;</blockquote>
                <figcaption class="testimonial-footer">
                    <span class="testimonial-name"><?= e($t['name']) ?></span>
                    <?php if ($t['person_title']): ?>
                    <span class="testimonial-role"><?= e($t['person_title']) ?></span>
                    <?php endif; ?>
                </figcaption>
            </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- INSIGHTS -->
<?php if ($insights): ?>
<section class="section insights-section" id="insights">
    <div class="insights-container">
        <p class="insights-eyebrow fade-up">Property Insights</p>
        <h2 class="insights-heading fade-up">Perspective, in writing.</h2>
        <div class="insights-rule fade-up"></div>
        <div class="insights-editorial-grid">
            <?php foreach ($insights as $i => $ins): ?>
            <?php
                $colClass = $i === 0 ? 'insights-col-7' : ($i === 1 ? 'insights-col-5' : 'insights-col-12');
            ?>
            <article class="insights-article <?= $colClass ?> fade-up" style="transition-delay:<?= min($i*0.12,0.3) ?>s">
                <a href="<?= BASE_URL ?>/insight?slug=<?= e($ins['slug']) ?>" class="insights-article-link">
                    <div class="insights-article-img">
                        <?php if ($ins['cover_image']): ?>
                        <img src="<?= e(assetUrl($ins['cover_image'])) ?>" alt="<?= e($ins['title']) ?>" loading="lazy">
                        <?php else: ?>
                        <div class="insight-card-img-placeholder"><i class="bi bi-newspaper"></i></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($ins['category']): ?>
                    <p class="insights-article-cat"><?= e($ins['category']) ?></p>
                    <?php endif; ?>
                    <h3 class="insights-article-title"><?= e($ins['title']) ?></h3>
                    <div class="insights-article-bar"></div>
                </a>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CONTACT -->
<section class="contact-cta-section">
    <?php $ctaBg = getSetting('investment_bg_image',''); ?>
    <?php if ($ctaBg): ?>
    <img src="<?= e(assetUrl($ctaBg)) ?>" alt="" aria-hidden="true" loading="lazy" class="contact-cta-bg-img">
    <?php endif; ?>
    <div class="contact-cta-overlay"></div>
    <div class="contact-cta-inner fade-up">
        <h2 class="contact-cta-heading">Your next property<br>starts with a conversation.</h2>
        <p class="contact-cta-sub">Whether you're searching for a home, commercial property or investment opportunity, let's discuss what you're looking for.</p>
        <div class="contact-cta-btns">
            <a href="#contact-form" class="contact-cta-btn-primary">Book a Private Consultation</a>
            <a href="#contact-form" class="contact-cta-btn-ghost">Contact <?= e(explode(' ', getSetting('site_title','Haile'))[0]) ?></a>
        </div>
    </div>
</section>

<section class="contact-section" id="contact">
    <div class="contact-lv-grid">

        <!-- LEFT: Info -->
        <div class="contact-lv-info fade-up">
            <p class="contact-lv-eyebrow">Contact <?= e(explode(' ', getSetting('site_title','Haile'))[0]) ?></p>
            <h2 class="contact-lv-name"><?= e($about['name'] ?? getSetting('site_title','Haile Ebabu')) ?></h2>
            <p class="contact-lv-role"><?= e($about['title'] ?? 'Senior Property Consultant / Managing Director') ?></p>
            <div class="contact-lv-rule"></div>
            <dl class="contact-lv-dl">
                <?php $phone1 = getSetting('contact_phone'); if ($phone1): ?>
                <div class="contact-lv-row">
                    <dt>Phone</dt>
                    <dd><a href="tel:<?= e(preg_replace('/\s+/','',$phone1)) ?>"><?= e($phone1) ?></a></dd>
                </div>
                <?php endif; ?>
                <?php $phone2 = getSetting('contact_phone_2'); if ($phone2): ?>
                <div class="contact-lv-row">
                    <dt>Phone</dt>
                    <dd><a href="tel:<?= e(preg_replace('/\s+/','',$phone2)) ?>"><?= e($phone2) ?></a></dd>
                </div>
                <?php endif; ?>
                <?php $tg = getSetting('contact_telegram'); if ($tg): ?>
                <div class="contact-lv-row">
                    <dt>Telegram</dt>
                    <dd><a href="https://t.me/<?= e(ltrim($tg,'@')) ?>" target="_blank"><?= e($tg) ?></a></dd>
                </div>
                <?php endif; ?>
                <?php $wa = getSetting('contact_whatsapp'); if ($wa): ?>
                <div class="contact-lv-row">
                    <dt>WhatsApp</dt>
                    <dd><a href="https://wa.me/<?= e(preg_replace('/\D/','',$wa)) ?>" target="_blank"><?= e($wa) ?></a></dd>
                </div>
                <?php endif; ?>
                <?php $ig = getSetting('social_instagram'); if ($ig): ?>
                <div class="contact-lv-row">
                    <dt>Instagram</dt>
                    <dd><a href="<?= e($ig) ?>" target="_blank"><i class="bi bi-instagram"></i> Instagram</a></dd>
                </div>
                <?php endif; ?>
                <?php $fb = getSetting('social_facebook'); if ($fb): ?>
                <div class="contact-lv-row">
                    <dt>Facebook</dt>
                    <dd><a href="<?= e($fb) ?>" target="_blank"><i class="bi bi-facebook"></i> Facebook</a></dd>
                </div>
                <?php endif; ?>
                <?php $socialTgUrl = getSetting('social_telegram'); if ($socialTgUrl): ?>
                <div class="contact-lv-row">
                    <dt>Telegram</dt>
                    <dd><a href="<?= e($socialTgUrl) ?>" target="_blank"><i class="bi bi-telegram"></i> Telegram</a></dd>
                </div>
                <?php endif; ?>
                <?php $socialWaUrl = getSetting('social_whatsapp'); if ($socialWaUrl): ?>
                <div class="contact-lv-row">
                    <dt>WhatsApp</dt>
                    <dd><a href="<?= e($socialWaUrl) ?>" target="_blank"><i class="bi bi-whatsapp"></i> WhatsApp</a></dd>
                </div>
                <?php endif; ?>
                <?php $em = getSetting('contact_email'); if ($em): ?>
                <div class="contact-lv-row">
                    <dt>Email</dt>
                    <dd><a href="mailto:<?= e($em) ?>"><?= e($em) ?></a></dd>
                </div>
                <?php endif; ?>
            </dl>
            <div class="contact-mobile-phones">
                <?php $phone1 = getSetting('contact_phone'); if ($phone1): ?>
                <a href="tel:<?= e(preg_replace('/\s+/','',$phone1)) ?>" class="contact-mobile-phone"><i class="bi bi-telephone"></i> <?= e($phone1) ?></a>
                <?php endif; ?>
                <?php $phone2 = getSetting('contact_phone_2'); if ($phone2): ?>
                <a href="tel:<?= e(preg_replace('/\s+/','',$phone2)) ?>" class="contact-mobile-phone"><i class="bi bi-telephone"></i> <?= e($phone2) ?></a>
                <?php endif; ?>
            </div>

            <div class="contact-lv-quick fade-up">
                <?php if ($wa): ?>
                <a href="https://wa.me/<?= e(preg_replace('/\D/','',$wa)) ?>" target="_blank" class="contact-lv-quick-btn"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                <?php endif; ?>
                <?php if ($tg): ?>
                <a href="https://t.me/<?= e(ltrim($tg,'@')) ?>" target="_blank" class="contact-lv-quick-btn"><i class="bi bi-telegram"></i> Telegram</a>
                <?php endif; ?>
                <?php $socialIg = getSetting('social_instagram'); if ($socialIg): ?>
                <a href="<?= e($socialIg) ?>" target="_blank" class="contact-lv-quick-btn"><i class="bi bi-instagram"></i> Instagram</a>
                <?php endif; ?>
                <?php $socialFb = getSetting('social_facebook'); if ($socialFb): ?>
                <a href="<?= e($socialFb) ?>" target="_blank" class="contact-lv-quick-btn"><i class="bi bi-facebook"></i> Facebook</a>
                <?php endif; ?>
                <?php $socialTg = getSetting('social_telegram'); if ($socialTg): ?>
                <a href="<?= e($socialTg) ?>" target="_blank" class="contact-lv-quick-btn"><i class="bi bi-telegram"></i> Telegram</a>
                <?php endif; ?>
                <?php $socialWa = getSetting('social_whatsapp'); if ($socialWa): ?>
                <a href="<?= e($socialWa) ?>" target="_blank" class="contact-lv-quick-btn"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: Form -->
        <div class="contact-lv-form-wrap fade-up" id="contact-form">
            <?php if ($contactErrors): ?>
            <div class="form-errors">
                <?php foreach ($contactErrors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($contactSuccess): ?>
            <div class="form-success">
                <div class="form-success-icon"><i class="bi bi-check-lg"></i></div>
                <h3>Message Sent</h3>
                <p>Thank you for your enquiry. We will be in touch shortly.</p>
            </div>
            <?php else: ?>
            <form method="POST" action="<?= BASE_URL ?>/#contact" id="contactForm" class="contact-lv-form">
                <?= csrfField() ?>
                <input type="hidden" name="contact_form" value="1">
                <h3 class="contact-lv-form-title">Send an inquiry</h3>
                <div class="contact-lv-fields">
                    <div class="contact-lv-field">
                        <label for="f_name" class="contact-lv-label">Your Name</label>
                        <input type="text" id="f_name" name="name" class="contact-lv-input" value="<?= e($contactData['name']) ?>" required maxlength="100">
                    </div>
                    <div class="contact-lv-field">
                        <label for="f_phone" class="contact-lv-label">Phone / WhatsApp</label>
                        <input type="tel" id="f_phone" name="phone" class="contact-lv-input" value="<?= e($contactData['phone']) ?>" maxlength="30">
                    </div>
                    <div class="contact-lv-field">
                        <label for="f_email" class="contact-lv-label">Email</label>
                        <input type="email" id="f_email" name="email" class="contact-lv-input" value="<?= e($contactData['email']) ?>" required maxlength="150">
                    </div>
                    <div class="contact-lv-field">
                        <label for="f_interest" class="contact-lv-label">Property Interest</label>
                          <select id="f_interest" name="interest" class="contact-lv-input contact-lv-select">
                            <option value="" disabled <?= !$contactData['interest']?'selected':'' ?>>Select</option>
                            <option value="Residential" <?= $contactData['interest']==='Residential'?'selected':'' ?>>Residential</option>
                            <option value="Commercial" <?= $contactData['interest']==='Commercial'?'selected':'' ?>>Commercial</option>
                            <option value="Investment" <?= $contactData['interest']==='Investment'?'selected':'' ?>>Investment</option>
                            <option value="Land & Development" <?= $contactData['interest']==='Land & Development'?'selected':'' ?>>Land &amp; Development</option>
                            <option value="Other" <?= $contactData['interest']==='Other'?'selected':'' ?>>Other</option>
                        </select>
                    </div>
                    <div class="contact-lv-field">
                        <label for="f_message" class="contact-lv-label">Message</label>
                        <textarea id="f_message" name="message" class="contact-lv-input contact-lv-textarea" rows="4" required maxlength="5000"><?= e($contactData['message']) ?></textarea>
                    </div>
                </div>
                <button type="submit" class="contact-lv-submit">Send Inquiry</button>
            </form>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
