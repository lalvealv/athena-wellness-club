<?php
// Registra un nuevo usuario y crea su suscripción inicial

// Incluir conexión a la base de datos
require_once 'conexion.php';

// Comprobar que el formulario se envía por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acceso no permitido.");
}

// Recoger y limpiar datos personales
$alias = trim($_POST['alias'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
$dni = strtoupper(trim($_POST['dni'] ?? ''));
$telefono = trim($_POST['telefono'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';
$sexo = trim($_POST['sexo'] ?? '');

// Recoger y limpiar datos de dirección
$calle = trim($_POST['calle'] ?? '');
$portal = trim($_POST['portal'] ?? '');
$piso = trim($_POST['piso'] ?? '');
$cp = trim($_POST['cp'] ?? '');
$ciudad = trim($_POST['ciudad'] ?? '');
$pais = trim($_POST['pais'] ?? '');

// Recoger membresía y aceptación de términos
$tarifa = trim($_POST['tarifa'] ?? '');
$terminos = isset($_POST['terminos']) ? 1 : 0;

// VALIDACIONES BÁSICAS
// Comprobar campos obligatorios
if (
    empty($alias) || empty($nombre) || empty($apellidos) || empty($fecha_nacimiento) ||
    empty($dni) || empty($telefono) || empty($correo) || empty($password) ||
    empty($password2) || empty($sexo) || empty($calle) || empty($portal) ||
    empty($piso) || empty($cp) || empty($ciudad) || empty($pais) || empty($tarifa)
) {
    die("Todos los campos obligatorios deben estar completos.");
}

// Comprobar aceptación de términos
if ($terminos !== 1) {
    die("Debes aceptar los términos y condiciones.");
}

// Comprobar que las contraseñas coinciden
if ($password !== $password2) {
    die("Las contraseñas no coinciden.");
}

// Validar formato del correo electrónico
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die("El correo electrónico no es válido.");
}

// Validar formato del DNI
if (!preg_match('/^[0-9]{8}[A-Z]$/', $dni)) {
    die("El DNI debe tener 8 números y una letra.");
}

// Validar formato del teléfono
if (!preg_match('/^[6-9][0-9]{8}$/', $telefono)) {
    die("El teléfono debe tener 9 dígitos y empezar por 6, 7, 8 o 9.");
}

// Validar formato del código postal
if (!preg_match('/^[0-9]{5}$/', $cp)) {
    die("El código postal debe tener 5 dígitos.");
}

try {
    // Iniciar transacción para guardar todo o nada
    $conn->beginTransaction();

    // COMPROBAR DUPLICADOS
    // Comprobar que no exista otro usuario con el mismo alias, correo o DNI
    $sqlComprobar = "SELECT COUNT(*) FROM usuario 
                     WHERE alias = :alias OR correo = :correo OR dni = :dni";

    $stmtComprobar = $conn->prepare($sqlComprobar);
    $stmtComprobar->execute([
        ':alias' => $alias,
        ':correo' => $correo,
        ':dni' => $dni
    ]);

    // Si existe duplicado, cancelar registro
    if ($stmtComprobar->fetchColumn() > 0) {
        $conn->rollBack();
        die("Ya existe un usuario con ese alias, correo o DNI.");
    }

    // INSERTAR DIRECCIÓN
    // Guardar dirección del usuario
    $sqlDireccion = "INSERT INTO direccion (calle, portal, piso, cp, ciudad, pais)
                     VALUES (:calle, :portal, :piso, :cp, :ciudad, :pais)";

    $stmtDireccion = $conn->prepare($sqlDireccion);
    $stmtDireccion->execute([
        ':calle' => $calle,
        ':portal' => $portal,
        ':piso' => $piso,
        ':cp' => $cp,
        ':ciudad' => $ciudad,
        ':pais' => $pais
    ]);

    // Obtener el ID de la dirección recién creada
    $idDireccion = $conn->lastInsertId();

    // OBTENER PERFIL CLIENTE
    // Buscar el perfil CLIENTE para asignarlo al nuevo usuario
    $sqlPerfil = "SELECT id_perfil FROM perfil WHERE nombre_perfil = 'CLIENTE' LIMIT 1";
    $stmtPerfil = $conn->query($sqlPerfil);
    $idPerfil = $stmtPerfil->fetchColumn();

    // Si no existe el perfil CLIENTE, cancelar registro
    if (!$idPerfil) {
        $conn->rollBack();
        die("No se encontró el perfil CLIENTE en la base de datos.");
    }

    // INSERTAR USUARIO
    // Cifrar contraseña antes de guardarla
    $contrasenaHash = password_hash($password, PASSWORD_DEFAULT);

    // Guardar usuario en la base de datos
    $sqlUsuario = "INSERT INTO usuario
        (alias, nombre, apellidos, fecha_nacimiento, dni, telefono, correo, contrasena, sexo, id_direccion, id_perfil)
        VALUES
        (:alias, :nombre, :apellidos, :fecha_nacimiento, :dni, :telefono, :correo, :contrasena, :sexo, :id_direccion, :id_perfil)";

    $stmtUsuario = $conn->prepare($sqlUsuario);
    $stmtUsuario->execute([
        ':alias' => $alias,
        ':nombre' => $nombre,
        ':apellidos' => $apellidos,
        ':fecha_nacimiento' => $fecha_nacimiento,
        ':dni' => $dni,
        ':telefono' => $telefono,
        ':correo' => $correo,
        ':contrasena' => $contrasenaHash,
        ':sexo' => $sexo,
        ':id_direccion' => $idDireccion,
        ':id_perfil' => $idPerfil
    ]);

    // Obtener el ID del usuario recién creado
    $idUsuario = $conn->lastInsertId();

    // CREAR SUSCRIPCIÓN INICIAL
    // Buscar la membresía seleccionada en el formulario
    $sqlMembresia = "SELECT id_membresia FROM membresia WHERE nombre = :nombre LIMIT 1";

    $stmtMembresia = $conn->prepare($sqlMembresia);
    $stmtMembresia->execute([
        ':nombre' => $tarifa
    ]);

    $idMembresia = $stmtMembresia->fetchColumn();

    // Si no existe la membresía, cancelar registro
    if (!$idMembresia) {
        $conn->rollBack();
        die("No se encontró la membresía seleccionada.");
    }

    // Calcular fechas de inicio y renovación
    $fechaInicio = date('Y-m-d');
    $fechaRenovacion = date('Y-m-d', strtotime('+1 month'));

    // Crear suscripción activa con renovación automática
    $sqlSuscripcion = "INSERT INTO suscripcion
        (id_usuario, id_membresia, fecha_inicio, fecha_renovacion, fecha_fin, renovacion_automatica, estado)
        VALUES
        (:id_usuario, :id_membresia, :fecha_inicio, :fecha_renovacion, NULL, 1, 'Activa')";

    $stmtSuscripcion = $conn->prepare($sqlSuscripcion);
    $stmtSuscripcion->execute([
        ':id_usuario' => $idUsuario,
        ':id_membresia' => $idMembresia,
        ':fecha_inicio' => $fechaInicio,
        ':fecha_renovacion' => $fechaRenovacion
    ]);

    // Confirmar todos los cambios
    $conn->commit();

    // Redirigir al login con mensaje de registro correcto
    header("Location: publico/socios.html?registro=ok");
    exit;
} catch (PDOException $e) {
    // Si hay error, deshacer todos los cambios
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Mostrar error de registro
    die("Error al registrar el usuario: " . $e->getMessage());
}
