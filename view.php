<?php
require_once 'config/database.php';

$page_title = "Detalles del Servicio";
include 'includes/header.php';

$servicio = null;
$errors = [];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM servicios WHERE id = ?");
        $stmt->execute([$id]);
        $servicio = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$servicio) {
            $errors[] = "Servicio no encontrado.";
        }
    } catch (PDOException $e) {
        $errors[] = "Error al cargar los detalles del servicio: " . $e->getMessage();
    }
} else {
    $errors[] = "No se proporcionó un ID de servicio para ver.";
}

function formatDetail($value, $icon, $label, $is_currency = false, $is_date = false) {
    $formatted_value = '<span class="text-warning-emphasis fw-semibold">PENDIENTE</span>';
    if (!empty($value) && $value !== '0.00') {
        if ($is_currency) {
            $formatted_value = 'S/ ' . htmlspecialchars(number_format($value, 2));
        } elseif ($is_date) {
            $formatted_value = date("d/m/Y", strtotime($value));
        } else {
            $value = htmlspecialchars($value);
            // Si el valor es 'No aplica', mostrarlo en rojo
            if ($value === 'No aplica') {
                $formatted_value = '<span class="text-danger fw-semibold">' . $value . '</span>';
            } else {
                $formatted_value = $value;
            }
        }
    }

    echo '<div class="detail-item">
            <i class="detail-icon bi ' . $icon . '"></i>
            <div>
                <div class="detail-label">' . $label . '</div>
                <div class="detail-value">' . $formatted_value . '</div>
            </div>
          </div>';
}
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?><li><?= $error; ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php elseif ($servicio): ?>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0 text-gray-800">Servicio #<?= htmlspecialchars($servicio['nro_ticket']) ?></h1>
            <p class="mb-0 text-muted">Detalles completos del servicio registrado.</p>
        </div>
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-2"></i>Volver a la Lista</a>
    </div>

    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-7">
            <!-- General Info -->
            <div class="detail-card card">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 text-primary">Información General</h5>
                </div>
                <div class="card-body pt-0">
                    <?php formatDetail($servicio['cliente'], 'bi-person-fill', 'Cliente'); ?>
                    <?php formatDetail($servicio['local'], 'bi-geo-alt-fill', 'Local'); ?>
                    <?php formatDetail($servicio['descripcion'], 'bi-text-paragraph', 'Descripción'); ?>
                </div>
            </div>
             <!-- Cierre Info -->
            <div class="detail-card card mt-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 text-primary">Detalles de Cierre</h5>
                </div>
                <div class="card-body pt-0">
                    <?php formatDetail($servicio['nro_oc_os'], 'bi-hash', 'N° OC/OS'); ?>
                    <?php formatDetail($servicio['nro_certificacion'], 'bi-patch-check-fill', 'N° de Certificación'); ?>
                    <?php formatDetail($servicio['nro_factura'], 'bi-receipt-cutoff', 'N° de Factura'); ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-5">
            <!-- Total Card -->
            <div class="detail-card total-card bg-gradient-primary text-white p-4 text-center mb-4">
                <h6 class="text-uppercase letter-spacing-1">Monto Total</h6>
                <p class="h3 mb-0">S/ <?= htmlspecialchars(number_format($servicio['total'] ?? 0, 2)); ?></p>
            </div>

            <!-- Status Card -->
             <div class="detail-card card mb-4">
                <div class="card-body">
                     <?php 
                        $estado = htmlspecialchars($servicio['estado'] ?? 'Desconocido');
                        $badge_class = 'bg-secondary';
                        $icon_class = 'bi-question-circle-fill';
                        if ($estado == 'Pendiente') { $badge_class = 'bg-warning text-dark'; $icon_class = 'bi-clock-fill'; }
                        if ($estado == 'Ejecutado') { $badge_class = 'bg-info text-dark'; $icon_class = 'bi-check2-all'; }
                        if ($estado == 'Sustentado') { $badge_class = 'bg-success'; $icon_class = 'bi-file-earmark-check-fill'; }
                        if ($estado == 'Facturado') { $badge_class = 'bg-primary'; $icon_class = 'bi-receipt-cutoff'; }
                    ?>
                    <div class="detail-item border-0">
                        <i class="detail-icon bi <?= $icon_class ?>"></i>
                        <div>
                            <div class="detail-label">Estado Actual</div>
                            <div class="detail-value"><span class="badge <?= $badge_class ?> fs-6"><?= $estado ?></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dates Card -->
            <div class="detail-card card">
                 <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 text-primary">Fechas Clave</h5>
                </div>
                <div class="card-body pt-0">
                    <?php formatDetail($servicio['fecha_solicitud'], 'bi-calendar-plus-fill', 'Fecha de Solicitud', false, true); ?>
                    <?php formatDetail($servicio['fecha_ejecucion'], 'bi-calendar-check-fill', 'Fecha de Ejecución', false, true); ?>
                    <?php formatDetail($servicio['fecha_sustentado'], 'bi-calendar-heart-fill', 'Fecha de Sustentado', false, true); ?>
                    <?php formatDetail($servicio['fecha_facturacion'], 'bi-calendar-week-fill', 'Fecha de Facturación', false, true); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="text-end mt-4">
        <a href="edit.php?id=<?= $servicio['id']; ?>" class="btn btn-lg btn-primary"><i class="bi bi-pencil-square me-2"></i>Editar Servicio</a>
    </div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>