# MyPyMEs Training Platform - Guía de Instalación

## 📋 Información del Proyecto

- **Dominio:** training.onoboaits.com/mypymes
- **Base de Datos:** onoboait_training_mypymes
- **Email:** training@onoboaits.com
- **PHP:** 8.1 - 8.3

## 📁 Estructura de Archivos

```
/public_html/mypymes/
├── index.html              # Frontend React
├── config.php              # Configuración del sistema
├── api/
│   ├── auth.php           # API de autenticación
│   ├── progress.php       # API de progreso
│   └── exam.php           # API de exámenes
├── includes/
│   └── email.php          # Gestión de emails
└── .htaccess              # Configuración Apache
```

## 🚀 Paso 1: Crear la Base de Datos

1. Accede a **phpMyAdmin** en tu cPanel
2. Crea la base de datos: `onoboait_training_mypymes`
3. Crea un usuario MySQL con permisos completos
4. Ejecuta el script SQL que te proporcioné (`database.sql`)

## 📝 Paso 2: Configurar PHP

### Editar `config.php`:

```php
// Cambiar estas líneas con tus datos reales:
define('DB_USER', 'tu_usuario_mysql');
define('DB_PASS', 'tu_password_mysql');
define('SMTP_PASS', 'password_de_training@onoboaits.com');
define('JWT_SECRET', 'genera_una_clave_segura_aqui');
```

### Generar JWT_SECRET:
En terminal ejecuta:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

O usa un generador online: https://randomkeygen.com/

## 📂 Paso 3: Subir Archivos al Servidor

### Opción A: Via FTP (FileZilla)
1. Conecta a tu servidor FTP
2. Navega a `/public_html/mypymes/`
3. Sube todos los archivos PHP
4. Asegúrate de mantener la estructura de carpetas

### Opción B: Via cPanel File Manager
1. Accede a cPanel → File Manager
2. Navega a `public_html`
3. Crea la carpeta `mypymes`
4. Sube los archivos

## 🔒 Paso 4: Configurar Permisos

```bash
# Conecta via SSH o usa Terminal en cPanel
cd /home/tu_usuario/public_html/mypymes/
chmod 755 api/
chmod 755 includes/
chmod 644 config.php
chmod 644 api/*.php
chmod 644 includes/*.php
```

## 📧 Paso 5: Configurar Email (training@onoboaits.com)

### Opción A: Email de cPanel
1. Ve a cPanel → Email Accounts
2. Crea/verifica la cuenta `training@onoboaits.com`
3. Anota el servidor SMTP (generalmente `mail.onoboaits.com`)
4. Usa puerto 587 para TLS o 465 para SSL

### Actualizar en `config.php`:
```php
define('SMTP_HOST', 'mail.onoboaits.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'training@onoboaits.com');
define('SMTP_PASS', 'password_del_email');
define('SMTP_ENCRYPTION', 'tls');
```

### Opción B: Gmail SMTP (Alternativa)
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'tu_email@gmail.com');
define('SMTP_PASS', 'app_password'); // Usar App Password de Gmail
define('SMTP_ENCRYPTION', 'tls');
```

## 🌐 Paso 6: Configurar .htaccess

Crea un archivo `.htaccess` en `/mypymes/`:

```apache
# Habilitar mod_rewrite
RewriteEngine On
RewriteBase /mypymes/

# Redirigir a HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Proteger archivos de configuración
<FilesMatch "^(config\.php)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Permitir acceso a API
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1 [L]

# Página principal
DirectoryIndex index.html

# Comprimir archivos
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Cache control
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/html "access plus 1 hour"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

## 🔧 Paso 7: Actualizar el Frontend (index.html)

El archivo `index.html` necesita conectarse con tu API. Busca en el código React y actualiza:

```javascript
// Cambiar la URL base de la API
const API_BASE = 'https://training.onoboaits.com/mypymes/api/';

// Ejemplo de llamada de login:
const response = await fetch(`${API_BASE}auth.php?action=login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ email, password })
});
```

## ✅ Paso 8: Probar la Instalación

### 1. Verificar Base de Datos:
```
https://training.onoboaits.com:2083
→ phpMyAdmin
→ Verificar que las tablas existen
```

### 2. Probar APIs:
```bash
# Test de conexión (usa Postman o curl)
curl https://training.onoboaits.com/mypymes/api/auth.php?action=verify
```

### 3. Probar Login:
```
Usuario: demo@mypymes.com
Contraseña: demo123
```

### 4. Verificar Email:
- Registra un usuario nuevo
- Completa un módulo
- Verifica que llegue el email de certificado

## 🐛 Solución de Problemas

### Error: "Database Connection Error"
```bash
# Verificar credenciales en config.php
# Verificar que el usuario MySQL tiene permisos
# Verificar que la base de datos existe
```

### Error: "No autenticado"
```bash
# Verificar que las sesiones PHP están habilitadas
# Verificar permisos de carpeta /tmp
# Verificar que CORS está configurado correctamente
```

### Emails no se envían
```bash
# Verificar credenciales SMTP en config.php
# Verificar que el puerto SMTP no está bloqueado
# Revisar logs: /var/log/mail.log
# Probar con: php -r "mail('test@email.com', 'Test', 'Test');"
```

### Error 500
```bash
# Activar debug mode en config.php:
define('DEBUG_MODE', true);

# Ver logs de PHP:
tail -f /var/log/php_errors.log
```

## 📊 Monitoreo y Mantenimiento

### Logs a Revisar:
- `/var/log/php_errors.log` - Errores de PHP
- `/var/log/apache2/error.log` - Errores de Apache
- `/var/log/mail.log` - Logs de email

### Backup Automático:
```bash
# Crear backup de DB (ejecutar vía cron diario)
mysqldump -u usuario -p onoboait_training_mypymes > backup_$(date +%Y%m%d).sql
```

### Optimización:
```sql
-- Ejecutar mensualmente en phpMyAdmin
OPTIMIZE TABLE users;
OPTIMIZE TABLE user_progress;
OPTIMIZE TABLE content_ratings;
OPTIMIZE TABLE certificates;
```

## 🔐 Seguridad

### Checklist de Seguridad:
- [ ] Cambiar JWT_SECRET por una clave única
- [ ] Usar HTTPS (Let's Encrypt gratuito en cPanel)
- [ ] Actualizar contraseñas de MySQL
- [ ] Habilitar firewall en el servidor
- [ ] Configurar límites de rate limiting
- [ ] Actualizar PHP a la última versión
- [ ] Hacer backups regulares

## 📱 Probar en Móvil

```
# Accede desde tu celular:
https://training.onoboaits.com/mypymes/
```

## 💡 Características Adicionales (Opcional)

### Instalar PHPMailer (Recomendado):
```bash
# Via Composer (si está disponible)
cd /home/tu_usuario/public_html/mypymes/
composer require phpmailer/phpmailer

# O descargar manual:
# https://github.com/PHPMailer/PHPMailer/releases
```

### Google Analytics:
Agrega en `index.html` antes de `</head>`:
```html
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=TU-ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'TU-ID');
</script>
```

## 📞 Soporte

- **Email:** training@onoboaits.com
- **WhatsApp:** +593 999 999 999
- **Documentación:** training.onoboaits.com/mypymes/docs

## 🎉 ¡Listo!

Tu plataforma MyPyMEs Training ahora está lista en:
**https://training.onoboaits.com/mypymes/**

---

**Desarrollado para:** MyPyMEs  
**Fecha:** Enero 2026  
**Versión:** 1.0