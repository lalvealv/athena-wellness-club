// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga las estadísticas del socio al abrir la página
    cargarEstadisticas();
});

// Muestra mensajes de estado en la pantalla de estadísticas
function mostrarMensajeEstadisticas(tipo, texto) {
    const mensaje = document.getElementById("mensaje-estadisticas");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

// Pinta la barra visual del porcentaje de asistencia
function pintarBarraAsistencia(porcentajeNumero, textoResumen) {
    const titulo = document.getElementById("asistencia-porcentaje-visual");
    const texto = document.getElementById("asistencia-texto-visual");
    const barra = document.getElementById("barra-asistencia");

    // Muestra el porcentaje en texto
    titulo.textContent = `${porcentajeNumero}%`;

    // Muestra el resumen de asistencia
    texto.textContent = textoResumen;

    // Ajusta la barra entre 0% y 100%
    barra.style.width = `${Math.max(0, Math.min(100, porcentajeNumero))}%`;
}

// Pinta la tabla de ranking de actividades
function pintarRankingActividad(ranking) {
    const tbody = document.getElementById("tabla-ranking-actividad");
    tbody.innerHTML = "";

    // Si no hay ranking, muestra mensaje vacío
    if (!ranking || ranking.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4">Todavía no hay actividades para mostrar.</td></tr>`;
        return;
    }

    // Calcula el valor máximo para sacar porcentajes proporcionales
    const maximo = Math.max(...ranking.map(item => item.total), 1);

    // Recorre cada actividad del ranking
    ranking.forEach((item, index) => {
        const porcentaje = Math.round((item.total / maximo) * 100);

        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td>${index + 1}</td>
            <td>${item.actividad}</td>
            <td>${item.total}</td>
            <td>
                <div class="table-progress">
                    <div class="table-progress-fill" style="width:${porcentaje}%"></div>
                </div>
            </td>
        `;

        tbody.appendChild(fila);
    });
}

// Pinta una gráfica simple de resumen de actividad
function pintarGraficaResumenActividad(data) {
    const contenedor = document.getElementById("grafica-resumen-actividad");
    contenedor.innerHTML = "";

    // Datos que se mostrarán en la gráfica
    const elementos = [
        {
            etiqueta: "Clases mes",
            valor: Number(data.clases_mes_numero || 0)
        },
        {
            etiqueta: "Entrenos semana",
            valor: Number(data.entrenamientos_semanales_numero || 0)
        },
        {
            etiqueta: "Asistencias",
            valor: Number(data.asistencias_totales_numero || 0)
        }
    ];

    // Calcula el máximo para hacer barras proporcionales
    const maximo = Math.max(...elementos.map(item => item.valor), 1);

    // Crea una barra por cada dato
    elementos.forEach(item => {
        const porcentaje = Math.round((item.valor / maximo) * 100);

        const bloque = document.createElement("div");
        bloque.className = "chart-bar-item";
        bloque.innerHTML = `
            <div class="chart-bar-header">
                <span>${item.etiqueta}</span>
                <strong>${item.valor}</strong>
            </div>
            <div class="chart-bar-track">
                <div class="chart-bar-fill" style="width:${porcentaje}%"></div>
            </div>
        `;

        contenedor.appendChild(bloque);
    });
}

// Pinta una gráfica de barras con las actividades más reservadas
function pintarGraficaRankingActividad(ranking) {
    const contenedor = document.getElementById("grafica-ranking-actividad");
    contenedor.innerHTML = "";

    // Si no hay actividades, muestra mensaje
    if (!ranking || ranking.length === 0) {
        contenedor.innerHTML = `<div class="chart-empty">Sin actividades para mostrar.</div>`;
        return;
    }

    // Calcula el máximo para hacer barras proporcionales
    const maximo = Math.max(...ranking.map(item => item.total), 1);

    // Crea una barra por cada actividad
    ranking.forEach(item => {
        const porcentaje = Math.round((item.total / maximo) * 100);

        const bloque = document.createElement("div");
        bloque.className = "chart-bar-item";
        bloque.innerHTML = `
            <div class="chart-bar-header">
                <span>${item.actividad}</span>
                <strong>${item.total}</strong>
            </div>
            <div class="chart-bar-track">
                <div class="chart-bar-fill" style="width:${porcentaje}%"></div>
            </div>
        `;

        contenedor.appendChild(bloque);
    });
}

// Carga las estadísticas desde el backend
async function cargarEstadisticas() {
    try {
        const response = await fetch("../API/estadistica.php", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeEstadisticas("error", data.mensaje || "No se pudieron cargar las estadísticas.");
            return;
        }

        // Carga los datos del socio en el sidebar
        document.getElementById("sidebar-foto").src = data.sidebar.foto_perfil;
        document.getElementById("sidebar-nombre").textContent = data.sidebar.nombre_completo;
        document.getElementById("sidebar-plan").textContent = data.sidebar.membresia;

        // Carga estadísticas principales
        document.getElementById("estadistica-clases-mes").textContent = data.estadisticas.clases_mes;
        document.getElementById("estadistica-clases-mes-detalle").textContent = data.estadisticas.clases_mes_detalle;

        document.getElementById("estadistica-actividad-favorita").textContent = data.estadisticas.actividad_favorita;
        document.getElementById("estadistica-actividad-favorita-detalle").textContent = data.estadisticas.actividad_favorita_detalle;

        document.getElementById("estadistica-entrenamientos-semanales").textContent = data.estadisticas.entrenamientos_semanales;
        document.getElementById("estadistica-entrenamientos-semanales-detalle").textContent = data.estadisticas.entrenamientos_semanales_detalle;

        document.getElementById("estadistica-asistencia").textContent = data.estadisticas.asistencia;
        document.getElementById("estadistica-asistencia-detalle").textContent = data.estadisticas.asistencia_detalle;

        // Pinta barra visual de asistencia
        pintarBarraAsistencia(
            Number(data.estadisticas.asistencia_porcentaje_numero || 0),
            data.estadisticas.asistencia_detalle
        );

        // Pinta tabla y gráficas visuales
        pintarRankingActividad(data.ranking_actividad || []);
        pintarGraficaResumenActividad(data.estadisticas);
        pintarGraficaRankingActividad(data.ranking_actividad || []);

        // Muestra mensaje de éxito
        mostrarMensajeEstadisticas("success", "Estadísticas cargadas correctamente.");

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mostrarMensajeEstadisticas("error", "Error de conexión con el servidor.");
    }
}