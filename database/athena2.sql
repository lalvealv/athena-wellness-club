-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-04-2026 a las 09:44:04
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `athena`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividad`
--

CREATE TABLE `actividad` (
  `id_actividad` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `nivel` varchar(30) DEFAULT NULL,
  `duracion_minutos` int(11) NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Volcado de datos para la tabla `actividad`
--

INSERT INTO `actividad` (`id_actividad`, `nombre`, `descripcion`, `categoria`, `nivel`, `duracion_minutos`, `activa`) VALUES
(1, 'Yoga / Pilates', 'Actividad orientada al equilibrio, movilidad y control postural.', 'Mind & Body', 'Todos', 50, 1),
(2, 'Spinning', 'Sesión de ciclismo indoor de intensidad media-alta.', 'Cardio', 'Intermedio', 50, 1),
(3, 'Zumba', 'Actividad colectiva con música y trabajo cardiovascular.', 'Baile', 'Todos', 50, 1),
(4, 'Full Body', 'Entrenamiento global de fuerza y resistencia.', 'Fuerza', 'Intermedio', 55, 1),
(5, 'HIIT', 'Entrenamiento interválico de alta intensidad.', 'Cardio', 'Intermedio', 50, 1),
(6, 'Functional Training', 'Entrenamiento funcional para fuerza, coordinación y resistencia.', 'Funcional', 'Todos', 50, 1),
(7, 'Strength', 'Sesión enfocada en trabajo de fuerza general.', 'Fuerza', 'Intermedio', 50, 1),
(8, 'Mobility', 'Clase de movilidad articular, estiramientos y control corporal.', 'Mind & Body', 'Todos', 50, 1),
(9, 'Hipopresivos', 'Trabajo postural y abdominal mediante ejercicios hipopresivos.', 'Core', 'Todos', 50, 1),
(10, 'Personal Training', 'Sesión personalizada de entrenamiento individual.', 'Personal', 'Todos', 50, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contacto`
--

