<?php

namespace App\Http\Controllers;

use App\Services\VoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VoiceController extends Controller
{
    private VoiceService $voiceService;

    public function __construct(VoiceService $voiceService)
    {
        $this->voiceService = $voiceService;
    }

    /**
     * Obtener archivos de audio para un turno específico
     */
    public function getTurnAudio(Request $request): JsonResponse
    {
        $request->validate([
            'letra' => 'required|string|size:1|regex:/^[A-Z]$/',
            'numero' => 'required|integer|min:1|max:999',
            'caja' => 'required|integer|min:1|max:99'
        ]);

        $audioFiles = $this->voiceService->generateTurnCallAudio(
            $request->letra,
            $request->numero,
            $request->caja
        );

        return response()->json([
            'success' => true,
            'audio_files' => $audioFiles,
            'total_files' => count($audioFiles),
            'estimated_duration' => count($audioFiles) * 0.8 // Estimación en segundos
        ]);
    }

    /**
     * Verificar estado del sistema de voz
     */
    public function getSystemStatus(): JsonResponse
    {
        $systemInfo = $this->voiceService->getSystemInfo();
        $fileStatus = $this->voiceService->checkRequiredFiles();

        return response()->json([
            'success' => true,
            'system_info' => $systemInfo,
            'file_status' => $fileStatus,
            'recommendations' => $this->getRecommendations($systemInfo, $fileStatus)
        ]);
    }

    /**
     * Generar archivos de voz faltantes
     */
    public function generateMissingFiles(): JsonResponse
    {
        $results = $this->voiceService->generateMissingFiles();

        return response()->json([
            'success' => $results['failed'] === 0,
            'results' => $results,
            'message' => $this->getGenerationMessage($results)
        ]);
    }

    /**
     * Generar archivo de audio específico
     */
    public function generateSpecificAudio(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:100',
            'filename' => 'required|string|max:50|regex:/^[a-zA-Z0-9_-]+\.mp3$/',
            'category' => 'required|string|in:frases,numeros,letras,custom'
        ]);

        $outputPath = $request->category . '/' . $request->filename;
        $success = $this->voiceService->generateAudioFile($request->text, $outputPath);

        return response()->json([
            'success' => $success,
            'message' => $success 
                ? "Archivo generado: {$request->filename}"
                : "Error generando archivo: {$request->filename}",
            'audio_url' => $success ? asset("audio/turnero/voice/{$outputPath}") : null
        ]);
    }

    /**
     * Obtener recomendaciones basadas en el estado del sistema
     */
    private function getRecommendations(array $systemInfo, array $fileStatus): array
    {
        $recommendations = [];

        if (!$systemInfo['espeak_available']) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'eSpeak-NG no disponible',
                'message' => 'Instale eSpeak-NG para generar archivos de voz automáticamente',
                'action' => 'Descargar desde: https://github.com/espeak-ng/espeak-ng/releases'
            ];
        }

        if ($fileStatus['percentage'] < 50) {
            $recommendations[] = [
                'type' => 'error',
                'title' => 'Archivos de voz insuficientes',
                'message' => "Solo {$fileStatus['percentage']}% de archivos disponibles",
                'action' => 'Ejecutar script de generación de archivos'
            ];
        } elseif ($fileStatus['percentage'] < 90) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Archivos de voz incompletos',
                'message' => "Faltan algunos archivos ({$fileStatus['percentage']}% disponible)",
                'action' => 'Generar archivos faltantes'
            ];
        } else {
            $recommendations[] = [
                'type' => 'success',
                'title' => 'Sistema de voz completo',
                'message' => "Sistema funcionando correctamente ({$fileStatus['percentage']}% archivos disponibles)",
                'action' => null
            ];
        }

        return $recommendations;
    }

    /**
     * Obtener mensaje de resultado de generación
     */
    private function getGenerationMessage(array $results): string
    {
        if ($results['generated'] === 0 && $results['failed'] === 0) {
            return 'No hay archivos para generar';
        }

        $message = "Generados: {$results['generated']} archivos";
        
        if ($results['failed'] > 0) {
            $message .= ", Fallidos: {$results['failed']} archivos";
        }

        return $message;
    }

    /**
     * Vista de administración del sistema de voz
     */
    public function adminPanel()
    {
        $systemInfo = $this->voiceService->getSystemInfo();
        $fileStatus = $this->voiceService->checkRequiredFiles();

        return view('admin.voice-system', compact('systemInfo', 'fileStatus'));
    }

    /**
     * Probar reproducción de audio
     */
    public function testAudio(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:complete,phrase,number,letter',
            'value' => 'nullable|string|max:10'
        ]);

        $audioFiles = [];

        switch ($request->type) {
            case 'complete':
                $audioFiles = $this->voiceService->generateTurnCallAudio('A', 123, 5);
                break;
            
            case 'phrase':
                $phrase = $request->value ?? 'turno';
                $audioFiles[] = asset("audio/turnero/voice/frases/{$phrase}.mp3");
                break;
            
            case 'number':
                $number = $request->value ?? '1';
                $audioFiles[] = asset("audio/turnero/voice/numeros/{$number}.mp3");
                break;
            
            case 'letter':
                $letter = strtoupper($request->value ?? 'A');
                $audioFiles[] = asset("audio/turnero/voice/letras/{$letter}.mp3");
                break;
        }

        return response()->json([
            'success' => true,
            'audio_files' => array_filter($audioFiles),
            'test_type' => $request->type
        ]);
    }
}
