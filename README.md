# Portal del Empleado<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>



Sistema de gestión de empleados desarrollado con Laravel 11 y Filament 3.<p align="center">

<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>

## Características<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>

<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>

### 📋 Funcionalidades Principales<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>

</p>

- **Gestión de Usuarios**: Sistema de roles (Administrador/Trabajador) con DNI

- **Fichajes**: Control de entrada/salida y pausas laborales## About Laravel

- **Documentos**: Gestión y distribución de documentos (subida por admin)

- **Solicitudes**: Sistema de peticiones (vacaciones, teletrabajo, citas médicas)Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- **Incidencias**: Reporte y resolución de problemas de fichaje

- [Simple, fast routing engine](https://laravel.com/docs/routing).

### 👥 Roles de Usuario- [Powerful dependency injection container](https://laravel.com/docs/container).

- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.

#### Administrador- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).

- Gestión completa de usuarios- Database agnostic [schema migrations](https://laravel.com/docs/migrations).

- Supervisión de fichajes- [Robust background job processing](https://laravel.com/docs/queues).

- Subida y asignación de documentos- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

- Aprobación/rechazo de solicitudes

- Resolución de incidenciasLaravel is accessible, powerful, and provides tools required for large, robust applications.



#### Trabajador## Learning Laravel

- Registro de fichajes (entrada/salida/pausas)

- Acceso a documentos asignadosLaravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

- Creación de solicitudes

- Reporte de incidenciasIf you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.



## Requisitos del Sistema## Laravel Sponsors



- PHP >= 8.2We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

- Composer

- Base de datos (SQLite/MySQL/PostgreSQL)### Premium Partners

- Node.js & NPM (para assets)

- **[Vehikl](https://vehikl.com)**

## Instalación- **[Tighten Co.](https://tighten.co)**

- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**

### 1. Clonar el repositorio o instalar dependencias- **[64 Robots](https://64robots.com)**

- **[Curotec](https://www.curotec.com/services/technologies/laravel)**

```bash- **[DevSquad](https://devsquad.com/hire-laravel-developers)**

composer install- **[Redberry](https://redberry.international/laravel-development)**

```- **[Active Logic](https://activelogic.com)**



### 2. Configurar el entorno## Contributing



```bashThank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

cp .env.example .env

php artisan key:generate## Code of Conduct

```

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

### 3. Configurar la base de datos

## Security Vulnerabilities

Edita el archivo `.env` con tus credenciales de base de datos:

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

```env

DB_CONNECTION=sqlite  # o mysql, pgsql, etc.## License

DB_DATABASE=/ruta/completa/a/database.sqlite

```The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


### 4. Ejecutar migraciones

```bash
php artisan migrate
```

### 5. Crear usuario administrador

```bash
php artisan make:filament-user
```

Sigue las instrucciones para crear tu primer usuario administrador.

### 6. Iniciar el servidor

```bash
php artisan serve
```

Accede al panel de administración en: `http://localhost:8000/admin`

## Estructura de la Base de Datos

### Tablas Principales

- **users**: Usuarios del sistema (admin/trabajador) con DNI
- **time_entries**: Registro de fichajes
- **documents**: Documentos del sistema
- **document_user**: Relación documentos-usuarios
- **requests**: Solicitudes de los trabajadores
- **incidents**: Incidencias reportadas

## Uso del Sistema

### Panel de Administración

Accede a `http://localhost:8000/admin` con tus credenciales.

#### Gestión de Usuarios
1. Navega a "Usuarios"
2. Crea nuevos usuarios especificando nombre, email, DNI y rol
3. Asigna rol de administrador o trabajador

#### Fichajes
- Visualiza todos los fichajes en tiempo real
- Filtra por usuario, tipo y fecha
- Edita o elimina registros si es necesario

#### Documentos
1. Sube documentos desde "Documentos" > "Nuevo"
2. Marca como "Visible para todos" o asigna a usuarios específicos
3. Los trabajadores verán solo sus documentos asignados

#### Solicitudes
- Revisa solicitudes pendientes
- Aprueba o rechaza con notas explicativas
- Filtra por estado, tipo o usuario

#### Incidencias
- Revisa incidencias reportadas
- Cambia estado a "En revisión"
- Resuelve o rechaza con explicación

## Tecnologías Utilizadas

- **Laravel 11**: Framework PHP
- **Filament 3**: Panel de administración
- **SQLite/MySQL**: Base de datos
- **Livewire**: Componentes reactivos
- **TailwindCSS**: Estilos

## Estructura del Proyecto

```
app/
├── Models/              # Modelos Eloquent
│   ├── User.php
│   ├── TimeEntry.php
│   ├── Document.php
│   ├── Request.php
│   └── Incident.php
├── Filament/
│   └── Resources/       # Recursos de Filament
│       ├── UserResource.php
│       ├── TimeEntryResource.php
│       ├── DocumentResource.php
│       ├── RequestResource.php
│       └── IncidentResource.php
database/
├── migrations/          # Migraciones de base de datos
```

## Seguridad

- Contraseñas hasheadas con bcrypt
- Validación de permisos por rol
- Protección CSRF
- Sanitización de inputs
- Autenticación requerida para todas las rutas

## Personalización

### Cambiar colores y tema

Edita `app/Providers/Filament/AdminPanelProvider.php` para personalizar colores, logo y configuraciones del panel.

### Agregar campos personalizados

Modifica los Resources en `app/Filament/Resources/` para añadir campos adicionales a los formularios y tablas.

## Desarrollo

### Comandos útiles

```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Crear nuevo recurso de Filament
php artisan make:filament-resource NombreModelo --generate

# Ejecutar migraciones fresh
php artisan migrate:fresh --seed
```

## Soporte

Para problemas o consultas, consulta la documentación oficial:

- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)

## Licencia

Este proyecto es de código abierto bajo la licencia MIT.

---

Desarrollado con ❤️ para la gestión eficiente de empleados
