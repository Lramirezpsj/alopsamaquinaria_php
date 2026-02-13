<?php
ob_start();
// 1. Iniciar sesión
session_start();

// 2. Verificar autenticación y rol de administrador
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}
require 'db.php';

// 3. Verificar conexión a la base de datos
if (!isset($pdo) || $pdo === null) {
    header("Location: /error.php?code=db");
    exit();
}

// 4. Configurar headers de seguridad
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$registrosPorPagina = 25;
$totalRegistrosDeseados = 250;
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;

try {
    // Obtener los últimos 250 registros usando prepared statement
    $sql = "SELECT * FROM usuarios ORDER BY id_usuario DESC LIMIT :limite";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limite', $totalRegistrosDeseados, PDO::PARAM_INT);
    $stmt->execute();
    $todosRegistros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalRegistros = count($todosRegistros);
    $totalPaginas = $totalRegistros > 0 ? ceil($totalRegistros / $registrosPorPagina) : 1;

    if ($paginaActual < 1)
        $paginaActual = 1;
    if ($paginaActual > $totalPaginas)
        $paginaActual = $totalPaginas;

    $offset = ($paginaActual - 1) * $registrosPorPagina;
    $usuarios = array_slice($todosRegistros, $offset, $registrosPorPagina);

    if (empty($usuarios)) {
        $mensaje = "No hay registros disponibles";
    }

} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

$usuarioLogeado = $_SESSION['usuario'];

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="HandheldFriendly" content="true">
    <meta name="MobileOptimized" content="width">
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Usuarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="listar_suministros.css?v=<?= time() ?>">
    <link rel="stylesheet" href="modal.css?v=<?= time() ?>">
    <link rel="stylesheet" href="navbar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="modalNSuministro.css?v=<?= time() ?>">

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const modals = document.querySelectorAll('.modal, .modalNSuministro');
            modals.forEach(m => { if(m) m.style.display = 'none'; });
        });
    </script>

</head>

