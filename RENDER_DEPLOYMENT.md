# 🚀 GUÍA DE DESPLIEGUE EN RENDER

## Pasos para desplegar MIRAMAX en Render

### 1️⃣ Preparación Previa

✅ Asegurate de tener:
- Código subido a GitHub
- Archivo `.env.example` en el repositorio
- Base de datos MySQL lista (Render o servicio externo)
- Token de GitHub

### 2️⃣ Crear Cuenta en Render

1. Ve a [https://render.com](https://render.com)
2. Regístrate con GitHub
3. Autoriza la conexión con tu cuenta de GitHub

### 3️⃣ Crear Nuevo Web Service

1. Dashboard → "New +"  → "Web Service"
2. Selecciona tu repositorio
3. Configura:
   - **Nombre:** `sistema-cobranza`
   - **Entorno:** PHP
   - **Plan:** Free (o Starter según necesidad)
   - **Region:** Elige la más cercana

### 4️⃣ Configurar Variables de Entorno

En Render dashboard, ve a la sección "Environment" y agrega:

```
DB_HOST=tu-host-mysql.com
DB_PORT=3306
DB_NAME=sistema_cobranza
DB_USER=usuario_db
DB_PASSWORD=contraseña_segura
APP_ENV=production
APP_DEBUG=false
PHP_VERSION=8.1
```

**Obtener detalles de MySQL:**
- Si usas **Render Database:** Ve a tu base de datos y copia los detalles
- Si usas **otro servicio:** Obtén los datos de tu proveedor

### 5️⃣ Configurar Build y Deploy

**Build Command (si se requiere):**
```bash
composer install --no-dev
php bin/render-build.php
```

**Start Command:**
```bash
php -S 0.0.0.0:8000 -t .
```

⚠️ *Render configura esto automáticamente si tiene `render.yaml`*

### 6️⃣ Importar Base de Datos

Una vez que el servicio esté deployado:

1. Conéctate a tu MySQL desde la terminal o cliente MySQL
2. Importa el schema:
```bash
mysql -h tu-host -u usuario -p sistema_cobranza < database/schema.sql
```

3. Verifica que las tablas se crearon:
```bash
mysql -h tu-host -u usuario -p -e "USE sistema_cobranza; SHOW TABLES;"
```

### 7️⃣ Verificar Despliegue

1. Ve al URL de tu aplicación en Render
2. Debería ver: "BIENVENIDO A MIRAMAX"
3. Prueba acceder a:
   - `https://tu-app.onrender.com/consulta.php` - Consulta de deuda
   - `https://tu-app.onrender.com/admin/login.php` - Panel admin

### 8️⃣ Credenciales de Ingreso (⚠️ CAMBIAR EN PRODUCCIÓN)

**Usuario:** admin  
**Contraseña:** admin123

🔐 **IMPORTANTE:** Cambiar estas credenciales inmediatamente en la base de datos.

---

## ⚡ Solución de Problemas

### Error: "No database selected"
- Verifica que las variables `DB_*` estén correctas en Render
- Asegurate que la base de datos existe

### Error: "Connection refused"
- Verifica el `DB_HOST` y `DB_PORT`
- Comprueba que tu MySQL acepta conexiones externas
- Whitelist la IP de Render

### Archivos cargados (uploads) no persisten
- Render no tiene almacenamiento persistente en plan Free
- Solución: Usar AWS S3 o servicio similar para uploads

### Logs de error
- En Render: Vé a "Logs" para ver errores en tiempo real
- Archivos locales: `/logs/error.log`

---

## 📊 Monitoreo

En el dashboard de Render:
- Observa CPU, memoria y solicitudes
- Configura alertas para downtime
- Revisa logs regularmente

---

## 🔄 Actualizaciones

Para actualizar el código:

1. Push a GitHub:
```bash
git add .
git commit -m "Update: descripción"
git push origin main
```

2. Render automáticamente redeploya
3. O redeploya manualmente desde dashboard

---

## 📞 Soporte

- Documentación Render: [https://render.com/docs](https://render.com/docs)
- Issues: Abre un issue en GitHub
- Email: contacto@miramax.local

---

**Última actualización:** 12 de noviembre de 2025
