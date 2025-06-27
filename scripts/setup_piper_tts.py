#!/usr/bin/env python3
"""
Script para instalar y configurar Piper TTS con voz claude[high] en español mexicano
"""

import os
import sys
import urllib.request
import zipfile
import subprocess
from pathlib import Path
import shutil

# Configuración
BASE_DIR = Path(__file__).parent.parent
TOOLS_DIR = BASE_DIR / 'tools' / 'piper'
MODELS_DIR = TOOLS_DIR / 'models'
AUDIO_DIR = BASE_DIR / 'public' / 'audio' / 'turnero' / 'voice'

# URLs de descarga
PIPER_URLS = {
    'windows': 'https://github.com/rhasspy/piper/releases/download/v1.2.0/piper_windows_amd64.tar.gz',
    'model': 'https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/es/es_MX/claude/high/es_MX-claude-high.onnx',
    'model_config': 'https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/es/es_MX/claude/high/es_MX-claude-high.onnx.json'
}

def create_directories():
    """Crear directorios necesarios"""
    directories = [
        TOOLS_DIR,
        MODELS_DIR,
        AUDIO_DIR / 'numeros',
        AUDIO_DIR / 'letras',
        AUDIO_DIR / 'frases'
    ]
    
    for directory in directories:
        directory.mkdir(parents=True, exist_ok=True)
        print(f"✓ Directorio: {directory}")

