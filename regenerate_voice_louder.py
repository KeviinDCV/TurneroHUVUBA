#!/usr/bin/env python3
"""
Regenerar archivos de voz con mayor volumen
"""

import urllib.request
import urllib.parse
from pathlib import Path
import time

# Configuración
BASE_DIR = Path(__file__).parent
AUDIO_DIR = BASE_DIR / 'public' / 'audio' / 'turnero' / 'voice'

def generate_audio_google_loud(text, output_file):
    """Generar archivo de audio usando Google TTS con configuración para mayor volumen"""
    try:
        # URL de Google TTS con parámetros optimizados para volumen
        base_url = "https://translate.google.com/translate_tts"
        
        # Parámetros optimizados
        params = {
            'ie': 'UTF-8',
            'q': text,
            'tl': 'es-mx',
            'client': 'tw-ob',
            'ttsspeed': '0.9',  # Ligeramente más lento para mayor claridad
            'total': '1',
            'idx': '0'
        }
        
        # Construir URL
        url = base_url + '?' + urllib.parse.urlencode(params)
        
        # Headers mejorados
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'audio/mpeg, audio/*, */*',
            'Accept-Language': 'es-MX,es;q=0.9,en;q=0.8',
            'Referer': 'https://translate.google.com/'
        }
        
        # Crear request
        req = urllib.request.Request(url, headers=headers)
        
        # Descargar
        with urllib.request.urlopen(req, timeout=15) as response:
            if response.status == 200:
                with open(output_file, 'wb') as f:
                    f.write(response.read())
                return True
            else:
                return False
                
    except Exception as e:
        print(f"Error: {e}")
        return False

def main():
    """Función principal"""
    print("🔊 Regenerando archivos de voz con mayor volumen...")
    print("=" * 50)
    
    # Regenerar frases básicas
    print("\n💬 Regenerando frases básicas...")
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
        print(f"Regenerando {filename}...")
        if generate_audio_google_loud(text, output_file):
            print(f"✓ {filename}")
        else:
            print(f"❌ {filename}")
        time.sleep(0.5)  # Pausa para no saturar la API
    
    # Regenerar letras más comunes
    print("\n🔤 Regenerando letras comunes...")
    common_letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']
    letters_dir = AUDIO_DIR / 'letras'
    
    for letter in common_letters:
        output_file = letters_dir / f'{letter}.mp3'
        print(f"Regenerando {letter}.mp3...")
        if generate_audio_google_loud(letter, output_file):
            print(f"✓ {letter}.mp3")
        else:
            print(f"❌ {letter}.mp3")
        time.sleep(0.3)
    
    # Regenerar números más comunes
    print("\n🔢 Regenerando números comunes...")
    common_numbers = list(range(1, 21)) + [25, 30, 35, 40, 45, 50, 100, 200, 300, 400, 500]
    numbers_dir = AUDIO_DIR / 'numeros'
    
    for number in common_numbers:
        output_file = numbers_dir / f'{number}.mp3'
        print(f"Regenerando {number}.mp3...")
        if generate_audio_google_loud(str(number), output_file):
            print(f"✓ {number}.mp3")
        else:
            print(f"❌ {number}.mp3")
        time.sleep(0.2)
    
    # Regenerar frase de prueba
    print("\n🧪 Regenerando prueba completa...")
    test_complete = AUDIO_DIR / 'test_complete_loud.mp3'
    if generate_audio_google_loud("Turno A 123, por favor diríjase a la caja número 5", test_complete):
        print("✓ test_complete_loud.mp3")
    else:
        print("❌ test_complete_loud.mp3")
    
    print("\n🎉 Regeneración completada!")
    print("Los archivos ahora deberían tener mejor calidad y volumen.")
    print("\n📋 Próximos pasos:")
    print("1. Probar el sistema en la vista de TV")
    print("2. Verificar que el volumen sea adecuado")
    print("3. Ajustar configuración si es necesario")

if __name__ == "__main__":
    main()
