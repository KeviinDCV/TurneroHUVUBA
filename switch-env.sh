#!/bin/bash

echo "========================================"
echo "  Turnero HUV - Cambio de Configuracion"
echo "========================================"
echo ""
echo "Selecciona el entorno:"
echo "1. Local (desarrollo en red local)"
echo "2. Produccion (servidor web)"
echo ""
read -p "Ingresa tu opcion (1 o 2): " choice

case $choice in
    1)
        echo ""
        echo "Configurando para desarrollo local..."
        if cp .env.local .env 2>/dev/null; then
            echo "✓ Configuracion local aplicada"
            echo "✓ APP_URL: http://192.168.2.202:3000"
            echo "✓ SESSION_SECURE_COOKIE: false"
            echo "✓ APP_DEBUG: true"
            echo ""
            echo "Ahora puedes ejecutar:"
            echo "php artisan serve --host=0.0.0.0 --port=3000"
        else
            echo "✗ Error al aplicar configuracion local"
        fi
        ;;
    2)
        echo ""
        echo "Configurando para produccion..."
        if cp .env.production .env 2>/dev/null; then
            echo "✓ Configuracion de produccion aplicada"
            echo "✓ APP_URL: https://turnero.huv.gov.co"
            echo "✓ SESSION_SECURE_COOKIE: true"
            echo "✓ APP_DEBUG: false"
            echo ""
            echo "Recuerda actualizar las credenciales de base de datos"
        else
            echo "✗ Error al aplicar configuracion de produccion"
        fi
        ;;
    *)
        echo ""
        echo "Opcion invalida. Usa 1 o 2."
        ;;
esac

echo ""
read -p "Presiona Enter para continuar..."
