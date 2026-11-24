<?php
$pageTitle = "HIMS | Hardware Inventory Management System";
?>
<!DOCTYPE html>
<html lang="en" class="landing-document">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HIMS helps organizations track hardware, locations, assignments, maintenance, and users from one clear inventory system.">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body class="landing-page">
    <a class="landing-skip-link" href="#main-content">Skip to content</a>

    <header class="landing-header" data-landing-header>
        <div class="landing-container landing-header-inner">
            <a class="landing-brand" href="#home" aria-label="HIMS home">
                <span class="landing-brand-mark" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
                <span class="landing-brand-copy">
                    <strong>HIMS</strong>
                    <small>Hardware Inventory</small>
                </span>
            </a>

            <button class="landing-menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-controls="landing-primary-nav landing-header-actions" aria-label="Open navigation menu">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <nav class="landing-nav" id="landing-primary-nav" aria-label="Primary navigation">
                <a href="#home">Home</a>
                <a href="#features">Features</a>
                <a href="#about">About</a>
            </nav>

            <div class="landing-header-actions" id="landing-header-actions">
                <a class="landing-text-link" href="account/login.php">Login</a>
                <a class="landing-button landing-button-primary landing-button-small" href="account/register.php">Get Started</a>
            </div>
        </div>
    </header>

    <main id="main-content">
        <section class="landing-hero" id="home" aria-labelledby="hero-heading">
            <div class="landing-container landing-hero-grid">
                <div class="landing-hero-copy">
                    <span class="landing-eyebrow"><i class="bi bi-shield-check" aria-hidden="true"></i> Organized hardware control</span>
                    <h1 id="hero-heading">Hardware Inventory Management System</h1>
                    <p class="landing-hero-tagline">Manage your hardware. Stay organized. Stay in control.</p>
                    <p class="landing-hero-description">
                        HIMS gives your team one clear place to track hardware, categories, locations, assignments, maintenance, and users. Access is requested, verified, and approved before entry to the system.
                    </p>
                    <div class="landing-hero-actions">
                        <a class="landing-button landing-button-primary" href="account/register.php">
                            Get Started <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                        <a class="landing-button landing-button-secondary" href="#features">Learn More</a>
                    </div>
                    <p class="landing-hero-note"><i class="bi bi-check2-circle" aria-hidden="true"></i> Email verification and admin approval required</p>
                </div>

                <div class="landing-hero-visual" aria-hidden="true">
                    <div class="preview-window">
                        <div class="preview-topbar">
                            <span class="preview-dot"></span>
                            <span class="preview-dot"></span>
                            <span class="preview-dot"></span>
                            <span class="preview-address">HIMS / Dashboard</span>
                        </div>
                        <div class="preview-body">
                            <aside class="preview-sidebar">
                                <div class="preview-sidebar-brand"><i class="bi bi-box-seam"></i> HIMS</div>
                                <div class="preview-sidebar-link active"><i class="bi bi-grid-1x2"></i> Dashboard</div>
                                <div class="preview-sidebar-link"><i class="bi bi-boxes"></i> Hardware</div>
                                <div class="preview-sidebar-link"><i class="bi bi-tags"></i> Categories</div>
                                <div class="preview-sidebar-link"><i class="bi bi-geo-alt"></i> Locations</div>
                                <div class="preview-sidebar-link"><i class="bi bi-clipboard-check"></i> Assignments</div>
                                <div class="preview-sidebar-link"><i class="bi bi-wrench"></i> Maintenance</div>
                            </aside>
                            <div class="preview-content">
                                <div class="preview-heading">
                                    <strong>Dashboard overview</strong>
                                    <span><i class="bi bi-calendar3"></i> This month</span>
                                </div>
                                <div class="preview-stats">
                                    <div class="preview-stat">
                                        <i class="bi bi-boxes"></i>
                                        <span>Total hardware</span>
                                        <strong>248</strong>
                                    </div>
                                    <div class="preview-stat">
                                        <i class="bi bi-clipboard-check"></i>
                                        <span>Assigned</span>
                                        <strong>164</strong>
                                    </div>
                                    <div class="preview-stat">
                                        <i class="bi bi-wrench"></i>
                                        <span>In service</span>
                                        <strong>72</strong>
                                    </div>
                                </div>
                                <div class="preview-panels">
                                    <div class="preview-card">
                                        <div class="preview-card-title">Inventory by category</div>
                                        <div class="preview-bars">
                                            <span class="preview-bar primary" style="height: 78%"></span>
                                            <span class="preview-bar" style="height: 56%"></span>
                                            <span class="preview-bar primary" style="height: 66%"></span>
                                            <span class="preview-bar" style="height: 42%"></span>
                                            <span class="preview-bar primary" style="height: 86%"></span>
                                            <span class="preview-bar" style="height: 62%"></span>
                                        </div>
                                    </div>
                                    <div class="preview-card">
                                        <div class="preview-card-title">Recent activity</div>
                                        <div class="preview-activity">
                                            <div class="preview-activity-row">
                                                <span class="preview-activity-icon"><i class="bi bi-box-arrow-in-right"></i></span>
                                                <span class="preview-activity-copy"><strong>New asset added</strong><span>Dell Latitude · 2 min ago</span></span>
                                            </div>
                                            <div class="preview-activity-row">
                                                <span class="preview-activity-icon"><i class="bi bi-person-check"></i></span>
                                                <span class="preview-activity-copy"><strong>Asset assigned</strong><span>Engineering · 18 min ago</span></span>
                                            </div>
                                            <div class="preview-activity-row">
                                                <span class="preview-activity-icon"><i class="bi bi-wrench"></i></span>
                                                <span class="preview-activity-copy"><strong>Maintenance due</strong><span>Monitor · 1 hr ago</span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="landing-visually-hidden">Dashboard preview showing hardware totals, an inventory chart, and recent activity.</p>
            </div>
        </section>

        <section class="landing-section" id="features" aria-labelledby="features-heading">
            <div class="landing-container">
                <div class="landing-section-heading">
                    <span class="landing-section-kicker">Features</span>
                    <h2 id="features-heading">Everything needed to keep hardware accountable</h2>
                    <p>Focused modules cover the complete asset lifecycle without adding unnecessary complexity.</p>
                </div>

                <div class="landing-feature-grid">
                    <article class="landing-feature-card">
                        <span class="landing-feature-icon" aria-hidden="true"><i class="bi bi-boxes"></i></span>
                        <h3>Hardware Management</h3>
                        <p>Keep detailed records for every device, including specifications, status, condition, and asset tags.</p>
                    </article>
                    <article class="landing-feature-card">
                        <span class="landing-feature-icon" aria-hidden="true"><i class="bi bi-tags"></i></span>
                        <h3>Category Management</h3>
                        <p>Group hardware into clear categories so inventory stays easy to search, filter, and report on.</p>
                    </article>
                    <article class="landing-feature-card">
                        <span class="landing-feature-icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                        <h3>Location Tracking</h3>
                        <p>Know where every asset belongs and quickly identify what is stored at each location.</p>
                    </article>
                    <article class="landing-feature-card">
                        <span class="landing-feature-icon" aria-hidden="true"><i class="bi bi-clipboard-check"></i></span>
                        <h3>Asset Assignments</h3>
                        <p>Record who uses each item, when it was issued, and when it should be returned.</p>
                    </article>
                    <article class="landing-feature-card">
                        <span class="landing-feature-icon" aria-hidden="true"><i class="bi bi-wrench"></i></span>
                        <h3>Maintenance Tracking</h3>
                        <p>Log service, repairs, and upcoming maintenance so hardware remains reliable and accounted for.</p>
                    </article>
                    <article class="landing-feature-card">
                        <span class="landing-feature-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                        <h3>User Management</h3>
                        <p>Approve accounts and assign roles so each person sees the right level of access.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-section landing-steps-section" id="how-it-works" aria-labelledby="steps-heading">
            <div class="landing-container">
                <div class="landing-section-heading">
                    <span class="landing-section-kicker">How it works</span>
                    <h2 id="steps-heading">Three simple steps to get started</h2>
                    <p>Access is deliberate and secure from the first request to the first login.</p>
                </div>

                <ol class="landing-steps">
                    <li class="landing-step">
                        <span class="landing-step-number">1</span>
                        <h3>Register and verify email</h3>
                        <p>Create an account with your details, then confirm your email address using the verification link.</p>
                    </li>
                    <li class="landing-step">
                        <span class="landing-step-number">2</span>
                        <h3>Admin approval and role assignment</h3>
                        <p>An administrator reviews the request, approves the account, and assigns the appropriate role.</p>
                    </li>
                    <li class="landing-step">
                        <span class="landing-step-number">3</span>
                        <h3>Login and access the system</h3>
                        <p>Sign in with your verified credentials and start managing the hardware inventory right away.</p>
                    </li>
                </ol>
            </div>
        </section>

        <section class="landing-section landing-about-section" id="about" aria-labelledby="about-heading">
            <div class="landing-container landing-about-grid">
                <div>
                    <span class="landing-section-kicker">About HIMS</span>
                    <h2 id="about-heading">A clearer way to manage every hardware asset</h2>
                </div>
                <div class="landing-about-copy">
                    <p>
                        HIMS is a hardware inventory management system built for teams that need a dependable record of the devices they own and use. It brings asset details, categories, locations, assignments, maintenance, and users together in one organized workspace.
                    </p>
                    <p>
                        Its purpose is simple: reduce lost equipment, make accountability visible, and help administrators make confident decisions with accurate inventory information.
                    </p>
                    <div class="landing-about-points">
                        <span class="landing-about-point"><i class="bi bi-check2" aria-hidden="true"></i> Single source of truth</span>
                        <span class="landing-about-point"><i class="bi bi-check2" aria-hidden="true"></i> Role-based access</span>
                        <span class="landing-about-point"><i class="bi bi-check2" aria-hidden="true"></i> Clear asset history</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-cta" aria-labelledby="cta-heading">
            <div class="landing-container landing-cta-inner">
                <div>
                    <h2 id="cta-heading">Ready to bring your inventory under control?</h2>
                    <p>Request an account today, or sign in if your access has already been approved.</p>
                </div>
                <div class="landing-cta-actions">
                    <a class="landing-button landing-button-primary" href="account/register.php">Create Account</a>
                    <a class="landing-button landing-button-secondary" href="account/login.php">Login</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="landing-container landing-footer-grid">
            <div class="landing-footer-brand">
                <a class="landing-brand" href="#home" aria-label="HIMS home">
                    <span class="landing-brand-mark" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
                    <span class="landing-brand-copy">
                        <strong>HIMS</strong>
                        <small>Hardware Inventory</small>
                    </span>
                </a>
                <p>Hardware Inventory Management System — a clear, organized way to track hardware, assignments, maintenance, and users.</p>
            </div>
            <nav aria-label="Footer navigation">
                <h3>Explore</h3>
                <a href="#home">Home</a>
                <a href="#features">Features</a>
                <a href="#how-it-works">How It Works</a>
                <a href="#about">About</a>
            </nav>
            <nav aria-label="Account navigation">
                <h3>Account</h3>
                <a href="account/login.php">Login</a>
                <a href="account/register.php">Get Started</a>
            </nav>
        </div>
        <div class="landing-container landing-footer-bottom">
            <p>&copy; <?= date('Y') ?> HIMS. All rights reserved.</p>
            <p>Built for reliable hardware inventory control.</p>
        </div>
    </footer>

    <script src="assets/js/landing.js" defer></script>
</body>
</html>
