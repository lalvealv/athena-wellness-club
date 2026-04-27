<?php
// Comprueba que el usuario logueado tiene permisos de administrador
require_once __DIR__ . '/../comprobar-admin.php';

// Importa la conexión a la base de datos
require_once __DIR__ . '/../conexion.php';

// Indica que la respuesta del archivo será JSON
header('Content-Type: application/json; charset=utf-8');

// Función reutilizable para devolver respuestas JSON al JavaScript
function responderJSON(bool $ok, string $mensaje, int $codigo = 200, array $extra = []): void
{
    // Define el código HTTP de la respuesta
    http_response_code($codigo);

    // Devuelve un JSON con estado, mensaje y datos extra si los hay
    echo json_encode(array_merge([
        'ok' => $ok,
        'mensaje' => $mensaje
    ], $extra), JSON_UNESCAPED_UNICODE);

    // Detiene la ejecución del script
    exit;
}

try {
    // Si la petición es GET, solo se devuelven los datos del administrador logueado
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $idAdmin = $_SESSION['id_usuario'];

        // Consulta los datos básicos del administrador
        $sqlAdmin = "SELECT nombre, apellidos, foto_perfil
                     FROM usuario
                     WHERE id_usuario = :id_usuario
                     LIMIT 1";

        $stmtAdmin = $conn->prepare($sqlAdmin);
        $stmtAdmin->execute([
            ':id_usuario' => $idAdmin
        ]);
        $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

        // Si no se encuentra el administrador, se devuelve error
        if (!$admin) {
            responderJSON(false, 'No se encontró el administrador logueado.', 404);
        }

        // Si no tiene foto, se usa una imagen por defecto
        $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/admin.jpg';

        // Construye el nombre completo
        $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

        // Devuelve los datos del administrador al frontend
        responderJSON(true, 'Datos cargados correctamente.', 200, [
            'admin' => [
                'foto_perfil' => $fotoAdmin,
                'nombre_completo' => $nombreAdmin !== '' ? $nombreAdmin : 'Administrador ATHENA',
                'perfil' => 'Perfil ADMIN'
            ]
        ]);
    }

    // Si no es GET ni POST, se rechaza la petición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderJSON(false, 'Método no permitido.', 405);
    }

    // Recoge y limpia los datos personales recibidos por POST
    $alias = trim($_POST['alias'] ?? '');
    $dni = strtoupper(trim($_POST['dni'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fechaNacimiento = trim($_POST['fechaNacimiento'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');

    // Recoge y limpia los datos de dirección
    $calle = trim($_POST['calle'] ?? '');
    $portal = trim($_POST['portal'] ?? '');
    $piso = trim($_POST['piso'] ?? '');
    $cp = trim($_POST['cp'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $pais = trim($_POST['pais'] ?? '');

    // Recoge y limpia los datos de acceso y permisos
    $perfil = trim($_POST['perfil'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmarContrasena = $_POST['confirmarContrasena'] ?? '';
    $fotoPerfil = trim($_POST['fotoPerfil'] ?? '');

    // Recoge los datos de la membresía inicial
    $membresia = trim($_POST['membresia'] ?? '');
    $renovacionAutomatica = trim($_POST['renovacionAutomatica'] ?? 'Si');

    // Valida que los campos obligatorios no estén vacíos
    if (
        $alias === '' || $dni === '' || $nombre === '' || $apellidos === '' || $correo === '' ||
        $telefono === '' || $fechaNacimiento === '' || $sexo === '' || $perfil === '' ||
        $estado === '' || $contrasena === '' || $confirmarContrasena === '' ||
        $calle === '' || $cp === '' || $ciudad === '' || $pais === ''
    ) {
        responderJSON(false, 'Debes completar todos los campos obligatorios.');
    }

    // Comprueba que ambas contraseñas coinciden
    if ($contrasena !== $confirmarContrasena) {
        responderJSON(false, 'Las contraseñas no coinciden.');
    }

    // Valida el formato del correo electrónico
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        responderJSON(false, 'El correo electrónico no es válido.');
    }

    // Valida el formato del DNI
    if (!preg_match('/^[0-9]{8}[A-Z]$/', $dni)) {
        responderJSON(false, 'El DNI debe tener 8 números y una letra.');
    }

    // Valida el formato del teléfono
    if (!preg_match('/^[6-9][0-9]{8}$/', $telefono)) {
        responderJSON(false, 'El teléfono debe tener 9 dígitos y empezar por 6, 7, 8 o 9.');
    }

    // Valida el código postal
    if (!preg_match('/^[0-9]{5}$/', $cp)) {
        responderJSON(false, 'El código postal debe tener 5 dígitos.');
    }

    // Inicia una transacción para que todas las inserciones se hagan juntas
    $conn->beginTransaction();

    // Comprueba si ya existe un usuario con el mismo alias, correo o DNI
    $sqlDuplicados = "SELECT COUNT(*)
                      FROM usuario
                      WHERE alias = :alias OR correo = :correo OR dni = :dni";

    $stmtDuplicados = $conn->prepare($sqlDuplicados);
    $stmtDuplicados->execute([
        ':alias' => $alias,
        ':correo' => $correo,
        ':dni' => $dni
    ]);

    // Si existe duplicado, cancela la operación
    if ((int)$stmtDuplicados->fetchColumn() > 0) {
        $conn->rollBack();
        responderJSON(false, 'Ya existe un usuario con ese alias, correo o DNI.');
    }

    // Inserta la dirección del nuevo usuario
    $sqlDireccion = "INSERT INTO direccion (calle, portal, piso, cp, ciudad, pais)
                     VALUES (:calle, :portal, :piso, :cp, :ciudad, :pais)";

    $stmtDireccion = $conn->prepare($sqlDireccion);
    $stmtDireccion->execute([
        ':calle' => $calle,
        ':portal' => $portal !== '' ? $portal : null,
        ':piso' => $piso !== '' ? $piso : null,
        ':cp' => $cp,
        ':ciudad' => $ciudad,
        ':pais' => $pais
    ]);

    // Guarda el ID de la dirección insertada
    $idDireccion = (int)$conn->lastInsertId();

    // Busca el ID del perfil seleccionado
    $sqlPerfil = "SELECT id_perfil
                  FROM perfil
                  WHERE nombre_perfil = :perfil
                  LIMIT 1";

    $stmtPerfil = $conn->prepare($sqlPerfil);
    $stmtPerfil->execute([
        ':perfil' => $perfil
    ]);
    $idPerfil = $stmtPerfil->fetchColumn();

    // Si no existe el perfil, se cancela la operación
    if (!$idPerfil) {
        $conn->rollBack();
        responderJSON(false, 'No se encontró el perfil indicado.');
    }

    // Encripta la contraseña antes de guardarla
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Inserta el usuario en la tabla usuario
    $sqlUsuario = "INSERT INTO usuario
        (alias, nombre, apellidos, fecha_nacimiento, dni, telefono, correo, contrasena, sexo, estado, foto_perfil, id_direccion, id_perfil)
        VALUES
        (:alias, :nombre, :apellidos, :fecha_nacimiento, :dni, :telefono, :correo, :contrasena, :sexo, :estado, :foto_perfil, :id_direccion, :id_perfil)";

    $stmtUsuario = $conn->prepare($sqlUsuario);
    $stmtUsuario->execute([
        ':alias' => $alias,
        ':nombre' => $nombre,
        ':apellidos' => $apellidos,
        ':fecha_nacimiento' => $fechaNacimiento,
        ':dni' => $dni,
        ':telefono' => $telefono,
        ':correo' => $correo,
        ':contrasena' => $hash,
        ':sexo' => $sexo,
        ':estado' => $estado,
        ':foto_perfil' => $fotoPerfil !== '' ? $fotoPerfil : null,
        ':id_direccion' => $idDireccion,
        ':id_perfil' => $idPerfil
    ]);

    // Guarda el ID del usuario insertado
    $idUsuario = (int)$conn->lastInsertId();

    // Si se ha seleccionado membresía, crea también la suscripción inicial
    if ($membresia !== '') {

        // Busca el ID de la membresía seleccionada
        $sqlMembresia = "SELECT id_membresia
                         FROM membresia
                         WHERE nombre = :nombre
                         LIMIT 1";

        $stmtMembresia = $conn->prepare($sqlMembresia);
        $stmtMembresia->execute([
            ':nombre' => $membresia
        ]);
        $idMembresia = $stmtMembresia->fetchColumn();

        // Si no existe la membresía, se cancela la operación
        if (!$idMembresia) {
            $conn->rollBack();
            responderJSON(false, 'No se encontró la membresía seleccionada.');
        }

        // Define fecha de inicio y renovación
        $fechaInicio = date('Y-m-d');
        $fechaRenovacion = date('Y-m-d', strtotime('+1 month'));

        // Convierte Sí/No a 1/0 para guardar en base de datos
        $renovacion = $renovacionAutomatica === 'Si' ? 1 : 0;

        // Inserta la suscripción inicial del usuario
        $sqlSuscripcion = "INSERT INTO suscripcion
            (id_usuario, id_membresia, fecha_inicio, fecha_renovacion, fecha_fin, renovacion_automatica, estado)
            VALUES
            (:id_usuario, :id_membresia, :fecha_inicio, :fecha_renovacion, NULL, :renovacion_automatica, 'Activa')";

        $stmtSuscripcion = $conn->prepare($sqlSuscripcion);
        $stmtSuscripcion->execute([
            ':id_usuario' => $idUsuario,
            ':id_membresia' => $idMembresia,
            ':fecha_inicio' => $fechaInicio,
            ':fecha_renovacion' => $fechaRenovacion,
            ':renovacion_automatica' => $renovacion
        ]);
    }

    // Confirma todos los cambios de la transacción
    $conn->commit();

    // Respuesta final correcta
    responderJSON(true, 'Usuario creado correctamente.');
} catch (PDOException $e) {
    // Si ocurre un error y hay una transacción activa, se deshacen los cambios
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Devuelve error en formato JSON
    responderJSON(false, 'Error al crear el usuario.', 500, [
        'error' => $e->getMessage()
    ]);
}
