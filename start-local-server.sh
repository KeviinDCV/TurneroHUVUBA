#!/bin/bash

echo "========================================"
echo "  Turnero HUV - Servidor de Red Local"
echo "========================================"
echo ""

echo "Configurando entorno local..."
php artisan env:switch local

if [ $? -ne 0 ]; then
    echo "✗ Error al configurar entorno local"
    read -p "Presiona Enter para continuar..."
    exit 1
fi

echo ""
echo "Limpiando cache..."
php artisan config:clear
php artisan cache:clear

echo ""
echo "========================================"
echo "  Servidor iniciado en red local"
echo "========================================"
echo ""
echo "Accede desde cualquier equipo en:"
echo "  http://192.168.2.202:3000"
echo ""
echo "Para detener el servidor presiona Ctrl+C"
echo "========================================"
echo ""

php artisan serve --host=0.0.0.0 --port=3000
