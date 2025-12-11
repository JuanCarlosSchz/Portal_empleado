#!/bin/bash

echo "🚀 Iniciando Portal del Empleado con Docker..."

# Construir los contenedores
echo "📦 Construyendo contenedores..."
docker-compose build

# Iniciar los contenedores
echo "🔄 Iniciando contenedores..."
docker-compose up -d

# Esperar a que los contenedores estén listos
echo "⏳ Esperando a que los contenedores estén listos..."
sleep 5

# Instalar dependencias
echo "📥 Instalando dependencias de Composer..."
docker-compose exec app composer install

# Generar permisos
echo "🔐 Configurando permisos..."
docker-compose exec app chmod -R 775 storage bootstrap/cache

# Ejecutar migraciones
echo "🗄️  Ejecutando migraciones..."
docker-compose exec app php artisan migrate --force

# Ejecutar seeders
echo "🌱 Creando usuario administrador..."
docker-compose exec app php artisan db:seed --force

# Crear enlace simbólico de storage
echo "🔗 Creando enlace simbólico de storage..."
docker-compose exec app php artisan storage:link

# Limpiar caché
echo "🧹 Limpiando caché..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear

echo ""
echo "✅ ¡Portal del Empleado está listo!"
echo "🌐 Accede a: http://localhost:8000"
echo "📧 Usuario admin: super@portalempleado.com"
echo "🔑 Contraseña: cesurfp"
echo ""
echo "Comandos útiles:"
echo "  docker-compose up -d          # Iniciar contenedores"
echo "  docker-compose down           # Detener contenedores"
echo "  docker-compose logs -f        # Ver logs"
echo "  docker-compose exec app bash  # Acceder al contenedor"
echo ""
