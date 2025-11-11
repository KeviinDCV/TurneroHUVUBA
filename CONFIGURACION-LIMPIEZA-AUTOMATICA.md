# 🔄 Configuración de Limpieza Automática de Turnos

## 📋 Descripción

El sistema ahora incluye **limpieza automática de turnos** que se ejecuta todos los días a las **12:00 AM (medianoche)**.

### ✨ Características

- ✅ Se ejecuta automáticamente a medianoche (00:00 horas)
- ✅ Elimina los turnos del día actual de la tabla `turnos` (temporal)
- ✅ **PRESERVA** el historial completo en `turno_historial` para reportes
- ✅ Registra logs de cada ejecución
- ✅ Zona horaria: Colombia (America/Bogota)

---

## 🚀 Configuración en el Servidor

Para que la limpieza automática funcione, debes configurar un **cron job** en tu servidor.

### **Opción 1: Servidor Linux/cPanel**

1. Accede al **cPanel** → **Cron Jobs** (o edita crontab manualmente)

2. Agrega la siguiente línea:

```bash
* * * * * cd /ruta/a/tu/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

**Ejemplo con ruta completa:**
```bash
* * * * * cd /home/usuario/public_html/turnero-huv && php artisan schedule:run >> /dev/null 2>&1
```

3. Guarda el cron job

**Explicación:**
- `* * * * *` = Se ejecuta cada minuto
- Laravel internamente verifica qué comandos deben ejecutarse según su horario
- El comando `turnos:limpiar-antiguos` solo se ejecutará a medianoche

### **Opción 2: Servidor Windows**

1. Abre el **Programador de tareas** de Windows

2. Crea una nueva tarea:
   - **Nombre:** Turnero - Limpieza Automática
   - **Desencadenador:** Cada día a las 12:00 AM
   - **Acción:** Iniciar programa
   - **Programa:** `C:\ruta\php\php.exe`
   - **Argumentos:** `artisan schedule:run`
   - **Iniciar en:** `C:\ruta\proyecto\turnero-huv`

### **Opción 3: Local (Desarrollo con XAMPP)**

Para pruebas locales, ejecuta manualmente:

```bash
php artisan schedule:work
```

Este comando simula el cron y ejecuta los comandos programados. **Déjalo corriendo en una terminal.**

---

## 🧪 Pruebas Manuales

### **Ejecutar limpieza inmediatamente**

```bash
# Limpiar turnos del día actual
php artisan turnos:limpiar-antiguos

# Limpiar turnos de hace 1 día
php artisan turnos:limpiar-antiguos --dias=1

# Limpiar turnos de hace 7 días
php artisan turnos:limpiar-antiguos --dias=7
```

### **Ver el resultado**

El comando mostrará:
- Fecha limpiada
- Desglose de turnos por estado (pendientes, atendidos, etc.)
- Total de turnos eliminados
- Confirmación de que el historial se preservó

**Ejemplo de salida:**
```
🔄 Iniciando limpieza automática de turnos...
⏰ Hora de ejecución: 2025-11-11 00:00:00
📅 Limpiando turnos del día: 2025-11-11

📊 Resumen de turnos a eliminar:
   • Pendientes: 15
   • Llamados: 2
   • Atendidos: 143
   • Aplazados: 8
   • Total: 168

✅ Limpieza completada exitosamente!
   • Turnos eliminados: 168
   • Registros en historial: 5,234 (✓ preservados)
```

---

## 📊 Verificar que el Cron está Funcionando

### **1. Revisar logs de Laravel**

```bash
# Ver últimas líneas del log
tail -f storage/logs/laravel.log
```

Deberías ver entradas como:
```
[2025-11-11 00:00:05] local.INFO: ✅ Limpieza automática de turnos completada exitosamente
```

### **2. Verificar comandos programados**

```bash
php artisan schedule:list
```

Deberías ver:
```
0 0 * * *  turnos:limpiar-antiguos --dias=0 .... Next Due: 1 day from now
```

### **3. Probar el scheduler manualmente**

```bash
php artisan schedule:run
```

Si NO es medianoche, verá:
```
No scheduled commands are ready to run.
```

---

## ⚙️ Personalización

### **Cambiar hora de ejecución**

Edita `routes/console.php`, línea 18:

```php
// Para ejecutar a las 2:00 AM
->dailyAt('02:00')

// Para ejecutar varias veces al día
->dailyAt('00:00')  // Medianoche
->dailyAt('12:00')  // Mediodía
```

### **Cambiar días a limpiar**

Por defecto limpia turnos del día actual (`--dias=0`).

Para limpiar días anteriores, edita la línea 17:

```php
// Limpiar turnos de hace 1 día (ayer)
Schedule::command('turnos:limpiar-antiguos --dias=1')

// Limpiar turnos de hace 7 días
Schedule::command('turnos:limpiar-antiguos --dias=7')
```

---

## 🔒 Seguridad y Respaldo

### **El historial SIEMPRE se preserva**

- Tabla `turnos` → Se limpia (datos temporales del día)
- Tabla `turno_historial` → **NUNCA se toca** (respaldo permanente)

Esto garantiza que:
- ✅ Los reportes históricos siguen funcionando
- ✅ Puedes auditar cualquier turno del pasado
- ✅ El sistema se mantiene ligero y rápido

### **Respaldo manual antes de configurar**

Recomendado antes de activar el cron:

```bash
# Backup de la base de datos
mysqldump -u usuario -p turnero_huv > backup_antes_limpieza.sql
```

---

## ❓ Solución de Problemas

### **El cron no se ejecuta**

1. **Verifica que el cron esté configurado:**
   ```bash
   crontab -l
   ```

2. **Revisa los permisos del proyecto:**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

3. **Verifica la ruta de PHP:**
   ```bash
   which php
   # Usa la ruta completa en el cron
   ```

### **Error "Command not found"**

El cron debe usar la ruta completa de PHP:

```bash
* * * * * /usr/bin/php /ruta/completa/artisan schedule:run
```

### **No se ven logs**

Asegúrate de que la carpeta `storage/logs` tenga permisos de escritura:

```bash
chmod -R 775 storage/logs
```

---

## 📞 Soporte

Si tienes problemas con la configuración:

1. Revisa los logs: `storage/logs/laravel.log`
2. Ejecuta el comando manualmente para ver errores
3. Verifica que el cron esté corriendo: `service cron status` (Linux)

---

## ✅ Checklist de Implementación

- [ ] Comando creado: `app/Console/Commands/LimpiarTurnosAntiguos.php`
- [ ] Scheduler configurado: `routes/console.php`
- [ ] Cron job agregado en el servidor
- [ ] Prueba manual ejecutada exitosamente
- [ ] Logs verificados
- [ ] Backup de base de datos realizado (recomendado)
- [ ] Esperar hasta medianoche para verificar ejecución automática

---

**¡La limpieza automática está lista para funcionar! 🎉**
