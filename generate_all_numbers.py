#!/usr/bin/env python3
"""
Generar TODOS los archivos de audio de números faltantes (1-999)
Usa Google TTS con la misma voz que los archivos existentes (es-mx)
"""

import urllib.request
import urllib.parse
from pathlib import Path
import time
import sys

# Configuración
BASE_DIR = Path(__file__).parent
NUMBERS_DIR = BASE_DIR / 'public' / 'audio' / 'turnero' / 'voice' / 'numeros'

def generate_audio(text, output_file):
    """Generar archivo de audio usando Google TTS (misma config que archivos existentes)"""
    try:
        base_url = "https://translate.google.com/translate_tts"
        
        params = {
            'ie': 'UTF-8',
            'q': text,
            'tl': 'es-mx',
            'client': 'tw-ob',
            'ttsspeed': '0.9',
            'total': '1',
            'idx': '0'
        }
        
        url = base_url + '?' + urllib.parse.urlencode(params)
        
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'audio/mpeg, audio/*, */*',
            'Accept-Language': 'es-MX,es;q=0.9,en;q=0.8',
            'Referer': 'https://translate.google.com/'
        }
        
        req = urllib.request.Request(url, headers=headers)
        
        with urllib.request.urlopen(req, timeout=15) as response:
            if response.status == 200:
                data = response.read()
                if len(data) > 100:  # Verificar que no sea un archivo vacío
                    with open(output_file, 'wb') as f:
                        f.write(data)
                    return True
                else:
                    return False
            return False
                
    except Exception as e:
        print(f"  ❌ Error: {e}")
        return False

def main():
    """Generar todos los números faltantes del 1 al 999"""
    print("🔢 Generador de archivos de audio para números 1-999")
    print("=" * 55)
    print(f"📁 Directorio: {NUMBERS_DIR}")
    print("🎙️  Motor: Google TTS (es-mx) - misma voz que archivos existentes")
    print("=" * 55)
    
    # Asegurar que el directorio existe
    NUMBERS_DIR.mkdir(parents=True, exist_ok=True)
    
    # Detectar archivos existentes
    existing = set()
    for f in NUMBERS_DIR.glob('*.mp3'):
        try:
            num = int(f.stem)
            existing.add(num)
        except ValueError:
            pass
    
    print(f"\n📊 Archivos existentes: {len(existing)}")
    
    # Determinar faltantes (1-999)
    all_numbers = set(range(1, 1000))
    missing = sorted(all_numbers - existing)
    
    print(f"📊 Archivos faltantes: {len(missing)}")
    
    if not missing:
        print("\n✅ ¡Todos los números del 1 al 999 ya tienen audio!")
        return
    
    print(f"\n🔄 Generando {len(missing)} archivos faltantes...")
    print(f"   Rango: {missing[0]} - {missing[-1]}")
    print(f"   Tiempo estimado: ~{len(missing) * 0.6:.0f} segundos\n")
    
    success = 0
    errors = 0
    error_numbers = []
    
    for i, number in enumerate(missing):
        output_file = NUMBERS_DIR / f'{number}.mp3'
        
        # Mostrar progreso cada 20 números o en el primero/último
        if i % 20 == 0 or i == len(missing) - 1:
            progress = (i + 1) / len(missing) * 100
            print(f"  [{progress:5.1f}%] Generando {number}... ({i+1}/{len(missing)})")
        
        if generate_audio(str(number), output_file):
            success += 1
        else:
            errors += 1
            error_numbers.append(number)
            print(f"  ❌ Error en número {number}")
        
        # Pausa para no saturar la API de Google
        time.sleep(0.4)
    
    print(f"\n{'=' * 55}")
    print(f"📊 Resultados:")
    print(f"   ✅ Generados exitosamente: {success}")
    print(f"   ❌ Errores: {errors}")
    
    if error_numbers:
        print(f"   ⚠️  Números con error: {error_numbers}")
        print(f"\n   Puedes volver a ejecutar el script para reintentar los faltantes.")
    
    # Verificar total
    total_now = len(list(NUMBERS_DIR.glob('*.mp3')))
    print(f"\n📁 Total de archivos de números: {total_now}/999")
    
    if total_now >= 999:
        print("🎉 ¡Todos los números del 1 al 999 tienen audio!")
    else:
        print(f"⚠️  Faltan {999 - total_now} archivos. Ejecuta el script de nuevo.")

if __name__ == "__main__":
    main()
