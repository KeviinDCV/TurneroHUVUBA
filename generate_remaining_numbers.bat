@echo off
echo Generador de archivos de voz - Turnero HUV
echo ==========================================

set ESPEAK_CMD=espeak-ng
set AUDIO_DIR=C:\Users\Kechavarro\Desktop\Proyectos\turnero-huv\public\audio\turnero\voice
set VOICE=es-419
set SPEED=150
set PITCH=50

echo Generando numeros 51-999...
for /L %%i in (51,1,999) do (
    "%ESPEAK_CMD%" -v%VOICE% -s%SPEED% -p%PITCH% "%%i" -w "%AUDIO_DIR%\numeros\%%i.mp3"
    if %%i LEQ 100 echo ✓ %%i
    if %%i GTR 100 if %%i LEQ 200 echo ✓ %%i
    if %%i GTR 200 if %%i LEQ 300 echo ✓ %%i
    if %%i GTR 300 if %%i LEQ 400 echo ✓ %%i
    if %%i GTR 400 if %%i LEQ 500 echo ✓ %%i
    if %%i GTR 500 if %%i LEQ 600 echo ✓ %%i
    if %%i GTR 600 if %%i LEQ 700 echo ✓ %%i
    if %%i GTR 700 if %%i LEQ 800 echo ✓ %%i
    if %%i GTR 800 if %%i LEQ 900 echo ✓ %%i
    if %%i GTR 900 echo ✓ %%i
)

echo.
echo ✅ Generacion completada!
echo 📁 Archivos en: %AUDIO_DIR%
pause
