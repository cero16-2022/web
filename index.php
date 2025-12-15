<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding("UTF-8");
require_once 'config/database.php';

include 'includes/header.php';

// Initialize filter variables
$filter_nro_ticket = $_GET['nro_ticket'] ?? '';
$filter_descripcion = $_GET['descripcion'] ?? '';
$filter_cliente = $_GET['cliente'] ?? '';
$filter_local = $_GET['local'] ?? '';
$filter_estado = $_GET['estado'] ?? '';

// Pagination variables
$default_limit = 50;
$allowed_limits = [10, 25, 50, 100]; // Opciones permitidas para registros por página
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowed_limits) ? (int)$_GET['limit'] : $default_limit;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build the SQL query with filters
$sql = "FROM servicios WHERE 1=1";
$params = [];

if (!empty($filter_nro_ticket)) {
    $sql .= " AND nro_ticket LIKE :nro_ticket";
    $params[':nro_ticket'] = '%' . $filter_nro_ticket . '%';
}
if (!empty($filter_descripcion)) {
    $sql .= " AND descripcion LIKE :descripcion";
    $params[':descripcion'] = '%' . $filter_descripcion . '%';
}
if (!empty($filter_cliente)) {
    $sql .= " AND cliente LIKE :cliente";
    $params[':cliente'] = '%' . $filter_cliente . '%';
}
if (!empty($filter_local)) {
    $sql .= " AND local LIKE :local";
    $params[':local'] = '%' . $filter_local . '%';
}
if (!empty($filter_estado)) {
    $sql .= " AND estado = :estado";
    $params[':estado'] = $filter_estado;
}

