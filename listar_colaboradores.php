<?php
// 2. Iniciar sesión PRIMERO
session_start();

// 3. Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';
$colaboradores = $pdo->query("SELECT * FROM colaboradores")->fetchAll();

// 6. Configurar headers de seguridad
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colaboradores</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="listar_colaboradores.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="modalNColaborador.css">
    <style>
    </style>
</head>

<body>

    <?php include 'navbar.php' ?>

    <!-- Botones de acción -->
    <div class="fab-actions">

        <a class="fab-btn fab-primary" id="btnNuevo"> <i class="fas fa-plus"></i></a>

    </div>

    <div class="container">
        <h1>Colaboradores</h1>

        <!-- Modal para nuevo registro -->
        <div id="modalColaborador" class="modalNColaborador">
            <div class="modal-contenido-colaborador">
                <span class="cerrar-modal">&times;</span>
                <h2 id="tituloModal">
                    <i class="fas fa-plus"></i> Nuevo registro
                </h2>

                <form action="crear_colaborador.php" method="post" id="formColaborador">
                    <input type="hidden" name="id_colaborador" id="id_colaborador">

                    <div class="form-group-colaborador">
                        <label for="fecha">Nombre:</label>
                        <input type="text" name="nombre" required>
                    </div>

                    <div class="form-actions-colaborador">
                        <button type="submit" class="btn-submit" id="btnSubmit">Guardar</button>
                        <a href="listar.php" class="btn-cancel" id="btnCancelar">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <table>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($colaboradores as $col): ?>
                <tr>
                    <td><?= $col['id_colaborador'] ?></td>
                    <td><?= $col['nombre'] ?></td>
                    <td class="actions-cell">
                        <a class="btn-edit" onclick='abrirEditar(<?= json_encode($col, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            ✏️ Editar
                        </a>

                        <?php if ($_SESSION['rol'] === 'ADMIN'): ?>
                            <a href="eliminar_colaborador.php?id=<?= $col['id_colaborador'] ?>" class="btn-delete"
                                onclick="return confirm('¿Estás seguro de que deseas eliminar este registro?');">
                                🗑️ Eliminar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {

            /* ========= ELEMENTOS ========= */
            const modalColaborador = document.getElementById("modalColaborador");
            const btnNuevo = document.getElementById("btnNuevo");

            const form = document.getElementById("formColaborador");
            const tituloModal = document.getElementById("tituloModal");
            const btnSubmit = document.getElementById("btnSubmit");
            const btnCancelar = document.getElementById("btnCancelar");

            const idColaborador = document.getElementById("id_colaborador");
            const cerrarColaborador = document.querySelector(".cerrar-modal");
            const nombre = form.querySelector('input[name="nombre"]');



            /* ========= NUEVO ========= */
            function abrirNuevo() {
                form.reset();
                form.action = "crear_colaborador.php";
                tituloModal.innerText = "Nuevo registro";
                btnSubmit.innerText = "Guardar";

                modalColaborador.style.display = "block";
            }

            /* ========= EDITAR ========= */
            window.abrirEditar = function (data) {
                form.action = "editar_colaborador.php";
                tituloModal.innerText = "Editar registro";
                btnSubmit.innerText = "Actualizar";

                idColaborador.value = data.id_colaborador;
                nombre.value = data.nombre;

                modalColaborador.style.display = "block";
            }

            /* ========= EVENTOS ========= */
            btnNuevo?.addEventListener("click", abrirNuevo);

            cerrarColaborador?.addEventListener("click", () => {
                modalColaborador.style.display = "none";
            });

            btnCancelar?.addEventListener("click", (e) => {
                e.preventDefault();
                modalColaborador.style.display = "none";
            });

            window.addEventListener("click", (e) => {
                if (e.target === modalColaborador) modalColaborador.style.display = "none";
            });

        });
    </script>
</body>

</html>