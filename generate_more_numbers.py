#!/usr/bin/env python3
"""
Generar números adicionales para el sistema de turnos
"""

import urllib.request
import urllib.parse
from pathlib import Path

# Configuración
BASE_DIR = Path(__file__).parent
AUDIO_DIR = BASE_DIR / 'public' / 'audio' / 'turnero' / 'voice' / 'numeros'

def generate_audio_google(text, output_file):
    """Generar archivo de audio usando Google TTS"""
    try:
        # URL de Google TTS
        base_url = "https://translate.google.com/translate_tts"
        
        # Parámetros
        params = {
            'ie': 'UTF-8',
            'q': text,
            'tl': 'es-mx',
            'client': 'tw-ob'
        }
        
        # Construir URL
        url = base_url + '?' + urllib.parse.urlencode(params)
        
        # Headers
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }
        
        # Crear request
        req = urllib.request.Request(url, headers=headers)
        
        # Descargar
        with urllib.request.urlopen(req, timeout=10) as response:
            if response.status == 200:
                with open(output_file, 'wb') as f:
                    f.write(response.read())
                return True
            else:
                return False
                
    except Exception as e:
        return False

def main():
    """Función principal"""
    print("🔢 Generando números adicionales para turnos...")
    print("=" * 45)
    
    # Números adicionales comunes en sistemas de turnos
    additional_numbers = list(range(51, 101)) + list(range(100, 201, 5)) + [200, 250, 300, 400, 500, 999]
    
    generated = 0
    failed = 0
    
    for i, number in enumerate(additional_numbers):
        output_file = AUDIO_DIR / f'{number}.mp3'
        
        # Solo generar si no existe
        if not output_file.exists():
            if generate_audio_google(str(number), output_file):
                generated += 1
                if number <= 100 or number % 25 == 0:
                    print(f"✓ {number}.mp3")
            else:
                failed += 1
                print(f"❌ {number}.mp3")
        
        # Mostrar progreso cada 20 archivos
        if (i + 1) % 20 == 0:
            print(f"   Progreso: {i + 1}/{len(additional_numbers)} números procesados")
    
    print(f"\n🎉 Generación completada!")
    print(f"   • Generados: {generated} archivos")
    print(f"   • Fallidos: {failed} archivos")
    print(f"   • Ya existían: {len(additional_numbers) - generated - failed} archivos")
    
    # Contar total de archivos
    total_files = len(list(AUDIO_DIR.glob('*.mp3')))
    print(f"   • Total de números disponibles: {total_files}")

if __name__ == "__main__":
    main()
