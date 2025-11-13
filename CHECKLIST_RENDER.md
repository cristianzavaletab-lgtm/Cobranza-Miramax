# 🔄 Checklist Final Antes de Subir a Render

## ✅ Verificaciones Técnicas

- [ ] Código PHP sin errores de sintaxis
- [ ] Base de datos schema creado (database/schema.sql)
- [ ] Variables de entorno en .env.example
- [ ] Archivo .gitignore configurado
- [ ] Carpeta uploads/ con .gitkeep
- [ ] Archivo render.yaml presente
- [ ] composer.json creado

## 📝 Archivos Obligatorios Presentes

- [ ] README.md
- [ ] RENDER_DEPLOYMENT.md
- [ ] .env.example
- [ ] .gitignore
- [ ] composer.json
- [ ] render.yaml
- [ ] database/schema.sql
- [ ] includes/config.php

## 🔐 Seguridad

- [ ] Cambiar credenciales de admin en producción
- [ ] Verificar que .env NO está en git (solo .env.example)
- [ ] Habilitar HTTPS en Render
- [ ] Revisar logs de error
- [ ] Cambiar contraseña de MySQL

## 📋 Configuración en Render

- [ ] Variables de entorno definidas
- [ ] Base de datos conectada y funcional
- [ ] Build command correcto
- [ ] Start command correcto
- [ ] Region cercana seleccionada

## 🧪 Pruebas Finales

- [ ] Página principal carga (/)
- [ ] Consulta de deuda funciona (/consulta.php)
- [ ] Login de admin funciona (/admin/login.php)
- [ ] Base de datos accesible
- [ ] Uploads funciona (si aplica)

## 📦 Pasos para Subir a GitHub

```powershell
# 1. Inicializar git si no existe
git init

# 2. Agregar archivos
git add .

# 3. Crear commit
git commit -m "Initial commit: Sistema MIRAMAX listo para Render"

# 4. Conectar repositorio remoto
git remote add origin https://github.com/tu-usuario/nombre-repo.git

# 5. Subir código
git branch -M main
git push -u origin main
```

## 🚀 Próximos Pasos

1. ✅ Subirlo todo a GitHub
2. ✅ Conectar en Render.com
3. ✅ Configurar variables de entorno
4. ✅ Deployar
5. ✅ Importar base de datos
6. ✅ Verificar que todo funciona

## 📞 Notas Importantes

- Render redeploya automáticamente cuando empujas cambios a main
- Los cambios de variables de entorno requieren redeploy manual
- Plan Free tiene limitaciones: 0.5 GB RAM, sleep después de 15 min inactividad
- Considera actualizar a Starter para producción ($7/mes)

---

**Creado:** 12 de noviembre de 2025  
**Estado:** ✅ LISTO PARA SUBIR
