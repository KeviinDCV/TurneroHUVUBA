# Script simple para instalar Piper TTS
Write-Host "Configurando Piper TTS - Turnero HUV" -ForegroundColor Cyan
Write-Host "Voz: claude[high] - Espanol Mexicano"
Write-Host "=================================================="

# Configuracion
$toolsDir = "tools\piper"
$modelsDir = "$toolsDir\models"
$audioDir = "public\audio\turnero\voice"

# URLs de descarga
$piperUrl = "https://github.com/rhasspy/piper/releases/download/v1.2.0/piper_windows_amd64.tar.gz"
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

# Descargar Piper TTS
$piperArchive = "$toolsDir\piper_windows_amd64.tar.gz"
if (-not (Test-Path "$toolsDir\piper.exe")) {
    Write-Host "Descargando Piper TTS..." -ForegroundColor Yellow
    try {
        Invoke-WebRequest -Uri $piperUrl -OutFile $piperArchive -UseBasicParsing
        Write-Host "OK Piper TTS descargado" -ForegroundColor Green
        
        Write-Host "Extrayendo Piper TTS..." -ForegroundColor Yellow
        tar -xzf $piperArchive -C $toolsDir
        
        # Mover archivos si estan en subdirectorio
        $extractedDir = Get-ChildItem -Path $toolsDir -Directory | Where-Object { $_.Name -like "*piper*" } | Select-Object -First 1
        if ($extractedDir) {
            Get-ChildItem -Path $extractedDir.FullName | Move-Item -Destination $toolsDir
            Remove-Item -Path $extractedDir.FullName -Recurse
        }
        
        Write-Host "OK Piper TTS extraido" -ForegroundColor Green
    }
    catch {
        Write-Host "ERROR extrayendo Piper TTS: $_" -ForegroundColor Red
        exit 1
    }
}

# Descargar modelo de voz
$modelFile = "$modelsDir\es_MX-claude-high.onnx"
$configFile = "$modelsDir\es_MX-claude-high.onnx.json"

if (-not (Test-Path $modelFile)) {
    Write-Host "Descargando modelo de voz..." -ForegroundColor Yellow
    try {
        Invoke-WebRequest -Uri $modelUrl -OutFile $modelFile -UseBasicParsing
        Write-Host "OK modelo descargado" -ForegroundColor Green
    }
    catch {
        Write-Host "ERROR descargando modelo: $_" -ForegroundColor Red
        exit 1
    }
}

if (-not (Test-Path $configFile)) {
    Write-Host "Descargando configuracion del modelo..." -ForegroundColor Yellow
    try {
        Invoke-WebRequest -Uri $configUrl -OutFile $configFile -UseBasicParsing
        Write-Host "OK configuracion descargada" -ForegroundColor Green
    }
    catch {
        Write-Host "ERROR descargando configuracion: $_" -ForegroundColor Red
        exit 1
    }
}

# Verificar instalacion
$piperExe = "$toolsDir\piper.exe"
if ((Test-Path $piperExe) -and (Test-Path $modelFile)) {
    Write-Host "Probando Piper TTS..." -ForegroundColor Yellow
    
    $testFile = "$audioDir\test_piper.wav"
    $testText = "Hola, soy la voz de Claude para el sistema de turnos del hospital."
    
    try {
        $testText | & $piperExe --model $modelFile --output_file $testFile
        
        if (Test-Path $testFile) {
            $fileSize = (Get-Item $testFile).Length
            Write-Host "OK Prueba exitosa! Archivo: $testFile ($fileSize bytes)" -ForegroundColor Green
        }
        else {
            Write-Host "ERROR: No se genero el archivo de prueba" -ForegroundColor Red
            exit 1
        }
    }
    catch {
        Write-Host "ERROR probando Piper TTS: $_" -ForegroundColor Red
        exit 1
    }
}
else {
    Write-Host "ERROR: Piper TTS o modelo no disponible" -ForegroundColor Red
    exit 1
}

