<?php
require_once 'config/database.php';
$page_title = "Crear Nuevo Servicio";
$errors = [];
$success_message = '';

// Define all fields to avoid "undefined array key" warnings
$form_data = [
    'nro_ticket' => '', 'cliente' => '', 'local' => '', 'descripcion' => '', 
    'fecha_solicitud' => date('Y-m-d'), 'fecha_ejecucion' => '',
    'sub_total' => '', 'total' => '', 'estado' => 'Pendiente', 'nro_oc_os' => '', 
    'nro_certificacion' => '', 'fecha_sustentado' => '', 'nro_factura' => '', 'fecha_facturacion' => ''
];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_data = array_merge($form_data, $_POST);
    // ... (Validation and Insertion logic remains exactly the same) ...
    $nro_ticket = trim($form_data['nro_ticket']);
    $cliente = trim($form_data['cliente']);
    $descripcion = trim($form_data['descripcion']);
    $fecha_solicitud = trim($form_data['fecha_solicitud']);
    
    if (empty($nro_ticket)) { $errors[] = "El N° de Ticket es obligatorio."; }
    if (empty($cliente)) { $errors[] = "El nombre del cliente es obligatorio."; }
    if (empty($descripcion)) { $errors[] = "La descripción del servicio es obligatoria."; }
    if (empty($fecha_solicitud)) { $errors[] = "La fecha de solicitud es obligatoria."; }
    if (!empty($form_data['sub_total']) && !is_numeric($form_data['sub_total'])) { $errors[] = "El subtotal debe ser un número."; }

    if (empty($errors)) {
        try {
            $ticket_exists = false;
            if ($nro_ticket !== 'No aplica') {
                $stmt_check = $pdo->prepare("SELECT id FROM servicios WHERE nro_ticket = ?");
                $stmt_check->execute([$nro_ticket]);
                if ($stmt_check->fetch()) {
                    $ticket_exists = true;
                }
            }

            if ($ticket_exists) {
                $errors[] = "El N° de Ticket '".htmlspecialchars($nro_ticket)."' ya existe.";
            } else {
                $sub_total_for_db = (empty($form_data['sub_total']) || !is_numeric($form_data['sub_total'])) ? 0 : (float)$form_data['sub_total'];
                $total_for_db = $sub_total_for_db * (1 + 0.18);

                $sql = "INSERT INTO servicios (nro_ticket, cliente, local, descripcion, fecha_solicitud, fecha_ejecucion, sub_total, total, estado, nro_oc_os, nro_certificacion, fecha_sustentado, nro_factura, fecha_facturacion) 
                        VALUES (:nro_ticket, :cliente, :local, :descripcion, :fecha_solicitud, :fecha_ejecucion, :sub_total, :total, :estado, :nro_oc_os, :nro_certificacion, :fecha_sustentado, :nro_factura, :fecha_facturacion)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nro_ticket' => $nro_ticket, ':cliente' => $cliente, ':local' => $form_data['local'], ':descripcion' => $descripcion,
                    ':fecha_solicitud' => $fecha_solicitud, ':fecha_ejecucion' => empty($form_data['fecha_ejecucion']) ? null : $form_data['fecha_ejecucion'],
                    ':sub_total' => $sub_total_for_db, ':total' => $total_for_db, ':estado' => $form_data['estado'],
                    ':nro_oc_os' => $form_data['nro_oc_os'], ':nro_certificacion' => $form_data['nro_certificacion'],
                    ':fecha_sustentado' => empty($form_data['fecha_sustentado']) ? null : $form_data['fecha_sustentado'],
                    ':nro_factura' => $form_data['nro_factura'], ':fecha_facturacion' => empty($form_data['fecha_facturacion']) ? null : $form_data['fecha_facturacion']
                ]);
                $success_message = "¡Servicio creado con éxito! <a href='index.php' class='alert-link'>Volver a la lista</a>.";
                $form_data = array_map(function() { return ''; }, $form_data);
                $form_data['fecha_solicitud'] = date('Y-m-d');
                $form_data['estado'] = 'Pendiente';
            }
        } catch (PDOException $e) { $errors[] = "Error al guardar en la base de datos: " . $e->getMessage(); }
    }
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 text-gray-800">Añadir Nuevo Servicio</h1>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-2"></i>Volver a la Lista</a>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success"><?= $success_message; ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <p class="mb-1"><strong>Por favor, corrige los siguientes errores:</strong></p>
        <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= $error; ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form action="create.php" method="POST" novalidate>
    <div class="card form-card">
        <!-- Tab Navs -->
        <div class="card-header bg-white border-0 px-4 pt-4">
            <ul class="nav nav-tabs" id="formTab" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">Información General</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="dates-tab" data-bs-toggle="tab" data-bs-target="#dates" type="button" role="tab" aria-controls="dates" aria-selected="false">Fechas y Montos</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="closing-tab" data-bs-toggle="tab" data-bs-target="#closing" type="button" role="tab" aria-controls="closing" aria-selected="false">Detalles de Cierre</button></li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="card-body p-4">
            <div class="tab-content" id="formTabContent">
                <!-- General Info Tab -->
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label for="nro_ticket" class="form-label">N° de Ticket <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="nro_ticket" name="nro_ticket" value="<?= htmlspecialchars($form_data['nro_ticket']); ?>" required>
                                <button class="btn btn-outline-secondary" type="button" id="na_ticket_btn">No aplica</button>
                            </div>
                        </div>
                        <div class="col-md-4"><label for="cliente" class="form-label">Cliente <span class="text-danger">*</span></label><input type="text" class="form-control" id="cliente" name="cliente" value="<?= htmlspecialchars($form_data['cliente']); ?>" required></div>
                        <div class="col-md-4"><label for="local" class="form-label">Local</label><input type="text" class="form-control" id="local" name="local" value="<?= htmlspecialchars($form_data['local']); ?>"></div>
                        <div class="col-12"><label for="descripcion" class="form-label">Descripción del Servicio <span class="text-danger">*</span></label><textarea class="form-control" id="descripcion" name="descripcion" rows="4" required><?= htmlspecialchars($form_data['descripcion']); ?></textarea></div>
                    </div>
                </div>

                <!-- Dates & Amounts Tab -->
                <div class="tab-pane fade" id="dates" role="tabpanel" aria-labelledby="dates-tab">
                    <div class="row g-4">
                        <div class="col-md-3"><label for="fecha_solicitud" class="form-label">Fecha de Solicitud <span class="text-danger">*</span></label><input type="date" class="form-control" id="fecha_solicitud" name="fecha_solicitud" value="<?= htmlspecialchars($form_data['fecha_solicitud']); ?>" required></div>
                        <div class="col-md-3"><label for="fecha_ejecucion" class="form-label">Fecha de Ejecución</label><input type="date" class="form-control" id="fecha_ejecucion" name="fecha_ejecucion" value="<?= htmlspecialchars($form_data['fecha_ejecucion']); ?>"></div>
                        <div class="col-md-3"><label for="sub_total" class="form-label">Sub Total</label><div class="input-group"><span class="input-group-text">S/</span><input type="number" step="0.01" class="form-control" id="sub_total" name="sub_total" value="<?= htmlspecialchars($form_data['sub_total']); ?>"></div></div>
                        <div class="col-md-3"><label for="total" class="form-label">Total (IGV incl.)</label><div class="input-group"><span class="input-group-text">S/</span><input type="number" step="0.01" class="form-control" id="total" name="total" value="<?= htmlspecialchars($form_data['total']); ?>" readonly></div></div>
                        <div class="col-md-3"><label for="estado" class="form-label">Estado</label><select id="estado" name="estado" class="form-select"><option value="Pendiente" <?= ($form_data['estado'] == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option><option value="Ejecutado" <?= ($form_data['estado'] == 'Ejecutado') ? 'selected' : ''; ?>>Ejecutado</option><option value="Sustentado" <?= ($form_data['estado'] == 'Sustentado') ? 'selected' : ''; ?>>Sustentado</option><option value="Facturado" <?= ($form_data['estado'] == 'Facturado') ? 'selected' : ''; ?>>Facturado</option></select></div>
                    </div>
                </div>

                <!-- Closing Details Tab -->
                <div class="tab-pane fade" id="closing" role="tabpanel" aria-labelledby="closing-tab">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <label for="nro_oc_os" class="form-label">N° OC / OS</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="nro_oc_os" name="nro_oc_os" value="<?= htmlspecialchars($form_data['nro_oc_os']); ?>">
                                <button class="btn btn-outline-secondary" type="button" id="na_oc_os_btn">No aplica</button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="nro_certificacion" class="form-label">N° de Certificación</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="nro_certificacion" name="nro_certificacion" value="<?= htmlspecialchars($form_data['nro_certificacion']); ?>">
                                <button class="btn btn-outline-secondary" type="button" id="na_certificacion_btn">No aplica</button>
                            </div>
                        </div>
                        <div class="col-md-2"><label for="nro_factura" class="form-label">N° de Factura</label><input type="text" class="form-control" id="nro_factura" name="nro_factura" value="<?= htmlspecialchars($form_data['nro_factura']); ?>"></div>
                        <div class="col-md-2"><label for="fecha_sustentado" class="form-label">Fecha de Sustentado</label><input type="date" class="form-control" id="fecha_sustentado" name="fecha_sustentado" value="<?= htmlspecialchars($form_data['fecha_sustentado']); ?>"></div>
                        <div class="col-md-2"><label for="fecha_facturacion" class="form-label">Fecha de Facturación</label><input type="date" class="form-control" id="fecha_facturacion" name="fecha_facturacion" value="<?= htmlspecialchars($form_data['fecha_facturacion']); ?>"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Footer -->
        <div class="card-footer form-footer text-end">
            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle-fill me-2"></i>Guardar Servicio</button>
            <a href="index.php" class="btn btn-secondary btn-lg">Cancelar</a>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const subTotalInput = document.getElementById('sub_total');
    const totalInput = document.getElementById('total');
    const igvRate = 0.18; // 18% IGV

    function calculateTotal() {
        let subTotal = parseFloat(subTotalInput.value);
        if (isNaN(subTotal)) {
            subTotal = 0;
        }
        const total = subTotal * (1 + igvRate);
        totalInput.value = total.toFixed(2); // Format to 2 decimal places
    }

    subTotalInput.addEventListener('input', calculateTotal);
    if(subTotalInput.value) { calculateTotal(); }
});
</script>

<?php include 'includes/footer.php'; ?>