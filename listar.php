<?php
// 1. Limpieza radical de buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}

// 2. Iniciar sesión PRIMERO
session_start();

// 3. Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuarioLogeado = $_SESSION['usuario'];


$q = trim($_GET['q'] ?? '');


// 4. Incluir db.php DESPUÉS de manejar headers
require __DIR__ . '/db.php';

// 5. Verificar conexión
if (!isset($pdo) || $pdo === null) {
    header("Location: /error.php?code=db");
    exit();
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

// 6. Configurar headers de seguridad
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Configuración de paginación
$registrosPorPagina = 25; // 25 registros por página
$totalRegistrosDeseados = 600; // Mostrar solo los últimos 600 registros
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;


try {
    // Consulta para obtener los últimos 75 registros
    $sql = "SELECT * FROM horometros WHERE 1=1";
    $params = [];

    if ($q !== '') {
        $sql .= " AND (
        fecha LIKE ?
        OR maquina LIKE ?
        OR cliente LIKE ?
        OR operador LIKE ?
        OR turno LIKE ?
        OR comentarios LIKE ?
    )";

        $buscar = "%$q%";
        $params = array_fill(0, 6, $buscar);
    }

    $sql .= " ORDER BY fecha DESC LIMIT $offset, $registrosPorPagina";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $horometros = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

$sqlTotal = "SELECT COUNT(*) FROM horometros WHERE 1=1";
$paramsTotal = [];

if ($q !== '') {
    $sqlTotal .= " AND (
        fecha LIKE ?
        OR maquina LIKE ?
        OR cliente LIKE ?
        OR operador LIKE ?
        OR turno LIKE ?
        OR comentarios LIKE ?
    )";

    $paramsTotal = array_fill(0, 6, "%$q%");
}

$stmtTotal = $pdo->prepare($sqlTotal);
$stmtTotal->execute($paramsTotal);
$totalRegistros = $stmtTotal->fetchColumn();

