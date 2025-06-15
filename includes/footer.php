
<style>
/* Variables CSS pour la cohérence */
:root {
    --primary-blue: #003366;
    --accent-green: #4CAF50;
    --action-orange: #FF9800;
    --bg-light: #F5F5F5;
    --bg-white: #FFFFFF;
    --text-primary: #212121;
    --text-secondary: #666666;
    --border-color: #E0E0E0;
    --shadow-sm: 0 2px 4px rgba(0, 51, 102, 0.1);
    --shadow-md: 0 4px 8px rgba(0, 51, 102, 0.15);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Navigation principale */
.navbar {
    background: var(--bg-white) !important;
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    padding: 1rem 0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar-brand {
    font-weight: 800;
    font-size: 1.75rem;
    color: var(--primary-blue) !important;
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: var(--transition);
}

.navbar-brand:hover {
    color: var(--accent-green) !important;
    transform: translateY(-1px);
}

.navbar-brand i {
    color: var(--accent-green);
    font-size: 2rem;
}

/* Bouton toggle mobile */
.navbar-toggler {
    border: none;
    padding: 0.5rem;
    border-radius: 8px;
    background: var(--bg-light);
    transition: var(--transition);
}

.navbar-toggler:hover {
    background: rgba(0, 51, 102, 0.1);
}

.navbar-toggler:focus {
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.3);
}

.navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2833, 37, 41, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

/* Liens de navigation */
.navbar-nav .nav-link {
    font-weight: 500;
    color: var(--text-secondary) !important;
    margin: 0 0.25rem;
    padding: 0.75rem 1rem !important;
    border-radius: 8px;
    transition: var(--transition);
    display: flex;
    align-items: center;
    position: relative;
}

.navbar-nav .nav-link:hover {
    color: var(--primary-blue) !important;
    background: rgba(0, 51, 102, 0.05);
    transform: translateY(-1px);
}

.navbar-nav .nav-link i {
    color: var(--accent-green);
    transition: var(--transition);
}

.navbar-nav .nav-link:hover i {
    transform: scale(1.1);
}

/* Badge du panier */
.cart-badge {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    background: var(--action-orange);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.65rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Menu utilisateur */
.user-menu {
    background: var(--bg-light) !important;
    border: 1px solid var(--border-color) !important;
    border-radius: 25px !important;
    padding: 0.5rem 1rem 0.5rem 0.5rem !important;
    margin-left: 0.5rem;
}

.user-menu:hover {
    background: rgba(0, 51, 102, 0.05) !important;
    border-color: var(--accent-green) !important;
}

.user-avatar {
    width: 32px;
    height: 32px;
    background: var(--accent-green);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 0.875rem;
    margin-right: 0.5rem;
}

.user-name {
    font-weight: 600;
    color: var(--text-primary) !important;
}

/* Dropdown utilisateur */
.user-dropdown {
    border: none;
    box-shadow: var(--shadow-md);
    border-radius: 12px;
    padding: 0;
    min-width: 280px;
    margin-top: 0.5rem;
}

.dropdown-header {
    padding: 1.5rem;
    background: var(--bg-light);
    border-radius: 12px 12px 0 0;
    border-bottom: 1px solid var(--border-color);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar-large {
    width: 48px;
    height: 48px;
    background: var(--accent-green);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.25rem;
}

.user-name-large {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 1rem;
}

.dropdown-item {
    padding: 0.75rem 1.5rem;
    color: var(--text-secondary);
    transition: var(--transition);
    display: flex;
    align-items: center;
}

.dropdown-item:hover {
    background: rgba(0, 51, 102, 0.05);
    color: var(--primary-blue);
}

.dropdown-item i {
    color: var(--accent-green);
    width: 20px;
}

.admin-link {
    color: var(--action-orange) !important;
}

.admin-link:hover {
    background: rgba(255, 152, 0, 0.1) !important;
}

.admin-link i {
    color: var(--action-orange) !important;
}

.logout-link:hover {
    background: rgba(229, 57, 53, 0.1) !important;
    color: #E53935 !important;
}

.logout-link:hover i {
    color: #E53935 !important;
}

/* Liens d'authentification */
.auth-link {
    color: var(--primary-blue) !important;
    font-weight: 600 !important;
}

.auth-link:hover {
    background: rgba(0, 51, 102, 0.1) !important;
}

.btn-register {
    background: var(--accent-green) !important;
    color: white !important;
    font-weight: 600 !important;
    border-radius: 20px !important;
    margin-left: 0.5rem;
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
}

.btn-register:hover {
    background: #45a049 !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
}

.btn-register i {
    color: white !important;
}

/* Responsive */
@media (max-width: 991.98px) {
    .navbar-collapse {
        background: var(--bg-white);
        border-radius: 12px;
        margin-top: 1rem;
        padding: 1rem;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
    }
    
    .navbar-nav .nav-link {
        margin: 0.25rem 0;
        border-radius: 8px;
    }
    
    .user-menu {
        background: transparent !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 0.75rem 1rem !important;
        margin: 0.25rem 0;
    }
    
    .user-dropdown {
        position: static !important;
        transform: none !important;
        box-shadow: none;
        border: 1px solid var(--border-color);
        margin-top: 0.5rem;
    }
    
    .btn-register {
        margin-left: 0;
        margin-top: 0.5rem;
    }
}

@media (max-width: 576px) {
    .navbar-brand {
        font-size: 1.5rem;
    }
    
    .navbar-brand i {
        font-size: 1.75rem;
    }
    
    .user-name {
        display: none;
    }
    
    .user-menu {
        padding: 0.5rem !important;
    }
}

/* Animation d'entrée */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-menu.show {
    animation: slideDown 0.3s ease-out;
}

/* Focus et accessibilité */
.nav-link:focus,
.dropdown-item:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.3);
}

/* État actif des liens */
.navbar-nav .nav-link.active {
    color: var(--primary-blue) !important;
    background: rgba(0, 51, 102, 0.1);
    font-weight: 600;
}

.navbar-nav .nav-link.active i {
    color: var(--primary-blue);
}
</style>

<!-- Footer Stylisé -->
<footer class="footer-modern">
    <div class="container">
        <!-- Section principale du footer -->
        <div class="footer-content">
            <div class="row">
                <!-- À propos -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-section">
                        <div class="footer-brand">
                            <i class="bi bi-dribbble"></i>
                            Football Tickets
                        </div>
                        <p class="footer-description">
                            Votre billetterie officielle pour les matchs de football au Maroc. 
                            Vivez l'émotion des plus grands matchs avec nous !
                        </p>
                        <div class="footer-stats">
                            <div class="stat-item">
                                <i class="bi bi-people"></i>
                                <span>50K+ Fans</span>
                            </div>
                            <div class="stat-item">
                                <i class="bi bi-ticket-perforated"></i>
                                <span>100K+ Billets vendus</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Liens rapides -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <div class="footer-section">
                        <h5 class="footer-title">Navigation</h5>
                        <ul class="footer-links">
                            <li>
                                <a href="matches.php">
                                    <i class="bi bi-calendar-event"></i>
                                    Matchs à venir
                                </a>
                            </li>
                            <li>
                                <a href="contact.php">
                                    <i class="bi bi-envelope"></i>
                                    Contact
                                </a>
                            </li>
                            <li>
                                <a href="faq.php">
                                    <i class="bi bi-question-circle"></i>
                                    FAQ
                                </a>
                            </li>
                            <li>
                                <a href="about.php">
                                    <i class="bi bi-info-circle"></i>
                                    À propos
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Services -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <div class="footer-section">
                        <h5 class="footer-title">Services</h5>
                        <ul class="footer-links">
                            <li>
                                <a href="booking.php">
                                    <i class="bi bi-ticket"></i>
                                    Réservation
                                </a>
                            </li>
                            <li>
                                <a href="group-booking.php">
                                    <i class="bi bi-people"></i>
                                    Groupes
                                </a>
                            </li>
                            <li>
                                <a href="vip.php">
                                    <i class="bi bi-star"></i>
                                    VIP
                                </a>
                            </li>
                            <li>
                                <a href="mobile-app.php">
                                    <i class="bi bi-phone"></i>
                                    App Mobile
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Contact et réseaux sociaux -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-section">
                        <h5 class="footer-title">Restons connectés</h5>
                        
                        <!-- Informations de contact -->
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="bi bi-geo-alt"></i>
                                <span>Casablanca, Maroc</span>
                            </div>
                            <div class="contact-item">
                                <i class="bi bi-telephone"></i>
                                <span>+212 5 22 XX XX XX</span>
                            </div>
                            <div class="contact-item">
                                <i class="bi bi-envelope"></i>
                                <span>contact@footballtickets.ma</span>
                            </div>
                        </div>
                        
                        <!-- Réseaux sociaux -->
                        <div class="social-section">
                            <p class="social-label">Suivez-nous</p>
                            <div class="social-links">
                                <a href="#" class="social-link facebook">
                                    <i class="bi bi-facebook"></i>
                                </a>
                                <a href="#" class="social-link twitter">
                                    <i class="bi bi-twitter"></i>
                                </a>
                                <a href="#" class="social-link instagram">
                                    <i class="bi bi-instagram"></i>
                                </a>
                                <a href="#" class="social-link youtube">
                                    <i class="bi bi-youtube"></i>
                                </a>
                                <a href="#" class="social-link linkedin">
                                    <i class="bi bi-linkedin"></i>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Newsletter -->
                        <div class="newsletter">
                            <p class="newsletter-label">Newsletter</p>
                            <div class="newsletter-form">
                                <input type="email" placeholder="Votre email..." class="newsletter-input">
                                <button class="newsletter-btn">
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ligne de séparation -->
        <div class="footer-divider"></div>
        
        <!-- Section copyright -->
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright">
                        &copy; <?php echo date('Y'); ?> Football Tickets. Tous droits réservés.
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="footer-legal">
                        <a href="privacy.php">Politique de confidentialité</a>
                        <a href="terms.php">Conditions d'utilisation</a>
                        <a href="cookies.php">Cookies</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Indicateur "Retour en haut" -->
        <button class="back-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
            <i class="bi bi-arrow-up"></i>
        </button>
    </div>
</footer>

<style>
/* Footer moderne */
.footer-modern {
    background: var(--primary-blue);
    color: white;
    margin-top: 5rem;
    position: relative;
    overflow: hidden;
}

.footer-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, var(--primary-blue) 0%, #004080 50%, var(--primary-blue) 100%);
    opacity: 0.9;
}

