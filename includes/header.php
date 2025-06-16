<!-- Navigation Stylisée -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-dribbble me-2"></i>
            Football Tickets
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="matches.php">
                        <i class="bi bi-calendar-event me-1"></i>
                        Matchs
                    </a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="cart.php">
                        <i class="bi bi-bag me-1"></i>
                        Panier
                        <?php if(isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                        <span class="cart-badge"><?php echo $_SESSION['cart_count']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if(isset($_SESSION['user_id'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle user-menu" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                        <span class="user-name"><?php echo $_SESSION['username']; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end user-dropdown">
                        <li>
                            <div class="dropdown-header">
                                <div class="user-info">
                                    <div class="user-avatar-large">
                                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="user-name-large"><?php echo $_SESSION['username']; ?></div>
                                        <small class="text-muted">Membre depuis 2024</small>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li>
                            <a class="dropdown-item admin-link" href="admin/dashboard.php">
                                <i class="bi bi-gear me-2"></i>
                                Administration
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li>
                            <a class="dropdown-item" href="my_orders.php">
                                <i class="bi bi-receipt me-2"></i>
                                Mes Commandes
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item logout-link" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Déconnexion
                            </a>
                        </li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link auth-link" href="login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Connexion
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn-register" href="register.php">
                        <i class="bi bi-person-plus me-1"></i>
                        Inscription
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

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
    font-weight: 700;
    color: var(--text-primary);
    font-size: 1.125rem;
}

.dropdown-item {
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: var(--transition);
}

.dropdown-item:hover {
    background: var(--bg-light);
    color: var(--primary-blue);
}

.dropdown-item i {
    color: var(--accent-green);
    font-size: 1.25rem;
}

.admin-link {
    color: var(--primary-blue) !important;
    font-weight: 600;
}

.admin-link i {
    color: var(--primary-blue) !important;
}

.logout-link {
    color: var(--error-red) !important;
}

.logout-link i {
    color: var(--error-red) !important;
}

/* Liens d'authentification */
.auth-link {
    color: var(--primary-blue) !important;
    font-weight: 600;
}

.btn-register {
    background: var(--accent-green) !important;
    color: white !important;
    border-radius: 25px !important;
    padding: 0.5rem 1.25rem !important;
    margin-left: 0.5rem;
}

.btn-register:hover {
    background: var(--success-green) !important;
    transform: translateY(-1px);
}
</style>