CREATE TABLE `contacto` (
  `id_contacto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `asunto` varchar(150) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `fecha_envio` datetime NOT NULL DEFAULT current_timestamp(),
  `respondido` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_entrenamiento`
--

CREATE TABLE `detalle_entrenamiento` (
  `id_detalle` int(11) NOT NULL,
  `id_entrenamiento` int(11) NOT NULL,
  `ejercicio` varchar(100) NOT NULL,
  `series` int(11) DEFAULT NULL,
  `repeticiones` int(11) DEFAULT NULL,
  `peso` decimal(6,2) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direccion`
--

CREATE TABLE `direccion` (
  `id_direccion` int(11) NOT NULL,
  `calle` varchar(100) NOT NULL,
  `portal` varchar(10) DEFAULT NULL,
  `piso` varchar(10) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `ciudad` varchar(100) NOT NULL,
  `pais` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `direccion`
--

INSERT INTO `direccion` (`id_direccion`, `calle`, `portal`, `piso`, `cp`, `ciudad`, `pais`) VALUES
(1, 'inventada', '1', '1A', '28850', 'madrid', 'espana'),
(2, 'inventada 2', '2', '2B', '28850', 'madrid', 'espana');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entrenamiento`
--

CREATE TABLE `entrenamiento` (
  `id_entrenamiento` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `duracion_minutos` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario_actividad`
--

CREATE TABLE `horario_actividad` (
  `id_horario` int(11) NOT NULL,
  `id_actividad` int(11) NOT NULL,
  `id_sala` int(11) DEFAULT NULL,
  `dia_semana` enum('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horario_actividad`
--

INSERT INTO `horario_actividad` (`id_horario`, `id_actividad`, `id_sala`, `dia_semana`, `hora_inicio`, `hora_fin`, `activo`) VALUES
(1, 2, 2, 'Lunes', '07:30:00', '08:20:00', 1),
(2, 5, 5, 'Martes', '07:30:00', '08:20:00', 1),
(3, 2, 2, 'Miercoles', '07:30:00', '08:20:00', 1),
(4, 6, 5, 'Jueves', '07:30:00', '08:20:00', 1),
(5, 2, 2, 'Viernes', '07:30:00', '08:20:00', 1),
(6, 1, 1, 'Sabado', '07:30:00', '08:20:00', 1),
(7, 8, 6, 'Domingo', '07:30:00', '08:20:00', 1),
(8, 7, 4, 'Lunes', '08:30:00', '09:20:00', 1),
(9, 6, 5, 'Martes', '08:30:00', '09:20:00', 1),
(10, 7, 4, 'Miercoles', '08:30:00', '09:20:00', 1),
(11, 5, 5, 'Jueves', '08:30:00', '09:20:00', 1),
(12, 7, 4, 'Viernes', '08:30:00', '09:20:00', 1),
(13, 2, 2, 'Sabado', '08:30:00', '09:20:00', 1),
(14, 9, 6, 'Domingo', '08:30:00', '09:20:00', 1),
(15, 1, 1, 'Lunes', '09:30:00', '10:20:00', 1),
(16, 8, 6, 'Martes', '09:30:00', '10:20:00', 1),
(17, 9, 6, 'Miercoles', '09:30:00', '10:20:00', 1),
(18, 1, 1, 'Jueves', '09:30:00', '10:20:00', 1),
(19, 8, 6, 'Viernes', '09:30:00', '10:20:00', 1),
(20, 3, 3, 'Sabado', '09:30:00', '10:20:00', 1),
(21, 1, 1, 'Domingo', '09:30:00', '10:20:00', 1),
(22, 6, 5, 'Lunes', '10:30:00', '11:20:00', 1),
(23, 7, 4, 'Martes', '10:30:00', '11:20:00', 1),
(24, 6, 5, 'Miercoles', '10:30:00', '11:20:00', 1),
(25, 7, 4, 'Jueves', '10:30:00', '11:20:00', 1),
(26, 6, 5, 'Viernes', '10:30:00', '11:20:00', 1),
(27, 5, 5, 'Sabado', '10:30:00', '11:20:00', 1),
(28, 8, 6, 'Domingo', '10:30:00', '11:20:00', 1),
(29, 10, 7, 'Lunes', '11:30:00', '12:20:00', 1),
(30, 10, 7, 'Martes', '11:30:00', '12:20:00', 1),
(31, 10, 7, 'Miercoles', '11:30:00', '12:20:00', 1),
(32, 10, 7, 'Jueves', '11:30:00', '12:20:00', 1),
(33, 10, 7, 'Viernes', '11:30:00', '12:20:00', 1),
(34, 10, 7, 'Sabado', '11:30:00', '12:20:00', 1),
(35, 10, 7, 'Domingo', '11:30:00', '12:20:00', 1),
(36, 8, 6, 'Lunes', '12:30:00', '13:20:00', 1),
(37, 9, 6, 'Martes', '12:30:00', '13:20:00', 1),
(38, 1, 1, 'Miercoles', '12:30:00', '13:20:00', 1),
(39, 8, 6, 'Jueves', '12:30:00', '13:20:00', 1),
(40, 9, 6, 'Viernes', '12:30:00', '13:20:00', 1),
(41, 6, 5, 'Sabado', '12:30:00', '13:20:00', 1),
(42, 3, 3, 'Domingo', '12:30:00', '13:20:00', 1),
(43, 9, 6, 'Lunes', '14:00:00', '14:50:00', 1),
(44, 1, 1, 'Martes', '14:00:00', '14:50:00', 1),
(45, 8, 6, 'Miercoles', '14:00:00', '14:50:00', 1),
(46, 9, 6, 'Jueves', '14:00:00', '14:50:00', 1),
(47, 1, 1, 'Viernes', '14:00:00', '14:50:00', 1),
(48, 7, 4, 'Sabado', '14:00:00', '14:50:00', 1),
(49, 3, 3, 'Lunes', '17:30:00', '18:20:00', 1),
(50, 2, 2, 'Martes', '17:30:00', '18:20:00', 1),
(51, 3, 3, 'Miercoles', '17:30:00', '18:20:00', 1),
(52, 2, 2, 'Jueves', '17:30:00', '18:20:00', 1),
(53, 3, 3, 'Viernes', '17:30:00', '18:20:00', 1),
(54, 5, 5, 'Sabado', '17:30:00', '18:20:00', 1),
(55, 7, 4, 'Lunes', '18:30:00', '19:20:00', 1),
(56, 6, 5, 'Martes', '18:30:00', '19:20:00', 1),
(57, 7, 4, 'Miercoles', '18:30:00', '19:20:00', 1),
(58, 6, 5, 'Jueves', '18:30:00', '19:20:00', 1),
(59, 7, 4, 'Viernes', '18:30:00', '19:20:00', 1),
(60, 1, 1, 'Sabado', '18:30:00', '19:20:00', 1),
(61, 5, 5, 'Lunes', '19:30:00', '20:20:00', 1),
(62, 3, 3, 'Martes', '19:30:00', '20:20:00', 1),
(63, 2, 2, 'Miercoles', '19:30:00', '20:20:00', 1),
(64, 5, 5, 'Jueves', '19:30:00', '20:20:00', 1),
(65, 2, 2, 'Viernes', '19:30:00', '20:20:00', 1),
(66, 8, 6, 'Sabado', '19:30:00', '20:20:00', 1),
(67, 1, 1, 'Lunes', '20:30:00', '21:20:00', 1),
(68, 9, 6, 'Martes', '20:30:00', '21:20:00', 1),
(69, 8, 6, 'Miercoles', '20:30:00', '21:20:00', 1),
(70, 1, 1, 'Jueves', '20:30:00', '21:20:00', 1),
(71, 9, 6, 'Viernes', '20:30:00', '21:20:00', 1),
(72, 3, 3, 'Sabado', '20:30:00', '21:20:00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `membresia`
--

CREATE TABLE `membresia` (
  `id_membresia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cuota` decimal(8,2) NOT NULL,
  `id_periodo` int(11) NOT NULL,
  `horario` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Volcado de datos para la tabla `membresia`
--

INSERT INTO `membresia` (`id_membresia`, `nombre`, `cuota`, `id_periodo`, `horario`, `descripcion`, `activa`) VALUES
(1, 'Essential Morning', 34.90, 1, 'Acceso completo hasta las 15:00', 'Acceso completo hasta las 15:00 más clases dirigidas.', 1),
(2, 'Essential', 59.90, 1, 'Horario estándar', 'Fitness y clases dirigidas en horario estándar.', 1),
(3, 'Premium', 79.90, 1, 'Acceso completo', 'Todo Essential más 2 sesiones PT al mes y Recovery Lounge.', 1),
(4, 'Executive', 109.90, 1, 'Acceso premium total', 'Entrenador dedicado, prioridad y área exclusiva.', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificacion`
--

CREATE TABLE `notificacion` (
  `id_notificacion` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` enum('General','Reserva','Suscripcion','Recordatorio') NOT NULL DEFAULT 'General',
  `destinatario_tipo` enum('Todos','Essential Morning','Essential','Premium','Executive','Usuario') NOT NULL DEFAULT 'Usuario',
  `fecha_envio` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `objetivo_fitness`
--

CREATE TABLE `objetivo_fitness` (
  `id_objetivo` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `objetivo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('Activo','Completado','Pausado') NOT NULL DEFAULT 'Activo'
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfil`
--

CREATE TABLE `perfil` (
  `id_perfil` int(11) NOT NULL,
  `nombre_perfil` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `perfil`
--

INSERT INTO `perfil` (`id_perfil`, `nombre_perfil`) VALUES
(1, 'ADMIN'),
(2, 'CLIENTE');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodo`
--

CREATE TABLE `periodo` (
  `id_periodo` int(11) NOT NULL,
  `nombre_periodo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `periodo`
--

INSERT INTO `periodo` (`id_periodo`, `nombre_periodo`) VALUES
(3, 'Anual'),
(1, 'Mensual'),
(2, 'Trimestral');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `progreso_fisico`
--

CREATE TABLE `progreso_fisico` (
  `id_progreso` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_registro` date NOT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `altura` decimal(5,2) DEFAULT NULL,
  `imc` decimal(5,2) DEFAULT NULL,
  `grasa_corporal` decimal(5,2) DEFAULT NULL,
  `masa_muscular` decimal(5,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva`
--

CREATE TABLE `reserva` (
  `id_reserva` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_sesion` int(11) NOT NULL,
  `fecha_reserva` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('Confirmada','Cancelada','Asistida','No asistida') NOT NULL DEFAULT 'Confirmada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sala`
--

CREATE TABLE `sala` (
  `id_sala` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `capacidad` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL
) ;

--
-- Volcado de datos para la tabla `sala`
--

INSERT INTO `sala` (`id_sala`, `nombre`, `capacidad`, `descripcion`) VALUES
(1, 'Sala Mind & Body', 25, 'Espacio para yoga, pilates y actividades suaves.'),
(2, 'Sala Cycle', 20, 'Sala especializada en spinning.'),
(3, 'Studio 2', 30, 'Sala polivalente para clases colectivas.'),
(4, 'Sala Strength', 24, 'Espacio para sesiones de fuerza y full body.'),
(5, 'Sala Functional', 22, 'Sala de entrenamiento funcional y HIIT.'),
(6, 'Sala Mobility', 18, 'Espacio para movilidad, hipopresivos y trabajo suave.'),
(7, 'Sala Personal Training', 8, 'Zona para sesiones personalizadas.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesion_actividad`
--

CREATE TABLE `sesion_actividad` (
  `id_sesion` int(11) NOT NULL,
  `id_horario` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `plazas_totales` int(11) NOT NULL,
  `estado` enum('Programada','Cancelada','Completada') NOT NULL DEFAULT 'Programada'
) ;

--
-- Volcado de datos para la tabla `sesion_actividad`
--

INSERT INTO `sesion_actividad` (`id_sesion`, `id_horario`, `fecha`, `instructor`, `plazas_totales`, `estado`) VALUES
(1, 1, '2026-04-10', 'David León', 20, 'Programada'),
(2, 2, '2026-04-10', 'Marcos Vega', 18, 'Programada'),
(3, 3, '2026-04-10', 'David León', 20, 'Programada'),
(4, 4, '2026-04-10', 'Lucía Martín', 20, 'Programada'),
(5, 5, '2026-04-10', 'David León', 20, 'Programada'),
(6, 6, '2026-04-10', 'Claudia Ruiz', 18, 'Programada'),
(7, 7, '2026-04-10', 'Paula Gil', 18, 'Programada'),
(8, 8, '2026-04-10', 'Sergio Mora', 20, 'Programada'),
(9, 9, '2026-04-10', 'Elena Ruiz', 18, 'Programada'),
(10, 10, '2026-04-10', 'Javier León', 20, 'Programada'),
(11, 11, '2026-04-11', 'Nuria Costa', 8, 'Programada'),
(12, 12, '2026-04-11', 'Sergio Mora', 18, 'Programada'),
(13, 13, '2026-04-11', 'Elena Ruiz', 18, 'Programada'),
(14, 14, '2026-04-11', 'Claudia Ruiz', 20, 'Programada'),
(15, 15, '2026-04-11', 'Sergio Mora', 18, 'Programada'),
(16, 16, '2026-04-11', 'Elena Ruiz', 18, 'Programada'),
(17, 17, '2026-04-11', 'David León', 20, 'Programada'),
(18, 18, '2026-04-11', 'Marcos Vega', 20, 'Programada'),
(19, 19, '2026-04-11', 'Lucía Martín', 20, 'Programada'),
(20, 20, '2026-04-11', 'Paula Gil', 18, 'Programada'),
(21, 21, '2026-04-12', 'Javier León', 20, 'Programada'),
(22, 22, '2026-04-12', 'Nuria Costa', 8, 'Programada'),
(23, 23, '2026-04-12', 'Sergio Mora', 18, 'Programada'),
(24, 24, '2026-04-12', 'Elena Ruiz', 18, 'Programada'),
(25, 25, '2026-04-12', 'Claudia Ruiz', 20, 'Programada'),
(26, 26, '2026-04-12', 'Sergio Mora', 18, 'Programada'),
(27, 27, '2026-04-12', 'David León', 20, 'Programada'),
(28, 28, '2026-04-12', 'Marcos Vega', 20, 'Programada'),
(29, 29, '2026-04-12', 'Lucía Martín', 20, 'Programada'),
(30, 30, '2026-04-12', 'Paula Gil', 18, 'Programada'),
(31, 31, '2026-04-13', 'Javier León', 20, 'Programada'),
(32, 32, '2026-04-13', 'Claudia Ruiz', 18, 'Programada'),
(33, 33, '2026-04-13', 'David León', 20, 'Programada'),
(34, 34, '2026-04-13', 'Paula Gil', 18, 'Programada'),
(35, 35, '2026-04-13', 'Elena Ruiz', 18, 'Programada'),
(36, 36, '2026-04-13', 'Sergio Mora', 18, 'Programada'),
(37, 37, '2026-04-13', 'Lucía Martín', 20, 'Programada'),
(38, 38, '2026-04-13', 'Marcos Vega', 20, 'Programada'),
(39, 39, '2026-04-13', 'David León', 20, 'Programada'),
(40, 40, '2026-04-13', 'Elena Ruiz', 18, 'Programada'),
(41, 41, '2026-04-14', 'Claudia Ruiz', 20, 'Programada'),
(42, 42, '2026-04-14', 'Marcos Vega', 20, 'Programada'),
(43, 43, '2026-04-14', 'Sergio Mora', 18, 'Programada'),
(44, 44, '2026-04-14', 'David León', 20, 'Programada'),
(45, 45, '2026-04-14', 'Paula Gil', 18, 'Programada'),
(46, 46, '2026-04-14', 'Claudia Ruiz', 18, 'Programada'),
(47, 47, '2026-04-14', 'Lucía Martín', 20, 'Programada'),
(48, 48, '2026-04-14', 'Javier León', 20, 'Programada'),
(49, 49, '2026-04-14', 'Sergio Mora', 18, 'Programada'),
(50, 50, '2026-04-14', 'David León', 20, 'Programada'),
(51, 51, '2026-04-15', 'Nuria Costa', 8, 'Programada'),
(52, 52, '2026-04-15', 'Nuria Costa', 8, 'Programada'),
(53, 53, '2026-04-15', 'Nuria Costa', 8, 'Programada'),
(54, 54, '2026-04-15', 'Nuria Costa', 8, 'Programada'),
(55, 55, '2026-04-15', 'Nuria Costa', 8, 'Programada'),
(56, 56, '2026-04-15', 'Nuria Costa', 8, 'Programada'),
(57, 57, '2026-04-15', 'Nuria Costa', 8, 'Programada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suscripcion`
--

CREATE TABLE `suscripcion` (
  `id_suscripcion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_membresia` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_renovacion` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `renovacion_automatica` tinyint(1) NOT NULL DEFAULT 1,
  `estado` enum('Activa','Pausada','Cancelada','Finalizada') NOT NULL DEFAULT 'Activa'
) ;

--
-- Volcado de datos para la tabla `suscripcion`
--

INSERT INTO `suscripcion` (`id_suscripcion`, `id_usuario`, `id_membresia`, `fecha_inicio`, `fecha_renovacion`, `fecha_fin`, `renovacion_automatica`, `estado`) VALUES
(1, 2, 3, '2026-04-07', '2026-05-07', NULL, 1, 'Activa'),
(2, 3, 4, '2026-04-08', '2026-05-08', NULL, 1, 'Activa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `alias` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `dni` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `sexo` enum('Hombre','Mujer','Otro') DEFAULT NULL,
  `estado` enum('Activo','Inactivo','Bloqueado') NOT NULL DEFAULT 'Activo',
  `foto_perfil` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `id_direccion` int(11) DEFAULT NULL,
  `id_perfil` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `alias`, `nombre`, `apellidos`, `fecha_nacimiento`, `dni`, `telefono`, `correo`, `contrasena`, `sexo`, `estado`, `foto_perfil`, `fecha_registro`, `id_direccion`, `id_perfil`) VALUES
(1, 'adminathena', 'Administrador', 'ATHENA', '1990-01-01', '00000000A', '600000000', 'admin@athena.com', '$2y$10$z2PrhJVP9GtH5P6Q9A/mz.ERyCUd0HCHL1exrSPV.LNJraksoeHk.', 'Otro', 'Activo', NULL, '2026-03-23 17:50:21', NULL, 1),
(2, 'Usuario1', 'Lorena', 'A', '1997-04-02', '12378945A', '612378945', 'usuario1@athena.com', '$2y$10$K6KxBbSvZ/mUj8Gxow0AC.Dhfr/oYARwWIpfHEFOl/jd1hKWiDlOe', 'Mujer', 'Activo', NULL, '2026-04-07 17:41:18', 1, 2),
(3, 'Usuario2', 'Luis', 'SPL', '1990-04-14', '78912345B', '678912345', 'usuario2@athena.com', '$2y$10$Qqe7Uo4M8ZTKRH96xoi6xOrqE.lCnCTbuXLL6KHrbqUPC2bNxw45u', 'Hombre', 'Activo', NULL, '2026-04-08 09:13:50', 2, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_notificacion`
--

CREATE TABLE `usuario_notificacion` (
  `id_usuario_notificacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_notificacion` int(11) NOT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_lectura` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividad`
--
ALTER TABLE `actividad`
  ADD PRIMARY KEY (`id_actividad`);

--
-- Indices de la tabla `contacto`
--
ALTER TABLE `contacto`
  ADD PRIMARY KEY (`id_contacto`);

--
-- Indices de la tabla `detalle_entrenamiento`
--
ALTER TABLE `detalle_entrenamiento`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `fk_detalle_entrenamiento` (`id_entrenamiento`);

--
-- Indices de la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD PRIMARY KEY (`id_direccion`);

--
-- Indices de la tabla `entrenamiento`
--
ALTER TABLE `entrenamiento`
  ADD PRIMARY KEY (`id_entrenamiento`),
  ADD KEY `fk_entrenamiento_usuario` (`id_usuario`);

--
-- Indices de la tabla `horario_actividad`
--
ALTER TABLE `horario_actividad`
  ADD PRIMARY KEY (`id_horario`),
  ADD KEY `fk_horario_actividad` (`id_actividad`),
  ADD KEY `fk_horario_sala` (`id_sala`);

--
-- Indices de la tabla `membresia`
--
ALTER TABLE `membresia`
  ADD PRIMARY KEY (`id_membresia`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `fk_membresia_periodo` (`id_periodo`);

--
-- Indices de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  ADD PRIMARY KEY (`id_notificacion`);

--
-- Indices de la tabla `objetivo_fitness`
--
ALTER TABLE `objetivo_fitness`
  ADD PRIMARY KEY (`id_objetivo`),
  ADD KEY `fk_objetivo_usuario` (`id_usuario`);

--
-- Indices de la tabla `perfil`
--
ALTER TABLE `perfil`
  ADD PRIMARY KEY (`id_perfil`),
  ADD UNIQUE KEY `nombre_perfil` (`nombre_perfil`);

--
-- Indices de la tabla `periodo`
--
ALTER TABLE `periodo`
  ADD PRIMARY KEY (`id_periodo`),
  ADD UNIQUE KEY `nombre_periodo` (`nombre_periodo`);

--
-- Indices de la tabla `progreso_fisico`
--
ALTER TABLE `progreso_fisico`
  ADD PRIMARY KEY (`id_progreso`),
  ADD KEY `fk_progreso_usuario` (`id_usuario`);

--
-- Indices de la tabla `reserva`
--
ALTER TABLE `reserva`
  ADD PRIMARY KEY (`id_reserva`),
  ADD UNIQUE KEY `uq_reserva_usuario_sesion` (`id_usuario`,`id_sesion`),
  ADD KEY `idx_reserva_usuario` (`id_usuario`),
  ADD KEY `idx_reserva_sesion` (`id_sesion`);

--
-- Indices de la tabla `sala`
--
ALTER TABLE `sala`
  ADD PRIMARY KEY (`id_sala`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `sesion_actividad`
--
ALTER TABLE `sesion_actividad`
  ADD PRIMARY KEY (`id_sesion`),
  ADD KEY `fk_sesion_horario` (`id_horario`);

--
-- Indices de la tabla `suscripcion`
--
ALTER TABLE `suscripcion`
  ADD PRIMARY KEY (`id_suscripcion`),
  ADD KEY `idx_suscripcion_usuario` (`id_usuario`),
  ADD KEY `idx_suscripcion_membresia` (`id_membresia`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `alias` (`alias`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `fk_usuario_direccion` (`id_direccion`),
  ADD KEY `idx_usuario_correo` (`correo`),
  ADD KEY `idx_usuario_alias` (`alias`),
  ADD KEY `idx_usuario_perfil` (`id_perfil`);

--
-- Indices de la tabla `usuario_notificacion`
--
ALTER TABLE `usuario_notificacion`
  ADD PRIMARY KEY (`id_usuario_notificacion`),
  ADD UNIQUE KEY `uq_usuario_notificacion` (`id_usuario`,`id_notificacion`),
  ADD KEY `fk_usuario_notificacion_notificacion` (`id_notificacion`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividad`
--
ALTER TABLE `actividad`
  MODIFY `id_actividad` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contacto`
--
ALTER TABLE `contacto`
  MODIFY `id_contacto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_entrenamiento`
--
ALTER TABLE `detalle_entrenamiento`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `direccion`
--
ALTER TABLE `direccion`
  MODIFY `id_direccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `entrenamiento`
--
ALTER TABLE `entrenamiento`
  MODIFY `id_entrenamiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `horario_actividad`
--
ALTER TABLE `horario_actividad`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT de la tabla `membresia`
--
ALTER TABLE `membresia`
  MODIFY `id_membresia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `objetivo_fitness`
--
ALTER TABLE `objetivo_fitness`
  MODIFY `id_objetivo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `perfil`
--
ALTER TABLE `perfil`
  MODIFY `id_perfil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `periodo`
--
ALTER TABLE `periodo`
  MODIFY `id_periodo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `progreso_fisico`
--
ALTER TABLE `progreso_fisico`
  MODIFY `id_progreso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reserva`
--
ALTER TABLE `reserva`
  MODIFY `id_reserva` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sala`
--
ALTER TABLE `sala`
  MODIFY `id_sala` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesion_actividad`
--
ALTER TABLE `sesion_actividad`
  MODIFY `id_sesion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `suscripcion`
--
ALTER TABLE `suscripcion`
  MODIFY `id_suscripcion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuario_notificacion`
--
ALTER TABLE `usuario_notificacion`
  MODIFY `id_usuario_notificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_entrenamiento`
--
ALTER TABLE `detalle_entrenamiento`
  ADD CONSTRAINT `fk_detalle_entrenamiento` FOREIGN KEY (`id_entrenamiento`) REFERENCES `entrenamiento` (`id_entrenamiento`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `entrenamiento`
--
ALTER TABLE `entrenamiento`
  ADD CONSTRAINT `fk_entrenamiento_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `horario_actividad`
--
ALTER TABLE `horario_actividad`
  ADD CONSTRAINT `fk_horario_actividad` FOREIGN KEY (`id_actividad`) REFERENCES `actividad` (`id_actividad`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_horario_sala` FOREIGN KEY (`id_sala`) REFERENCES `sala` (`id_sala`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `membresia`
--
ALTER TABLE `membresia`
  ADD CONSTRAINT `fk_membresia_periodo` FOREIGN KEY (`id_periodo`) REFERENCES `periodo` (`id_periodo`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `objetivo_fitness`
--
ALTER TABLE `objetivo_fitness`
  ADD CONSTRAINT `fk_objetivo_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `progreso_fisico`
--
ALTER TABLE `progreso_fisico`
  ADD CONSTRAINT `fk_progreso_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `reserva`
--
ALTER TABLE `reserva`
  ADD CONSTRAINT `fk_reserva_sesion` FOREIGN KEY (`id_sesion`) REFERENCES `sesion_actividad` (`id_sesion`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reserva_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `sesion_actividad`
--
ALTER TABLE `sesion_actividad`
  ADD CONSTRAINT `fk_sesion_horario` FOREIGN KEY (`id_horario`) REFERENCES `horario_actividad` (`id_horario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `suscripcion`
--
ALTER TABLE `suscripcion`
  ADD CONSTRAINT `fk_suscripcion_membresia` FOREIGN KEY (`id_membresia`) REFERENCES `membresia` (`id_membresia`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_suscripcion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_direccion` FOREIGN KEY (`id_direccion`) REFERENCES `direccion` (`id_direccion`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_perfil` FOREIGN KEY (`id_perfil`) REFERENCES `perfil` (`id_perfil`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario_notificacion`
--
ALTER TABLE `usuario_notificacion`
  ADD CONSTRAINT `fk_usuario_notificacion_notificacion` FOREIGN KEY (`id_notificacion`) REFERENCES `notificacion` (`id_notificacion`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_notificacion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
