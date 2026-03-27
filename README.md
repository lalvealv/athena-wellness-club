# ATHENA Wellness Club – Aplicación Web de Gestión de Gimnasio

## Descripción del proyecto
ATHENA Wellness Club es una aplicación web desarrollada como proyecto final del ciclo 
de Desarrollo de Aplicaciones Web (DAW).

El objetivo del proyecto es crear una plataforma web para la gestión de un club deportivo,
donde los usuarios puedan registrarse, acceder a su área privada, gestionar su suscripción,
reservar actividades y consultar su progreso.

El sistema también dispone de un área de administración para la gestión de usuarios,
reservas, entrenamientos y notificaciones.

---

## Funcionalidades principales

### Parte pública
- Página de inicio
- Información del club
- Tarifas y membresías
- Actividades
- Horarios
- Registro de usuarios
- Acceso al área de socios

### Área de socios
- Resumen del usuario
- Perfil personal
- Suscripción
- Reservas
- Historial de reservas
- Entrenamientos
- Estadísticas
- Objetivo fitness
- Notificaciones

### Área de administrador
- Gestión de usuarios
- Gestión de suscripciones
- Gestión de reservas
- Gestión de entrenamientos
- Gestión de notificaciones

---

## Tecnologías utilizadas

- HTML5
- CSS3
- PHP
- SQL
- GitHub
- XAMPP
- Visual Studio Code

---

## Estructura del proyecto
TFG/
│
├── admin/
│ ├── admin-panel.php
│ ├── admin-crear-usuario.html
│ ├── admin-editar-usuario.html
│ ├── admin-entrenamientos.html
│ ├── admin-notificaciones.html
│ ├── admin-reservas.html
│ ├── admin-suscripciones.html
│ └── admin-usuarios.html
│
├── publico/
│ ├── index.html
│ ├── nosotros.html
│ ├── tarifas.html
│ ├── actividades.html
│ ├── horarios.html
│ ├── unete.html
│ └── socios.html
│
├── socios/
│ ├── area-socios.php
│ ├── perfil.html
│ ├── suscripcion.html
│ ├── horarios-reservas.html
│ ├── historial-reservas.html
│ ├── entrenamientos.html
│ ├── estadisticas.html
│ ├── objetivo-fitness.html
│ ├── notificaciones.html
│ ├── login.php
│ ├── logout.php
│ ├── registro.php
│ ├── conexion.php
│ ├── comprobar-login.php
│ ├── comprobar-admin.php
│ └── crear-admin.php
│
├── database/
│ └── athena.sql
│
├── img/
├── img-socios/
├── estilo-admin.css
├── estilo-general.css
├── estilo-socios.css
└── README.md

---

## Base de datos

El archivo de la base de datos se encuentra en:
database/athena.sql


La base de datos contiene las tablas necesarias para gestionar:
- Usuarios
- Perfiles
- Direcciones
- Membresías
- Actividades
- Reservas
- Entrenamientos
- Notificaciones

---

## Instalación y uso

1. Instalar XAMPP
2. Copiar la carpeta del proyecto en:
   C:\xampp\htdocs\
3. Iniciar Apache y MySQL desde XAMPP
4. Importar la base de datos athena.sql
5. Abrir el navegador y acceder a:
http://localhost/TFG/publico/index.html


---

## Autor

Proyecto desarrollado por:
**Lorena Alvarez Alves**

Proyecto Final – Desarrollo de Aplicaciones Web (DAW)
