#!/usr/bin/env php
<?php
/**
 * Script de inicialización para Render
 * Se ejecuta automáticamente durante el despliegue
 */

echo "🔧 Iniciando configuración para Render...\n";

// Crear carpeta de uploads si no existe
$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
    echo "✅ Carpeta de uploads creada\n";
}

// Crear carpeta .gitkeep en uploads
if (!file_exists($uploadsDir . '/.gitkeep')) {
    touch($uploadsDir . '/.gitkeep');
    echo "✅ Archivo .gitkeep creado\n";
}

// Verificar variables de entorno
$requiredEnvVars = ['DB_HOST', 'DB_NAME', 'DB_USER'];
$missingVars = [];

foreach ($requiredEnvVars as $var) {
    if (!getenv($var)) {
        $missingVars[] = $var;
    }
}

if (!empty($missingVars)) {
    echo "⚠️  Variables de entorno faltantes: " . implode(', ', $missingVars) . "\n";
    echo "Por favor, configúralas en el panel de Render\n";
} else {
    echo "✅ Variables de entorno configuradas correctamente\n";
}

echo "\n✨ Configuración completada!\n";
?>
