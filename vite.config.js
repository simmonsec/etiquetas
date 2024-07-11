import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import dotenv from 'dotenv';

// Cargar las variables de entorno desde el archivo .env
dotenv.config();

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: process.env.IP_DESARROLLO || 'localhost', // Usa la IP fija o 'localhost' como valor predeterminado
        port: 3000, // Puedes cambiar el puerto si lo prefieres
    },
});