<body>
    <?php include 'navbar.php'; ?>

    <!-- Botones de acción -->
    <div class="fab-actions">
        <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'ADMIN' || $_SESSION['rol'] === 'USUARIO')): ?>
            <button id="btnExportarExcel" class="fab-btn">
                <i class="fas fa-file-excel"></i>
            </button>
        <?php endif; ?>

        <a class="fab-btn fab-primary" id="btnNuevo"> <i class="fas fa-plus"></i></a>

    </div>

    <div class="container">

        <h1>Usuarios</h1>

        <!-- Modal para exportar -->
        <div id="modalExportar" class="modal" style="display: none;">
            <div class="modal-contenido">
                <span class="cerrar-modal">&times;</span>
                <h2><i class="fas fa-file-excel"></i> Exportar usuarios</h2>
                <p style="text-align: center; margin: 20px 0;">¿Deseas exportar todos los usuarios a Excel?</p>
                <form action="exportar_usuarios.php" method="post">
                    <button type="submit" class="btn-exportar-excel">
                        <i class="fas fa-download"></i> Generar Reporte
                    </button>
                </form>
            </div>
        </div>

        <!-- Modal para nuevo/editar registro -->
        <div id="modalUsuario" class="modalNSuministro" style="display: none;">
            <div class="modal-contenido-suministro">
                <span class="cerrar-modal">&times;</span>
                <h2 id="tituloModal">
                    <i class="fas fa-plus"></i> Nuevo usuario
                </h2>

                <form action="crear_usuario.php" method="post" id="formUsuario">
                    <input type="hidden" name="id_usuario" id="id_usuario">

                    <div class="form-group-suministro">
                        <label for="usuario">Usuario:</label>
                        <input type="text" id="usuario" name="usuario" required>
                    </div>

                    <div class="form-group-suministro">
                        <label for="contrasenia">Contraseña:</label>
                        <input type="text" id="contrasenia" name="contrasenia" required>
                    </div>

                    <div class="form-group-suministro">
                        <label for="rol">Rol:</label>
                        <select id="rol" name="rol" required>
                            <option value="">Selecciona un rol</option>
                            <option value="ADMIN">ADMIN</option>
                            <option value="USUARIO">USUARIO</option>
                            <option value="INVITADO">INVITADO</option>
                        </select>
                    </div>

                    <div class="form-actions-horometro">
                        <button type="submit" class="btn-submit" id="btnSubmit">Guardar</button>
                        <a href="listar_usuarios.php" class="btn-cancel" id="btnCancelar">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de usuarios -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Acciones</th>
                        <th>Usuario</th>
                        <th>Contraseña</th>
                        <th>Rol</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($usuarios)): ?>
                        <?php foreach ($usuarios as $usu): ?>
                            <tr>
                                <td><?= htmlspecialchars($usu['id_usuario']) ?></td>
                                <td class="actions-cell">
                                    <a class="btn-edit"
                                        onclick='abrirEditar(<?= json_encode($usu, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        ✏️ Editar
                                    </a>

                                    <?php if ($_SESSION['rol'] === 'ADMIN'): ?>
                                        <a href="eliminar_usuario.php?id=<?= $usu['id_usuario'] ?>" class="btn-delete"
                                            onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">
                                            🗑️ Eliminar</a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap"><?= htmlspecialchars($usu['usuario']) ?></td>
                                <td class="text-nowrap"><?= str_repeat('*', strlen($usu['contrasenia'])) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($usu['rol']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-records"><?= $mensaje ?? 'No hay registros disponibles' ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Navegación entre páginas -->
            <?php if (!empty($usuarios)): ?>
                <div class="paginacion">
                    <?php if ($paginaActual > 1): ?>
                        <a href="?pagina=<?= $paginaActual - 1 ?>" class="btn-pagina">
                            &laquo; Anterior
                        </a>
                    <?php endif; ?>

                    <span class="info-pagina">
                        Página <?= $paginaActual ?> de <?= $totalPaginas ?>
                    </span>

                    <?php if ($paginaActual < $totalPaginas): ?>
                        <a href="?pagina=<?= $paginaActual + 1 ?>" class="btn-pagina">
                            Siguiente &raquo;
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {

            /* ========= ELEMENTOS ========= */
            const modalExportar = document.getElementById("modalExportar");
            const modalUsuario = document.getElementById("modalUsuario");

            const btnExportar = document.getElementById("btnExportarExcel");
            const btnNuevo = document.getElementById("btnNuevo");

            const cerrarExportar = modalExportar?.querySelector(".cerrar-modal");
            const cerrarUsuario = modalUsuario?.querySelector(".cerrar-modal");

            const form = document.getElementById("formUsuario");
            const tituloModal = document.getElementById("tituloModal");
            const btnSubmit = document.getElementById("btnSubmit");
            const btnCancelar = document.getElementById("btnCancelar");

            const idUsuario = document.getElementById("id_usuario");

            /* ========= NUEVO ========= */
            function abrirNuevo() {
                form.reset();
                form.action = "crear_usuario.php";
                tituloModal.innerText = "Nuevo usuario";
                btnSubmit.innerText = "Guardar";

                modalUsuario.style.display = "block";
            }


            /* ========= EDITAR ========= */
            window.abrirEditar = function (data) {
                form.action = "editar_usuario.php";
                tituloModal.innerText = "Editar usuario";
                btnSubmit.innerText = "Actualizar";

                idUsuario.value = data.id_usuario;
                document.getElementById("usuario").value = data.usuario;
                document.getElementById("contrasenia").value = data.contrasenia;
                document.getElementById("rol").value = data.rol;

                modalUsuario.style.display = "block";
            }

            /* ========= EVENTOS ========= */
            btnNuevo?.addEventListener("click", abrirNuevo);

            btnExportar?.addEventListener("click", () => {
                modalExportar.style.display = "block";
            });

            cerrarExportar?.addEventListener("click", () => {
                modalExportar.style.display = "none";
            });

            cerrarUsuario?.addEventListener("click", () => {
                modalUsuario.style.display = "none";
            });

            btnCancelar?.addEventListener("click", (e) => {
                e.preventDefault();
                modalUsuario.style.display = "none";
            });

            window.addEventListener("click", (e) => {
                if (e.target === modalExportar) modalExportar.style.display = "none";
                if (e.target === modalUsuario) modalUsuario.style.display = "none";
            });

        });
    </script>
</body>

</html>