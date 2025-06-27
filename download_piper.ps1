# Script para descargar Piper TTS desde GitHub Releases
Write-Host "Descargando Piper TTS desde GitHub..." -ForegroundColor Cyan
Write-Host "=================================================="

# Configuracion
$toolsDir = "tools\piper"
$modelsDir = "$toolsDir\models"
$audioDir = "public\audio\turnero\voice"

# URLs actualizadas desde GitHub Releases
$piperUrl = "https://github.com/rhasspy/piper/releases/download/2023.11.14-2/piper_windows_amd64.tar.gz"
$modelUrl = "https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/es/es_MX/claude/high/es_MX-claude-high.onnx"
$configUrl = "https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/es/es_MX/claude/high/es_MX-claude-high.onnx.json"

# Crear directorios
Write-Host "Creando directorios..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path $toolsDir -Force | Out-Null
New-Item -ItemType Directory -Path $modelsDir -Force | Out-Null
New-Item -ItemType Directory -Path "$audioDir\frases" -Force | Out-Null
New-Item -ItemType Directory -Path "$audioDir\letras" -Force | Out-Null
New-Item -ItemType Directory -Path "$audioDir\numeros" -Force | Out-Null
Write-Host "OK Directorios creados" -ForegroundColor Green

# Verificar si ya existe
if (Test-Path "$toolsDir\piper.exe") {
    Write-Host "Piper TTS ya esta instalado" -ForegroundColor Green
} else {
    # Descargar Piper TTS
    $piperArchive = "$toolsDir\piper_windows_amd64.tar.gz"
    Write-Host "Descargando Piper TTS..." -ForegroundColor Yellow
    Write-Host "URL: $piperUrl"
    
    try {
        # Usar ProgressPreference para mostrar progreso
        $ProgressPreference = 'Continue'
        Invoke-WebRequest -Uri $piperUrl -OutFile $piperArchive -UseBasicParsing
        Write-Host "OK Piper TTS descargado ($([math]::Round((Get-Item $piperArchive).Length / 1MB, 2)) MB)" -ForegroundColor Green
        
        # Extraer usando tar nativo de Windows
        Write-Host "Extrayendo Piper TTS..." -ForegroundColor Yellow
        tar -xzf $piperArchive -C $toolsDir --strip-components=1
        
        # Verificar extraccion
        if (Test-Path "$toolsDir\piper.exe") {
            Write-Host "OK Piper TTS extraido correctamente" -ForegroundColor Green
            Remove-Item $piperArchive -Force
        } else {
            Write-Host "ERROR: piper.exe no encontrado despues de extraer" -ForegroundColor Red
            Write-Host "Contenido del directorio:" -ForegroundColor Yellow
            Get-ChildItem $toolsDir | ForEach-Object { Write-Host "  $($_.Name)" }
            exit 1
        }
    }
    catch {
        Write-Host "ERROR descargando/extrayendo Piper TTS: $_" -ForegroundColor Red
        exit 1
    }
}

# Descargar modelo de voz claude[high]
$modelFile = "$modelsDir\es_MX-claude-high.onnx"
if (Test-Path $modelFile) {
    Write-Host "Modelo de voz ya existe" -ForegroundColor Green
} else {
    Write-Host "Descargando modelo de voz claude[high]..." -ForegroundColor Yellow
    Write-Host "URL: $modelUrl"
    try {
        Invoke-WebRequest -Uri $modelUrl -OutFile $modelFile -UseBasicParsing
        Write-Host "OK Modelo descargado ($([math]::Round((Get-Item $modelFile).Length / 1MB, 2)) MB)" -ForegroundColor Green
    }
    catch {
        Write-Host "ERROR descargando modelo: $_" -ForegroundColor Red
        exit 1
    }
}

# Descargar configuracion del modelo
$configFile = "$modelsDir\es_MX-claude-high.onnx.json"
if (Test-Path $configFile) {
    Write-Host "Configuracion del modelo ya existe" -ForegroundColor Green
} else {
    Write-Host "Descargando configuracion del modelo..." -ForegroundColor Yellow
    try {
        Invoke-WebRequest -Uri $configUrl -OutFile $configFile -UseBasicParsing
        Write-Host "OK Configuracion descargada" -ForegroundColor Green
    }
    catch {
        Write-Host "ERROR descargando configuracion: $_" -ForegroundColor Red
        exit 1
    }
}

# Verificar instalacion completa
$piperExe = "$toolsDir\piper.exe"
if ((Test-Path $piperExe) -and (Test-Path $modelFile) -and (Test-Path $configFile)) {
    Write-Host ""
    Write-Host "Probando Piper TTS..." -ForegroundColor Yellow
    
    $testFile = "$audioDir\test_piper.wav"
    $testText = "Hola, soy Claude, la nueva voz del sistema de turnos del hospital."
    
    try {
        # Probar Piper TTS
        $process = Start-Process -FilePath $piperExe -ArgumentList "--model `"$modelFile`" --output_file `"$testFile`"" -NoNewWindow -PassThru -RedirectStandardInput
        $process.StandardInput.WriteLine($testText)
        $process.StandardInput.Close()
        $process.WaitForExit(10000) # Esperar max 10 segundos
        
        if (Test-Path $testFile) {
            $fileSize = (Get-Item $testFile).Length
            Write-Host "OK Prueba exitosa! Archivo: $testFile ($fileSize bytes)" -ForegroundColor Green
            
            # Reproducir archivo de prueba si es posible
            try {
                Start-Process -FilePath $testFile -Wait
            } catch {
                Write-Host "No se pudo reproducir automaticamente el archivo de prueba" -ForegroundColor Yellow
            }
        } else {
            Write-Host "ERROR: No se genero el archivo de prueba" -ForegroundColor Red
            exit 1
        }
    }
    catch {
        Write-Host "ERROR probando Piper TTS: $_" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "ERROR: Instalacion incompleta" -ForegroundColor Red
    Write-Host "  Piper.exe: $(Test-Path $piperExe)" -ForegroundColor Yellow
    Write-Host "  Modelo: $(Test-Path $modelFile)" -ForegroundColor Yellow
    Write-Host "  Config: $(Test-Path $configFile)" -ForegroundColor Yellow
    exit 1
}

Write-Host ""
Write-Host "Instalacion de Piper TTS completada!" -ForegroundColor Green
Write-Host "Archivos instalados en: $toolsDir" -ForegroundColor Cyan
Write-Host ""
Write-Host "Proximos pasos:" -ForegroundColor Cyan
Write-Host "1. Ejecutar: powershell -File generate_voice_files.ps1" -ForegroundColor White
Write-Host "2. Probar el sistema en la vista de TV" -ForegroundColor White
Write-Host "3. Ajustar configuracion si es necesario" -ForegroundColor White

# Mostrar informacion del sistema
Write-Host ""
Write-Host "Informacion del sistema:" -ForegroundColor Cyan
Write-Host "  Piper TTS: $(if (Test-Path $piperExe) { 'Instalado' } else { 'No instalado' })" -ForegroundColor White
Write-Host "  Modelo: es_MX-claude-high" -ForegroundColor White
Write-Host "  Idioma: Espanol (Mexico)" -ForegroundColor White
Write-Host "  Calidad: Alta" -ForegroundColor White
