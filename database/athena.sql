CREATE DATABASE IF NOT EXISTS athena
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE athena;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET FOREIGN_KEY_CHECKS = 1;


-- 1. TABLAS BÁSICAS

CREATE TABLE direccion (
    id_direccion INT AUTO_INCREMENT PRIMARY KEY,
    calle VARCHAR(100) NOT NULL,
    portal VARCHAR(10) DEFAULT NULL,
    piso VARCHAR(10) DEFAULT NULL,
    cp VARCHAR(10) DEFAULT NULL,
    ciudad VARCHAR(100) NOT NULL,
    pais VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE perfil (
    id_perfil INT AUTO_INCREMENT PRIMARY KEY,
    nombre_perfil VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    alias VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE DEFAULT NULL,
    dni VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(20) DEFAULT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    sexo ENUM('Hombre', 'Mujer', 'Otro') DEFAULT NULL,
    estado ENUM('Activo', 'Inactivo', 'Bloqueado') NOT NULL DEFAULT 'Activo',
    foto_perfil VARCHAR(255) DEFAULT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_direccion INT DEFAULT NULL,
    id_perfil INT NOT NULL,
    CONSTRAINT fk_usuario_direccion
        FOREIGN KEY (id_direccion) REFERENCES direccion(id_direccion)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_usuario_perfil
        FOREIGN KEY (id_perfil) REFERENCES perfil(id_perfil)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_usuario_correo ON usuario(correo);
CREATE INDEX idx_usuario_alias ON usuario(alias);
CREATE INDEX idx_usuario_perfil ON usuario(id_perfil);



-- 2. MEMBRESÍAS Y SUSCRIPCIONES

CREATE TABLE periodo (
    id_periodo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_periodo VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE membresia (
    id_membresia INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    cuota DECIMAL(8,2) NOT NULL,
    id_periodo INT NOT NULL,
    horario VARCHAR(100) DEFAULT NULL,
    descripcion TEXT,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_membresia_periodo
        FOREIGN KEY (id_periodo) REFERENCES periodo(id_periodo)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_membresia_cuota
        CHECK (cuota >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE suscripcion (
    id_suscripcion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_membresia INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_renovacion DATE DEFAULT NULL,
    fecha_fin DATE DEFAULT NULL,
    renovacion_automatica TINYINT(1) NOT NULL DEFAULT 1,
    estado ENUM('Activa', 'Pausada', 'Cancelada', 'Finalizada') NOT NULL DEFAULT 'Activa',
    CONSTRAINT fk_suscripcion_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_suscripcion_membresia
        FOREIGN KEY (id_membresia) REFERENCES membresia(id_membresia)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_suscripcion_fechas_1
        CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio),
    CONSTRAINT chk_suscripcion_fechas_2
        CHECK (fecha_renovacion IS NULL OR fecha_renovacion >= fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_suscripcion_usuario ON suscripcion(id_usuario);
CREATE INDEX idx_suscripcion_membresia ON suscripcion(id_membresia);



-- 3. ACTIVIDADES, HORARIOS, SESIONES Y RESERVAS

CREATE TABLE sala (
    id_sala INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    capacidad INT DEFAULT NULL,
    descripcion TEXT,
    CONSTRAINT chk_sala_capacidad
        CHECK (capacidad IS NULL OR capacidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE actividad (
    id_actividad INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(50) DEFAULT NULL,
    nivel VARCHAR(30) DEFAULT NULL,
    duracion_minutos INT NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT chk_actividad_duracion
        CHECK (duracion_minutos > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE horario_actividad (
    id_horario INT AUTO_INCREMENT PRIMARY KEY,
    id_actividad INT NOT NULL,
    id_sala INT DEFAULT NULL,
    dia_semana ENUM('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_horario_actividad
        FOREIGN KEY (id_actividad) REFERENCES actividad(id_actividad)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_horario_sala
        FOREIGN KEY (id_sala) REFERENCES sala(id_sala)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sesion_actividad (
    id_sesion INT AUTO_INCREMENT PRIMARY KEY,
    id_horario INT NOT NULL,
    fecha DATE NOT NULL,
    instructor VARCHAR(100) DEFAULT NULL,
    plazas_totales INT NOT NULL,
    estado ENUM('Programada', 'Cancelada', 'Completada') NOT NULL DEFAULT 'Programada',
    CONSTRAINT fk_sesion_horario
        FOREIGN KEY (id_horario) REFERENCES horario_actividad(id_horario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT chk_sesion_plazas
        CHECK (plazas_totales > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reserva (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_sesion INT NOT NULL,
    fecha_reserva DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Confirmada', 'Cancelada', 'Asistida', 'No asistida') NOT NULL DEFAULT 'Confirmada',
    CONSTRAINT fk_reserva_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_reserva_sesion
        FOREIGN KEY (id_sesion) REFERENCES sesion_actividad(id_sesion)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT uq_reserva_usuario_sesion UNIQUE (id_usuario, id_sesion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_reserva_usuario ON reserva(id_usuario);
CREATE INDEX idx_reserva_sesion ON reserva(id_sesion);



-- 4. FITNESS

CREATE TABLE objetivo_fitness (
    id_objetivo INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    objetivo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE DEFAULT NULL,
    estado ENUM('Activo', 'Completado', 'Pausado') NOT NULL DEFAULT 'Activo',
    CONSTRAINT fk_objetivo_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT chk_objetivo_fechas
        CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE progreso_fisico (
    id_progreso INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha_registro DATE NOT NULL,
    peso DECIMAL(5,2) DEFAULT NULL,
    altura DECIMAL(5,2) DEFAULT NULL,
    imc DECIMAL(5,2) DEFAULT NULL,
    grasa_corporal DECIMAL(5,2) DEFAULT NULL,
    masa_muscular DECIMAL(5,2) DEFAULT NULL,
    observaciones TEXT,
    CONSTRAINT fk_progreso_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE entrenamiento (
    id_entrenamiento INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha DATE NOT NULL,
    duracion_minutos INT DEFAULT NULL,
    observaciones TEXT,
    CONSTRAINT fk_entrenamiento_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT chk_entrenamiento_duracion
        CHECK (duracion_minutos IS NULL OR duracion_minutos > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE detalle_entrenamiento (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_entrenamiento INT NOT NULL,
    ejercicio VARCHAR(100) NOT NULL,
    series INT DEFAULT NULL,
    repeticiones INT DEFAULT NULL,
    peso DECIMAL(6,2) DEFAULT NULL,
    CONSTRAINT fk_detalle_entrenamiento
        FOREIGN KEY (id_entrenamiento) REFERENCES entrenamiento(id_entrenamiento)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT chk_detalle_series
        CHECK (series IS NULL OR series > 0),
    CONSTRAINT chk_detalle_repeticiones
        CHECK (repeticiones IS NULL OR repeticiones > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- 5. CONTACTO Y NOTIFICACIONES

CREATE TABLE contacto (
    id_contacto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    asunto VARCHAR(150) DEFAULT NULL,
    mensaje TEXT NOT NULL,
    fecha_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    respondido TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notificacion (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    mensaje TEXT NOT NULL,
    tipo ENUM('General', 'Reserva', 'Suscripcion', 'Recordatorio') NOT NULL DEFAULT 'General',
    destinatario_tipo ENUM('Todos', 'Essential Morning', 'Essential', 'Premium', 'Executive', 'Usuario') NOT NULL DEFAULT 'Usuario',
    fecha_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuario_notificacion (
    id_usuario_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_notificacion INT NOT NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    fecha_lectura DATETIME DEFAULT NULL,
    CONSTRAINT uq_usuario_notificacion UNIQUE (id_usuario, id_notificacion),
    CONSTRAINT fk_usuario_notificacion_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_usuario_notificacion_notificacion
        FOREIGN KEY (id_notificacion) REFERENCES notificacion(id_notificacion)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- 6. DATOS INICIALES

INSERT INTO perfil (nombre_perfil) VALUES
('ADMIN'),
('CLIENTE');

INSERT INTO periodo (nombre_periodo) VALUES
('Mensual'),
('Trimestral'),
('Anual');

INSERT INTO membresia (nombre, cuota, id_periodo, horario, descripcion, activa) VALUES
('Essential Morning', 34.90, 1, 'Acceso completo hasta las 15:00', 'Acceso completo hasta las 15:00 más clases dirigidas.', 1),
('Essential', 59.90, 1, 'Horario estándar', 'Fitness y clases dirigidas en horario estándar.', 1),
('Premium', 79.90, 1, 'Acceso completo', 'Todo Essential más 2 sesiones PT al mes y Recovery Lounge.', 1),
('Executive', 109.90, 1, 'Acceso premium total', 'Entrenador dedicado, prioridad y área exclusiva.', 1);

INSERT INTO sala (nombre, capacidad, descripcion) VALUES
('Sala Mind & Body', 25, 'Espacio para yoga, pilates y actividades suaves.'),
('Sala Cycle', 20, 'Sala especializada en spinning.'),
('Studio 2', 30, 'Sala polivalente para clases colectivas.');

INSERT INTO actividad (nombre, descripcion, categoria, nivel, duracion_minutos, activa) VALUES
('Yoga / Pilates', 'Actividad orientada al equilibrio, movilidad y control postural.', 'Mind & Body', 'Todos', 50, 1),
('Spinning', 'Sesión de ciclismo indoor de intensidad media-alta.', 'Cardio', 'Intermedio', 50, 1),
('Zumba', 'Actividad colectiva con música y trabajo cardiovascular.', 'Baile', 'Todos', 50, 1),
('Full Body', 'Entrenamiento global de fuerza y resistencia.', 'Fuerza', 'Intermedio', 55, 1);

INSERT INTO horario_actividad (id_actividad, id_sala, dia_semana, hora_inicio, hora_fin, activo) VALUES
(1, 1, 'Lunes', '07:30:00', '08:20:00', 1),
(2, 2, 'Martes', '18:30:00', '19:20:00', 1),
(3, 3, 'Miercoles', '17:30:00', '18:20:00', 1);