# Generar archivos basicos
Write-Host ""
Write-Host "Generando archivos basicos..." -ForegroundColor Cyan

# Frases basicas
$phrases = @{
    "turno.wav" = "Turno"
    "dirigirse.wav" = "por favor dirijase a la"
    "caja.wav" = "caja"
    "numero.wav" = "numero"
    "atencion.wav" = "Atencion"
    "llamando.wav" = "llamando al turno"
    "por-favor.wav" = "por favor"
}

Write-Host "Generando frases..." -ForegroundColor Yellow
foreach ($phrase in $phrases.GetEnumerator()) {
    $outputFile = "$audioDir\frases\$($phrase.Key)"
    try {
        $phrase.Value | & $piperExe --model $modelFile --output_file $outputFile
        Write-Host "OK $($phrase.Key)" -ForegroundColor Green
    }
    catch {
        Write-Host "ERROR $($phrase.Key)" -ForegroundColor Red
    }
}

# Letras A-Z
Write-Host ""
Write-Host "Generando letras..." -ForegroundColor Yellow
for ($i = 0; $i -lt 26; $i++) {
    $letter = [char](65 + $i)
    $outputFile = "$audioDir\letras\$letter.wav"
    try {
        $letter | & $piperExe --model $modelFile --output_file $outputFile
        if ($i -lt 5) { Write-Host "OK $letter.wav" -ForegroundColor Green }
    }
    catch {
        Write-Host "ERROR $letter.wav" -ForegroundColor Red
    }
}
Write-Host "OK Letras A-Z completadas" -ForegroundColor Green

# Numeros 1-100
Write-Host ""
Write-Host "Generando numeros (1-100)..." -ForegroundColor Yellow
for ($i = 1; $i -le 100; $i++) {
    $outputFile = "$audioDir\numeros\$i.wav"
    try {
        $i.ToString() | & $piperExe --model $modelFile --output_file $outputFile
        if ($i -le 10 -or $i % 20 -eq 0) {
            Write-Host "OK $i.wav" -ForegroundColor Green
        }
    }
    catch {
        Write-Host "ERROR $i.wav" -ForegroundColor Red
    }
}

# Frase de prueba completa
Write-Host ""
Write-Host "Generando prueba completa..." -ForegroundColor Yellow
$completeTest = "$audioDir\test_complete.wav"
try {
    "Turno A 123, por favor dirijase a la caja numero 5" | & $piperExe --model $modelFile --output_file $completeTest
    Write-Host "OK test_complete.wav" -ForegroundColor Green
}
catch {
    Write-Host "ERROR test_complete.wav" -ForegroundColor Red
}

Write-Host ""
Write-Host "Configuracion completada!" -ForegroundColor Green
Write-Host "Archivos de voz en: $audioDir" -ForegroundColor Cyan

# Mostrar estadisticas
$frasesCount = (Get-ChildItem "$audioDir\frases\*.wav" -ErrorAction SilentlyContinue).Count
$letrasCount = (Get-ChildItem "$audioDir\letras\*.wav" -ErrorAction SilentlyContinue).Count
$numerosCount = (Get-ChildItem "$audioDir\numeros\*.wav" -ErrorAction SilentlyContinue).Count

Write-Host ""
Write-Host "Archivos generados:" -ForegroundColor Cyan
Write-Host "   Frases: $frasesCount archivos" -ForegroundColor White
Write-Host "   Letras: $letrasCount archivos" -ForegroundColor White
Write-Host "   Numeros: $numerosCount archivos" -ForegroundColor White
Write-Host "   Total: $($frasesCount + $letrasCount + $numerosCount) archivos" -ForegroundColor White

Write-Host ""
Write-Host "Proximos pasos:" -ForegroundColor Cyan
Write-Host "1. Prueba el sistema en la vista de TV" -ForegroundColor White
Write-Host "2. Ajusta la configuracion si es necesario" -ForegroundColor White
Write-Host "3. Genera mas numeros si los necesitas" -ForegroundColor White
