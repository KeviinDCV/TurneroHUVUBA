#!/usr/bin/env python3
"""
Generador de archivos de voz usando Google Text-to-Speech API
Alternativa gratuita mientras configuramos Piper TTS
"""

import os
import sys
import requests
import base64
import json
from pathlib import Path
import urllib.parse

# Configuración
BASE_DIR = Path(__file__).parent
AUDIO_DIR = BASE_DIR / 'public' / 'audio' / 'turnero' / 'voice'

# Configuración de Google TTS (sin API key - usando endpoint público)
GOOGLE_TTS_URL = "https://translate.google.com/translate_tts"

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

def generate_audio_google(text, output_file, lang='es-mx'):
    """Generar archivo de audio usando Google TTS"""
    try:
        # Parámetros para Google TTS
        params = {
            'ie': 'UTF-8',
            'q': text,
            'tl': lang,
            'client': 'tw-ob',
            'ttsspeed': '1.0'
        }
        
        # Headers para simular navegador
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        }
        
        # Hacer petición
        response = requests.get(GOOGLE_TTS_URL, params=params, headers=headers, timeout=10)
        
        if response.status_code == 200:
            # Guardar archivo de audio
            with open(output_file, 'wb') as f:
                f.write(response.content)
            print(f"✓ {output_file.name}")
            return True
        else:
            print(f"❌ Error {response.status_code} para {output_file.name}")
            return False
            
    except Exception as e:
        print(f"❌ Error generando {output_file.name}: {e}")
        return False

def generate_basic_files():
    """Generar archivos básicos de audio"""
    print("\n🎙️  Generando archivos básicos con Google TTS...")
    
    # Frases básicas
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
    print("\n💬 Frases:")
    for filename, text in phrases.items():
        output_file = phrases_dir / filename
        generate_audio_google(text, output_file)
    
    # Letras A-Z
    letters_dir = AUDIO_DIR / 'letras'
    print("\n🔤 Letras:")
    for i in range(26):
        letter = chr(ord('A') + i)
        output_file = letters_dir / f'{letter}.mp3'
        generate_audio_google(letter, output_file)
    
    # Números 1-100
    numbers_dir = AUDIO_DIR / 'numeros'
    print("\n🔢 Números (1-100):")
    for i in range(1, 101):
        output_file = numbers_dir / f'{i}.mp3'
        generate_audio_google(str(i), output_file)
        if i <= 20 or i % 10 == 0:  # Mostrar solo algunos para no saturar
            pass  # Ya se muestra en generate_audio_google
    
    # Frase de prueba completa
    test_file = AUDIO_DIR / 'test_complete.mp3'
    print("\n🧪 Prueba completa:")
    generate_audio_google("Turno A 123, por favor diríjase a la caja número 5", test_file)

def generate_extended_numbers():
    """Generar números adicionales hasta 999"""
    print("\n🔢 Generando números extendidos (101-999)...")
    numbers_dir = AUDIO_DIR / 'numeros'
    
    # Números comunes en sistemas de turnos
    important_numbers = list(range(101, 201)) + list(range(200, 1000, 50))
    
    for i, number in enumerate(important_numbers):
        output_file = numbers_dir / f'{number}.mp3'
        if not output_file.exists():
            generate_audio_google(str(number), output_file)
            if i % 20 == 0:
                print(f"  Progreso: {i+1}/{len(important_numbers)} números")

def test_google_tts():
    """Probar Google TTS"""
    print("🧪 Probando Google Text-to-Speech...")
    test_file = AUDIO_DIR / 'test_google.mp3'
    
    if generate_audio_google("Hola, esta es una prueba de Google Text to Speech", test_file):
        print(f"✓ Prueba exitosa: {test_file}")
        return True
    else:
        print("❌ Google TTS no está funcionando")
        return False

def main():
    """Función principal"""
    print("🎙️  Generador de Voz con Google TTS - Turnero HUV")
    print("=" * 55)
    print("Idioma: Español (México)")
    print("Calidad: Alta (Google TTS)")
    print("=" * 55)
    
    # Crear directorios
    create_directories()
    
    # Probar Google TTS
    if not test_google_tts():
        print("\n❌ No se puede continuar sin Google TTS")
        print("Alternativas:")
        print("1. Verificar conexión a internet")
        print("2. Intentar más tarde")
        print("3. Usar Piper TTS local")
        return
    
    # Generar archivos básicos
    generate_basic_files()
    
    # Preguntar si generar números extendidos
    print("\n❓ ¿Generar números adicionales (101-999)?")
    print("   Esto puede tomar varios minutos...")
    response = input("   (s/N): ").lower().strip()
    
    if response in ['s', 'si', 'sí', 'y', 'yes']:
        generate_extended_numbers()
    
    print("\n🎉 ¡Generación completada!")
    print(f"📁 Archivos de voz en: {AUDIO_DIR}")
    
    # Mostrar estadísticas
    frases_count = len(list((AUDIO_DIR / 'frases').glob('*.mp3')))
    letras_count = len(list((AUDIO_DIR / 'letras').glob('*.mp3')))
    numeros_count = len(list((AUDIO_DIR / 'numeros').glob('*.mp3')))
    
    print("\n📊 Resumen:")
    print(f"   • Frases: {frases_count} archivos")
    print(f"   • Letras: {letras_count} archivos")
    print(f"   • Números: {numeros_count} archivos")
    print(f"   • Total: {frases_count + letras_count + numeros_count} archivos")
    
    print("\n📋 Próximos pasos:")
    print("1. Actualizar VoiceService para usar archivos .mp3")
    print("2. Probar el sistema en la vista de TV")
    print("3. Ajustar volumen si es necesario")

if __name__ == "__main__":
    main()
