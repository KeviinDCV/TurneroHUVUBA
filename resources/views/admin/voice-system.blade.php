@extends('layouts.admin')

@section('title', 'Sistema de Voz')
@section('content')

<div class="bg-white shadow-md p-4 md:p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Sistema de Voz</h1>
    </div>

    <!-- Estado del Sistema -->
    <div class="bg-white border border-gray-200 shadow-sm mb-6">
        <div class="bg-hospital-blue text-white px-6 py-4">
            <h3 class="text-lg font-semibold flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Estado del Sistema
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Piper TTS Status -->
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-3 rounded-full flex items-center justify-center {{ $systemInfo['piper_available'] ? 'bg-green-100' : 'bg-red-100' }}">
                        <svg class="w-8 h-8 {{ $systemInfo['piper_available'] ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900">Piper TTS</h4>
                    <p class="text-sm {{ $systemInfo['piper_available'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $systemInfo['piper_available'] ? 'Disponible' : 'No disponible' }}
                    </p>
                    @if($systemInfo['piper_version'])
                        <p class="text-xs text-gray-500 mt-1">{{ $systemInfo['piper_version'] }}</p>
                    @endif
                </div>

                <!-- Modelo de Voz -->
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-3 rounded-full flex items-center justify-center {{ $systemInfo['model_available'] ? 'bg-green-100' : 'bg-red-100' }}">
                        <svg class="w-8 h-8 {{ $systemInfo['model_available'] ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900">Modelo Claude</h4>
                    <p class="text-sm {{ $systemInfo['model_available'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $systemInfo['model_available'] ? 'Disponible' : 'No disponible' }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">es_MX-claude-high</p>
                </div>

                <!-- Archivos de Voz -->
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-3 rounded-full flex items-center justify-center bg-blue-100">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900">Archivos de Voz</h4>
                    <p class="text-sm text-blue-600">{{ $fileStatus['percentage'] }}% Completo</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $fileStatus['existing'] }}/{{ $fileStatus['total_checked'] }} archivos</p>
                </div>

                <!-- Directorio -->
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-3 rounded-full flex items-center justify-center bg-yellow-100">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900">Directorio</h4>
                    <p class="text-sm text-yellow-600">Configurado</p>
                    <p class="text-xs text-gray-500 mt-1">public/audio/turnero/voice/</p>
                </div>

                <!-- Configuración -->
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-3 rounded-full flex items-center justify-center bg-purple-100">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900">Configuración</h4>
                    <p class="text-sm text-purple-600">{{ $systemInfo['voice_config']['voice'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Velocidad: {{ $systemInfo['voice_config']['speed'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="bg-white border border-gray-200 shadow-sm mb-6">
        <div class="bg-hospital-blue text-white px-6 py-4">
            <h3 class="text-lg font-semibold flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Acciones Rápidas
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <button onclick="testAudio('complete')" class="flex items-center justify-center p-4 border border-gray-200 hover:border-hospital-blue hover:bg-hospital-blue-light transition-all">
                    <svg class="w-5 h-5 mr-2 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H15M9 10v4a2 2 0 002 2h2a2 2 0 002-2v-4M9 10V9a2 2 0 012-2h2a2 2 0 012 2v1"></path>
                    </svg>
                    <span class="font-medium">Probar Turno Completo</span>
                </button>

                <button onclick="generateMissingFiles()" class="flex items-center justify-center p-4 border border-gray-200 hover:border-hospital-blue hover:bg-hospital-blue-light transition-all">
                    <svg class="w-5 h-5 mr-2 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span class="font-medium">Generar Archivos Faltantes</span>
                </button>

                <button onclick="checkSystemStatus()" class="flex items-center justify-center p-4 border border-gray-200 hover:border-hospital-blue hover:bg-hospital-blue-light transition-all">
                    <svg class="w-5 h-5 mr-2 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="font-medium">Actualizar Estado</span>
                </button>

                <button onclick="openFileManager()" class="flex items-center justify-center p-4 border border-gray-200 hover:border-hospital-blue hover:bg-hospital-blue-light transition-all">
                    <svg class="w-5 h-5 mr-2 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    <span class="font-medium">Abrir Directorio</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Generador de Archivos -->
    <div class="bg-white border border-gray-200 shadow-sm mb-6">
        <div class="bg-hospital-blue text-white px-6 py-4">
            <h3 class="text-lg font-semibold flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                </svg>
                Generador de Archivos Personalizado
            </h3>
        </div>
        <div class="p-6">
            <form id="customAudioForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Texto a Sintetizar</label>
                    <input type="text" id="customText" name="text" class="w-full border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue" placeholder="Ej: Hola mundo" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Archivo</label>
                    <input type="text" id="customFilename" name="filename" class="w-full border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue" placeholder="Ej: hola-mundo.mp3" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                    <select id="customCategory" name="category" class="w-full border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue" required>
                        <option value="frases">Frases</option>
                        <option value="numeros">Números</option>
                        <option value="letras">Letras</option>
                        <option value="custom">Personalizado</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="bg-hospital-blue hover:bg-hospital-blue-hover text-white font-medium py-2 px-6 transition-colors">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Generar Archivo de Audio
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Información del Sistema -->
    <div class="bg-white border border-gray-200 shadow-sm">
        <div class="bg-hospital-blue text-white px-6 py-4">
            <h3 class="text-lg font-semibold flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Información del Sistema
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-gray-900 mb-3">Configuración de Voz</h4>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Idioma:</dt>
                            <dd class="text-sm font-medium">{{ $systemInfo['voice_config']['voice'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Velocidad:</dt>
                            <dd class="text-sm font-medium">{{ $systemInfo['voice_config']['speed'] }} WPM</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Tono:</dt>
                            <dd class="text-sm font-medium">{{ $systemInfo['voice_config']['pitch'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Volumen:</dt>
                            <dd class="text-sm font-medium">{{ $systemInfo['voice_config']['amplitude'] }}</dd>
                        </div>
                    </dl>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 mb-3">Archivos Faltantes</h4>
                    @if(count($fileStatus['missing_files']) > 0)
                        <ul class="text-sm text-gray-600 space-y-1">
                            @foreach(array_slice($fileStatus['missing_files'], 0, 5) as $file)
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    {{ $file }}
                                </li>
                            @endforeach
                            @if(count($fileStatus['missing_files']) > 5)
                                <li class="text-gray-500 italic">... y {{ count($fileStatus['missing_files']) - 5 }} más</li>
                            @endif
                        </ul>
                    @else
                        <p class="text-sm text-green-600 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Todos los archivos básicos están disponibles
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Funciones JavaScript para la administración del sistema de voz
function testAudio(type, value = null) {
    fetch('{{ route('admin.voice.test-audio') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ type: type, value: value })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.audio_files.length > 0) {
            playAudioSequence(data.audio_files);
        } else {
            alert('No se encontraron archivos de audio para reproducir');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al probar audio');
    });
}

function playAudioSequence(audioFiles) {
    let currentIndex = 0;

    function playNext() {
        if (currentIndex < audioFiles.length) {
            const audio = new Audio(audioFiles[currentIndex]);
            audio.onended = () => {
                currentIndex++;
                setTimeout(playNext, 300); // Pausa de 300ms entre archivos
            };
            audio.onerror = () => {
                console.error('Error reproduciendo:', audioFiles[currentIndex]);
                currentIndex++;
                setTimeout(playNext, 100);
            };
            audio.play();
        }
    }

    playNext();
}

function generateMissingFiles() {
    if (!confirm('¿Generar archivos de voz faltantes? Esto puede tomar varios minutos.')) {
        return;
    }

    fetch('{{ route('admin.voice.generate-missing') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error generando archivos');
    });
}

function checkSystemStatus() {
    location.reload();
}

function openFileManager() {
    const path = '{{ $systemInfo['voice_directory'] }}';
    alert(`Directorio de archivos de voz:\n${path}`);
}

// Formulario personalizado
document.getElementById('customAudioForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    fetch('{{ route('admin.voice.generate-specific') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success && data.audio_url) {
            // Reproducir el archivo generado
            const audio = new Audio(data.audio_url);
            audio.play();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error generando archivo personalizado');
    });
});
</script>

@endsection
