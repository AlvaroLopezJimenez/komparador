#!/bin/bash

# Cambiar al directorio del proyecto
cd "/Users/coque/MEGA/Web/Dominios/chollopañales.com/laravel"

# Verificar si Docker está corriendo
if ! docker info > /dev/null 2>&1; then
    echo "⚠️  Docker no está corriendo. Iniciando Docker Desktop..."
    open -a Docker
    echo "⏳ Esperando 15 segundos a que Docker inicie completamente..."
    sleep 15
    
    # Verificar de nuevo
    if ! docker info > /dev/null 2>&1; then
        echo "❌ Docker no se pudo iniciar. Por favor, inicia Docker Desktop manualmente."
        exit 1
    fi
fi

echo "🐳 Docker está corriendo"
echo "📦 Construyendo e iniciando contenedores..."

# Construir e iniciar los servicios
docker-compose up -d --build

# Esperar un momento para que los servicios inicien
sleep 5

echo ""
echo "✅ Servicios iniciados correctamente!"
echo ""
echo "🌐 URLs disponibles:"
echo "   - Laravel: http://localhost:8000"
echo "   - Vite (frontend): http://localhost:5173"
echo ""
echo "📋 Comandos útiles:"
echo "   - Ver logs: docker-compose logs -f"
echo "   - Detener servicios: docker-compose down"
echo "   - Reiniciar: docker-compose restart"
echo ""
echo "💡 Para instalar dependencias de Composer (si es necesario):"
echo "   docker-compose exec app composer install"
echo ""
echo "💡 Para instalar dependencias de npm (si es necesario):"
echo "   docker-compose exec node npm install"
