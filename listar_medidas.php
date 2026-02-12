<?php

// Limpieza de buffers
/*while (ob_get_level() > 0) {
    ob_end_clean();
}*/

// inicia sesión
session_start();

// verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuarioLogeado = $_SESSION['usuario'];

// incluir db.php
require __DIR__ . '/db.php';

// obtener opciones para combos
/*function obtenerOpciones($pdo, $tabla, $id, $nombre): mixed
{
    $stmt = $pdo->query("SELECT $id, $nombre FROM $tabla ORDER BY $nombre");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$maquinas = obtenerOpciones($pdo, 'maquinas', 'id_maquina', 'maquina');
$clientes = obtenerOpciones($pdo, 'clientes', 'id_cliente', 'cliente');
$turnos = obtenerOpciones($pdo, 'turnos', 'id_turno', 'turno');*/

// Configueración de headers de seguridad
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');

/*// Configuración de paginación
$registrosPorPagina = 25; // 25 registros por página
$totalRegistrosDeseados = 600; // Mostrar solo los últimos 600 registros
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;*/

try {
    // consulta sql
    $sql = 'SELECT * FROM medidas ORDER BY medida DESC';
    $stmt = $pdo->query($sql);
    $medidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('error en la base de datos: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="listar_medidas.css">
    <link rel="stylesheet" href="modalNMedida.css">
    <link rel="stylesheet" href="navbar.css">
    <title>medidas</title>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <!-- botones de Acciones-->
    <div class="fab-actions">
        <!-- <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] == 'ADMIN' || $_SESSION['rol'] == 'USUARIO')): ?>
            <button id="btnExportarExcel" class="fab-btn">
                <i class="fas fa-file-excel"></i>
            </button>
        <?php endif; ?>-->

        <a class="fab-btn fab-primary" id="btnNuevo"><i class="fas fa-plus"></i></a>
    </div>

    <div class="container">
        <h1>Medidas</h1>

        <!-- modal para nuevo registro-->
        <div id="modalMedida" class="modalNMedida">
            <div class="modal-contenido-medida">
                <span class="cerrar-modal"></span>

                <h2 id="tituloModal">
                    <i class="fas fa-plus"></i> Nuevo registro
                </h2>
                <form action="crearMedida.php" method="post" id="formMedida">
                    <input type="hidden" name="id_medida" id="id_medida">
                    <div class="form-group-medida">
                        <label for="">Medida</label>
                        <input type="text" name="medida" id="medida" required>
                    </div>
                    <div class="form-actions-medida">
                        <button type="submit" class="btn-submit" id="btn-submit"> Guardar</button>
                        <a href="listar_medidas.php" class="btn-cancel" id="btn-cancel"> Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- tabla medidas-->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Acciones</th>
                        <th>Medida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($medidas)): ?>
                        <?php foreach ($medidas as $medida): ?>
                            <tr>

                                <td><?= htmlspecialchars($medida['id_medida']) ?></td>
                                <td class="actions-cell">
                                    <a class="btn-edit"
                                        onclick='abrirEditar (<?= json_encode($medida, JSON_HEX_APOS | JSON_HEX_QUOT) ?>); return false;'>
                                        ✏️ Editar
                                    </a>
                                    <?php if ($_SESSION['rol'] === 'ADMIN'): ?>
                                        <a href="eliminar_medida.php?id=<?= $medida['id_medida'] ?>" class="btn-delete"
                                            onclick="return confirm('¿Estás seguro de que deseas eliminar este registro?');">
                                            🗑️ Eliminar
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($medida['medida']) ?></td>
                            </tr>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="no records">
                                <?= $mensaje ?? 'No hay registros disponibles' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            //Constantes php
            const USUARIO_LOGEADO = "<?= htmlspecialchars_decode($usuarioLogeado) ?>";
            const ROL_LOGEADO = "<?= htmlspecialchars_decode($_SESSION['rol']) ?>";

            // Elementos
            const modalMedida = document.getElementById("modalMedida");
            const btnNuevo = document.getElementById("btnNuevo");
            const cerrarMedida = modalMedida?.querySelector(".cerrar-modal");
            const form = document.getElementById("formMedida");
            const tituloModal = document.getElementById("tituloModal");
            const btnSubmit = document.getElementById("btn-submit");
            const btnCancelar = document.getElementById("btn-cancel");
            const idMedida = document.getElementById("id_medida");

            // Nuevo
            function abrirNuevo() {
                form.reset();
                form.action = "crear_medida.php";
                tituloModal.innerText = "Nuevo registro";
                btnSubmit.innerText = "Guardar";

                modalMedida.style.display = "block";
            }

            window.abrirEditar = function (data) {
                form.action = "editar_medida.php";
                tituloModal.innerText = "Editar registro";
                btnSubmit.innerText = "Actualizar";

                idMedida.value = data.id_medida;
                medida.value = data.medida;

                modalMedida.style.display = "block";
            }

            // Eventos
            btnNuevo.addEventListener("click", abrirNuevo);

            cerrarMedida?.addEventListener("click", () => {
                modalMedida.style.display = "none";
            });
            btnCancelar.addEventListener("click", (e) => {
                e.preventDefault();
                modalMedida.style.display = "none";
            });
            window.addEventListener("click", (e) => {
                if (e.target === modalMedida) modalMedida.style.display = "none";
            });
        });
    </script>
</body>

</html>