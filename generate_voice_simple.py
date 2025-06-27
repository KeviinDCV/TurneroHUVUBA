#!/usr/bin/env python3
"""
Generador simple de archivos de voz usando urllib (sin dependencias externas)
"""

import os
import urllib.request
import urllib.parse
from pathlib import Path

# Configuración
BASE_DIR = Path(__file__).parent
AUDIO_DIR = BASE_DIR / 'public' / 'audio' / 'turnero' / 'voice'

def create_directories():
    """Crear directorios necesarios"""
    directories = [
        AUDIO_DIR / 'numeros',
        AUDIO_DIR / 'letras',
        AUDIO_DIR / 'frases'
    ]
    
    for directory in directories:
        directory.mkdir(parents=True, exist_ok=True)
        print(f"✓ Directorio: {directory}")

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
                print(f"✓ {output_file.name}")
                return True
            else:
                print(f"❌ Error {response.status} para {output_file.name}")
                return False
                
    except Exception as e:
        print(f"❌ Error generando {output_file.name}: {e}")
        return False

def main():
    """Función principal"""
    print("🎙️  Generador de Voz Simple - Turnero HUV")
    print("=" * 45)
    
    # Crear directorios
    create_directories()
    
    # Probar con una frase simple
    print("\n🧪 Probando Google TTS...")
    test_file = AUDIO_DIR / 'test.mp3'
    if generate_audio_google("Hola mundo", test_file):
        print("✓ Google TTS funciona correctamente")
    else:
        print("❌ Google TTS no está funcionando")
        return
    
    # Generar frases básicas
    print("\n💬 Generando frases básicas...")
    phrases = {
        'turno.mp3': 'Turno',
        'dirigirse.mp3': 'por favor diríjase a la',
        'caja.mp3': 'caja',
        'numero.mp3': 'número',
        'atencion.mp3': 'Atención',
        'llamando.mp3': 'llamando al turno',
        'por-favor.mp3': 'por favor'
    }
    
    phrases_dir = AUDIO_DIR / 'frases'
    for filename, text in phrases.items():
        output_file = phrases_dir / filename
        generate_audio_google(text, output_file)
    
    # Generar letras A-Z
    print("\n🔤 Generando letras...")
    letters_dir = AUDIO_DIR / 'letras'
    for i in range(26):
        letter = chr(ord('A') + i)
        output_file = letters_dir / f'{letter}.mp3'
        generate_audio_google(letter, output_file)
        if i < 5:  # Mostrar solo las primeras 5
            pass  # Ya se muestra en generate_audio_google
    print("✓ Letras A-Z completadas")
    
    # Generar números 1-50
    print("\n🔢 Generando números (1-50)...")
    numbers_dir = AUDIO_DIR / 'numeros'
    for i in range(1, 51):
        output_file = numbers_dir / f'{i}.mp3'
        generate_audio_google(str(i), output_file)
        if i <= 10 or i % 10 == 0:  # Mostrar algunos
            pass  # Ya se muestra en generate_audio_google
    
    # Frase de prueba completa
    print("\n🧪 Generando prueba completa...")
    test_complete = AUDIO_DIR / 'test_complete.mp3'
    generate_audio_google("Turno A 123, por favor diríjase a la caja número 5", test_complete)
    
    print("\n🎉 ¡Generación básica completada!")
    print(f"📁 Archivos en: {AUDIO_DIR}")
    
    # Mostrar estadísticas
    try:
        frases_count = len(list((AUDIO_DIR / 'frases').glob('*.mp3')))
        letras_count = len(list((AUDIO_DIR / 'letras').glob('*.mp3')))
        numeros_count = len(list((AUDIO_DIR / 'numeros').glob('*.mp3')))
        
        print(f"\n📊 Archivos generados:")
        print(f"   • Frases: {frases_count}")
        print(f"   • Letras: {letras_count}")
        print(f"   • Números: {numeros_count}")
        print(f"   • Total: {frases_count + letras_count + numeros_count}")
    except:
        print("📊 Archivos generados (conteo no disponible)")
    
    print("\n📋 Próximos pasos:")
    print("1. Probar el sistema en la vista de TV")
    print("2. Generar más números si es necesario")
    print("3. Ajustar configuración si es necesario")

if __name__ == "__main__":
    main()
