<nav class="wa-navbar">
    <div class="wa-navbar-top">
        <button class="wa-toggle" id="waToggle">☰</button>

        <span class="wa-title">
            Alopsa Maquinaria
        </span>
    </div>

    <!-- Menú tipo WhatsApp -->
    <div class="wa-menu" id="waMenu">
        <a href="dashboard.php">🏠 Inicio</a>
        <a href="listar.php">⏱ Hodómetros</a>
        <a href="listar_contenedores.php">📦 Contenedores</a>
        <a href="listar_suministros.php">⛽ Suministros</a>
        <a href="listar_pedidos.php"> 🛒 Pedidos</a>
        <a href="listar_colaboradores.php"> 👥 Colaboradores</a>
        <a href="listar_alimentos.php"> 🍽️ Alimentos</a>
        <a href="listar_usuarios.php"> 👥 usuarios</a>
        <a href="listar_medidas.php"> 📐 Medidas</a>
    </div>
</nav>

<script>
    const toggle = document.getElementById('waToggle');
    const menu = document.getElementById('waMenu');

    toggle.addEventListener('click', () => {
        menu.classList.toggle('show');
    });

    // Cerrar menú al hacer click en un link
    menu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            menu.classList.remove('show');
        });
    });
</script>