<?php
/* ================== SESIÓN ================== */
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuarioLogeado = $_SESSION['usuario'];

require __DIR__ . '/db.php';

/* ================== DATOS ================== */
$esAdmin = ($_SESSION['rol'] === 'ADMIN');

/* ================== FILTRO DE BÚSQUEDA ================== */
$q = trim($_GET['q'] ?? '');

/* ================== PAGINACIÓN ================== */
$registrosPorPagina = 25;
$paginaActual = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

/* ================== CONSULTA ================== */
$sql = "SELECT * FROM contenedores WHERE 1=1";
$params = [];

/*
    🔎 SI EL USUARIO ESCRIBE ALGO EN EL BUSCADOR
    se busca en VARIAS COLUMNAS (tipo AppSheet)
*/
if ($q !== '') {

    $sql .= " AND (
        fecha LIKE ?
        OR maquina LIKE ?
        OR contenedor LIKE ?
        OR medida LIKE ?
        OR movimiento LIKE ?
        OR colaborador LIKE ?
        OR comentarios LIKE ?
        OR operador LIKE ?
    )";

    /*
        👇 El mismo texto se usa para TODAS las columnas
    */
    $buscar = "%$q%";
    $params = array_fill(0, 8, $buscar);
}

/*
    📄 ORDEN + PAGINACIÓN
*/
$sql .= " ORDER BY fecha DESC LIMIT $offset, $registrosPorPagina";

/*
    ▶ EJECUCIÓN
*/
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contenedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================== TOTAL REGISTROS ================== */
$sqlTotal = "SELECT COUNT(*) FROM contenedores WHERE 1=1";
$paramsTotal = [];

/*
    🔎 MISMA BÚSQUEDA GENERAL QUE ARRIBA
    (OBLIGATORIO para que la paginación funcione bien)
*/
if ($q !== '') {

    $sqlTotal .= " AND (
        fecha LIKE ?
        OR maquina LIKE ?
        OR contenedor LIKE ?
        OR medida LIKE ?
        OR movimiento LIKE ?
        OR colaborador LIKE ?
        OR comentarios LIKE ?
        OR operador LIKE ?
    )";

    $paramsTotal = array_fill(0, 8, "%$q%");
}

$stmtTotal = $pdo->prepare($sqlTotal);
$stmtTotal->execute($paramsTotal);
$totalRegistros = $stmtTotal->fetchColumn();

$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

$queryString = '';

