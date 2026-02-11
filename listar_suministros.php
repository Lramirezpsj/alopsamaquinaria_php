<?php
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
    $sql = "SELECT * FROM suministros ORDER BY fecha DESC LIMIT :limite";
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
    $suministros = array_slice($todosRegistros, $offset, $registrosPorPagina);

    if (empty($suministros)) {
        $mensaje = "No hay registros disponibles";
    }

} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

// Obtener opciones para combos
function obtenerOpciones($pdo, $tabla, $id, $nombre)
{
    $stmt = $pdo->query("SELECT $id, $nombre FROM $tabla ORDER BY $nombre");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$maquinas = obtenerOpciones($pdo, 'maquinas', 'id_maquina', 'maquina');
$clientes = obtenerOpciones($pdo, 'clientes', 'id_cliente', 'cliente');
$turnos = obtenerOpciones($pdo, 'turnos', 'id_turno', 'turno');

$usuarioLogeado = $_SESSION['usuario'];



?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suministros</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="listar_suministros.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="modalNSuministro.css">

</head>

<body>
    <?php include 'navbar.php'; ?>

    <!-- Botones de acción -->
    <div class="fab-actions">
        <!--<a href="dashboard.php" class="btn btn-primary mt-1 fas fa-home"> Inicio</a>-->
        <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'ADMIN' || $_SESSION['rol'] === 'USUARIO')): ?>
            <button id="btnExportarExcel" class="fab-btn">
                <i class="fas fa-file-excel"></i>
            </button>
        <?php endif; ?>

        <a class="fab-btn fab-primary" id="btnNuevo"> <i class="fas fa-plus"></i></a>

    </div>

    <div class="container">

        <h1>Suministros</h1>

        <!-- Modal para exportar -->
        <div id="modalExportar" class="modal">
            <div class="modal-contenido">
                <span class="cerrar-modal">&times;</span>
                <h2><i class="fas fa-file-excel"></i> Exportar suministros</h2>
                <form action="exportar_suministros.php" method="post">
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

        <!-- Modal para nuevo registro -->
        <div id="modalSuministro" class="modalNSuministro">
            <div class="modal-contenido-suministro">
                <span class="cerrar-modal">&times;</span>
                <h2 id="tituloModal">
                    <i class="fas fa-plus"></i> Nuevo registro
                </h2>

                <form action="crear_suministro.php" method="post" id="formSuministro">
                    <input type="hidden" name="id_suministro" id="id_suministro">

                    <div class="form-group-suministro">
                        <label for="fecha">Fecha:</label>
                        <input type="date" id="fecha" name="fecha" required>
                    </div>
                    <div class="form-group-suministro">
                        <label>Máquina:</label>
                        <select id="maquina" name="maquina">
                            <option value="">Selecciona una máquina</option>
                            <?php foreach ($maquinas as $maquina): ?>
                                <option value="<?= htmlspecialchars($maquina['maquina']) ?>">
                                    <?= htmlspecialchars($maquina['maquina']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-suministro">
                        <label>Hodómetro:</label>
                        <input type="number" id="horometro" name="horometro" step="any" required>
                    </div>

                    <div class="form-group-suministro">
                        <label>I_bomba:</label>
                        <input type="number" name="i_bomba" id="i_bomba" step="any">
                    </div>
                    <div class="form-group-suministro">
                        <label>F_bomba:</label>
                        <input type="number" name="f_bomba" id="f_bomba" step="any">
                    </div>

                    <div class="form-group-suministro">
                        <label>Total:</label>
                        <input type="number" step="0.01" name="total" id="total" required>
                    </div>

                    <div class="form-group-suministro">
                        <label>Comentarios:</label>
                        <textarea id="comentarios" name="comentarios"></textarea>
                    </div>

                    <div class="form-group-suministro">
                        <label>Operador:</label>
                        <input type="text" id="operador_visible" readonly>
                        <input type="hidden" name="operador" id="operador">
                    </div>

                    <div class="form-actions-horometro">
                        <button type="submit" class="btn-submit" id="btnSubmit">Guardar</button>
                        <a href="listar.php" class="btn-cancel" id="btnCancelar">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de suministros -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Acciones</th>
                        <th>Operador</th>
                        <th>Fecha</th>
                        <th>Máquina</th>
                        <th>Hodómetro</th>
                        <th>I_bomba</th>
                        <th>F_bomba</th>
                        <th>Total</th>
                        <th>Comentarios</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($suministros)): ?>
                        <?php foreach ($suministros as $suministro): ?>
                            <tr>
                                <td><?= htmlspecialchars($suministro['id_suministro']) ?></td>
                                <td class="actions-cell">
                                    <a class="btn-edit"
                                        onclick='abrirEditar(<?= json_encode($suministro, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        ✏️ Editar
                                    </a>

                                    <?php if ($_SESSION['rol'] === 'ADMIN'): ?>
                                        <a href="eliminar_suministro.php?id=<?= $suministro['id_suministro'] ?>" class="btn-delete"
                                            onclick="return confirm('¿Estás seguro de que deseas eliminar este registro?');">
                                            🗑️ Eliminar</a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap"><?= htmlspecialchars($suministro['operador']) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($suministro['fecha']) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($suministro['maquina']) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($suministro['horometro']) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($suministro['i_bomba']) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($suministro['f_bomba']) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($suministro['total']) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($suministro['comentarios']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="no-records"><?= $mensaje ?? 'No hay registros disponibles' ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Navegación entre páginas -->
            <?php if (!empty($suministros)): ?>
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
            const modalSuministro = document.getElementById("modalSuministro");

            const btnExportar = document.getElementById("btnExportarExcel");
            const btnNuevo = document.getElementById("btnNuevo");

            const cerrarExportar = modalExportar?.querySelector(".cerrar-modal");
            const cerrarSuministro = modalSuministro?.querySelector(".cerrar-modal");

            const form = document.getElementById("formSuministro");
            const tituloModal = document.getElementById("tituloModal");
            const btnSubmit = document.getElementById("btnSubmit");
            const btnCancelar = document.getElementById("btnCancelar");

            const idSuministro = document.getElementById("id_suministro");

            const operadorVisible = document.getElementById("operador_visible");
            const operadorHidden = document.getElementById("operador");

            const USUARIO_LOGEADO = "<?= htmlspecialchars($usuarioLogeado) ?>";

            /* ========= NUEVO ========= */
            function abrirNuevo() {
                form.reset();
                form.action = "crear_suministro.php";
                tituloModal.innerText = "Nuevo registro";
                btnSubmit.innerText = "Guardar";

                operadorVisible.value = USUARIO_LOGEADO;
                operadorHidden.value = USUARIO_LOGEADO;

                modalSuministro.style.display = "block";
            }


            /* ========= EDITAR ========= */
            window.abrirEditar = function (data) {
                form.action = "editar_suministro.php";
                tituloModal.innerText = "Editar registro";
                btnSubmit.innerText = "Actualizar";

                idSuministro.value = data.id_suministro;
                document.getElementById("fecha").value = data.fecha;
                document.getElementById("maquina").value = data.maquina;
                document.getElementById("horometro").value = data.horometro;
                document.getElementById("i_bomba").value = data.i_bomba;
                document.getElementById("f_bomba").value = data.f_bomba;
                document.getElementById("total").value = data.total;
                document.getElementById("comentarios").value = data.comentarios;

                operadorVisible.value = USUARIO_LOGEADO;
                operadorHidden.value = USUARIO_LOGEADO;

                modalSuministro.style.display = "block";
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

            cerrarSuministro?.addEventListener("click", () => {
                modalSuministro.style.display = "none";
            });

            btnCancelar?.addEventListener("click", (e) => {
                e.preventDefault();
                modalSuministro.style.display = "none";
            });

            window.addEventListener("click", (e) => {
                if (e.target === modalExportar) modalExportar.style.display = "none";
                if (e.target === modalSuministro) modalSuministro.style.display = "none";
            });

        });
    </script>
</body>

</html>