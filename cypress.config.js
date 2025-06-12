// cypress.config.js
const { defineConfig } = require("cypress");

module.exports = defineConfig({
  e2e: {
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
    baseUrl: 'http://localhost/cine_web/', // ¡MUY IMPORTANTE! ASEGÚRATE QUE ESTA ES LA URL CORRECTA DE TU PROYECTO
    defaultCommandTimeout: 6000, // Aumentado a 6 segundos para dar un poco más de margen
  },
});