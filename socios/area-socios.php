<?php require_once 'comprobar-login.php'; ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Área de socios | ATHENA Wellness Club</title>
    <meta name="description"
        content="Consulta tu resumen general en el área privada de ATHENA Wellness Club: suscripción, próximas clases, entrenamientos y objetivo fitness.">
    <meta name="keywords"
        content="área de socios, panel privado, resumen socio, suscripción gimnasio, entrenamientos, objetivo fitness, ATHENA Wellness Club">
    <meta name="author" content="lavealv">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../estilo-general.css">
    <link rel="stylesheet" href="../estilo-socios.css">
</head>

<body>
    <!-- HEADER / CABECERA DEL ÁREA PRIVADA -->
    <header class="site-header site-header--socios">
        <div class="container header-inner header-inner--socios">

            <!-- Logo de la marca y acceso a la página principal -->
            <a class="brand" href="../publico/index.html" aria-label="ATHENA Wellness Club">
                <img src="../img/athena_logo.png" alt="Logo de ATHENA Wellness Club">
                <span class="brand-text">
                    <strong>ATHENA</strong>
                    <span>Wellness Club · Área privada</span>
                </span>
            </a>

            <!-- Menú superior de navegación del área de socios -->
            <nav class="nav nav--socios" aria-label="Navegación del área privada">
                <a class="is-active" href="area-socios.php">Resumen</a>
                <a href="perfil.html">Mi perfil</a>
                <a href="suscripcion.html">Suscripción</a>
                <a href="horarios-reservas.html">Reservas</a>
                <a href="entrenamientos.html">Entrenamientos</a>
                <a href="estadisticas.html">Estadísticas</a>
                <a href="notificaciones.html">Avisos</a>
                <button class="btn btn--ghost btn--small" type="button">Cerrar sesión</button>
            </nav>

        </div>
    </header>

    <!-- CONTENIDO PRINCIPAL DEL ÁREA DE SOCIOS -->
    <main class="section section-socios">

        <!-- Layout principal con sidebar y zona de contenido -->
        <div class="container socios-layout">

            <!-- SIDEBAR / MENÚ LATERAL DEL SOCIO -->
            <aside class="socios-sidebar">

                <!-- Tarjeta de información rápida del socio -->
                <div class="socios-user">
                    <img src="../img-socios/socio1.png" alt="Foto de perfil del socio">
                    <h3>Lorena Alvarez Alves</h3>
                    <p>Socia Premium</p>
                </div>

                <!-- Navegación lateral del área privada -->
                <nav class="socios-menu" aria-label="Menú lateral del área privada">
                    <a class="is-active" href="area-socios.php">Resumen</a>
                    <a href="perfil.html">Mi perfil</a>
                    <a href="suscripcion.html">Mi suscripción</a>
                    <a href="horarios-reservas.html">Horarios y reservas</a>
                    <a href="historial-reservas.html">Historial reservas</a>
                    <a href="entrenamientos.html">Entrenamientos</a>
                    <a href="estadisticas.html">Estadísticas</a>
                    <a href="objetivo-fitness.html">Objetivo fitness</a>
                    <a href="notificaciones.html">Notificaciones</a>
                </nav>
            </aside>

            <!-- ZONA CENTRAL DE CONTENIDO -->
            <div class="socios-content">

                <!-- Panel principal con el resumen general del socio -->
                <section class="socios-panel">

                    <!-- Título del panel -->
                    <h2 class="h2">Resumen general</h2>

                    <!-- Grid de tarjetas con información destacada -->
                    <div class="dashboard-grid">

                        <div class="info-card">
                            <span class="label">Suscripción</span>
                            <h3>Premium</h3>
                            <p>Renovación: 01/04/2026</p>
                        </div>

                        <div class="info-card">
                            <span class="label">Próxima clase</span>
                            <h3>Yoga / Pilates</h3>
                            <p>Sábado · 07:30</p>
                        </div>

                        <div class="info-card">
                            <span class="label">Entrenamientos esta semana</span>
                            <h3>4</h3>
                            <p>Muy buena constancia</p>
                        </div>

                        <div class="info-card">
                            <span class="label">Objetivo fitness</span>
                            <h3>75%</h3>
                            <p>Tonificación</p>
                        </div>

                    </div>

                </section>

            </div>

        </div>

    </main>

    <!-- FOOTER / PIE DE PÁGINA -->
    <footer class="site-footer">
        <div class="container footer-inner">
            <div>
                <p><strong>Contacto</strong></p>
                <p>Teléfono: 645 789 123</p>
                <p>Correo: club@gmail.com</p>
            </div>
            <div>
                <p><strong>Horario</strong></p>
                <p class="muted">L–V 07:00–22:30 · S–D 09:00–20:00</p>
            </div>
        </div>
    </footer>

</body>

</html>