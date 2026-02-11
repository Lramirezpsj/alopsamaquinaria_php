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

require 'db.php';

// Configuración de paginación
$registrosPorPagina = 25;
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
if ($paginaActual < 1)
    $paginaActual = 1; // Asegurar que no sea negativo
$offset = ($paginaActual - 1) * $registrosPorPagina;

// Consulta principal PAGINADA con conteo de total
try {
    // Primero obtener el total de registros
    $totalRegistrosReal = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
    $totalRegistros = min(1000, $totalRegistrosReal);
    $totalPaginas = ceil($totalRegistros / $registrosPorPagina);

    // Ajustar página actual si es mayor que el total de páginas
    if ($paginaActual > $totalPaginas && $totalPaginas > 0) {
        $paginaActual = $totalPaginas;
        $offset = ($paginaActual - 1) * $registrosPorPagina;
    }

    // Consulta paginada
    $sql = "
    SELECT 
        p.id_pedido,
        p.fecha,
        p.colaborador,
        c.nombre AS nombre_colaborador
    FROM pedidos p
    JOIN colaboradores c ON p.colaborador = c.id_colaborador
    ORDER BY p.fecha DESC, p.id_pedido DESC
    LIMIT :limit OFFSET :offset
";


    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $registrosPorPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener detalles de alimentos para cada pedido
    foreach ($pedidos as &$pedido) {

        $stmt = $pdo->prepare("
        SELECT 
            a.id_alimento,
            a.nombre,
            a.precio,
            dp.cantidad
        FROM detalle_pedidos dp
        JOIN alimentos a ON dp.id_alimento = a.id_alimento
        WHERE dp.id_pedido = ?
    ");
        $stmt->execute([$pedido['id_pedido']]);
        $pedido['alimentos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Total
        $pedido['total'] = array_reduce($pedido['alimentos'], function ($sum, $item) {
            return $sum + ($item['precio'] * $item['cantidad']);
        }, 0);
    }
    unset($pedido);


} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}
$alimentos = $pdo->query("SELECT * FROM alimentos")->fetchAll();
$colaboradores = $pdo->query("SELECT id_colaborador, nombre FROM colaboradores")->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Listado Completo de Pedidos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="listar_pedidos.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="modalNPedido.css">
    <style>
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

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

        <h1>Pedidos</h1>

        <?php if (empty($pedidos)): ?>
            <p>No hay pedidos registrados.</p>
        <?php else: ?>

            <!-- Modal para exportar -->
            <div id="modalExportar" class="modal">
                <div class="modal-contenido">
                    <span class="cerrar-modal">&times;</span>
                    <h2><i class="fas fa-file-excel"></i> Exportar suministros</h2>
                    <form action="exportar_alimento.php" method="post">
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
            <div id="modalPedido" class="modalNPedido">
                <div class="modal-contenido-pedido">
                    <span class="cerrar-modal">&times;</span>
                    <h2 id="tituloModal">
                        <i class="fas fa-plus"></i> Nuevo pedido
                    </h2>

                    <form action="crear_pedido.php" method="post" id="formPedido">
                        <input type="hidden" name="id_pedido" id="id_pedido">

                        <div class="form-group-pedido">
                            <label for="fecha">Fecha:</label>
                            <input type="date" id="fecha" name="fecha" required>
                        </div>

                        <div class="form-group-pedido">
                            <select name="colaborador" id="colaborador" required>
                                <option value="">Seleccione un colaborador</option>
                                <?php foreach ($colaboradores as $c): ?>
                                    <option value="<?= $c['id_colaborador'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="alimentos-scroll">
                            <?php foreach ($alimentos as $alimento): ?>
                                <div class="alimento-item"
                                    style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background-color: #f1f7fd; margin-bottom: 8px; border-radius: 5px;">
                                    <label style="margin: 0;">
                                        <input type="checkbox" name="alimentos[]" value="<?= $alimento['id_alimento'] ?>">
                                        <strong><?= htmlspecialchars($alimento['nombre']) ?>
                                            (Q<?= number_format($alimento['precio'], 2) ?>)</strong>
                                    </label>
                                    <input type="number" name="cantidades[]" min="1" value="1" class="cantidad-input"
                                        style="width: 80px; padding: 4px;">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-actions-pedido">
                            <button type="submit" class="btn-submit" id="btnSubmit">Guardar</button>
                            <a href="listar.php" class="btn-cancel" id="btnCancelar">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Colaborador</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><?= htmlspecialchars($pedido['id_pedido']) ?></td>
                                <td><?= htmlspecialchars($pedido['nombre_colaborador']) ?></td>
                                <td><?= htmlspecialchars($pedido['fecha']) ?></td>
                                <td>Q<?= number_format($pedido['total'], 2) ?></td>
                                <td>
                                    <a class="btn-edit"
                                        onclick='abrirEditar(<?= json_encode($pedido, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        ✏️ Editar
                                    </a>

                                    <?php if ($_SESSION['rol'] === 'ADMIN'): ?>
                                        <a href="eliminar_pedido.php?id=<?= $pedido['id_pedido'] ?>" class="btn-delete"
                                            onclick="return confirm('¿Estás seguro de que deseas eliminar este registro?');">
                                            🗑️ Eliminar</a>
                                    <?php endif; ?>

                                    <span class="detalles-toggle" onclick="toggleDetalles(<?= $pedido['id_pedido'] ?>)">▼
                                        Detalles</span> |
                                    <a href="ver_pedido.php?id=<?= $pedido['id_pedido'] ?>">Ver</a>

                                    <div id="detalles-<?= $pedido['id_pedido'] ?>" class="detalles-content"
                                        style="display:none;">
                                        <?php if (!empty($pedido['alimentos'])): ?>
                                            <?php foreach ($pedido['alimentos'] as $alimento): ?>
                                                <div class="alimento-item">
                                                    <span><?= htmlspecialchars($alimento['nombre']) ?></span>
                                                    <span><?= $alimento['cantidad'] ?> x
                                                        Q<?= number_format($alimento['precio'], 2) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                            <div class="total">
                                                Total: Q<?= number_format($pedido['total'], 2) ?>
                                            </div>
                                        <?php else: ?>
                                            <p>No hay alimentos en este pedido.</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación -->
        <div class="paginacion">
            <?php if ($paginaActual > 1): ?>
                <a href="?pagina=<?= $paginaActual - 1 ?>">&laquo; Anterior</a>
            <?php endif; ?>

            <?php
            // Mostrar solo 5 páginas alrededor de la actual para no saturar
            $inicio = max(1, $paginaActual - 2);
            $fin = min($totalPaginas, $paginaActual + 2);

            if ($inicio > 1): ?>
                <span>...</span>
            <?php endif;

            for ($i = $inicio; $i <= $fin; $i++): ?>
                <a href="?pagina=<?= $i ?>" <?= ($i == $paginaActual) ? 'class="active"' : '' ?>>
                    <?= $i ?>
                </a>
            <?php endfor;

            if ($fin < $totalPaginas): ?>
                <span>...</span>
            <?php endif; ?>

            <?php if ($paginaActual < $totalPaginas): ?>
                <a href="?pagina=<?= $paginaActual + 1 ?>">Siguiente &raquo;</a>
            <?php endif; ?>
        </div>

        <script>
            function toggleDetalles(id) {
                const element = document.getElementById(`detalles-${id}`);
                element.style.display = element.style.display === 'none' ? 'block' : 'none';
            }
        </script>
    <?php endif; ?>

    <script>
        document.addEventListener("DOMContentLoaded", () => {

            /* ========= ELEMENTOS ========= */
            const modalExportar = document.getElementById("modalExportar");
            const modalPedido = document.getElementById("modalPedido");

            const btnExportar = document.getElementById("btnExportarExcel");
            const btnNuevo = document.getElementById("btnNuevo");

            const cerrarExportar = modalExportar?.querySelector(".cerrar-modal");
            const cerrarPedido = modalPedido?.querySelector(".cerrar-modal");

            const form = document.getElementById("formPedido");
            const tituloModal = document.getElementById("tituloModal");
            const btnSubmit = document.getElementById("btnSubmit");
            const btnCancelar = document.getElementById("btnCancelar");

            const idPedido = document.getElementById("id_pedido");
            const fecha = document.getElementById("fecha");
            const colaborador = document.getElementById("colaborador");

            /* ========= NUEVO ========= */
            function abrirNuevo() {
                form.reset();
                form.action = "crear_pedido.php";
                tituloModal.innerText = "Nuevo pedido";
                btnSubmit.innerText = "Guardar";

                // Desmarcar checkboxes
                document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                document.querySelectorAll('.cantidad-input').forEach(ci => ci.value = 1);

                modalPedido.style.display = "block";
            }

            /* ========= EDITAR ========= */
            window.abrirEditar = function (data) {

                form.action = "editar_pedido.php";
                tituloModal.innerText = "Editar pedido";
                btnSubmit.innerText = "Actualizar";

                idPedido.value = data.id_pedido;
                fecha.value = data.fecha;
                colaborador.value = data.colaborador;

                // Reset alimentos
                document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                document.querySelectorAll('.cantidad-input').forEach(ci => ci.value = 1);

                // Marcar alimentos del pedido
                if (data.alimentos) {
                    data.alimentos.forEach(item => {
                        const checkbox = document.querySelector(
                            `input[type="checkbox"][value="${item.id_alimento}"]`
                        );
                        if (checkbox) {
                            checkbox.checked = true;
                            checkbox.closest('.alimento-item')
                                .querySelector('.cantidad-input').value = item.cantidad;
                        }
                    });
                }

                modalPedido.style.display = "block";
            };

            /* ========= EVENTOS ========= */
            btnNuevo?.addEventListener("click", abrirNuevo);

            btnExportar?.addEventListener("click", () => {
                document.getElementById("fecha_inicio").value = "";
                document.getElementById("fecha_fin").value = "";
                modalExportar.style.display = "block";
            });

            cerrarExportar?.addEventListener("click", () => modalExportar.style.display = "none");
            cerrarPedido?.addEventListener("click", () => modalPedido.style.display = "none");

            btnCancelar?.addEventListener("click", (e) => {
                e.preventDefault();
                modalPedido.style.display = "none";
            });

            window.addEventListener("click", (e) => {
                if (e.target === modalExportar) modalExportar.style.display = "none";
                if (e.target === modalPedido) modalPedido.style.display = "none";
            });

        });
    </script>

</body>

</html>