try {
    // Get total number of records for pagination
    $count_sql = "SELECT COUNT(*)" . $sql;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Fetch services for the current page
    $select_sql = "SELECT *" . $sql . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($select_sql);

    // Bind parameters for the main query
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    if ($e->getCode() === '42S02') {
        echo "<div class='alert alert-warning'>La tabla 'servicios' parece no existir. Asegúrate de haber ejecutado el script SQL de la base de datos.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error al obtener los servicios: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 text-gray-800">Lista de Servicios</h1>
    <div>
        <a href="create.php" class="btn btn-primary shadow-sm"><i class="bi bi-plus-circle-fill me-2"></i>Añadir Nuevo Servicio</a>
        <a href="export/export_excel.php?<?= http_build_query(array_filter(['nro_ticket' => $filter_nro_ticket, 'descripcion' => $filter_descripcion, 'cliente' => $filter_cliente, 'local' => $filter_local, 'estado' => $filter_estado])) ?>" class="btn btn-success shadow-sm btn-export-csv" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Exportar los servicios filtrados a archivo Excel">
            <i class="bi bi-file-earmark-excel me-2"></i>Exportar a Excel
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h5 class="mb-0">Filtros Avanzados</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label for="filter_nro_ticket" class="form-label">N° Ticket</label>
                <input type="text" class="form-control form-control-sm" id="filter_nro_ticket" name="nro_ticket" value="<?php echo htmlspecialchars($filter_nro_ticket); ?>">
            </div>
            <div class="col-md-3">
                <label for="filter_cliente" class="form-label">Cliente</label>
                <input type="text" class="form-control form-control-sm" id="filter_cliente" name="cliente" value="<?php echo htmlspecialchars($filter_cliente); ?>">
            </div>
            <div class="col-md-3">
                <label for="filter_descripcion" class="form-label">Descripción</label>
                <input type="text" class="form-control form-control-sm" id="filter_descripcion" name="descripcion" value="<?php echo htmlspecialchars($filter_descripcion); ?>">
            </div>
            <div class="col-md-2">
                <label for="filter_local" class="form-label">Local</label>
                <input type="text" class="form-control form-control-sm" id="filter_local" name="local" value="<?php echo htmlspecialchars($filter_local); ?>">
            </div>
            <div class="col-md-2">
                <label for="filter_estado" class="form-label">Estado</label>
                <select class="form-select form-select-sm" id="filter_estado" name="estado">
                    <option value="">Todos</option>
                    <option value="Pendiente" <?php echo ($filter_estado == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="Ejecutado" <?php echo ($filter_estado == 'Ejecutado') ? 'selected' : ''; ?>>Ejecutado</option>
                    <option value="Sustentado" <?php echo ($filter_estado == 'Sustentado') ? 'selected' : ''; ?>>Sustentado</option>
                    <option value="Facturado" <?php echo ($filter_estado == 'Facturado') ? 'selected' : ''; ?>>Facturado</option>
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end d-none">
                <button type="submit" class="btn btn-primary btn-sm me-2"><i class="bi bi-filter"></i> Aplicar Filtros</button>
                <a href="index.php" class="btn btn-secondary btn-sm"><i class="bi bi-x-circle"></i> Limpiar Filtros</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 10%;">N° Ticket</th>
                                    <th>Cliente</th>
                                    <th>Descripción</th>
                                    <th style="width: 8%;">Fecha Solicitud</th>
                                    <th class="text-center" style="width: 8%;">Total</th>
                                    <th class="text-center" style="width: 8%;">Estado</th>
                                    <th class="text-center" style="width: 12%;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($servicios) && !empty($servicios)): ?>
                                    <?php foreach ($servicios as $servicio):
                                        $estado = htmlspecialchars($servicio['estado'] ?? '');
                                        $badge_class = 'bg-secondary';
                                        if ($estado == 'Pendiente') $badge_class = 'bg-warning text-dark';
                                        if ($estado == 'Ejecutado') $badge_class = 'bg-info text-dark';
                                        if ($estado == 'Sustentado') $badge_class = 'bg-success';
                                        if ($estado == 'Facturado') $badge_class = 'bg-primary';
                                        
                                        $raw_message = "¡Hola! Te comparto los detalles de un servicio:\n\n" .
                                                       "*SERVICIO:*
" .
                                                       "*N° TICKET:* " . ($servicio['nro_ticket'] ?? '') . "\n" .
                                                       "*CLIENTE:* " . ($servicio['cliente'] ?? '') . "\n" .
                                                       "*LOCAL:* " . ($servicio['local'] ?? '') . "\n" .
                                                       "*DESCRIPCIÓN:* " . ($servicio['descripcion'] ?? '') . "\n" .
                                                       "*ESTADO:* " . $estado . "\n\n" .
                                                                                                                                           "*TOTAL:* S/ " . number_format(($servicio['total'] ?? 0), 2) . "\n\n" .
                                                       "¡Gracias!";
                                        $whatsapp_message = urlencode($raw_message);
                                    ?>
                                        <tr>
                                            <td><span class="fw-bold"><?php
    $ticket = htmlspecialchars($servicio['nro_ticket'] ?? '');
    if ($ticket === 'No aplica') {
        echo '<span class="text-danger">' . $ticket . '</span>';
    } else {
        echo $ticket;
    }
?></span></td>
                                            <td><?php echo htmlspecialchars($servicio['cliente'] ?? ''); ?></td>
                                            <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($servicio['descripcion'] ?? ''); ?>"><?php echo htmlspecialchars($servicio['descripcion'] ?? ''); ?></td>
                                            <td><?php echo !empty($servicio['fecha_solicitud']) ? date("d/m/Y", strtotime($servicio['fecha_solicitud'])) : ''; ?></td>
                                            <td class="text-end"><span class="fw-semibold text-success">S/ <?php echo htmlspecialchars(number_format($servicio['total'] ?? 0, 2)); ?></span></td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $badge_class; ?>"><?php echo $estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <a href="view.php?id=<?php echo $servicio['id']; ?>" class="btn btn-sm btn-outline-info me-1" title="Ver Detalles"><i class="bi bi-eye-fill"></i></a>
                                                <a href="edit.php?id=<?php echo $servicio['id']; ?>" class="btn btn-sm btn-outline-warning me-1" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                                <a href="https://wa.me/?text=<?php echo $whatsapp_message; ?>" class="btn btn-sm btn-outline-success me-1" title="Enviar por WhatsApp" target="_blank"><i class="bi bi-whatsapp"></i></a>
                                                <a href="delete.php?id=<?php echo $servicio['id']; ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de que quieres eliminar este servicio?');"><i class="bi bi-trash3-fill"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else:
                                    echo "<tr><td colspan=\"7\" class=\"text-center py-4\">No hay servicios registrados todavía. ¡Añade uno nuevo!</td></tr>";
                                endif;
                                ?>
                            </tbody>
                        </table>
        </div>
        
        <?php if (isset($total_pages) && $total_pages > 1):
            $query_params = http_build_query(array_filter([
                'nro_ticket' => $filter_nro_ticket,
                'descripcion' => $filter_descripcion,
                'cliente' => $filter_cliente,
                'local' => $filter_local,
                'estado' => $filter_estado,
                'limit' => $limit
            ]));

            // Helper function to generate page link
            function page_link($page, $query_params) {
                $link = "?page=" . $page;
                if (!empty($query_params)) {
                    $link .= "&" . $query_params;
                }
                return $link;
            }
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <label for="limit-select" class="form-label mb-0">Mostrar:</label>
                <select id="limit-select" class="form-select form-select-sm d-inline-block w-auto ms-2">
                    <?php foreach ($allowed_limits as $allowed_limit): ?>
                        <option value="<?php echo $allowed_limit; ?>" <?php echo ($limit == $allowed_limit) ? 'selected' : ''; ?>><?php echo $allowed_limit; ?> registros</option>
                    <?php endforeach; ?>
                </select>
                <span class="ms-3 text-muted">Total: <?php echo number_format($total_records); ?> servicios</span>
            </div>
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo page_link($page - 1, $query_params); ?>">Anterior</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++):
                echo "<li class=\"page-item ";
                echo ($page == $i) ? 'active' : '';
                echo "\"><a class=\"page-link\" href=\"" . page_link($i, $query_params) . "\">" . $i . "</a></li>";
                endfor;
                ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo page_link($page + 1, $query_params); ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.querySelector('.card-body form'); // Get the form
        const filterEstado = document.getElementById('filter_estado');
        const textInputs = document.querySelectorAll('#filter_nro_ticket, #filter_cliente, #filter_descripcion, #filter_local');
        let timer;

        if (filterEstado) {
            filterEstado.addEventListener('change', function() {
                filterForm.submit();
            });
        }

        textInputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    filterForm.submit();
                }, 500); // Submit after 500ms of inactivity
            });
        });

        // Selector de límite por página
        const limitSelect = document.getElementById('limit-select');
        if (limitSelect) {
            limitSelect.addEventListener('change', function() {
                // Actualizar la URL con el nuevo límite
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('limit', this.value);
                currentUrl.searchParams.set('page', 1); // Reiniciar a la primera página
                window.location.href = currentUrl.toString();
            });
        }
    });
</script>

<?php
include 'includes/footer.php';
?>
