<?php
require_once 'config/database.php';

$page_title = "Servicios Pendientes";
include 'includes/header.php';

// Obtener el tipo actual de pendiente para paginación
$current_tab = $_GET['tab'] ?? 'oc';

// Variables de paginación
$limit = 20; // Máximo 20 registros por página

// Consulta para obtener servicios pendientes organizados por estado
try {
    $sql = "SELECT * FROM servicios
            WHERE estado IN ('Sustentado', 'Ejecutado', 'Facturado')
            ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clasificar servicios según los criterios
    $todos_pendientes_oc = [];
    $todos_pendientes_certificacion = [];
    $todos_pendientes_facturacion = [];

    foreach ($servicios as $servicio) {
        $tiene_oc = !empty($servicio['nro_oc_os']) && $servicio['nro_oc_os'] !== 'No aplica';
        $tiene_certificacion = !empty($servicio['nro_certificacion']) && $servicio['nro_certificacion'] !== 'No aplica';
        $tiene_factura = !empty($servicio['nro_factura']);

        // Servicios con "No aplica" en OC o certificación van directamente a pendientes de facturación
        if (($servicio['nro_oc_os'] === 'No aplica' || $servicio['nro_certificacion'] === 'No aplica') && !$tiene_factura) {
            $todos_pendientes_facturacion[] = $servicio;
        }
        // Pendientes de OC (ya sustentados pero sin número de OC)
        elseif (!$tiene_oc && $servicio['estado'] === 'Sustentado' && !$tiene_factura) {
            $todos_pendientes_oc[] = $servicio;
        }
        // Pendientes de certificación (con OC pero sin certificación)
        elseif ($tiene_oc && !$tiene_certificacion && !$tiene_factura) {
            $todos_pendientes_certificacion[] = $servicio;
        }
        // Pendientes de facturación (con OC y certificación completadas)
        elseif ($tiene_oc && $tiene_certificacion && !$tiene_factura) {
            $todos_pendientes_facturacion[] = $servicio;
        }
    }

    // Calcular páginas para cada tipo
    $total_pendientes_oc = count($todos_pendientes_oc);
    $total_pendientes_certificacion = count($todos_pendientes_certificacion);
    $total_pendientes_facturacion = count($todos_pendientes_facturacion);

    $total_paginas_oc = ceil($total_pendientes_oc / $limit);
    $total_paginas_certificacion = ceil($total_pendientes_certificacion / $limit);
    $total_paginas_facturacion = ceil($total_pendientes_facturacion / $limit);

    // Obtener página actual para cada tipo
    $pagina_actual_oc = isset($_GET['pagina_oc']) ? (int)$_GET['pagina_oc'] : 1;
    $pagina_actual_certificacion = isset($_GET['pagina_certificacion']) ? (int)$_GET['pagina_certificacion'] : 1;
    $pagina_actual_facturacion = isset($_GET['pagina_facturacion']) ? (int)$_GET['pagina_facturacion'] : 1;

    // Calcular offsets
    $offset_oc = ($pagina_actual_oc - 1) * $limit;
    $offset_certificacion = ($pagina_actual_certificacion - 1) * $limit;
    $offset_facturacion = ($pagina_actual_facturacion - 1) * $limit;

    // Obtener servicios para la página actual
    $pendientes_oc = array_slice($todos_pendientes_oc, $offset_oc, $limit);
    $pendientes_certificacion = array_slice($todos_pendientes_certificacion, $offset_certificacion, $limit);
    $pendientes_facturacion = array_slice($todos_pendientes_facturacion, $offset_facturacion, $limit);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error al obtener los servicios: " . $e->getMessage() . "</div>";
}

