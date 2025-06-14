<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Football Tickets Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="matches.php">Matchs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="tickets.php">Billets</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="orders.php">Commandes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">Utilisateurs</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <span class="nav-link">Bienvenue, <?php echo $_SESSION['username']; ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../logout.php">Déconnexion</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
