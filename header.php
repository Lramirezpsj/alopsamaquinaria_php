<nav class="navbar navbar-expand-sm bg-primary" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Menú</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="dashboard.php">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="listar.php">Horometros</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="listar_contenedores.php">Contenedores</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="listar_suministros.php">suministros</a>
                </li>
                <!--<li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Dropdown
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Action</a></li>
                        <li><a class="dropdown-item" href="#">Another action</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="#">Something else here</a></li>
                    </ul>
                </li>-->
                <li class="nav-item">
                    <a class="nav-link disabled" aria-disabled="true">Disabled</a>
                </li>
            </ul>
            <form class="d-flex" role="search" onsubmit="return false;">
                <input id="buscador" class="form-control me-2" type="search" placeholder="Buscar..." aria-label="Search"
                    onkeyup="filtrarTabla()">
            </form>
        </div>
    </div>
</nav