def download_file(url, destination, description="archivo"):
    """Descargar archivo con barra de progreso"""
    print(f"📥 Descargando {description}...")
    print(f"    URL: {url}")
    
    def progress_hook(block_num, block_size, total_size):
        if total_size > 0:
            downloaded = block_num * block_size
            percent = min(100, (downloaded * 100) // total_size)
            bar_length = 30
            filled_length = (percent * bar_length) // 100
            bar = '█' * filled_length + '-' * (bar_length - filled_length)
            print(f'\r    [{bar}] {percent}%', end='', flush=True)
    
    try:
        urllib.request.urlretrieve(url, destination, progress_hook)
        print(f"\n✓ Descargado: {destination}")
        return True
    except Exception as e:
        print(f"\n❌ Error descargando {description}: {e}")
        return False

def extract_piper():
    """Extraer Piper TTS"""
    piper_archive = TOOLS_DIR / 'piper_windows_amd64.tar.gz'
    
    if not piper_archive.exists():
        if not download_file(PIPER_URLS['windows'], piper_archive, "Piper TTS"):
            return False
    
    print("\n📦 Extrayendo Piper TTS...")
    try:
        import tarfile
        with tarfile.open(piper_archive, 'r:gz') as tar:
            tar.extractall(TOOLS_DIR)
        
        # Mover archivos al directorio correcto
        extracted_dir = TOOLS_DIR / 'piper'
        if extracted_dir.exists():
            for item in extracted_dir.iterdir():
                shutil.move(str(item), str(TOOLS_DIR))
            extracted_dir.rmdir()
        
        print("✓ Piper TTS extraído correctamente")
        return True
    except Exception as e:
        print(f"❌ Error extrayendo Piper TTS: {e}")
        return False

def download_voice_model():
    """Descargar modelo de voz claude[high] en español mexicano"""
    model_file = MODELS_DIR / 'es_MX-claude-high.onnx'
    config_file = MODELS_DIR / 'es_MX-claude-high.onnx.json'
    
    success = True
    
    if not model_file.exists():
        success &= download_file(PIPER_URLS['model'], model_file, "modelo de voz claude[high]")
    
    if not config_file.exists():
        success &= download_file(PIPER_URLS['model_config'], config_file, "configuración del modelo")
    
    return success

def test_piper_installation():
    """Probar la instalación de Piper TTS"""
    piper_exe = TOOLS_DIR / 'piper.exe'
    model_file = MODELS_DIR / 'es_MX-claude-high.onnx'
    
    if not piper_exe.exists():
        print("❌ piper.exe no encontrado")
        return False
    
    if not model_file.exists():
        print("❌ Modelo de voz no encontrado")
        return False
    
    print("\n🧪 Probando Piper TTS...")
    try:
        # Crear archivo de prueba
        test_file = AUDIO_DIR / 'test_piper.wav'
        test_text = "Hola, soy la voz de Claude para el sistema de turnos del hospital."
        
        # Comando de prueba
        cmd = [
            str(piper_exe),
            '--model', str(model_file),
            '--output_file', str(test_file)
        ]
        
        # Ejecutar Piper TTS
        process = subprocess.Popen(
            cmd,
            stdin=subprocess.PIPE,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )
        
        stdout, stderr = process.communicate(input=test_text)
        
        if process.returncode == 0 and test_file.exists():
            print(f"✓ Prueba exitosa! Archivo generado: {test_file}")
            print(f"  Tamaño: {test_file.stat().st_size} bytes")
            return True
        else:
            print(f"❌ Error en la prueba:")
            print(f"  Return code: {process.returncode}")
            print(f"  Stderr: {stderr}")
            return False
            
    except Exception as e:
        print(f"❌ Error probando Piper TTS: {e}")
        return False

def generate_basic_files():
    """Generar archivos básicos de audio"""
    piper_exe = TOOLS_DIR / 'piper.exe'
    model_file = MODELS_DIR / 'es_MX-claude-high.onnx'
    
    if not piper_exe.exists() or not model_file.exists():
        print("❌ Piper TTS o modelo no disponible")
        return False
    
    print("\n🎙️  Generando archivos básicos...")
    
    # Frases básicas
    phrases = {
        'turno.wav': 'Turno',
        'dirigirse.wav': 'por favor diríjase a la',
        'caja.wav': 'caja',
        'numero.wav': 'número',
        'atencion.wav': 'Atención',
        'llamando.wav': 'llamando al turno',
        'por-favor.wav': 'por favor'
    }
    
    phrases_dir = AUDIO_DIR / 'frases'
    print("\n💬 Frases:")
    for filename, text in phrases.items():
        output_file = phrases_dir / filename
        if generate_audio_file(piper_exe, model_file, text, output_file):
            print(f"✓ {filename}")
        else:
            print(f"❌ {filename}")
    
    # Letras A-Z
    letters_dir = AUDIO_DIR / 'letras'
    print("\n🔤 Letras:")
    for i in range(26):
        letter = chr(ord('A') + i)
        output_file = letters_dir / f'{letter}.wav'
        if generate_audio_file(piper_exe, model_file, letter, output_file):
            print(f"✓ {letter}.wav")
        else:
            print(f"❌ {letter}.wav")
    
    # Números 1-100
    numbers_dir = AUDIO_DIR / 'numeros'
    print("\n🔢 Números (1-100):")
    for i in range(1, 101):
        output_file = numbers_dir / f'{i}.wav'
        if generate_audio_file(piper_exe, model_file, str(i), output_file):
            if i <= 20 or i % 10 == 0:  # Mostrar solo algunos para no saturar
                print(f"✓ {i}.wav")
        else:
            print(f"❌ {i}.wav")
    
    # Frase de prueba completa
    test_file = AUDIO_DIR / 'test_complete.wav'
    print("\n🧪 Prueba completa:")
    if generate_audio_file(piper_exe, model_file, "Turno A 123, por favor diríjase a la caja número 5", test_file):
        print("✓ test_complete.wav")
    else:
        print("❌ test_complete.wav")

def generate_audio_file(piper_exe, model_file, text, output_file):
    """Generar archivo de audio individual"""
    try:
        cmd = [
            str(piper_exe),
            '--model', str(model_file),
            '--output_file', str(output_file)
        ]
        
        process = subprocess.Popen(
            cmd,
            stdin=subprocess.PIPE,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )
        
        stdout, stderr = process.communicate(input=text)
        
        return process.returncode == 0 and output_file.exists()
        
    except Exception as e:
        return False

def main():
    """Función principal"""
    print("🎙️  Configurador de Piper TTS - Turnero HUV")
    print("=" * 50)
    print("Voz: claude[high] - Español Mexicano")
    print("=" * 50)
    
    # Crear directorios
    create_directories()
    
    # Descargar e instalar Piper TTS
    if not extract_piper():
        print("\n❌ No se pudo instalar Piper TTS")
        return
    
    # Descargar modelo de voz
    if not download_voice_model():
        print("\n❌ No se pudo descargar el modelo de voz")
        return
    
    # Probar instalación
    if not test_piper_installation():
        print("\n❌ La instalación no funciona correctamente")
        return
    
    # Generar archivos básicos
    generate_basic_files()
    
    print("\n🎉 ¡Configuración completada!")
    print(f"📁 Archivos de voz en: {AUDIO_DIR}")
    print("\n📋 Próximos pasos:")
    print("1. Prueba el sistema en la vista de TV")
    print("2. Ajusta la configuración si es necesario")
    print("3. Genera más números si los necesitas")

if __name__ == "__main__":
    main()
