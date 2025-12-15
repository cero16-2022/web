<?php
require_once 'config/database.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM servicios WHERE id = ?");
        $stmt->execute([$id]);

        // Redirigir de vuelta a index.php después de una eliminación exitosa
        header("Location: index.php?message=deleted");
        exit();
    } catch (PDOException $e) {
        // Manejar el error, por ejemplo, mostrar un mensaje o registrarlo
        // Aquí podrías redirigir con un mensaje de error también
        echo "<div class='alert alert-danger'>Error al eliminar el servicio: " . $e->getMessage() . "</div>";
        // Optionally redirect with an error message
        // header("Location: index.php?message=delete_error");
        // exit();
    }
} else {
    // Si no se proporciona un ID, redirigir a index.php
    header("Location: index.php?message=error_no_id");
    exit();
}
?>