.footer-modern::after {
    content: '';
    position: absolute;
    top: -50px;
    left: -20%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(76, 175, 80, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.footer-content {
    padding: 4rem 0 2rem;
    position: relative;
    z-index: 10;
}

/* Section du footer */
.footer-section {
    height: 100%;
}

/* Marque du footer */
.footer-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.5rem;
    font-weight: 800;
    color: white;
    margin-bottom: 1rem;
}

.footer-brand i {
    color: var(--accent-green);
    font-size: 1.75rem;
}

.footer-description {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

/* Statistiques du footer */
.footer-stats {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.875rem;
    font-weight: 500;
}

.stat-item i {
    color: var(--accent-green);
    font-size: 1rem;
}

/* Titres des sections */
.footer-title {
    color: white;
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    position: relative;
    padding-bottom: 0.5rem;
}

.footer-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 30px;
    height: 2px;
    background: var(--accent-green);
    border-radius: 1px;
}

/* Liens du footer */
.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 0.75rem;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    transition: var(--transition);
    padding: 0.25rem 0;
}

.footer-links a:hover {
    color: var(--accent-green);
    transform: translateX(5px);
}

.footer-links a i {
    font-size: 0.875rem;
    width: 16px;
    color: var(--accent-green);
}

/* Informations de contact */
.contact-info {
    margin-bottom: 2rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
}

