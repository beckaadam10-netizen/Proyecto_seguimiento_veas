# Sistema de Gestión — Estudio Jurídico
## Guía de instalación (Laravel 11 + MySQL)

---

### Requisitos
- PHP >= 8.2
- Composer
- MySQL 8+
- Node.js (para assets opcionales)

---

### 1. Crear proyecto Laravel

```bash
composer create-project laravel/laravel estudio-juridico
cd estudio-juridico
```

### 2. Instalar autenticación (Breeze recomendado)

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run build
```

### 3. Configurar la base de datos

Editar `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=estudio_juridico
DB_USERNAME=root
DB_PASSWORD=tu_password
```

Crear la base de datos en MySQL:

```sql
CREATE DATABASE estudio_juridico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

### 4. Copiar los archivos del sistema

Copiar los archivos de este scaffold al proyecto Laravel:

```
database/migrations/  → copiar los 4 archivos de migración
app/Models/           → Cliente.php, Expediente.php, Seguimiento.php, Audiencia.php
app/Http/Controllers/ → ClienteController.php, ExpedienteController.php,
                        SeguimientoController.php, AudienciaController.php
resources/views/      → carpetas: layouts/, clientes/, expedientes/,
                        seguimientos/, audiencias/
routes/web.php        → reemplazar (conservar el require auth.php al final)
```

---

### 5. Ejecutar migraciones

```bash
php artisan migrate
```

### 6. Configurar almacenamiento de archivos adjuntos

```bash
php artisan storage:link
```

---

### 7. Levantar el servidor

```bash
php artisan serve
```

Acceder en: **http://localhost:8000**

---

## Módulos del sistema

| Módulo | Ruta | Descripción |
|--------|------|-------------|
| Clientes | `/clientes` | Personas físicas y jurídicas |
| Expedientes | `/expedientes` | Casos con estado, tipo, juzgado |
| Seguimientos | `/seguimientos` | Actuaciones y vencimientos |
| Audiencias | `/audiencias` | Agenda judicial |

---

## Estructura de base de datos

```
clientes
  └── expedientes (N)
        ├── seguimientos (N)   ← actuaciones, escritos, resoluciones
        └── audiencias (N)     ← agenda de audiencias judiciales
```

---

## Personalización recomendada

- **Agregar módulo de Honorarios**: tabla `honorarios` vinculada a `expedientes`
- **Notificaciones por email**: usar `php artisan make:notification VencimientoProximo`
- **Roles y permisos**: instalar `spatie/laravel-permission`
- **Dashboard con gráficos**: agregar Chart.js en el layout

---

## Comandos útiles

```bash
# Crear un usuario administrador
php artisan tinker
> \App\Models\User::create(['name'=>'Admin','email'=>'admin@estudio.com','password'=>bcrypt('password')]);

# Ver todas las rutas
php artisan route:list

# Limpiar caché
php artisan optimize:clear
```
