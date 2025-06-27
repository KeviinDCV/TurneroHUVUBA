# Script para instalar Piper TTS con voz claude[high] en español mexicano
Write-Host "🎙️  Configurando Piper TTS - Turnero HUV" -ForegroundColor Cyan
Write-Host "Voz: claude[high] - Español Mexicano" -ForegroundColor Cyan
Write-Host "=" * 50

# Configuración
$toolsDir = "tools\piper"
$modelsDir = "$toolsDir\models"
$audioDir = "public\audio\turnero\voice"

# URLs de descarga
$piperUrl = "https://github.com/rhasspy/piper/releases/download/v1.2.0/piper_windows_amd64.tar.gz"
$modelUrl = "https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/es/es_MX/claude/high/es_MX-claude-high.onnx"
$configUrl = "https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/es/es_MX/claude/high/es_MX-claude-high.onnx.json"

# Crear directorios
Write-Host "📁 Creando directorios..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path $toolsDir -Force | Out-Null
New-Item -ItemType Directory -Path $modelsDir -Force | Out-Null
New-Item -ItemType Directory -Path "$audioDir\frases" -Force | Out-Null
New-Item -ItemType Directory -Path "$audioDir\letras" -Force | Out-Null
New-Item -ItemType Directory -Path "$audioDir\numeros" -Force | Out-Null
Write-Host "✓ Directorios creados" -ForegroundColor Green

# Función para descargar archivos
function Download-File {
    param($url, $output, $description)

    Write-Host "Descargando $description..." -ForegroundColor Yellow
    try {
        Invoke-WebRequest -Uri $url -OutFile $output -UseBasicParsing
        Write-Host "OK $description descargado" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "ERROR descargando $description : $_" -ForegroundColor Red
        return $false
    }
}

# Descargar Piper TTS
$piperArchive = "$toolsDir\piper_windows_amd64.tar.gz"
if (-not (Test-Path "$toolsDir\piper.exe")) {
    if (Download-File $piperUrl $piperArchive "Piper TTS") {
        Write-Host "📦 Extrayendo Piper TTS..." -ForegroundColor Yellow

        # Usar tar nativo de Windows 10+
        try {
            tar -xzf $piperArchive -C $toolsDir

            # Mover archivos si están en subdirectorio
            $extractedDir = Get-ChildItem -Path $toolsDir -Directory | Where-Object { $_.Name -like "*piper*" } | Select-Object -First 1
            if ($extractedDir) {
                Get-ChildItem -Path $extractedDir.FullName | Move-Item -Destination $toolsDir
                Remove-Item -Path $extractedDir.FullName -Recurse
            }

            Write-Host "✓ Piper TTS extraído" -ForegroundColor Green
        }
        catch {
            Write-Host "❌ Error extrayendo Piper TTS: $_" -ForegroundColor Red
            Write-Host "Por favor extrae manualmente el archivo $piperArchive" -ForegroundColor Yellow
        }
    }
}

# Descargar modelo de voz
$modelFile = "$modelsDir\es_MX-claude-high.onnx"
$configFile = "$modelsDir\es_MX-claude-high.onnx.json"

if (-not (Test-Path $modelFile)) {
    Download-File $modelUrl $modelFile "modelo de voz claude[high]"
}

if (-not (Test-Path $configFile)) {
    Download-File $configUrl $configFile "configuración del modelo"
}

# Verificar instalación
$piperExe = "$toolsDir\piper.exe"
if ((Test-Path $piperExe) -and (Test-Path $modelFile)) {
    Write-Host "🧪 Probando Piper TTS..." -ForegroundColor Yellow

    $testFile = "$audioDir\test_piper.wav"
    $testText = "Hola, soy la voz de Claude para el sistema de turnos del hospital."

    try {
        $testText | & $piperExe --model $modelFile --output_file $testFile

        if (Test-Path $testFile) {
            $fileSize = (Get-Item $testFile).Length
            Write-Host "OK Prueba exitosa! Archivo generado: $testFile ($fileSize bytes)" -ForegroundColor Green
        }
        else {
            Write-Host "ERROR: No se genero el archivo de prueba" -ForegroundColor Red
        }
    }
    catch {
        Write-Host "❌ Error probando Piper TTS: $_" -ForegroundColor Red
    }
}
else {
    Write-Host "❌ Piper TTS o modelo no disponible" -ForegroundColor Red
    exit 1
}

