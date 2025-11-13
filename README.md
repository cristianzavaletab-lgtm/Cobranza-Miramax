# MIRAMAX - Sistema de Cobro en Línea

Sistema web moderno para gestión de cobros con módulo administrativo integrado.

## 🚀 Características

- ✅ Consulta de deuda por DNI
- ✅ Registro de pagos en línea
- ✅ Panel administrativo con autenticación
- ✅ Gestión de clientes y reportes
- ✅ Carga de comprobantes de pago
- ✅ Estadísticas y métricas en tiempo real

## 📋 Requisitos

- PHP >= 7.4
- MySQL 5.7+
- Composer (opcional)

## 🔧 Instalación Local

1. Clonar el repositorio:
```bash
git clone <tu-repositorio> miramax
cd miramax
```

2. Configurar variables de entorno:
```bash
cp .env.example .env
```

3. Editar `.env` con tus credenciales de base de datos

4. Ejecutar en servidor local:
```bash
php -S localhost:8000
```

## 🌐 Despliegue en Render

### Pasos:

1. **Crear cuenta en [Render.com](https://render.com)**

2. **Conectar tu repositorio GitHub**

3. **Crear nuevo Web Service:**
   - Nombre: `sistema-cobranza`
   - Entorno: PHP
   - Plan: Free (o superior según necesidad)

4. **Configurar variables de entorno en Render:**
   ```
   DB_HOST: tu-host-mysql
   DB_PORT: 3306
   DB_NAME: sistema_cobranza
   DB_USER: tu-usuario
   DB_PASSWORD: tu-contraseña
   PHP_VERSION: 8.1
   ```

5. **Crear base de datos MySQL en Render o usar servicio externo**

6. **Importar base de datos:**
   - Usar script SQL en `database/schema.sql`

## 📁 Estructura del Proyecto

```
├── admin/              # Panel administrativo
├── process/            # Scripts de procesamiento
├── includes/           # Archivos incluibles
├── css/                # Estilos
├── js/                 # Scripts JavaScript
├── uploads/            # Comprobantes y archivos
├── .env.example        # Variables de entorno
├── composer.json       # Dependencias PHP
└── render.yaml         # Configuración Render
```

## 🔐 Credenciales de Prueba

- **Usuario:** admin
- **Contraseña:** admin123

⚠️ **CAMBIAR EN PRODUCCIÓN**

## 📞 Soporte

Para reportar problemas o sugerencias, contacta al equipo técnico.

---

**Última actualización:** 12 de noviembre de 2025