$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hodómetros</title>
    <!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="listar.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="modalNHorometro.css">
    <style>
    </style>
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
        <h1>Hodómetros</h1>

        <!-- Buscador general -->
        <form method="GET" class="buscador-container">
            <input type="text" name="q" placeholder="🔍 Buscar" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button type="submit" class="btn-filtro">Buscar</button>
        </form>


        <!-- Modal para exportar -->
        <div id="modalExportar" class="modal">
            <div class="modal-contenido">
                <span class="cerrar-modal">&times;</span>
                <h2><i class="fas fa-file-excel"></i> Exportar Hormetros</h2>
                <form action="exportar_excel.php" method="post">
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
        <div id="modalHorometro" class="modalNHorometro">
            <div class="modal-contenido-horometro">
                <span class="cerrar-modal">&times;</span>
                <h2 id="tituloModal">
                    <i class="fas fa-plus"></i> Nuevo registro
                </h2>

                <form action="crear.php" method="post" id="formHorometro">
                    <input type="hidden" name="id_horometro" id="id_horometro">

                    <div class="form-group-horometro">
                        <label for="fecha">Fecha:</label>
                        <input type="date" id="fecha" name="fecha" required>
                    </div>
                    <div class="form-group-horometro">
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
                    <div class="form-group-horometro">
                        <label>Cliente:</label>
                        <select id="cliente" name="cliente" required>
                            <option value="">Selecciona un cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= htmlspecialchars($cliente['cliente']) ?>">
                                    <?= htmlspecialchars($cliente['cliente']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-horometro">
                        <label>Turno:</label>
                        <div style="display:flex; gap:6px;">
                            <select id="turno" name="turno" required>
                                <option value="">Selecciona un turno</option>
                                <?php foreach ($turnos as $turno): ?>
                                    <option value="<?= htmlspecialchars($turno['turno']) ?>">
                                        <?= htmlspecialchars($turno['turno']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="button" id="btnAddTurno" title="Agregar turno">➕</button>
                        </div>
                    </div>

                    <div class="form-group-horometro">
                        <label>Hodómetro Inicio:</label>
                        <input type="number" id="h_inicio" name="h_inicio" step="any" required>
                    </div>

                    <div class="form-group-horometro">
                        <label>Hodómetro Final:</label>
                        <input type="number" id="h_final" name="h_final" step="any">
                    </div>

                    <div class="form-group-horometro">
                        <label>Comentarios:</label>
                        <textarea id="comentarios" name="comentarios"></textarea>
                    </div>

                    <div class="form-group-horometro">
                        <?php if ($_SESSION['rol'] === 'ADMIN'): ?>
                            <label>Operador:</label>
                            <input type="text" id="operador_visible">
                            <input type="hidden" name="operador" id="operador">
                        <?php else: ?>
                            <label>Operador:</label>
                            <input type="text" id="operador_visible" readonly>
                            <input type="hidden" name="operador" id="operador" readonly>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions-horometro">
                        <button type="submit" class="btn-submit" id="btnSubmit">Guardar</button>
                        <a href="listar.php" class="btn-cancel" id="btnCancelar">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de horómetros -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th class="text-nowrap">ID</th>
                        <th class="text-nowrap">Acciones</th>
                        <th class="text-nowrap">Operador</th>
                        <th class="text-nowrap">Fecha</th>
                        <th class="text-nowrap">Máquina</th>
                        <th class="text-nowrap">Cliente</th>
                        <th class="text-nowrap">H. Inicio</th>
                        <th class="text-nowrap">H. Final</th>
                        <th class="text-nowrap">Total</th>
                        <th class="text-nowrap">Turno</th>
                        <th class="text-nowrap">Comentarios</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($horometros)): ?>
                        <?php foreach ($horometros as $horometro): ?>

                            <?php
                            // 👉 CÁLCULO DEL TOTAL EN HORAS (HH:MM)
                            if ($horometro['h_final'] !== null && $horometro['h_final'] !== '') {
                                $horas_reales = $horometro['h_final'] - $horometro['h_inicio'];

                                $horas = floor($horas_reales);
                                $minutos = round(($horas_reales - $horas) * 60);

                                // Ajuste por si minutos llega a 60
                                if ($minutos === 60) {
                                    $horas++;
                                    $minutos = 0;
                                }

                                $total_hhmm = sprintf('%02d:%02d', $horas, $minutos);
                            } else {
                                $total_hhmm = '--:--';
                            }
                            ?>

                            <tr>
                                <td><?= htmlspecialchars($horometro['id_horometro']) ?></td>

                                <td class="actions-cell">
                                    <a class="btn-edit"
                                        onclick='abrirEditar(<?= json_encode($horometro, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        ✏️ Editar
                                    </a>

                                    <?php if ($_SESSION['rol'] === 'ADMIN'): ?>
                                        <a href="eliminar.php?id=<?= $horometro['id_horometro'] ?>" class="btn-delete"
                                            onclick="return confirm('¿Estás seguro de que deseas eliminar este registro?');">
                                            🗑️ Eliminar
                                        </a>
                                    <?php endif; ?>
                                </td>

                                <td class="text-nowrap"><?= htmlspecialchars($horometro['operador']) ?></td>
                                <td class="fecha"><?= htmlspecialchars($horometro['fecha']) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($horometro['maquina']) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($horometro['cliente']) ?></td>
                                <td class="hora"><?= htmlspecialchars($horometro['h_inicio']) ?></td>
                                <td class="hora"><?= htmlspecialchars($horometro['h_final']) ?></td>

                                <!-- 👉 TOTAL HORAS HH:MM -->
                                <td class="text-nowrap"><?= $total_hhmm ?></td>

                                <td class="text-nowrap"><?= htmlspecialchars($horometro['turno']) ?></td>
                                <td class="comentarios"><?= htmlspecialchars($horometro['comentarios']) ?></td>
                            </tr>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="no-records">
                                <?= $mensaje ?? 'No hay registros disponibles' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Navegación entre páginas -->
            <?php if (!empty($horometros)): ?>
                <div class="paginacion">
                    <?php if ($paginaActual > 1): ?>
                        <a href="?pagina=<?= $paginaActual - 1 ?>&q=<?= urlencode($q) ?>" class="btn-pagina">Anterior</a>
                    <?php endif; ?>

                    <span class="info-pagina">
                        Página <?= $paginaActual ?> de <?= $totalPaginas ?>
                    </span>

                    <?php if ($paginaActual < $totalPaginas): ?>
                        <a href="?pagina=<?= $paginaActual + 1 ?>&q=<?= urlencode($q) ?>" class="btn-pagina">Siguiente</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {

            /* ========= CONSTANTES PHP ========= */
            const USUARIO_LOGEADO = "<?= htmlspecialchars($usuarioLogeado) ?>";
            const ROL_LOGEADO = "<?= htmlspecialchars($_SESSION['rol']) ?>";

            /* ========= ELEMENTOS ========= */
            const modalExportar = document.getElementById("modalExportar");
            const modalHorometro = document.getElementById("modalHorometro");

            const btnExportar = document.getElementById("btnExportarExcel");
            const btnNuevo = document.getElementById("btnNuevo");

            const cerrarExportar = modalExportar?.querySelector(".cerrar-modal");
            const cerrarHorometro = modalHorometro?.querySelector(".cerrar-modal");

            const form = document.getElementById("formHorometro");
            const tituloModal = document.getElementById("tituloModal");
            const btnSubmit = document.getElementById("btnSubmit");
            const btnCancelar = document.getElementById("btnCancelar");

            const idHorometro = document.getElementById("id_horometro");

            const operadorVisible = document.getElementById("operador_visible");
            const operadorHidden = document.getElementById("operador");

            /* ========= SINCRONIZAR VISIBLE → HIDDEN (SEGURO) ========= */
            if (operadorVisible && operadorHidden) {
                operadorVisible.addEventListener("input", () => {
                    operadorHidden.value = operadorVisible.value;
                });
            }

            /* ========= NUEVO ========= */
            function abrirNuevo() {
                form.reset();
                form.action = "crear.php";
                tituloModal.innerText = "Nuevo registro";
                btnSubmit.innerText = "Guardar";

                if (operadorVisible && operadorHidden) {
                    operadorVisible.value = USUARIO_LOGEADO;
                    operadorHidden.value = USUARIO_LOGEADO;
                    operadorVisible.readOnly = true;
                }

                modalHorometro.style.display = "block";
            }

            /* ========= EDITAR ========= */
            window.abrirEditar = function (data) {
                form.action = "editar.php";
                tituloModal.innerText = "Editar registro";
                btnSubmit.innerText = "Actualizar";

                idHorometro.value = data.id_horometro;
                fecha.value = data.fecha;
                maquina.value = data.maquina;
                cliente.value = data.cliente;
                turno.value = data.turno;
                h_inicio.value = data.h_inicio;
                h_final.value = data.h_final;
                comentarios.value = data.comentarios;

                if (operadorVisible && operadorHidden) {
                    operadorVisible.value = data.operador;
                    operadorHidden.value = data.operador;

                    operadorVisible.readOnly = (ROL_LOGEADO !== 'ADMIN');
                }

                modalHorometro.style.display = "block";
            };

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

            cerrarHorometro?.addEventListener("click", () => {
                modalHorometro.style.display = "none";
            });

            btnCancelar?.addEventListener("click", (e) => {
                e.preventDefault();
                modalHorometro.style.display = "none";
            });

            window.addEventListener("click", (e) => {
                if (e.target === modalExportar) modalExportar.style.display = "none";
                if (e.target === modalHorometro) modalHorometro.style.display = "none";
            });

            //==== para guardar turnos =====

            const modalTurno = document.getElementById("modalTurno");
            const btnAddTurno = document.getElementById("btnAddTurno");
            const cerrarTurno = document.getElementById("cerrarTurno");
            const cancelarTurno = document.getElementById("cancelarTurno");
            const formTurno = document.getElementById("formTurno");
            const selectTurno = document.getElementById("turno");

            btnAddTurno?.addEventListener("click", () => {
                if (modalTurno) {
                    modalTurno.style.display = "block";
                    const nuevoTurnoInput = document.getElementById("nuevoTurno");
                    if (nuevoTurnoInput) nuevoTurnoInput.value = "";
                }
            });

            cerrarTurno?.addEventListener("click", () => {
                if (modalTurno) modalTurno.style.display = "none";
            });
            
            cancelarTurno?.addEventListener("click", () => {
                if (modalTurno) modalTurno.style.display = "none";
            });

            formTurno?.addEventListener("submit", e => {
                e.preventDefault();

                fetch("guardar_turno.php", {
                    method: "POST",
                    body: new FormData(formTurno)
                })
                    .then(r => r.json())
                    .then(res => {
                        if (!res.ok) {
                            alert(res.error);
                            return;
                        }

                        // agregar al select y seleccionar
                        if (selectTurno) {
                            const opt = document.createElement("option");
                            opt.value = res.turno;
                            opt.textContent = res.turno;
                            opt.selected = true;
                            selectTurno.appendChild(opt);
                        }

                        if (modalTurno) modalTurno.style.display = "none";
                    })
                    .catch(error => {
                        console.error("Error al guardar turno:", error);
                        alert("Error al guardar el turno");
                    });
            });
        });
    </script>

    <div id="modalTurno" class="modalNHorometro" style="display:none;">
        <div class="modal-contenido-horometro">
            <span class="cerrar-modal" id="cerrarTurno">&times;</span>
            <h3>Agregar turno</h3>

            <form id="formTurno">
                <input type="text" name="turno" id="nuevoTurno" required placeholder="Ej: 3">

                <div class="form-actions-horometro">
                    <button type="submit" class="btn-submit">Guardar</button>
                    <button type="button" class="btn-cancel" id="cancelarTurno">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>