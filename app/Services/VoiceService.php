<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VoiceService
{
    private const VOICE_BASE_PATH = 'audio/turnero/voice';
    private const VOICE_CONFIG = [
        'model' => 'es_MX-claude-high',
        'language' => 'es-MX',
        'quality' => 'high',
        'speaker' => 'claude'
    ];

    /**
     * Generar URL completa para reproducir un turno
     */
    public function generateTurnCallAudio(string $letra, int $numero, int $caja): array
    {
        $audioFiles = [];

        // 1. "Turno"
        $audioFiles[] = $this->getAudioUrl('frases/turno.mp3');

        // 2. Letra (A, B, C, etc.)
        $audioFiles[] = $this->getAudioUrl("letras/{$letra}.mp3");

        // 3. Número (1, 2, 3, etc.)
        $audioFiles[] = $this->getAudioUrl("numeros/{$numero}.mp3");

        // 4. "por favor diríjase a la caja número" (frase completa para sonar más natural)
        $audioFiles[] = $this->getAudioUrl('frases/dirigirse-caja-numero.mp3');

        // 5. Número de caja
        $audioFiles[] = $this->getAudioUrl("numeros/{$caja}.mp3");

        return array_filter($audioFiles); // Remover archivos que no existen
    }

    /**
     * Obtener URL de archivo de audio
     */
    private function getAudioUrl(string $filename): ?string
    {
        $path = self::VOICE_BASE_PATH . '/' . $filename;

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        Log::warning("Archivo de voz no encontrado: {$path}");
        return null;
    }

    /**
     * Verificar si todos los archivos necesarios existen
     */
    public function checkRequiredFiles(): array
    {
        $missing = [];
        $existing = [];

        // Verificar frases básicas (actualizada para nueva estructura)
        $phrases = ['turno', 'dirigirse-caja-numero', 'por-favor', 'atencion', 'llamando'];
        foreach ($phrases as $phrase) {
            $path = "frases/{$phrase}.mp3";
            if ($this->fileExists($path)) {
                $existing[] = $path;
            } else {
                $missing[] = $path;
            }
        }

        // Verificar letras A-Z
        for ($i = 0; $i < 26; $i++) {
            $letter = chr(ord('A') + $i);
            $path = "letras/{$letter}.mp3";
            if ($this->fileExists($path)) {
                $existing[] = $path;
            } else {
                $missing[] = $path;
            }
        }

        // Verificar números 1-100
        for ($i = 1; $i <= 100; $i++) {
            $path = "numeros/{$i}.mp3";
            if ($this->fileExists($path)) {
                $existing[] = $path;
            } else {
                $missing[] = $path;
            }
        }

        return [
            'existing' => count($existing),
            'missing' => count($missing),
            'missing_files' => array_slice($missing, 0, 10), // Solo primeros 10 para no saturar
            'total_checked' => count($existing) + count($missing),
            'percentage' => count($existing) > 0 ? round((count($existing) / (count($existing) + count($missing))) * 100, 2) : 0
        ];
    }

    /**
     * Verificar si un archivo de voz existe
     */
    private function fileExists(string $relativePath): bool
    {
        $fullPath = public_path(self::VOICE_BASE_PATH . '/' . $relativePath);
        return file_exists($fullPath);
    }

    /**
     * Generar archivo de audio usando Piper TTS
     */
    public function generateAudioFile(string $text, string $outputPath): bool
    {
        try {
            $fullOutputPath = public_path(self::VOICE_BASE_PATH . '/' . $outputPath);

            // Crear directorio si no existe
            $directory = dirname($fullOutputPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Comando para Piper TTS con voz claude[high] en español mexicano
            $piperPath = base_path('tools/piper/piper.exe');
            $modelPath = base_path('tools/piper/models/es_MX-claude-high.onnx');

            $command = sprintf(
                '"%s" --model "%s" --output_file "%s"',
                $piperPath,
                $modelPath,
                $fullOutputPath
            );

            // Crear proceso para enviar texto por stdin
            $descriptorspec = [
                0 => ["pipe", "r"],  // stdin
                1 => ["pipe", "w"],  // stdout
                2 => ["pipe", "w"]   // stderr
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (is_resource($process)) {
                // Enviar texto por stdin
                fwrite($pipes[0], $text);
                fclose($pipes[0]);

                // Leer salida
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);

                $returnCode = proc_close($process);

                if ($returnCode === 0 && file_exists($fullOutputPath)) {
                    Log::info("Archivo de voz generado con Piper TTS: {$outputPath}");
                    return true;
                } else {
                    Log::error("Error generando archivo de voz con Piper TTS: {$outputPath}", [
                        'command' => $command,
                        'stdout' => $stdout,
                        'stderr' => $stderr,
                        'return_code' => $returnCode
                    ]);
                    return false;
                }
            } else {
                Log::error("No se pudo crear el proceso de Piper TTS");
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Excepción generando archivo de voz con Piper TTS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener información del sistema de voz
     */
    public function getSystemInfo(): array
    {
        $info = [
            'piper_available' => false,
            'piper_version' => null,
            'model_available' => false,
            'voice_files_status' => $this->checkRequiredFiles(),
            'voice_directory' => public_path(self::VOICE_BASE_PATH),
            'voice_config' => self::VOICE_CONFIG
        ];

        // Verificar si Piper TTS está disponible
        $piperPath = base_path('tools/piper/piper.exe');
        $modelPath = base_path('tools/piper/models/es_MX-claude-high.onnx');

        if (file_exists($piperPath)) {
            $info['piper_available'] = true;
            try {
                $output = [];
                $returnCode = 0;
                exec("\"$piperPath\" --version 2>&1", $output, $returnCode);
                $info['piper_version'] = implode(' ', $output);
            } catch (\Exception $e) {
                $info['piper_version'] = 'Disponible';
            }
        }

        if (file_exists($modelPath)) {
            $info['model_available'] = true;
        }

        return $info;
    }

    /**
     * Generar archivos faltantes automáticamente
     */
    public function generateMissingFiles(): array
    {
        $results = [
            'generated' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $systemInfo = $this->getSystemInfo();
        if (!$systemInfo['piper_available']) {
            $results['errors'][] = 'Piper TTS no está disponible en el sistema';
            return $results;
        }

        if (!$systemInfo['model_available']) {
            $results['errors'][] = 'Modelo de voz es_MX-claude-high no está disponible';
            return $results;
        }

        // Generar frases básicas (actualizada para nueva estructura más natural)
        $phrases = [
            'turno' => 'Turno',
            'dirigirse-caja-numero' => 'por favor diríjase a la caja número',
            'por-favor' => 'por favor',
            'atencion' => 'Atención',
            'llamando' => 'llamando al turno'
        ];

        foreach ($phrases as $filename => $text) {
            $path = "frases/{$filename}.mp3";
            if (!$this->fileExists($path)) {
                if ($this->generateAudioFile($text, $path)) {
                    $results['generated']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Error generando: {$path}";
                }
            }
        }

        return $results;
    }
}