if ($q !== '') {
    $queryString = '&q=' . urlencode($q);
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
$medidas = obtenerOpciones($pdo, 'medidas', 'id_medida', 'medida');
$movimientos = obtenerOpciones($pdo, 'movimientos', 'id_movimiento', 'movimiento');
$colaboradores = obtenerOpciones($pdo, 'colaboradores', 'id_colaborador', 'nombre');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contenedores</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="listar_contenedores.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="modalNContenedor.css">
    <style>

    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <!-- ===== BOTONES FLOTANTES ===== -->
    <div class="fab-actions">
        <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'ADMIN' || $_SESSION['rol'] === 'USUARIO')): ?>
            <button class="fab-btn fab-secondary" id="btnExportar" title="Exportar">
                <i class="fas fa-file-excel"></i>
            </button>
        <?php endif; ?>

        <a class="fab-btn fab-primary" id="btnNuevo"> <i class="fas fa-plus"></i></a>

    </div>

    <div class="container">
        <h1>Contenedores</h1>

        <div class="barra-superior">

            <form method="GET" class="barra-busqueda">
                <input type="text" name="q" placeholder="🔍 Buscar" value="<?= htmlspecialchars($q) ?>">
                <button type="submit" class="btn-filtro">Buscar</button>
            </form>

            <div class="acciones-derecha">
                <button class="btn-importar" onclick="abrirImportar()">
                    📥 Importar Excel
                </button>
            </div>

        </div>

        <!-- Modal para exportar -->
        <div id="modalExportar" class="modal">
            <div class="modal-contenido">
                <span class="cerrar-modal">&times;</span>
                <h2><i class="fas fa-file-excel"></i> Exportar Contenedores</h2>
                <form action="exportar_contenedores.php" method="post">
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

        <!-- MODAL CONTENEDOR -->
        <div id="modalContenedor" class="modalNContenedor">
            <div class="modal-contenido-contenedor">
                <span class="cerrar-modal">&times;</span>

                <h2 id="tituloModal">Nuevo Contenedor</h2>

                <form action="crear_contenedor.php" id="formContenedor" method="post">
                    <input type="hidden" name="id_contenedor" id="id_contenedor">

                    <div class="form-group-contenedor">
                        <label>Fecha</label>
                        <input type="date" name="fecha" id="fecha" required>
                    </div>

                    <div class="form-group-contenedor">
                        <label>Contenedor</label>
                        <input type="text" name="contenedor" id="contenedor" maxlength="11" required>
                        <small id="mensajeContenedor"></small>
                    </div>

                    <div class="form-group-contenedor">
                        <label>Medida</label>
                        <select name="medida" id="medida" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($medidas as $m): ?>
                                <option value="<?= htmlspecialchars($m['medida']) ?>">
                                    <?= htmlspecialchars($m['medida']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-contenedor">
                        <label>Movimiento</label>
                        <select name="movimiento" id="movimiento" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($movimientos as $m): ?>
                                <option value="<?= htmlspecialchars($m['movimiento']) ?>">
                                    <?= htmlspecialchars($m['movimiento']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-contenedor">
                        <label>Máquina</label>
                        <select name="maquina" id="maquina" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($maquinas as $m): ?>
                                <option value="<?= htmlspecialchars($m['maquina']) ?>">
                                    <?= htmlspecialchars($m['maquina']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-contenedor">
                        <label>Colaborador</label>
                        <select name="colaborador" id="colaborador" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($colaboradores as $c): ?>
                                <option value="<?= htmlspecialchars($c['nombre']) ?>">
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-contenedor">
                        <label>Comentarios</label>
                        <textarea name="comentarios" id="comentarios"></textarea>
                    </div>

                    <div class="form-group-contenedor">
                        <?php if ($_SESSION['rol'] === 'ADMIN'): ?>
                            <label>Usuario:</label>
                            <input type="text" id="operador_visible">
                            <input type="hidden" name="operador" id="operador">
                        <?php else: ?>
                            <label>Usuario:</label>
                            <input type="text" id="operador_visible" readonly>
                            <input type="hidden" name="operador" id="operador" readonly>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions-contenedor">
                        <button type="submit" class="btnSubmit" id="btnSubmit">Guardar</button>
                        <button type="button" class="btnCancelar" id="btnCancelar">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- <form action="importar.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="archivo" accept=".xlsx,.xls" required>
            <button type="submit">Importar Excel</button>
        </form>-->

        <!-- ===== TABLA ===== -->
        <div class="table-container">
            <form method="Get">
                <table id="tabla-contenedores">
                    <thead>
                        <tr>
                            <th class="text-nowrap">ID</th>
                            <th class="text-nowrap">Acciones</th>
                            <th class="text-nowrap">Usuario</th>
                            <th class="text-nowrap">Fecha</th>
                            <th class="text-nowrap">Máquina</th>
                            <th class="text-nowrap">Contenedor</th>
                            <th class="text-nowrap">Medida</th>
                            <th class="text-nowrap">Movimiento</th>
                            <th class="text-nowrap">Operador</th>
                            <th class="text-nowrap">Comentarios</th>
                        </tr>

                    <tbody>
                        <?php if ($contenedores):
                            foreach ($contenedores as $c): ?>
                                <tr>
                                    <td><?= $c['id_contenedor'] ?></td>
                                    <td class="actions-cell">
                                        <a class="btn-edit"
                                            onclick='abrirEditar(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            ✏️ Editar
                                        </a>

                                        <?php if ($esAdmin): ?>
                                            <a class="btn-delete" href="eliminar_contenedor.php?id=<?= $c['id_contenedor'] ?>"
                                                onclick="return confirm('¿Eliminar registro?')">🗑️ Eliminar</a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap"><?= htmlspecialchars($c['operador']) ?></td>
                                    <td class="text-nowrap"><?= $c['fecha'] ?></td>
                                    <td class="text-nowrap"><?= htmlspecialchars($c['maquina']) ?></td>
                                    <td class="text-nowrap"><?= htmlspecialchars($c['contenedor']) ?></td>
                                    <td class="text-nowrap"><?= htmlspecialchars($c['medida']) ?></td>
                                    <td class="text-nowrap"><?= htmlspecialchars($c['movimiento']) ?></td>
                                    <td class="text-nowrap"><?= htmlspecialchars($c['colaborador']) ?></td>
                                    <td class="text-nowrap"><?= htmlspecialchars($c['comentarios']) ?></td>
                                </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="9">No hay registros</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <!-- ===== PAGINACIÓN ===== -->
        <?php if ($totalPaginas > 1): ?>
            <div class="paginacion">
                <?php if ($paginaActual > 1): ?>
                    <a href="?pagina=<?= $paginaActual - 1 ?><?= $queryString ?>" class="btn-pagina">
                        &laquo; Anterior
                    </a>
                <?php endif; ?>

                <span>Página <?= $paginaActual ?> de <?= $totalPaginas ?></span>

                <?php if ($paginaActual < $totalPaginas): ?>
                    <a href="?pagina=<?= $paginaActual + 1 ?><?= $queryString ?>" class="btn-pagina">
                        Siguiente &raquo;
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <script>
            document.addEventListener("DOMContentLoaded", () => {

                /* ========= CONSTANTES PHP ========= */
                const USUARIO_LOGEADO = "<?= htmlspecialchars($usuarioLogeado) ?>";
                const ROL_LOGEADO = "<?= htmlspecialchars($_SESSION['rol']) ?>";

                /* ========= MODAL EXPORTAR ========= */
                const modalExportar = document.getElementById("modalExportar");
                const btnExportar = document.getElementById("btnExportar");
                const operadorVisible = document.getElementById("operador_visible");
                const operadorHidden = document.getElementById("operador");
                const cerrarExportar = modalExportar?.querySelector(".cerrar-modal");

                btnExportar?.addEventListener("click", () => {
                    const fechaInicio = document.getElementById("fecha_inicio");
                    const fechaFin = document.getElementById("fecha_fin");
                    if (fechaInicio) fechaInicio.value = "";
                    if (fechaFin) fechaFin.value = "";
                    if (modalExportar) modalExportar.style.display = "block";
                });

                cerrarExportar?.addEventListener("click", () => {
                    if (modalExportar) modalExportar.style.display = "none";
                });

                /* ========= MODAL CONTENEDOR ========= */
                const modalContenedor = document.getElementById("modalContenedor");
                const btnNuevo = document.getElementById("btnNuevo");
                const cerrarContenedor = modalContenedor.querySelector(".cerrar-modal");
                const btnCancelar = document.getElementById("btnCancelar");

                const form = document.getElementById("formContenedor");
                const tituloModal = document.getElementById("tituloModal");
                const btnSubmit = document.getElementById("btnSubmit");
                const idContenedor = document.getElementById("id_contenedor");

                /* ========= SINCRONIZAR VISIBLE → HIDDEN (SEGURO) ========= */
                if (operadorVisible && operadorHidden) {
                    operadorVisible.addEventListener("input", () => {
                        operadorHidden.value = operadorVisible.value;
                    });
                }

                function abrirNuevo() {
                    form.reset();
                    form.action = "crear_contenedor.php";
                    idContenedor.value = "";
                    tituloModal.innerText = "Nuevo registro";
                    btnSubmit.innerText = "Guardar";
                    if (operadorVisible && operadorHidden) {
                        operadorVisible.value = USUARIO_LOGEADO;
                        operadorHidden.value = USUARIO_LOGEADO;
                        operadorVisible.readOnly = true;
                    }
                    modalContenedor.style.display = "block";
                }

                window.abrirEditar = function (data) {
                    form.action = "editar_contenedor.php";
                    tituloModal.innerText = "Editar registro";
                    btnSubmit.innerText = "Actualizar";

                    idContenedor.value = data.id_contenedor;
                    fecha.value = data.fecha;
                    maquina.value = data.maquina;
                    contenedor.value = data.contenedor;
                    medida.value = data.medida;
                    movimiento.value = data.movimiento;
                    colaborador.value = data.colaborador;
                    comentarios.value = data.comentarios;
                    if (operadorVisible && operadorHidden) {
                        operadorVisible.value = data.operador;
                        operadorHidden.value = data.operador;

                        operadorVisible.readOnly = (ROL_LOGEADO !== 'ADMIN');
                    }

                    modalContenedor.style.display = "block";
                };

                btnNuevo?.addEventListener("click", abrirNuevo);

                cerrarContenedor?.addEventListener("click", () => {
                    if (modalContenedor) modalContenedor.style.display = "none";
                });

                btnCancelar?.addEventListener("click", (e) => {
                    e.preventDefault();
                    modalContenedor.style.display = "none";
                });

                /* ========= CLICK FUERA ========= */
                window.addEventListener("click", (e) => {
                    if (e.target === modalExportar) modalExportar.style.display = "none";
                    if (e.target === modalContenedor) modalContenedor.style.display = "none";
                });

                /* ========= VALIDACIÓN FECHAS ========= */
                const formExportar = modalExportar.querySelector("form");
                formExportar.addEventListener("submit", (e) => {
                    const inicio = new Date(document.getElementById("fecha_inicio").value);
                    const fin = new Date(document.getElementById("fecha_fin").value);

                    if (fin < inicio) {
                        alert("La fecha de fin no puede ser anterior a la fecha de inicio");
                        e.preventDefault();
                    }
                });

            });

            function abrirImportar() {
                window.location.href = 'importar_contenedores.php';
            }

        /* validar contenedores*/

            function validarContenedorJS(contenedor) {

                contenedor = contenedor.toUpperCase().trim();

                if (!/^[A-Z]{4}[0-9]{7}$/.test(contenedor)) {
                    return false;
                }

                const letras = {
                    A: 10, B: 12, C: 13, D: 14, E: 15, F: 16, G: 17, H: 18,
                    I: 19, J: 20, K: 21, L: 23, M: 24, N: 25, O: 26, P: 27,
                    Q: 28, R: 29, S: 30, T: 31, U: 32, V: 34, W: 35, X: 36,
                    Y: 37, Z: 38
                };

                let suma = 0;

                for (let i = 0; i < 10; i++) {
                    let char = contenedor[i];

                    let valor = isNaN(char)
                        ? letras[char]
                        : parseInt(char);

                    suma += valor * Math.pow(2, i);
                }

                let digito = suma % 11;
                if (digito === 10) digito = 0;

                return digito === parseInt(contenedor[10]);
            }

            /* mostrar error y convertir a mayúsculas */

            const inputContenedor = document.getElementById("contenedor");
            const mensaje = document.getElementById("mensajeContenedor");

            if (inputContenedor && mensaje) {
                inputContenedor.addEventListener("input", function () {
                    // Convertir a mayúsculas
                    this.value = this.value.toUpperCase();

                    // Validar contenedor
                    if (validarContenedorJS(this.value)) {
                        mensaje.textContent = "✔ Contenedor válido";
                        mensaje.style.color = "green";
                    } else {
                        mensaje.textContent = "✖ Contenedor inválido";
                        mensaje.style.color = "red";
                    }
                });
            }

            /* enviar formulario */
            const formContenedor = document.getElementById("formContenedor");
            if (formContenedor && inputContenedor) {
                formContenedor.addEventListener("submit", function (e) {
                    if (!validarContenedorJS(inputContenedor.value)) {
                        e.preventDefault();
                        alert("El contenedor no es válido");
                    }
                });
            }

            /* mayusculas comentarios */
            const inputComentarios = document.getElementById("comentarios");
            if (inputComentarios) {
                inputComentarios.addEventListener("input", function () {
                    this.value = this.value.toUpperCase();
                });
            }
        </script>
    </div>

</body>

</html>