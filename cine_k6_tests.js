// cine_k6_tests.js

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { htmlReport } from "https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js"; // Para reportes HTML (opcional)

// Define la URL base de tu aplicación (AJUSTA ESTO A TU URL REAL)
const BASE_URL = 'http://localhost/cine_web';

// Define las opciones para tu prueba de carga
// vus: Usuarios virtuales concurrentes
// duration: Duración total de la prueba
export let options = {
    vus: 5,     // 5 usuarios virtuales
    duration: '30s', // La prueba durará 30 segundos
    thresholds: {
        http_req_failed: ['rate<0.01'], // menos del 1% de fallos en peticiones HTTP
        http_req_duration: ['p(95)<500'], // el 95% de las peticiones deben ser más rápidas que 500ms
    },
};

// Credenciales para pruebas de login/logout/historial
// ¡AJUSTA ESTAS A UN USUARIO VÁLIDO REAL EN TU BD!
const VALID_USER_EMAIL = 'doctor@hotmail.com';
const VALID_USER_PASSWORD = '12345678';

// Punto de entrada principal para el script de K6
export default function () {

    // 1. Carga de la página principal
    group('1. Carga de la página principal', () => {
        let res = http.get(BASE_URL);
        check(res, {
            'Homepage: status is 200': (r) => r.status === 200,
            'Homepage: contiene titulo': (r) => r.body.includes('Cine GrenMark'),
            'Homepage: contiene peliculas': (r) => r.body.includes('peliculas-grid'),
        });
        sleep(1); // Simular un tiempo de "pensamiento" del usuario
    });

    // 2. Inicio de sesión exitoso
    group('2. Inicio de sesión exitoso', () => {
        // Primero, obtener la página de login para obtener cookies de sesión si las hay
        let loginPageRes = http.get(`${BASE_URL}/login.php`);
        check(loginPageRes, {
            'Login Page: status is 200': (r) => r.status === 200,
        });

        const loginPayload = {
            correo: VALID_USER_EMAIL,
            contrasena: VALID_USER_PASSWORD,
        };

        // Enviar credenciales (K6 maneja las cookies automáticamente)
        let res = http.post(`${BASE_URL}/login.php`, loginPayload);

        check(res, {
            'Login Successful: status is 200 or 302 (redirect)': (r) => r.status === 200 || r.status === 302,
            'Login Successful: redirected to index.php': (r) => r.url.includes('/index.php'),
            'Login Successful: No "Iniciar Sesión" link': (r) => !r.body.includes('Iniciar Sesión'),
            'Login Successful: "Cerrar Sesión" link is present': (r) => r.body.includes('Cerrar Sesión'),
        });
        sleep(1);
    });

    // 3. Inicio de sesión fallido
    group('3. Inicio de sesión fallido', () => {
        let loginPageRes = http.get(`${BASE_URL}/login.php`);
        check(loginPageRes, {
            'Login Failed Page: status is 200': (r) => r.status === 200,
        });

        const invalidLoginPayload = {
            correo: 'invalid@example.com',
            contrasena: 'wrongpassword',
        };

        let res = http.post(`${BASE_URL}/login.php`, invalidLoginPayload);

        check(res, {
            'Login Failed: status is 200': (r) => r.status === 200, // Debería permanecer en la página de login
            'Login Failed: contains error message': (r) => r.body.includes('Correo o contraseña incorrectos.'),
            'Login Failed: still on login.php': (r) => r.url.includes('/login.php'),
        });
        sleep(1);
    });

    // 4. Registro de un nuevo usuario
    group('4. Registro de un nuevo usuario', () => {
        // Generar datos únicos para cada iteración/usuario virtual
        const uniqueEmail = `testuser_${__VU}_${__ITER}@example.com`;
        const newPassword = 'newpassword123';
        const newUsername = `TestUser_${__VU}_${__ITER}`;

        let registerPageRes = http.get(`${BASE_URL}/registro.php`);
        check(registerPageRes, {
            'Register Page: status is 200': (r) => r.status === 200,
        });

        const registerPayload = {
            nombre_usuario: newUsername,
            correo: uniqueEmail,
            contrasena: newPassword,
            // Si tu campo de confirmación no tiene ID 'confirmar_contrasena' o no existe,
            // esta línea no es necesaria y la quitamos para simplificar.
            // Si tu campo tiene un name diferente, ajústalo aquí:
            // confirmar_contrasena: newPassword, // <-- ajusta el 'name' si es diferente
        };

        let res = http.post(`${BASE_URL}/registro.php`, registerPayload);

        check(res, {
            'Registration Successful: status is 200 or 302': (r) => r.status === 200 || r.status === 302,
            'Registration Successful: redirected to login.php': (r) => r.url.includes('/login.php'),
            'Registration Successful: login form visible': (r) => r.body.includes('Iniciar Sesión'),
        });
        sleep(1);
    });

    // 5. Ver página de detalles de una película
    group('5. Ver página de detalles de una película', () => {
        // Necesitamos un ID de película válido. Para simplicidad, usaremos un ID fijo.
        // Si tu película ID 1 no existe, cámbialo por uno que sí exista.
        const PELICULA_ID = 1; 
        let res = http.get(`${BASE_URL}/pelicula.php?id=${PELICULA_ID}`);
        check(res, {
            'Movie Details Page: status is 200': (r) => r.status === 200,
            'Movie Details Page: contains movie title section': (r) => r.body.includes('<h3>Funciones Disponibles</h3>'),
        });
        sleep(1);
    });

    // 6. Navegación a la página de Snacks
    group('6. Navegación a la página de Snacks', () => {
        // Asegurarse de estar logueado para que el enlace sea visible en el menú si es necesario
        // Aunque para una carga directa, no siempre es indispensable.
        // Si la página de snacks requiere login, hay que hacer un login antes.
        // Por simplicidad, asumimos que se puede acceder directamente.
        let res = http.get(`${BASE_URL}/snacks.php`);
        check(res, {
            'Snacks Page: status is 200': (r) => r.status === 200,
            'Snacks Page: contains expected content': (r) => r.body.includes('Nuestros Snacks') || r.body.includes('Productos Disponibles'), // Ajusta esto al contenido real de tu página de snacks
        });
        sleep(1);
    });

    // 7. Ver Historial de Compras (requiere login)
    group('7. Ver Historial de Compras', () => {
        // Primero, loguearse para tener una sesión activa
        let loginPageRes = http.get(`${BASE_URL}/login.php`);
        http.post(`${BASE_URL}/login.php`, {
            correo: VALID_USER_EMAIL,
            contrasena: VALID_USER_PASSWORD,
        });

        let res = http.get(`${BASE_URL}/historial.php`);
        check(res, {
            'History Page: status is 200': (r) => r.status === 200,
            'History Page: contains "Historial de Compras"': (r) => r.body.includes('Historial de Compras'),
            // No verificamos el contenido específico de las compras, solo que la página carga
        });
        sleep(1);
    });

    // 8. Cierre de sesión (Logout)
    group('8. Cierre de sesión', () => {
        // Primero, loguearse para tener algo que cerrar sesión
        let loginPageRes = http.get(`${BASE_URL}/login.php`);
        http.post(`${BASE_URL}/login.php`, {
            correo: VALID_USER_EMAIL,
            contrasena: VALID_USER_PASSWORD,
        });

        let res = http.get(`${BASE_URL}/logout.php`);

        check(res, {
            'Logout Successful: status is 200 or 302': (r) => r.status === 200 || r.status === 302,
            'Logout Successful: redirected to index.php': (r) => r.url.includes('/index.php'),
            'Logout Successful: "Iniciar Sesión" link visible': (r) => r.body.includes('Iniciar Sesión'),
            'Logout Successful: No "Cerrar Sesión" link': (r) => !r.body.includes('Cerrar Sesión'),
        });
        sleep(1);
    });

    // 9. Intento de acceso a página protegida sin login (historial.php)
    group('9. Acceso a página protegida sin login', () => {
        // Asegurarse de que no hay sesión activa antes de intentar acceder
        http.get(`${BASE_URL}/logout.php`); // Forzar logout si hay una sesión activa

        let res = http.get(`${BASE_URL}/historial.php`);

        check(res, {
            'Access Protected Page: Redirected to login.php': (r) => r.url.includes('/login.php'),
            'Access Protected Page: Login form visible': (r) => r.body.includes('Iniciar Sesión'),
        });
        sleep(1);
    });
}

// Función para generar un reporte HTML (opcional)
// Para usarlo, descomenta la siguiente línea y la importación de `htmlReport` al inicio.
// export function handleSummary(data) {
//     return {
//         "summary.html": htmlReport(data),
//     };
// }