<?php ob_start();
require 'db.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$registrosPorPagina = 25;
$totalRegistrosDeseados = 250;
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;

try {
    // Obtener los últimos 250 registros usando prepared statement
    $sql = "SELECT * FROM alimentos ORDER BY id_alimento DESC LIMIT :limite";
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
    $alimentos = array_slice($todosRegistros, $offset, $registrosPorPagina);

    if (empty($alimentos)) {
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
    <title>Alimentos</title>
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

        <h1>Alimentos</h1>

        <!-- Modal para exportar -->
        <div id="modalExportar" class="modal" style="display: none;">
            <div class="modal-contenido">
                <span class="cerrar-modal">&times;</span>
                <h2><i class="fas fa-file-excel"></i> Exportar alimentos</h2>
                <form action="exportar_alimentos.php" method="post">
                    <div class="form-group">
                        <label for="fecha_inicio">Fecha de inicio:</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" required>
                    </div>
                    <div class="form-group">
                        <label for="fecha_fin">Fecha de fin:</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" required>
                    </div>
                    <button type="submit" class="btn-exportar-excel">
                        <i class="fas fa-download"></i> Generar Reporte
                    </button>
                </form>
            </div>
        </div>

        <!-- Modal para nuevo/editar registro -->
        <div id="modalAlimento" class="modalNSuministro" style="display: none;">
            <div class="modal-contenido-suministro">
                <span class="cerrar-modal">&times;</span>
                <h2 id="tituloModal">
                    <i class="fas fa-plus"></i> Nuevo registro
                </h2>

                <form action="crear_alimento.php" method="post" id="formAlimento">
                    <input type="hidden" name="id_alimento" id="id_alimento">

                    <div class="form-group-suministro">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>

                    <div class="form-group-suministro">
                        <label for="precio">Precio:</label>
                        <input type="number" step="0.01" name="precio" id="precio" required>
                    </div>

                    <div class="form-actions-horometro">
                        <button type="submit" class="btn-submit" id="btnSubmit">Guardar</button>
                        <a href="listar_alimentos.php" class="btn-cancel" id="btnCancelar">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de alimentos -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Acciones</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($alimentos)): ?>
                        <?php foreach ($alimentos as $alimento): ?>
                            <tr>
                                <td><?= htmlspecialchars($alimento['id_alimento']) ?></td>
                                <td class="actions-cell">
                                    <a class="btn-edit"
                                        onclick='abrirEditar(<?= json_encode($alimento, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        ✏️ Editar
                                    </a>

                                    <?php if ($_SESSION['rol'] === 'ADMIN'): ?>
                                        <a href="eliminar_alimento.php?id=<?= $alimento['id_alimento'] ?>" class="btn-delete"
                                            onclick="return confirm('¿Estás seguro de que deseas eliminar este alimento?');">
                                            🗑️ Eliminar</a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap"><?= htmlspecialchars($alimento['nombre']) ?></td>
                                <td class="text-nowrap">Q<?= number_format($alimento['precio'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-records"><?= $mensaje ?? 'No hay registros disponibles' ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Navegación entre páginas -->
            <?php if (!empty($alimentos)): ?>
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
            const modalAlimento = document.getElementById("modalAlimento");

            const btnExportar = document.getElementById("btnExportarExcel");
            const btnNuevo = document.getElementById("btnNuevo");

            const cerrarExportar = modalExportar?.querySelector(".cerrar-modal");
            const cerrarAlimento = modalAlimento?.querySelector(".cerrar-modal");

            const form = document.getElementById("formAlimento");
            const tituloModal = document.getElementById("tituloModal");
            const btnSubmit = document.getElementById("btnSubmit");
            const btnCancelar = document.getElementById("btnCancelar");

            const idAlimento = document.getElementById("id_alimento");

            /* ========= NUEVO ========= */
            function abrirNuevo() {
                form.reset();
                form.action = "crear_alimento.php";
                tituloModal.innerText = "Nuevo alimento";
                btnSubmit.innerText = "Guardar";

                modalAlimento.style.display = "block";
            }


            /* ========= EDITAR ========= */
            window.abrirEditar = function (data) {
                form.action = "editar_alimento.php";
                tituloModal.innerText = "Editar alimento";
                btnSubmit.innerText = "Actualizar";

                idAlimento.value = data.id_alimento;
                document.getElementById("nombre").value = data.nombre;
                document.getElementById("precio").value = data.precio;

                modalAlimento.style.display = "block";
            }

            /* ========= EVENTOS ========= */
            btnNuevo?.addEventListener("click", abrirNuevo);

            btnExportar?.addEventListener("click", () => {
                document.getElementById("fecha_inicio").value = "";
                document.getElementById("fecha_fin").value = "";
                modalExportar.style.display = "block";
            });

            cerrarExportar?.addEventListener("click", () => {
                modalExportar.style.display = "none";
            });

            cerrarAlimento?.addEventListener("click", () => {
                modalAlimento.style.display = "none";
            });

            btnCancelar?.addEventListener("click", (e) => {
                e.preventDefault();
                modalAlimento.style.display = "none";
            });

            window.addEventListener("click", (e) => {
                if (e.target === modalExportar) modalExportar.style.display = "none";
                if (e.target === modalAlimento) modalAlimento.style.display = "none";
            });

        });
    </script>
</body>

</html>