.contact-item i {
    color: var(--accent-green);
    font-size: 1rem;
    width: 20px;
    text-align: center;
}

/* Section réseaux sociaux */
.social-section {
    margin-bottom: 2rem;
}

.social-label {
    color: white;
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.social-links {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.social-link {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: var(--transition);
    border: 2px solid rgba(255, 255, 255, 0.2);
    position: relative;
    overflow: hidden;
}

.social-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 50%;
    transform: scale(0);
    transition: var(--transition);
}

.social-link:hover::before {
    transform: scale(1);
}

.social-link:hover {
    color: white;
    border-color: transparent;
    transform: translateY(-3px);
}

.social-link.facebook::before { background: #1877F2; }
.social-link.twitter::before { background: #1DA1F2; }
.social-link.instagram::before { background: linear-gradient(45deg, #E4405F, #FCAF45); }
.social-link.youtube::before { background: #FF0000; }
.social-link.linkedin::before { background: #0A66C2; }

.social-link i {
    position: relative;
    z-index: 10;
    font-size: 1.1rem;
}

/* Newsletter */
.newsletter-label {
    color: white;
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.newsletter-form {
    display: flex;
    gap: 0.5rem;
}

.newsletter-input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    font-size: 0.875rem;
    transition: var(--transition);
    backdrop-filter: blur(10px);
}

.newsletter-input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.newsletter-input:focus {
    outline: none;
    border-color: var(--accent-green);
    background: rgba(255, 255, 255, 0.15);
}

.newsletter-btn {
    padding: 0.75rem 1rem;
    background: var(--accent-green);
    border: none;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}

.newsletter-btn:hover {
    background: #45a049;
    transform: translateY(-2px);
}

/* Ligne de séparation */
.footer-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
    margin: 2rem 0;
    position: relative;
    z-index: 10;
}

/* Section copyright */
.footer-bottom {
    padding-bottom: 2rem;
    position: relative;
    z-index: 10;
}

.copyright {
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
    font-size: 0.875rem;
}

.footer-legal {
    display: flex;
    gap: 1.5rem;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.footer-legal a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.875rem;
    transition: var(--transition);
}

.footer-legal a:hover {
    color: var(--accent-green);
}

/* Bouton retour en haut */
.back-to-top {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 50px;
    height: 50px;
    background: var(--accent-green);
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.back-to-top:hover {
    background: #45a049;
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(76, 175, 80, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
    .footer-content {
        padding: 3rem 0 1.5rem;
    }
    
    .footer-stats {
        justify-content: center;
        margin-bottom: 1rem;
    }
    
    .social-links {
        justify-content: center;
    }
    
    .footer-legal {
        justify-content: center;
        margin-top: 1rem;
    }
    
    .newsletter-form {
        flex-direction: column;
    }
    
    .newsletter-btn {
        align-self: stretch;
    }
    
    .back-to-top {
        bottom: 1rem;
        right: 1rem;
        width: 45px;
        height: 45px;
    }
}

@media (max-width: 576px) {
    .footer-legal {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .contact-info {
        text-align: center;
    }
    
    .footer-title::after {
        left: 50%;
        transform: translateX(-50%);
    }
}
</style>