# Generar archivos básicos
Write-Host "`n🎙️  Generando archivos básicos..." -ForegroundColor Cyan

# Frases básicas
$phrases = @{
    "turno.wav" = "Turno"
    "dirigirse.wav" = "por favor diríjase a la"
    "caja.wav" = "caja"
    "numero.wav" = "número"
    "atencion.wav" = "Atención"
    "llamando.wav" = "llamando al turno"
    "por-favor.wav" = "por favor"
}

Write-Host "💬 Generando frases..." -ForegroundColor Yellow
foreach ($phrase in $phrases.GetEnumerator()) {
    $outputFile = "$audioDir\frases\$($phrase.Key)"
    try {
        $phrase.Value | & $piperExe --model $modelFile --output_file $outputFile
        Write-Host "✓ $($phrase.Key)" -ForegroundColor Green
    }
    catch {
        Write-Host "❌ $($phrase.Key)" -ForegroundColor Red
    }
}

# Letras A-Z
Write-Host "`n🔤 Generando letras..." -ForegroundColor Yellow
for ($i = 0; $i -lt 26; $i++) {
    $letter = [char](65 + $i)
    $outputFile = "$audioDir\letras\$letter.wav"
    try {
        $letter | & $piperExe --model $modelFile --output_file $outputFile
        Write-Host "✓ $letter.wav" -ForegroundColor Green
    }
    catch {
        Write-Host "❌ $letter.wav" -ForegroundColor Red
    }
}

# Números 1-100
Write-Host "`n🔢 Generando números (1-100)..." -ForegroundColor Yellow
for ($i = 1; $i -le 100; $i++) {
    $outputFile = "$audioDir\numeros\$i.wav"
    try {
        $i.ToString() | & $piperExe --model $modelFile --output_file $outputFile
        if ($i -le 20 -or $i % 10 -eq 0) {
            Write-Host "✓ $i.wav" -ForegroundColor Green
        }
    }
    catch {
        Write-Host "❌ $i.wav" -ForegroundColor Red
    }
}

# Frase de prueba completa
Write-Host "`n🧪 Generando prueba completa..." -ForegroundColor Yellow
$completeTest = "$audioDir\test_complete.wav"
try {
    "Turno A 123, por favor diríjase a la caja número 5" | & $piperExe --model $modelFile --output_file $completeTest
    Write-Host "✓ test_complete.wav" -ForegroundColor Green
}
catch {
    Write-Host "❌ test_complete.wav" -ForegroundColor Red
}

Write-Host "`n🎉 ¡Configuración completada!" -ForegroundColor Green
Write-Host "📁 Archivos de voz en: $audioDir" -ForegroundColor Cyan
Write-Host "`n📋 Próximos pasos:" -ForegroundColor Cyan
Write-Host "1. Prueba el sistema en la vista de TV" -ForegroundColor White
Write-Host "2. Ajusta la configuración si es necesario" -ForegroundColor White
Write-Host "3. Genera más números si los necesitas" -ForegroundColor White

# Mostrar estadísticas
$frasesCount = (Get-ChildItem "$audioDir\frases\*.wav" -ErrorAction SilentlyContinue).Count
$letrasCount = (Get-ChildItem "$audioDir\letras\*.wav" -ErrorAction SilentlyContinue).Count
$numerosCount = (Get-ChildItem "$audioDir\numeros\*.wav" -ErrorAction SilentlyContinue).Count

Write-Host "`n📊 Archivos generados:" -ForegroundColor Cyan
Write-Host "   • Frases: $frasesCount archivos" -ForegroundColor White
Write-Host "   • Letras: $letrasCount archivos" -ForegroundColor White
Write-Host "   • Números: $numerosCount archivos" -ForegroundColor White
Write-Host "   • Total: $($frasesCount + $letrasCount + $numerosCount) archivos" -ForegroundColor White