// Función para generar enlaces de paginación
function generar_enlace_pagina($pagina, $tipo, $otros_params = []) {
    $params = array_merge($_GET, ['pagina_' . $tipo => $pagina], $otros_params);
    unset($params['pagina_oc'], $params['pagina_certificacion'], $params['pagina_facturacion']); // Limpiar otros parámetros de página si estamos cambiando de pestaña
    $params['pagina_' . $tipo] = $pagina;
    return '?' . http_build_query($params);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 text-gray-800">Servicios Pendientes</h1>
</div>

<!-- Pestañas de servicios pendientes -->
<ul class="nav nav-tabs mb-4" id="pendingTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="oc-tab" data-bs-toggle="tab" data-bs-target="#oc" type="button" role="tab">Pendientes de OC (<?php echo count($pendientes_oc); ?>)</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="certificacion-tab" data-bs-toggle="tab" data-bs-target="#certificacion" type="button" role="tab">Pendientes de Certificación (<?php echo count($pendientes_certificacion); ?>)</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="facturacion-tab" data-bs-toggle="tab" data-bs-target="#facturacion" type="button" role="tab">Pendientes de Facturación (<?php echo count($pendientes_facturacion); ?>)</button>
    </li>
</ul>

<div class="tab-content" id="pendingTabsContent">
    <!-- Pendientes de OC -->
    <div class="tab-pane fade show active" id="oc" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>N° Ticket</th>
                                <th>Cliente</th>
                                <th>Descripción</th>
                                <th>Fecha Solicitud</th>
                                <th class="text-center">Total</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pendientes_oc)): ?>
                                <?php foreach ($pendientes_oc as $servicio): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold"><?php
                                                $ticket = htmlspecialchars($servicio['nro_ticket'] ?? '');
                                                if ($ticket === 'No aplica') {
                                                    echo '<span class="text-danger">' . $ticket . '</span>';
                                                } else {
                                                    echo $ticket;
                                                }
                                            ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($servicio['cliente'] ?? ''); ?></td>
                                        <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($servicio['descripcion'] ?? ''); ?>"><?php echo htmlspecialchars($servicio['descripcion'] ?? ''); ?></td>
                                        <td><?php echo !empty($servicio['fecha_solicitud']) ? date("d/m/Y", strtotime($servicio['fecha_solicitud'])) : ''; ?></td>
                                        <td class="text-end"><span class="fw-semibold text-success">S/ <?php echo htmlspecialchars(number_format($servicio['total'] ?? 0, 2)); ?></span></td>
                                        <td class="text-center">
                                            <?php
                                                $estado = htmlspecialchars($servicio['estado'] ?? '');
                                                $badge_class = 'bg-secondary';
                                                if ($estado == 'Pendiente') $badge_class = 'bg-warning text-dark';
                                                if ($estado == 'Ejecutado') $badge_class = 'bg-info text-dark';
                                                if ($estado == 'Sustentado') $badge_class = 'bg-success';
                                                if ($estado == 'Facturado') $badge_class = 'bg-primary';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $estado; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="view.php?id=<?php echo $servicio['id']; ?>" class="btn btn-sm btn-outline-info me-1" title="Ver Detalles"><i class="bi bi-eye-fill"></i></a>
                                            <a href="edit.php?id=<?php echo $servicio['id']; ?>" class="btn btn-sm btn-outline-warning me-1" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-4">No hay servicios pendientes de OC</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_paginas_oc > 1): ?>
                <nav aria-label="Paginación de pendientes de OC" class="pending-pagination">
                    <ul class="pagination justify-content-center mt-4">
                        <li class="page-item <?php echo ($pagina_actual_oc <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina(1, 'oc'); ?>">Primera</a>
                        </li>
                        <li class="page-item <?php echo ($pagina_actual_oc <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina(max(1, $pagina_actual_oc - 1), 'oc'); ?>">Anterior</a>
                        </li>

                        <?php for ($i = max(1, $pagina_actual_oc - 2); $i <= min($total_paginas_oc, $pagina_actual_oc + 2); $i++): ?>
                        <li class="page-item <?php echo ($pagina_actual_oc == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina($i, 'oc'); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo ($pagina_actual_oc >= $total_paginas_oc) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina(min($total_paginas_oc, $pagina_actual_oc + 1), 'oc'); ?>">Siguiente</a>
                        </li>
                        <li class="page-item <?php echo ($pagina_actual_oc >= $total_paginas_oc) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina($total_paginas_oc, 'oc'); ?>">Última</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

                <div class="mt-3 text-center text-muted">
                    Página <?php echo $pagina_actual_oc; ?> de <?php echo $total_paginas_oc; ?> (Total: <?php echo $total_pendientes_oc; ?> servicios)
                </div>
            </div>
        </div>
    </div>

    <!-- Pendientes de Certificación -->
    <div class="tab-pane fade" id="certificacion" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>N° Ticket</th>
                                <th>Cliente</th>
                                <th>Descripción</th>
                                <th>Fecha Solicitud</th>
                                <th class="text-center">Total</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pendientes_certificacion)): ?>
                                <?php foreach ($pendientes_certificacion as $servicio): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold"><?php
                                                $ticket = htmlspecialchars($servicio['nro_ticket'] ?? '');
                                                if ($ticket === 'No aplica') {
                                                    echo '<span class="text-danger">' . $ticket . '</span>';
                                                } else {
                                                    echo $ticket;
                                                }
                                            ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($servicio['cliente'] ?? ''); ?></td>
                                        <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($servicio['descripcion'] ?? ''); ?>"><?php echo htmlspecialchars($servicio['descripcion'] ?? ''); ?></td>
                                        <td><?php echo !empty($servicio['fecha_solicitud']) ? date("d/m/Y", strtotime($servicio['fecha_solicitud'])) : ''; ?></td>
                                        <td class="text-end"><span class="fw-semibold text-success">S/ <?php echo htmlspecialchars(number_format($servicio['total'] ?? 0, 2)); ?></span></td>
                                        <td class="text-center">
                                            <?php
                                                $estado = htmlspecialchars($servicio['estado'] ?? '');
                                                $badge_class = 'bg-secondary';
                                                if ($estado == 'Pendiente') $badge_class = 'bg-warning text-dark';
                                                if ($estado == 'Ejecutado') $badge_class = 'bg-info text-dark';
                                                if ($estado == 'Sustentado') $badge_class = 'bg-success';
                                                if ($estado == 'Facturado') $badge_class = 'bg-primary';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $estado; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="view.php?id=<?php echo $servicio['id']; ?>" class="btn btn-sm btn-outline-info me-1" title="Ver Detalles"><i class="bi bi-eye-fill"></i></a>
                                            <a href="edit.php?id=<?php echo $servicio['id']; ?>" class="btn btn-sm btn-outline-warning me-1" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-4">No hay servicios pendientes de certificación</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_paginas_certificacion > 1): ?>
                <nav aria-label="Paginación de pendientes de certificación" class="pending-pagination">
                    <ul class="pagination justify-content-center mt-4">
                        <li class="page-item <?php echo ($pagina_actual_certificacion <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina(1, 'certificacion'); ?>">Primera</a>
                        </li>
                        <li class="page-item <?php echo ($pagina_actual_certificacion <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina(max(1, $pagina_actual_certificacion - 1), 'certificacion'); ?>">Anterior</a>
                        </li>

                        <?php for ($i = max(1, $pagina_actual_certificacion - 2); $i <= min($total_paginas_certificacion, $pagina_actual_certificacion + 2); $i++): ?>
                        <li class="page-item <?php echo ($pagina_actual_certificacion == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina($i, 'certificacion'); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo ($pagina_actual_certificacion >= $total_paginas_certificacion) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina(min($total_paginas_certificacion, $pagina_actual_certificacion + 1), 'certificacion'); ?>">Siguiente</a>
                        </li>
                        <li class="page-item <?php echo ($pagina_actual_certificacion >= $total_paginas_certificacion) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina($total_paginas_certificacion, 'certificacion'); ?>">Última</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

                <div class="mt-3 text-center text-muted">
                    Página <?php echo $pagina_actual_certificacion; ?> de <?php echo $total_paginas_certificacion; ?> (Total: <?php echo $total_pendientes_certificacion; ?> servicios)
                </div>
            </div>
        </div>
    </div>

    <!-- Pendientes de Facturación -->
    <div class="tab-pane fade" id="facturacion" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>N° Ticket</th>
                                <th>Cliente</th>
                                <th>Descripción</th>
                                <th>Fecha Solicitud</th>
                                <th class="text-center">Total</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pendientes_facturacion)): ?>
                                <?php foreach ($pendientes_facturacion as $servicio): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold"><?php
                                                $ticket = htmlspecialchars($servicio['nro_ticket'] ?? '');
                                                if ($ticket === 'No aplica') {
                                                    echo '<span class="text-danger">' . $ticket . '</span>';
                                                } else {
                                                    echo $ticket;
                                                }
                                            ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($servicio['cliente'] ?? ''); ?></td>
                                        <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($servicio['descripcion'] ?? ''); ?>"><?php echo htmlspecialchars($servicio['descripcion'] ?? ''); ?></td>
                                        <td><?php echo !empty($servicio['fecha_solicitud']) ? date("d/m/Y", strtotime($servicio['fecha_solicitud'])) : ''; ?></td>
                                        <td class="text-end"><span class="fw-semibold text-success">S/ <?php echo htmlspecialchars(number_format($servicio['total'] ?? 0, 2)); ?></span></td>
                                        <td class="text-center">
                                            <?php
                                                $estado = htmlspecialchars($servicio['estado'] ?? '');
                                                $badge_class = 'bg-secondary';
                                                if ($estado == 'Pendiente') $badge_class = 'bg-warning text-dark';
                                                if ($estado == 'Ejecutado') $badge_class = 'bg-info text-dark';
                                                if ($estado == 'Sustentado') $badge_class = 'bg-success';
                                                if ($estado == 'Facturado') $badge_class = 'bg-primary';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $estado; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="view.php?id=<?php echo $servicio['id']; ?>" class="btn btn-sm btn-outline-info me-1" title="Ver Detalles"><i class="bi bi-eye-fill"></i></a>
                                            <a href="edit.php?id=<?php echo $servicio['id']; ?>" class="btn btn-sm btn-outline-warning me-1" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-4">No hay servicios pendientes de facturación</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_paginas_facturacion > 1): ?>
                <nav aria-label="Paginación de pendientes de facturación" class="pending-pagination">
                    <ul class="pagination justify-content-center mt-4">
                        <li class="page-item <?php echo ($pagina_actual_facturacion <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina(1, 'facturacion'); ?>">Primera</a>
                        </li>
                        <li class="page-item <?php echo ($pagina_actual_facturacion <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina(max(1, $pagina_actual_facturacion - 1), 'facturacion'); ?>">Anterior</a>
                        </li>

                        <?php for ($i = max(1, $pagina_actual_facturacion - 2); $i <= min($total_paginas_facturacion, $pagina_actual_facturacion + 2); $i++): ?>
                        <li class="page-item <?php echo ($pagina_actual_facturacion == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina($i, 'facturacion'); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo ($pagina_actual_facturacion >= $total_paginas_facturacion) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina(min($total_paginas_facturacion, $pagina_actual_facturacion + 1), 'facturacion'); ?>">Siguiente</a>
                        </li>
                        <li class="page-item <?php echo ($pagina_actual_facturacion >= $total_paginas_facturacion) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo generar_enlace_pagina($total_paginas_facturacion, 'facturacion'); ?>">Última</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

                <div class="mt-3 text-center text-muted">
                    Página <?php echo $pagina_actual_facturacion; ?> de <?php echo $total_paginas_facturacion; ?> (Total: <?php echo $total_pendientes_facturacion; ?> servicios)
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>