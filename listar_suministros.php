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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="HandheldFriendly" content="true">
    <meta name="MobileOptimized" content="width">
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Suministros</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="listar_suministros.css?v=<?= time() ?>">
    <link rel="stylesheet" href="modal.css?v=<?= time() ?>">
    <link rel="stylesheet" href="navbar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="modalNSuministro.css?v=<?= time() ?>">

    <script>
        // Fail-safe para ocultar modales inmediatamente si el CSS falla o hay caché agresivo
        document.addEventListener("DOMContentLoaded", () => {
            const modalIds = ['modalExportar', 'imageModal', 'modalSuministro', 'downloadImage'];
            modalIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
            const modals = document.querySelectorAll('.modal, .modal-image, .modalNSuministro');
            modals.forEach(m => { if (m) m.style.display = 'none'; });
        });
    </script>

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
        <div id="modalExportar" class="modal" style="display: none;">
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
        <!-- Modal para imagen (estático, reutiliza estilos de .modal) -->
        <div id="imageModal" class="modal" style="display: none;">
            <div class="modal-contenido" style="max-width:90%;text-align:center;position:relative;">
                <span class="cerrar-modal" id="cerrarImageModal">&times;</span>
                <div id="imgSpinner" style="display:none;position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);">
                    Cargando...
                </div>
                <img id="imgModal" src="" alt="Imagen" style="max-width:100%;height:auto;border-radius:6px;box-shadow:0 8px 32px rgba(0,0,0,0.6);display:none;" />
            </div>
        </div>

        <a id="downloadImage" download style="position:fixed;right:24px;top:24px;z-index:20001;background:rgba(255,255,255,0.95);padding:8px 10px;border-radius:6px;color:#000;text-decoration:none;display:none;">
            <i class="fas fa-download"></i>
        </a>

        <!-- Modal para nuevo registro -->
        <div id="modalSuministro" class="modalNSuministro" style="display: none;">
            <div class="modal-contenido-suministro">
                <span class="cerrar-modal">&times;</span>
                <h2 id="tituloModal">
                    <i class="fas fa-plus"></i> Nuevo registro
                </h2>

                <form action="crear_suministro.php" method="post" id="formSuministro" enctype="multipart/form-data">
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
                        <label>Foto (opcional):</label>
                        <div class="photo-input-container" style="display: flex; align-items: center; gap: 10px;">
                            <button type="button" id="btnShowPhotoMenu" class="btn-submit" style="background: #6c757d; margin: 0;">
                                <i class="fas fa-camera"></i> Seleccionar Foto
                            </button>
                            <span id="photoStatus" style="font-size: 0.9rem; color: #666;">No se ha seleccionado archivo</span>
                        </div>
                        <input type="file" name="foto" id="modal_foto" accept="image/*" style="display: none;">
                        
                        <div id="modalFotoArea" style="margin-top:6px; display:none;">
                            <a href="#" id="modalFotoLink" target="_blank">Ver foto actual</a>
                            <button type="button" id="modalFotoDelete" style="margin-left:8px;" class="btn-delete-photo">Eliminar foto</button>
                        </div>
                    </div>

                    <div class="form-group-suministro">
                        <label>Comentarios:</label>
                        <textarea id="comentarios" name="comentarios"></textarea>
                    </div>

                    <div class="form-group-suministro">
                        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'ADMIN'): ?>
                            <label>Operador:</label>
                            <input type="text" id="operador_visible">
                            <input type="hidden" name="operador" id="operador">
                        <?php else: ?>
                            <label>Operador:</label>
                            <input type="text" id="operador_visible" readonly>
                            <input type="hidden" name="operador" id="operador" readonly>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions-suministro">
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
                        <th>Foto</th>
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
                            <?php
                            // Preparar ruta de la foto para soportar tanto filenames como URLs completas
                            $fotoHref = '';
                            if (!empty($suministro['foto'])) {
                                $foto = $suministro['foto'];
                                // Si es una URL absoluta (http/https) o una ruta absoluta en servidor
                                if (preg_match('#^https?://#i', $foto) || strpos($foto, '/') === 0) {
                                    // intentar localizar un archivo local con el mismo basename
                                    $basename = basename($foto);
                                    if (file_exists(__DIR__ . '/uploads/suministros/' . $basename)) {
                                        $fotoHref = 'uploads/suministros/' . $basename;
                                    } else {
                                        $fotoHref = $foto; // dejar la URL tal cual
                                    }
                                } else {
                                    // asumir que es un nombre de archivo
                                    if (file_exists(__DIR__ . '/uploads/suministros/' . $foto)) {
                                        $fotoHref = 'uploads/suministros/' . $foto;
                                    } elseif (file_exists(__DIR__ . '/' . $foto)) {
                                        $fotoHref = $foto;
                                    } else {
                                        // fallback: usar como href directo
                                        $fotoHref = $foto;
                                    }
                                }
                            }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($suministro['id_suministro']) ?></td>
                                <td class="foto-cell">
                                    <?php if (!empty($fotoHref)): ?>
                                        <a href="#" class="foto-link" data-foto="<?= htmlspecialchars($fotoHref) ?>">Ver foto</a>
                                    <?php else: ?>
                                        <div class="thumb-placeholder"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </td>

                                <td class="actions-cell">
                                    <a class="btn-edit" onclick='abrirEditar(<?= json_encode($suministro, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>✏️ Editar</a>

                                    <?php if ($_SESSION['rol'] === 'ADMIN'): ?>
                                        <a href="eliminar_suministro.php?id=<?= $suministro['id_suministro'] ?>" class="btn-delete" onclick="return confirm('¿Estás seguro de que deseas eliminar este registro?');">🗑️ Eliminar</a>
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

    <!-- Action Sheet para Fotos -->
    <div class="action-sheet-overlay" id="photoActionSheetOverlay"></div>
    <div class="action-sheet" id="photoActionSheet">
        <div class="action-sheet-title">Opciones de Fotografía</div>
        <button type="button" class="action-sheet-button" id="btnTakePhoto">
            <i class="fas fa-camera"></i> Tomar fotografía
        </button>
        <button type="button" class="action-sheet-button" id="btnPickGallery">
            <i class="fas fa-images"></i> Escoger de galería
        </button>
        <button type="button" class="action-sheet-button cancel" id="btnCancelPhoto">
            Cancelar
        </button>
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
            const ROL_LOGEADO = "<?= htmlspecialchars($_SESSION['rol'] ?? '') ?>";

            /* ========= SINCRONIZAR VISIBLE → HIDDEN (SEGURO) ========= */
            if (operadorVisible && operadorHidden) {
                operadorVisible.addEventListener("input", () => {
                    operadorHidden.value = operadorVisible.value;
                });
            }

            /* ========= NUEVO ========= */
            function abrirNuevo() {
                form.reset();
                form.action = "crear_suministro.php";
                tituloModal.innerText = "Nuevo registro";
                btnSubmit.innerText = "Guardar";

                if (operadorVisible && operadorHidden) {
                    operadorVisible.value = USUARIO_LOGEADO;
                    operadorHidden.value = USUARIO_LOGEADO;
                    operadorVisible.readOnly = (ROL_LOGEADO !== 'ADMIN');
                }

                // limpiar campo de foto y ocultar link
                const modalFotoArea = document.getElementById('modalFotoArea');
                const modalFotoLink = document.getElementById('modalFotoLink');
                const modalFotoInput = document.getElementById('modal_foto');
                if (modalFotoArea) modalFotoArea.style.display = 'none';
                if (modalFotoLink) { modalFotoLink.href = '#'; modalFotoLink.innerText = 'Ver foto actual'; }
                if (modalFotoInput) modalFotoInput.value = '';

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

                if (operadorVisible && operadorHidden) {
                    operadorVisible.value = data.operador;
                    operadorHidden.value = data.operador;
                    operadorVisible.readOnly = (ROL_LOGEADO !== 'ADMIN');
                }

                // mostrar link a foto existente si hay
                const modalFotoArea = document.getElementById('modalFotoArea');
                const modalFotoLink = document.getElementById('modalFotoLink');
                const modalFotoInput = document.getElementById('modal_foto');
                if (data.foto) {
                    // soportar: URL absoluta, ruta absoluta, ruta relativa 'uploads/...' o solo nombre de archivo
                    let src = data.foto;
                    if (!(src.startsWith('uploads/') || src.startsWith('/') || src.match(/^https?:\/\//i))) {
                        src = 'uploads/suministros/' + src;
                    }
                    if (modalFotoLink) {
                        modalFotoLink.href = src;
                        modalFotoLink.innerText = 'Ver foto actual';
                    }
                    if (modalFotoArea) modalFotoArea.style.display = 'block';
                } else {
                    if (modalFotoArea) modalFotoArea.style.display = 'none';
                    if (modalFotoInput) modalFotoInput.value = '';
                }

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

            /* ======= Imagen: abrir modal y subida por fila ======= */
            // Crear modal y download link dinámicamente si no existen
            let imageModal = document.getElementById('imageModal');
            if (!imageModal) {
                imageModal = document.createElement('div');
                imageModal.id = 'imageModal';
                imageModal.className = 'modal';
                imageModal.innerHTML = '<div class="modal-contenido" style="max-width:90%;text-align:center;"><img id="imgModal" src="" alt="Imagen" style="max-width:100%;height:auto;border-radius:6px;box-shadow:0 8px 32px rgba(0,0,0,0.6);"></div>';
                document.body.appendChild(imageModal);

                const downloadA = document.createElement('a');
                downloadA.id = 'downloadImage';
                downloadA.innerHTML = '<i class="fas fa-download"></i>';
                downloadA.setAttribute('download', 'foto');
                downloadA.style.cssText = 'position:fixed;right:24px;top:24px;z-index:20001;background:rgba(255,255,255,0.95);padding:8px 10px;border-radius:6px;color:#000;text-decoration:none;display:none;';
                downloadA.href = '#';
                document.body.appendChild(downloadA);
            }

            let imgModal = document.getElementById('imgModal');
            let downloadImage = document.getElementById('downloadImage');

            // Abre modal al click en enlace de foto (delegación para evitar problemas de binding)
            document.addEventListener('click', function (e) {
                const link = e.target.closest && e.target.closest('.foto-link');
                if (!link) return;
                e.preventDefault();
                const rawSrc = link.getAttribute('data-foto') || link.href;
                if (!rawSrc) return;

                // resolver URL relativa a la página para evitar rutas rotas
                let resolvedSrc;
                try {
                    resolvedSrc = new URL(rawSrc, window.location.href).href;
                } catch (err) {
                    resolvedSrc = rawSrc;
                }

                // asegurar elementos
                if (!imageModal) imageModal = document.getElementById('imageModal');
                if (!imgModal) imgModal = document.getElementById('imgModal');
                if (!downloadImage) downloadImage = document.getElementById('downloadImage');

                // ocultar hasta que la imagen cargue correctamente
                imgModal.style.display = 'none';
                downloadImage.style.display = 'none';
                const spinner = document.getElementById('imgSpinner');
                if (spinner) spinner.style.display = 'block';
                // marcar modal como de imagen para estilos
                if (imageModal) {
                    imageModal.classList.add('modal-image');
                    imageModal.style.display = 'flex';
                }
                
                // asignar manejadores de carga/errores
                imgModal.onload = function () {
                    // ocultar spinner y mostrar imagen + enlace de descarga
                    if (spinner) spinner.style.display = 'none';
                    imgModal.style.display = '';
                    downloadImage.href = resolvedSrc;
                    downloadImage.style.display = 'block';
                };

                imgModal.onerror = function () {
                    if (spinner) spinner.style.display = 'none';
                    imageModal.style.display = 'none';
                    downloadImage.style.display = 'none';
                    imgModal.style.display = 'none';
                    console.error('Error cargando imagen:', resolvedSrc);
                    alert('No se pudo cargar la imagen. Comprueba que el archivo existe y que la ruta es accesible.');
                };

                // iniciar carga usando fetch -> blob para comprobar status y evitar problemas de rutas/ORIGIN
                (async () => {
                    try {
                        const response = await fetch(resolvedSrc, { cache: 'no-store' });
                        if (!response.ok) throw new Error('HTTP ' + response.status);
                        const blob = await response.blob();

                        // liberar objeto anterior si existía
                        if (imgModal._objectUrl) {
                            URL.revokeObjectURL(imgModal._objectUrl);
                            imgModal._objectUrl = null;
                        }

                        const objectUrl = URL.createObjectURL(blob);
                        imgModal._objectUrl = objectUrl;
                        // asignar src (esto disparará onload)
                        imgModal.src = objectUrl;
                    } catch (err) {
                        const spinner = document.getElementById('imgSpinner');
                        if (spinner) spinner.style.display = 'none';
                        if (imageModal) imageModal.style.display = 'none';
                        if (downloadImage) downloadImage.style.display = 'none';
                        console.error('Error cargando imagen (fetch):', resolvedSrc, err);
                        alert('No se pudo cargar la imagen. Comprueba la ruta o los permisos del archivo. Revisa la consola para más detalles.');
                    }
                })();
            });

            // Cerrar modal al click fuera de la imagen
            if (imageModal) {
                imageModal.addEventListener('click', (e) => {
                    if (e.target === imageModal) {
                        imageModal.style.display = 'none';
                        if (downloadImage) downloadImage.style.display = 'none';
                    }
                });
            }

            // cerrar con la X del modal de imagen
            const cerrarImage = document.getElementById('cerrarImageModal');
            if (cerrarImage) {
                cerrarImage.addEventListener('click', () => {
                    if (imageModal) imageModal.style.display = 'none';
                    if (downloadImage) downloadImage.style.display = 'none';
                });
            }

            // Cerrar modal con Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (imageModal && imageModal.style.display !== 'none') {
                        imageModal.style.display = 'none';
                        if (downloadImage) downloadImage.style.display = 'none';
                    }
                }
            });

            // Manejar borrado de foto desde modal
            const modalFotoDelete = document.getElementById('modalFotoDelete');
            if (modalFotoDelete) {
                modalFotoDelete.addEventListener('click', () => {
                    const id = document.getElementById('id_suministro')?.value;
                    if (!id) return alert('ID no disponible');
                    if (!confirm('¿Eliminar foto? Esta acción no se puede deshacer.')) return;

                    const form = new FormData();
                    form.append('id_suministro', id);

                    fetch('eliminar_foto_suministro.php', { method: 'POST', body: form })
                        .then(res => res.json())
                        .then(json => {
                            if (json.success) {
                                // ocultar área en modal
                                const area = document.getElementById('modalFotoArea');
                                if (area) area.style.display = 'none';

                                // actualizar la tabla: reemplazar enlace por placeholder
                                document.querySelectorAll('.foto-link').forEach(link => {
                                    if (link.getAttribute('data-foto') && link.getAttribute('data-foto').endsWith(json.foto)) {
                                        const placeholder = document.createElement('div');
                                        placeholder.className = 'thumb-placeholder';
                                        placeholder.innerHTML = '<i class="fas fa-image"></i>';
                                        link.replaceWith(placeholder);
                                    }
                                });

                                alert('Foto eliminada');
                            } else {
                                alert('Error: ' + (json.error || 'no se pudo eliminar'));
                            }
                        }).catch(err => alert('Error en la petición'));
                });
            }

            // No hay controles de subida en la tabla: la subida se realiza desde el modal.

            /* ========= ACTION SHEET FOTO ========= */
            const btnShowPhotoMenu = document.getElementById('btnShowPhotoMenu');
            const photoActionSheet = document.getElementById('photoActionSheet');
            const photoActionSheetOverlay = document.getElementById('photoActionSheetOverlay');
            const modalFotoInput = document.getElementById('modal_foto');
            const photoStatus = document.getElementById('photoStatus');

            const showPhotoMenu = () => {
                photoActionSheetOverlay.style.display = 'block';
                setTimeout(() => photoActionSheet.classList.add('active'), 10);
            };

            const hidePhotoMenu = () => {
                photoActionSheet.classList.remove('active');
                setTimeout(() => photoActionSheetOverlay.style.display = 'none', 300);
            };

            btnShowPhotoMenu?.addEventListener('click', showPhotoMenu);
            photoActionSheetOverlay?.addEventListener('click', hidePhotoMenu);
            document.getElementById('btnCancelPhoto')?.addEventListener('click', hidePhotoMenu);

            document.getElementById('btnTakePhoto')?.addEventListener('click', () => {
                modalFotoInput.setAttribute('capture', 'environment');
                modalFotoInput.click();
                hidePhotoMenu();
            });

            document.getElementById('btnPickGallery')?.addEventListener('click', () => {
                modalFotoInput.removeAttribute('capture');
                modalFotoInput.click();
                hidePhotoMenu();
            });

            modalFotoInput?.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    photoStatus.innerText = 'Archivo: ' + this.files[0].name;
                    photoStatus.style.color = '#28a745';
                } else {
                    photoStatus.innerText = 'No se ha seleccionado archivo';
                    photoStatus.style.color = '#666';
                }
            });

            // Reset status when opening modal
            const originalAbrirNuevo = abrirNuevo;
            window.abrirNuevo = function() {
                photoStatus.innerText = 'No se ha seleccionado archivo';
                photoStatus.style.color = '#666';
                if(originalAbrirNuevo) originalAbrirNuevo();
            };

            const originalAbrirEditar = window.abrirEditar;
            window.abrirEditar = function(data) {
                photoStatus.innerText = 'No se ha seleccionado archivo';
                photoStatus.style.color = '#666';
                if(originalAbrirEditar) originalAbrirEditar(data);
            };

        });
    </script>
</body>

</html>