<!-- Sidebar -->
<div class="col-md-3 col-lg-2 px-0 bg-dark text-white">
    <div class="sidebar">
        <div class="p-3">
            <h4 class="mb-4">Menu Admin</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'matches.php' ? 'active' : ''; ?>" href="matches.php">
                        <i class="bi bi-calendar-event me-2"></i> Matchs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'tickets.php' ? 'active' : ''; ?>" href="tickets.php">
                        <i class="bi bi-ticket me-2"></i> Billets
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                        <i class="bi bi-cart me-2"></i> Commandes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>" href="users.php">
                        <i class="bi bi-people me-2"></i> Utilisateurs
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
