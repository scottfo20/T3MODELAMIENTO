/// <reference types="cypress" />

describe('Pruebas End-to-End para la Aplicación de Cine (Conjunto Completo)', () => {

    // La URL base se configura en cypress.config.js
    // Credenciales de prueba (¡AJUSTA ESTAS A UN UN USUARIO VÁLIDO REAL EN TU BD!)
    const VALID_USERNAME_EMAIL = 'doctor@hotmail.com'; // <--- ¡CREDENCIALES PROPORCIONADAS POR EL USUARIO!
    const VALID_PASSWORD = '12345678'; // <--- ¡CREDENCIALES PROPORCIONADAS POR EL USUARIO!

    // Credenciales inválidas para pruebas de error
    const INVALID_USERNAME_EMAIL = 'no_existe@example.com';
    const INVALID_PASSWORD = 'clave_incorrecta';

    // Se ejecuta antes de cada test para asegurar que siempre iniciamos desde la página principal
    beforeEach(() => {
        cy.visit('/'); // Usamos '/' porque baseUrl ya está configurada en cypress.config.js
        cy.log(`Navegando a la URL base: ${Cypress.config('baseUrl')}`);
    });

    // --- Escenario 1: Carga de la página principal ---
    it('1. Debe cargar la página principal y mostrar al menos una película', () => {
        cy.log('Verificando la carga de la página principal y visibilidad de películas.');
        cy.url().should('eq', Cypress.config('baseUrl')); // Verifica que la URL sea la base

        cy.get('div.peliculas-grid').should('be.visible');
        cy.get('.pelicula-item').should('have.length.at.least', 1);
        cy.get('.pelicula-item h3').first().should('not.be.empty');
        cy.log('Página principal cargada y películas visibles correctamente.');
    });

    // --- Escenario 2: Inicio de sesión exitoso ---
    it('2. Debe permitir a un usuario iniciar sesión exitosamente', () => {
        cy.log('Probando el inicio de sesión con credenciales válidas.');
        cy.get('a[href="login.php"]').click();
        cy.url().should('include', '/login.php');

        cy.get('#correo').type(VALID_USERNAME_EMAIL);
        cy.get('#contrasena').type(VALID_PASSWORD);
        cy.get('form button[type="submit"]').click();

        cy.get('a[href="logout.php"]').should('be.visible', { timeout: 10000 });
        cy.contains('Iniciar Sesión').should('not.exist');
        cy.url().should('include', '/index.php'); 
        cy.log('Inicio de sesión exitoso y redirección verificada.');
    });
    it ('4. Debe permitir a un usuario seleccionar una función y comprar un boleto'

    )



    // --- Escenario 3: Flujo de compra de un boleto ---
    it('4. Debe permitir a un usuario seleccionar una función y comprar un boleto', () => {
        cy.log('Iniciando el flujo de compra de boletos.');

        cy.log('Realizando login para iniciar el flujo de compra...');
        cy.get('a[href="login.php"]').click();
        cy.get('#correo').type(VALID_USERNAME_EMAIL);
        cy.get('#contrasena').type(VALID_PASSWORD);
        cy.get('form button[type="submit"]').click();
        
        cy.get('a[href="logout.php"]').should('be.visible'); 
        cy.url().should('include', '/index.php');
        cy.log('Login exitoso para la compra.');

        cy.contains('Furiosa: De la saga Mad Max')
            .closest('.pelicula-item')
            .find('.boton-comprar-boletos')
            .click();
        
        cy.url().should('include', '/pelicula.php');

        cy.get('.funcion-item').first().find('a.button').click();

        cy.url().should('include', '/asientos.php');

        cy.get('.asiento').not('.ocupado').first().click();
        cy.get('#btn-comprar').click();

        cy.url().should('include', '/confirmar_compra.php');

        cy.contains('Boleta de Compra').should('be.visible');
        cy.log('Compra de boleto completada exitosamente.');
    });

    // --- Escenario 4: Verificación del historial de compras (Simplificado a solo navegación) ---
    it('5. Debe permitir al usuario navegar a la página del historial', () => {
        cy.log('Verificando la navegación a la página del historial.');

        // PRE-CONDICIÓN: Iniciar sesión para asegurar que los enlaces de navegación estén visibles.
        cy.log('Realizando login para acceder al historial...');
        cy.get('a[href="login.php"]').click();
        cy.get('#correo').type(VALID_USERNAME_EMAIL);
        cy.get('#contrasena').type(VALID_PASSWORD);
        cy.get('form button[type="submit"]').click();
        
        cy.get('a[href="logout.php"]').should('be.visible'); 
        cy.url().should('include', '/index.php');
        cy.log('Login exitoso para navegar al historial.');
        
        cy.get('nav a[href="historial.php"]').click(); 
        
        cy.url().should('include', '/historial.php');
        cy.log('Navegación al historial verificada. No se verifica el contenido de las compras.');
    });

    // --- Escenario 5: Cierre de sesión (Logout) ---
    it('6. Debe permitir a un usuario cerrar sesión exitosamente', () => {
        cy.log('Realizando login para luego probar el logout.');
        cy.get('a[href="login.php"]').click();
        cy.get('#correo').type(VALID_USERNAME_EMAIL);
        cy.get('#contrasena').type(VALID_PASSWORD);
        cy.get('form button[type="submit"]').click();
        
        cy.get('a[href="logout.php"]').should('be.visible'); 
        cy.url().should('include', '/index.php');
        cy.log('Login exitoso para logout.');

        cy.log('Intentando cerrar sesión.');
        cy.get('a[href="logout.php"]').click();

        cy.url().should('include', '/index.php'); 
        
        cy.get('a[href="login.php"]').should('be.visible'); 
        cy.get('a[href="logout.php"]').should('not.exist'); 
        cy.log('Cierre de sesión verificado.');
    });

    // --- Escenario 7: Registro de un nuevo usuario exitoso (Campo de confirmación de contraseña REMOVIDO) ---
    it('7. Debe permitir a un nuevo usuario registrarse exitosamente', () => {
        cy.log('Probando el registro de un nuevo usuario.');
        // Generar un correo electrónico único para cada ejecución del test
        const uniqueEmail = `testuser_${Cypress._.random(0, 1e6)}@example.com`;
        const newPassword = 'newpassword123';
        const newUsername = `TestUser_${Cypress._.random(0, 1e6)}`;

        cy.get('a[href="registro.php"]').click();
        cy.url().should('include', '/registro.php');

        cy.get('#nombre_usuario').type(newUsername);
        cy.get('#correo').type(uniqueEmail);
        cy.get('#contrasena').type(newPassword);
        // cy.get('#confirmar_contrasena').type(newPassword); // ¡Línea removida!
        
        cy.get('form button[type="submit"]').click();

        // Después de un registro exitoso, se espera una redirección a login.php
        // (asumiendo que tu aplicación redirige al login después del registro)
        cy.url().should('include', '/login.php'); 
        cy.contains('Iniciar Sesión').should('be.visible'); // Asegura que se ve el formulario de login
        cy.log(`Usuario ${newUsername} con correo ${uniqueEmail} registrado exitosamente y redirigido a login.`);
    });

    // --- Escenario 8: Navegación a la página de Snacks ---
    it('8. Debe permitir la navegación a la página de Snacks', () => {
        cy.log('Probando navegación a la página de Snacks.');
        // Primero, iniciar sesión para asegurar que el enlace "Ver Snacks" esté visible en la navegación.
        cy.log('Realizando login para acceder a la página de Snacks...');
        cy.get('a[href="login.php"]').click();
        cy.get('#correo').type(VALID_USERNAME_EMAIL);
        cy.get('#contrasena').type(VALID_PASSWORD);
        cy.get('form button[type="submit"]').click();
        cy.get('a[href="logout.php"]').should('be.visible'); 
        cy.log('Login exitoso para navegar a Snacks.');

        cy.get('nav a[href="snacks.php"]').click();
        cy.url().should('include', '/snacks.php');
        // Opcional: Puedes añadir una aserción para verificar algún contenido en la página de snacks,
        // por ejemplo, cy.contains('Nuestros Snacks').should('be.visible');
        cy.log('Navegación a la página de Snacks verificada.');
    });

    // --- Escenario 9: Acceso a página protegida sin login ---
    it('9. Debe redirigir a login.php si se intenta acceder a historial.php sin iniciar sesión', () => {
        cy.log('Probando acceso a página protegida sin login.');
        // Aseguramos que no estamos logueados (si un test anterior dejó la sesión abierta)
        // Usamos { failOnStatusCode: false } por si el logout.php redirige antes de que Cypress vea un 200 OK
        cy.visit('/logout.php', { failOnStatusCode: false });

        // Intentar visitar historial.php directamente
        cy.visit('/historial.php');
        
        // Debería redirigirnos a login.php
        cy.url().should('include', '/login.php');
        cy.contains('Iniciar Sesión').should('be.visible'); // Asegura que el formulario de login es visible
        cy.log('Redirección a login.php verificada al intentar acceder a historial.php sin sesión.');
    });

});