@extends('layouts.admin')

@section('title', 'Configuración TV')

@section('styles')
    <style>
        :root {
            --hospital-blue: #064b9e;
            --hospital-blue-hover: #053d7a;
            --hospital-blue-light: #e6f0ff;
        }

        .bg-hospital-blue {
            background-color: var(--hospital-blue);
        }

        .text-hospital-blue {
            color: var(--hospital-blue);
        }

        .border-hospital-blue {
            border-color: var(--hospital-blue);
        }

        .hover\:bg-hospital-blue-hover:hover {
            background-color: var(--hospital-blue-hover);
        }

        .bg-hospital-blue-light {
            background-color: var(--hospital-blue-light);
        }

        /* Animaciones suaves */
        .transition-all {
            transition: all 0.3s ease;
        }

        /* Mejora del scroll en la sidebar */
        .sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 2px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }

        .modal-overlay {
            background-color: rgba(100, 116, 139, 0.25) !important;
            backdrop-filter: blur(2px) !important;
            -webkit-backdrop-filter: blur(2px) !important;
        }

        /* Responsive sidebar */
        @media (max-width: 768px) {
            .sidebar-mobile {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }

            .sidebar-mobile.open {
                transform: translateX(0);
            }
        }

        /* Estilos adicionales para la sidebar */
        .sidebar-item {
            position: relative;
            overflow: hidden;
        }

        .sidebar-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .sidebar-item:hover::before {
            left: 100%;
        }

        /* Animación suave para el indicador activo */
        .active-indicator {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        /* Estilos para tabs */
        .tab-content {
            display: block;
        }

        .tab-content.hidden {
            display: none;
        }

        /* Estilos para multimedia */
        .file-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
        }

        .video-preview {
            max-width: 100px;
            max-height: 100px;
        }

        .sortable-item {
            cursor: move;
            transition: all 0.2s ease;
        }

        .sortable-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .sortable-item[draggable="true"]:hover .drag-handle {
            color: var(--hospital-blue);
        }

        .sortable-item.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow-md p-4 md:p-6 max-w-7xl mx-auto">
                    <!-- Header -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Configuración del TV</h1>
                        <a href="{{ route('tv.display') }}" target="_blank" class="bg-hospital-blue text-white px-4 py-2 rounded cursor-pointer w-full sm:w-auto flex items-center justify-center focus:outline-none">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            Ver TV
                        </a>
                    </div>

                    <!-- Tabs Navigation -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="-mb-px flex space-x-8">
                            <button onclick="showTab('ticker')" id="ticker-tab" class="tab-button border-b-2 border-hospital-blue text-hospital-blue py-2 px-1 text-sm font-medium">
                                Mensaje Ticker
                            </button>
                            <button onclick="showTab('multimedia')" id="multimedia-tab" class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-2 px-1 text-sm font-medium">
                                Multimedia
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Content: Ticker -->
                    <div id="ticker-content" class="tab-content">
                        <!-- Formulario de configuración del ticker -->
                        <form id="tvConfigForm" method="POST" action="{{ route('admin.tv-config.update') }}" class="space-y-6">
                        @csrf
                        
                        <!-- Mensaje del ticker -->
                        <div>
                            <label for="ticker_message" class="block text-sm font-medium text-gray-700 mb-2">
                                Mensaje del Ticker
                            </label>
                            <textarea 
                                id="ticker_message" 
                                name="ticker_message" 
                                rows="4" 
                                class="form-input w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-hospital-blue"
                                placeholder="Ingrese el mensaje que aparecerá en el ticker del TV..."
                                required
                            >{{ old('ticker_message', $tvConfig->ticker_message) }}</textarea>
                            <p class="mt-1 text-sm text-gray-500">Este mensaje se mostrará corriendo de derecha a izquierda en la parte inferior del TV.</p>
                        </div>

                        <!-- Velocidad del ticker -->
                        <div>
                            <label for="ticker_speed" class="block text-sm font-medium text-gray-700 mb-2">
                                Velocidad del Ticker (segundos)
                            </label>
                            <div class="flex items-center space-x-4">
                                <input 
                                    type="range" 
                                    id="ticker_speed" 
                                    name="ticker_speed" 
                                    min="10" 
                                    max="120" 
                                    value="{{ old('ticker_speed', $tvConfig->ticker_speed) }}"
                                    class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                    oninput="updateSpeedValue(this.value)"
                                >
                                <span id="speed_value" class="text-sm font-medium text-gray-700 min-w-[60px]">{{ $tvConfig->ticker_speed }}s</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Controla qué tan rápido se mueve el mensaje. Menor valor = más rápido.</p>
                        </div>

                        <!-- Estado del ticker -->
                        <div>
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    name="ticker_enabled" 
                                    value="1"
                                    {{ old('ticker_enabled', $tvConfig->ticker_enabled) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-hospital-blue shadow-sm focus:border-hospital-blue focus:ring focus:ring-hospital-blue focus:ring-opacity-50"
                                >
                                <span class="ml-2 text-sm font-medium text-gray-700">Activar ticker</span>
                            </label>
                            <p class="mt-1 text-sm text-gray-500">Desmarque para ocultar completamente el ticker del TV.</p>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            <button
                                type="button"
                                onclick="resetForm()"
                                class="px-4 py-2 border border-gray-300 rounded text-sm font-medium text-gray-700 focus:outline-none"
                            >
                                Restablecer
                            </button>
                            <button
                                type="submit"
                                id="submitBtn"
                                class="bg-hospital-blue text-white px-4 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none"
                            >
                                <span id="submitText">Guardar Configuración</span>
                                <span id="loadingText" class="hidden">Guardando...</span>
                            </button>
                        </div>
                        </form>
                    </div>

                    <!-- Tab Content: Multimedia -->
                    <div id="multimedia-content" class="tab-content hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-semibold text-gray-800">Gestión de Multimedia</h2>
                            @if($multimedia->count() > 0)
                            <button onclick="showUploadModal()" class="bg-hospital-blue text-white px-4 py-2 rounded cursor-pointer flex items-center focus:outline-none">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Subir Archivo
                            </button>
                            @endif
                        </div>

                        <!-- Lista de multimedia -->
                        <div id="multimediaList" class="space-y-4">
                            @forelse($multimedia as $item)
                                <div class="sortable-item bg-gray-50 border border-gray-200 rounded-lg p-4 transition-all duration-200" data-id="{{ $item->id }}" data-order="{{ $item->orden }}" draggable="true">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <!-- Drag handle -->
                                            <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                                </svg>
                                            </div>

                                            <!-- Preview -->
                                            <div class="flex-shrink-0">
                                                @if($item->tipo === 'imagen')
                                                    <img src="{{ $item->url }}" alt="{{ $item->nombre }}" class="file-preview rounded border">
                                                @else
                                                    <video class="video-preview rounded border" muted>
                                                        <source src="{{ $item->url }}" type="video/{{ $item->extension }}">
                                                    </video>
                                                @endif
                                            </div>

                                            <!-- Info -->
                                            <div class="flex-1">
                                                <h3 class="font-medium text-gray-900">{{ $item->nombre }}</h3>
                                                <p class="text-sm text-gray-500">
                                                    {{ ucfirst($item->tipo) }} • {{ $item->extension }} • {{ $item->tamaño_formateado }}
                                                    @if($item->tipo === 'imagen')
                                                        • {{ $item->duracion }}s
                                                    @endif
                                                </p>
                                                <p class="text-xs text-gray-400">Orden: {{ $item->orden }}</p>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center space-x-2">
                                            <!-- Toggle activo -->
                                            <button onclick="toggleActive({{ $item->id }})"
                                                    class="px-3 py-1 rounded text-xs font-medium {{ $item->activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }} focus:outline-none">
                                                {{ $item->activo ? 'Activo' : 'Inactivo' }}
                                            </button>

                                            <!-- Eliminar -->
                                            <button onclick="confirmDelete({{ $item->id }}, '{{ addslashes($item->nombre) }}')"
                                                    class="px-3 py-1 bg-red-100 text-red-800 rounded text-xs font-medium focus:outline-none">
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-12">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v18a1 1 0 01-1 1H4a1 1 0 01-1-1V1a1 1 0 011-1h2a1 1 0 011 1v3m0 0h8m-8 0V1"></path>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay archivos multimedia</h3>
                                    <p class="mt-1 text-sm text-gray-500">Comience subiendo imágenes o videos para mostrar en el TV.</p>
                                    <div class="mt-6">
                                        <button onclick="showUploadModal()" class="bg-hospital-blue text-white px-4 py-2 rounded cursor-pointer flex items-center mx-auto focus:outline-none">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            Subir Archivo
                                        </button>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
@endsection

@section('scripts')
    <!-- Modal de éxito -->
    <div id="successModal" class="fixed inset-0 modal-overlay z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-md">
            <div class="p-6">
                <div class="mb-4">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="mt-3 text-lg font-medium text-center text-gray-900">Configuración Guardada</h3>
                    <p class="mt-2 text-sm text-center text-gray-500">
                        La configuración del TV se ha actualizado correctamente.
                    </p>
                </div>

                <div class="mt-6 flex justify-center">
                    <button onclick="closeSuccessModal()" class="bg-hospital-blue text-white px-4 py-2 rounded cursor-pointer focus:outline-none">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variable global para almacenar el ID del elemento a eliminar
        let deleteItemId = null;

        // Actualizar valor de velocidad en tiempo real
        function updateSpeedValue(value) {
            document.getElementById('speed_value').textContent = value + 's';
        }

        // Restablecer formulario
        function resetForm() {
            document.getElementById('tvConfigForm').reset();
            updateSpeedValue({{ $tvConfig->ticker_speed }});
        }

        // Mostrar modal de éxito
        function showSuccessModal() {
            document.getElementById('successModal').style.display = 'flex';
        }

        // Cerrar modal de éxito
        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        // Función para mostrar pestañas
        function showTab(tabName) {
            // Ocultar todos los contenidos de pestañas
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });

            // Remover clases activas de todos los botones de pestañas
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('border-hospital-blue', 'text-hospital-blue');
                button.classList.add('border-transparent', 'text-gray-500');
            });

            // Mostrar el contenido de la pestaña seleccionada
            const selectedContent = document.getElementById(tabName + '-content');
            if (selectedContent) {
                selectedContent.classList.remove('hidden');
            }

            // Activar el botón de la pestaña seleccionada
            const selectedButton = document.getElementById(tabName + '-tab');
            if (selectedButton) {
                selectedButton.classList.remove('border-transparent', 'text-gray-500');
                selectedButton.classList.add('border-hospital-blue', 'text-hospital-blue');
            }
        }

        // Función para mostrar modal de subida de archivos
        function showUploadModal() {
            const modal = document.getElementById('uploadModal');
            if (modal) modal.style.display = 'flex';
        }

        // Cerrar modal de subida
        function closeUploadModal() {
            const modal = document.getElementById('uploadModal');
            const form = document.getElementById('uploadForm');
            if (modal) modal.style.display = 'none';
            if (form) form.reset();
            hideUploadProgress();
            hideFilePreview();
        }

        // Subir archivo
        function uploadFile() {
            const form = document.getElementById('uploadForm');

            // Validar que todos los campos estén llenos antes de enviar
            const archivo = document.getElementById('archivo').files[0];
            const nombre = document.getElementById('nombre').value.trim();
            const duracion = document.getElementById('duracion').value;

            if (!archivo) {
                showUploadErrorModal('Por favor seleccione un archivo');
                return;
            }

            if (!nombre) {
                showUploadErrorModal('Por favor ingrese un nombre para el archivo');
                return;
            }

            if (!duracion || duracion < 1 || duracion > 300) {
                showUploadErrorModal('La duración debe estar entre 1 y 300 segundos');
                return;
            }

            // Verificar tamaño del archivo (500MB = 524288000 bytes)
            const maxSize = 524288000; // 500MB en bytes
            if (archivo.size > maxSize) {
                const sizeMB = (archivo.size / 1024 / 1024).toFixed(2);
                showUploadErrorModal(`El archivo es demasiado grande (${sizeMB}MB). El tamaño máximo permitido es 500MB.`);
                return;
            }

            const formData = new FormData(form);

            showUploadProgress();

            // Usar XMLHttpRequest para tener progreso real
            const xhr = new XMLHttpRequest();

            // Configurar progreso de subida
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    updateUploadProgress(percentComplete);
                }
            });

            xhr.addEventListener('load', function() {
                if (xhr.status === 413) {
                    hideUploadProgress();
                    showUploadErrorModal('El archivo es demasiado grande. El tamaño máximo permitido es 500MB.');
                    return;
                }

                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        closeUploadModal();
                        showUploadSuccessModal();
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        hideUploadProgress();
                        let errorMessage = data.message || 'Error al subir el archivo';

                        // Si hay errores de validación específicos, mostrarlos
                        if (data.errors) {
                            const errorList = Object.values(data.errors).flat();
                            errorMessage = 'Errores de validación: ' + errorList.join(', ');
                        }

                        showUploadErrorModal(errorMessage);
                    }
                } catch (error) {
                    hideUploadProgress();
                    showUploadErrorModal('Error al procesar la respuesta del servidor');
                }
            });

            xhr.addEventListener('error', function() {
                hideUploadProgress();
                showUploadErrorModal('Error de conexión al subir el archivo');
            });

            xhr.addEventListener('timeout', function() {
                hideUploadProgress();
                showUploadErrorModal('Tiempo de espera agotado. El archivo puede ser demasiado grande.');
            });

            // Configurar y enviar la petición
            xhr.open('POST', '/tv-config/multimedia');
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            xhr.timeout = 600000; // 10 minutos de timeout
            xhr.send(formData);
        }

        // Mostrar progreso de subida
        function showUploadProgress() {
            const progress = document.getElementById('uploadProgress');
            const btn = document.getElementById('uploadBtn');
            const btnText = document.getElementById('uploadBtnText');
            const btnLoading = document.getElementById('uploadBtnLoading');

            if (progress) progress.style.display = 'block';
            if (btn) btn.disabled = true;
            if (btnText) btnText.style.display = 'none';
            if (btnLoading) btnLoading.style.display = 'inline';

            // Resetear progreso
            updateUploadProgress(0);
        }

        // Actualizar progreso de subida
        function updateUploadProgress(percent) {
            const progressBar = document.querySelector('#uploadProgress .bg-hospital-blue');
            const progressText = document.getElementById('progressText');

            if (progressBar) {
                progressBar.style.width = percent + '%';
            }

            if (progressText) {
                if (percent < 100) {
                    progressText.textContent = `Subiendo archivo... ${Math.round(percent)}%`;
                } else {
                    progressText.textContent = 'Procesando archivo...';
                }
            }
        }

        // Ocultar progreso de subida
        function hideUploadProgress() {
            const progress = document.getElementById('uploadProgress');
            const btn = document.getElementById('uploadBtn');
            const btnText = document.getElementById('uploadBtnText');
            const btnLoading = document.getElementById('uploadBtnLoading');

            if (progress) progress.style.display = 'none';
            if (btn) btn.disabled = false;
            if (btnText) btnText.style.display = 'inline';
            if (btnLoading) btnLoading.style.display = 'none';
        }

        // Mostrar modal de éxito de subida
        function showUploadSuccessModal() {
            const modal = document.getElementById('uploadSuccessModal');
            if (modal) modal.style.display = 'flex';
        }

        // Cerrar modal de éxito de subida
        function closeUploadSuccessModal() {
            const modal = document.getElementById('uploadSuccessModal');
            if (modal) modal.style.display = 'none';
        }

        // Mostrar modal de error de subida
        function showUploadErrorModal(message) {
            const messageEl = document.getElementById('uploadErrorMessage');
            const modal = document.getElementById('uploadErrorModal');
            if (messageEl) messageEl.textContent = message;
            if (modal) modal.style.display = 'flex';
        }

        // Cerrar modal de error de subida
        function closeUploadErrorModal() {
            const modal = document.getElementById('uploadErrorModal');
            if (modal) modal.style.display = 'none';
        }

        // Mostrar modal de error de archivo (para validaciones de selección)
        function showFileErrorModal(message) {
            const messageEl = document.getElementById('fileErrorMessage');
            const modal = document.getElementById('fileErrorModal');
            if (messageEl) messageEl.textContent = message;
            if (modal) modal.style.display = 'flex';
        }

        // Cerrar modal de error de archivo
        function closeFileErrorModal() {
            const modal = document.getElementById('fileErrorModal');
            if (modal) modal.style.display = 'none';
        }

        // Confirmar eliminación
        function confirmDelete(id, nombre) {
            deleteItemId = id;
            const fileName = document.getElementById('deleteFileName');
            const modal = document.getElementById('deleteModal');
            if (fileName) fileName.textContent = nombre;
            if (modal) modal.style.display = 'flex';
        }

        // Cerrar modal de eliminación
        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            if (modal) modal.style.display = 'none';
            deleteItemId = null;
        }

        // Eliminar archivo
        function deleteFile() {
            if (!deleteItemId) return;

            fetch(`{{ url('/tv-config/multimedia') }}/${deleteItemId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeDeleteModal();
                    showDeleteSuccessModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showDeleteErrorModal(data.message || 'Error al eliminar el archivo');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showDeleteErrorModal('Error de conexión al eliminar el archivo');
            });
        }

        // Mostrar modal de éxito de eliminación
        function showDeleteSuccessModal() {
            const modal = document.getElementById('deleteSuccessModal');
            if (modal) modal.style.display = 'flex';
        }

        // Cerrar modal de éxito de eliminación
        function closeDeleteSuccessModal() {
            const modal = document.getElementById('deleteSuccessModal');
            if (modal) modal.style.display = 'none';
        }

        // Mostrar modal de error de eliminación
        function showDeleteErrorModal(message) {
            const messageEl = document.getElementById('deleteErrorMessage');
            const modal = document.getElementById('deleteErrorModal');
            if (messageEl) messageEl.textContent = message;
            if (modal) modal.style.display = 'flex';
        }

        // Cerrar modal de error de eliminación
        function closeDeleteErrorModal() {
            const modal = document.getElementById('deleteErrorModal');
            if (modal) modal.style.display = 'none';
        }

        // Manejar selección de archivo
        function handleFileSelect(input) {
            const file = input.files[0];
            if (!file) {
                hideFilePreview();
                // Limpiar campo nombre también
                const nombreField = document.getElementById('nombre');
                if (nombreField) nombreField.value = '';
                return;
            }

            const fileName = file.name;
            const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
            const fileExtension = fileName.split('.').pop().toLowerCase();

            // Determinar si es imagen o video
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            const videoExtensions = ['mp4', 'mov', 'avi'];

            const isImage = imageExtensions.includes(fileExtension);
            const isVideo = videoExtensions.includes(fileExtension);

            if (!isImage && !isVideo) {
                showFileErrorModal('Formato de archivo no válido. Use: JPG, PNG, GIF, MP4, MOV, AVI');
                input.value = '';
                hideFilePreview();
                const nombreField = document.getElementById('nombre');
                if (nombreField) nombreField.value = '';
                return;
            }

            // Verificar tamaño del archivo (500MB = 524288000 bytes)
            const maxSize = 524288000; // 500MB en bytes
            if (file.size > maxSize) {
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                showFileErrorModal(`El archivo es demasiado grande (${sizeMB}MB). El tamaño máximo permitido es 500MB.`);
                input.value = '';
                hideFilePreview();
                const nombreField = document.getElementById('nombre');
                if (nombreField) nombreField.value = '';
                return;
            }

            // Llenar automáticamente el campo nombre con el nombre del archivo (sin extensión)
            const fileNameWithoutExtension = fileName.substring(0, fileName.lastIndexOf('.'));
            const nombreField = document.getElementById('nombre');
            if (nombreField) nombreField.value = fileNameWithoutExtension;

            // Mostrar preview
            showFilePreview(file, fileName, fileSize, isImage, isVideo);

            // Configurar campo de duración según el tipo
            if (isImage) {
                setupImageDuration();
            } else if (isVideo) {
                setupVideoDuration(file);
            }
        }

        // Mostrar preview del archivo
        function showFilePreview(file, fileName, fileSize, isImage, isVideo) {
            const preview = document.getElementById('filePreview');
            const container = document.getElementById('previewContainer');
            const nameElement = document.getElementById('fileName');
            const infoElement = document.getElementById('fileInfo');

            nameElement.textContent = fileName;
            infoElement.textContent = `${fileSize} MB • ${isImage ? 'Imagen' : 'Video'}`;

            // Crear preview visual
            container.innerHTML = '';

            if (isImage) {
                const img = document.createElement('img');
                const objectURL = URL.createObjectURL(file);
                img.src = objectURL;
                img.className = 'w-12 h-12 object-cover rounded';
                img.onload = () => {
                    // Revocar URL después de un pequeño delay para asegurar que se cargue
                    setTimeout(() => URL.revokeObjectURL(objectURL), 100);
                };
                img.onerror = () => {
                    URL.revokeObjectURL(objectURL);
                };
                container.appendChild(img);
            } else {
                const videoIcon = document.createElement('div');
                videoIcon.className = 'w-12 h-12 bg-blue-100 rounded flex items-center justify-center';
                videoIcon.innerHTML = `
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                `;
                container.appendChild(videoIcon);
            }

            preview.classList.remove('hidden');
        }

        // Ocultar preview del archivo
        function hideFilePreview() {
            const preview = document.getElementById('filePreview');
            if (preview) preview.classList.add('hidden');
            resetDurationField();
        }

        // Configurar duración para imágenes
        function setupImageDuration() {
            const duracionInput = document.getElementById('duracion');
            const duracionLabel = document.getElementById('duracionLabel');
            const duracionHelp = document.getElementById('duracionHelp');

            if (duracionLabel) duracionLabel.textContent = 'Duración (segundos)';
            if (duracionHelp) duracionHelp.textContent = 'Entre 1 y 300 segundos - Tiempo que se mostrará la imagen';

            if (duracionInput) {
                duracionInput.disabled = false;
                duracionInput.readOnly = false;
                duracionInput.value = 10;
                duracionInput.min = 1;
                duracionInput.max = 300;
                duracionInput.className = 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent';
            }
        }

        // Configurar duración para videos
        function setupVideoDuration(file) {
            const duracionInput = document.getElementById('duracion');
            const duracionLabel = document.getElementById('duracionLabel');
            const duracionHelp = document.getElementById('duracionHelp');

            if (duracionLabel) duracionLabel.textContent = 'Duración (automática)';
            if (duracionHelp) duracionHelp.textContent = 'La duración se detectará automáticamente del video';

            // Crear elemento video temporal para obtener duración
            const video = document.createElement('video');
            video.preload = 'metadata';

            const objectURL = URL.createObjectURL(file);

            video.onloadedmetadata = function() {
                const duration = Math.ceil(video.duration);
                if (duracionInput) {
                    duracionInput.value = duration;
                    // NO deshabilitar el campo para que se envíe en el formulario
                    duracionInput.readOnly = true;
                    duracionInput.className = 'w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-500 cursor-not-allowed';
                }

                if (duracionHelp) duracionHelp.textContent = `Duración detectada: ${duration} segundos`;

                // Limpiar objeto URL después de un delay
                setTimeout(() => URL.revokeObjectURL(objectURL), 100);
            };

            video.onerror = function() {
                if (duracionHelp) duracionHelp.textContent = 'No se pudo detectar la duración. Ingrese manualmente.';
                if (duracionInput) {
                    duracionInput.readOnly = false;
                    duracionInput.value = 30;
                    duracionInput.className = 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent';
                }
                URL.revokeObjectURL(objectURL);
            };

            video.src = objectURL;
        }

        // Resetear campo de duración
        function resetDurationField() {
            const duracionInput = document.getElementById('duracion');
            const duracionLabel = document.getElementById('duracionLabel');
            const duracionHelp = document.getElementById('duracionHelp');

            if (duracionLabel) duracionLabel.textContent = 'Duración (segundos)';
            if (duracionHelp) duracionHelp.textContent = 'Entre 1 y 300 segundos';

            if (duracionInput) {
                duracionInput.disabled = false;
                duracionInput.readOnly = false;
                duracionInput.value = 10;
                duracionInput.min = 1;
                duracionInput.max = 300;
                duracionInput.className = 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent';
            }
        }

        // Activar/desactivar archivo multimedia
        function toggleActive(id) {
            fetch(`{{ url('/tv-config/multimedia') }}/${id}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito y recargar la página
                    showSuccessModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    alert('Error al cambiar el estado: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al cambiar el estado');
            });
        }

        // Inicializar funcionalidad de ordenamiento
        function initializeSortable() {
            const list = document.getElementById('multimediaList');
            if (!list || list.children.length === 0) return;

            // Implementación simple de drag and drop
            let draggedElement = null;

            list.addEventListener('dragstart', function(e) {
                if (e.target.classList.contains('sortable-item')) {
                    draggedElement = e.target;
                    e.target.classList.add('dragging');
                }
            });

            list.addEventListener('dragend', function(e) {
                if (e.target.classList.contains('sortable-item')) {
                    e.target.classList.remove('dragging');
                    draggedElement = null;
                }
            });

            list.addEventListener('dragover', function(e) {
                e.preventDefault();
            });

            list.addEventListener('drop', function(e) {
                e.preventDefault();

                if (!draggedElement) return;

                const afterElement = getDragAfterElement(list, e.clientY);

                if (afterElement == null) {
                    list.appendChild(draggedElement);
                } else {
                    list.insertBefore(draggedElement, afterElement);
                }

                // Actualizar orden en el servidor
                updateMultimediaOrder();
            });
        }

        // Obtener elemento después del cual insertar
        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.sortable-item:not(.dragging)')];

            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;

                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        // Actualizar orden en el servidor
        function updateMultimediaOrder() {
            const items = document.querySelectorAll('.sortable-item');
            const orderData = [];

            items.forEach((item, index) => {
                orderData.push({
                    id: parseInt(item.dataset.id),
                    orden: index + 1
                });
            });

            fetch('{{ route('admin.tv-config.multimedia.order') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ items: orderData })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    showSuccessModal();
                } else {
                    alert('Error al actualizar el orden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al actualizar el orden');
            });
        }

        // Inicializar la primera pestaña como activa al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            showTab('ticker');
            // Inicializar funcionalidad de ordenamiento
            initializeSortable();
        });

        // Manejar envío del formulario
        const tvConfigForm = document.getElementById('tvConfigForm');
        if (tvConfigForm) {
            tvConfigForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const loadingText = document.getElementById('loadingText');

            // Mostrar estado de carga
            if (submitBtn) submitBtn.disabled = true;
            if (submitText) submitText.classList.add('hidden');
            if (loadingText) loadingText.classList.remove('hidden');

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showSuccessModal();
                } else {
                    alert('Error al guardar la configuración: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión: ' + error.message);
            })
            .finally(() => {
                // Restaurar estado del botón
                if (submitBtn) submitBtn.disabled = false;
                if (submitText) submitText.classList.remove('hidden');
                if (loadingText) loadingText.classList.add('hidden');
            });
            });
        }

        // Mostrar mensaje de éxito si viene de redirección
        @if(session('success'))
            showSuccessModal();
        @endif
    </script>

    <!-- Modales -->
    <!-- Modal de subida de archivos -->
    <div id="uploadModal" class="fixed inset-0 modal-overlay z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-md">
            <div class="p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-center text-gray-900">Subir Archivo Multimedia</h3>
                    <p class="mt-2 text-sm text-center text-gray-500">
                        Sube imágenes (JPG, PNG, GIF) o videos (MP4, MOV, AVI) para mostrar en el TV.
                    </p>
                </div>

                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="archivo" class="block text-sm font-medium text-gray-700 mb-1">Archivo</label>
                            <input type="file" id="archivo" name="archivo" accept=".jpg,.jpeg,.png,.gif,.mp4,.mov,.avi" required
                                   onchange="handleFileSelect(this)"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                            <p class="mt-1 text-xs text-gray-500">Máximo 500MB. Formatos: JPG, PNG, GIF, MP4, MOV, AVI</p>

                            <!-- Preview del archivo -->
                            <div id="filePreview" class="mt-3 hidden">
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-md">
                                    <div id="previewContainer" class="flex-shrink-0"></div>
                                    <div class="flex-1">
                                        <p id="fileName" class="text-sm font-medium text-gray-900"></p>
                                        <p id="fileInfo" class="text-xs text-gray-500"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                            <input type="text" id="nombre" name="nombre" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent"
                                   placeholder="Nombre descriptivo del archivo">
                        </div>

                        <div>
                            <label for="duracion" class="block text-sm font-medium text-gray-700 mb-1">
                                <span id="duracionLabel">Duración (segundos)</span>
                            </label>
                            <input type="number" id="duracion" name="duracion" min="1" max="300" value="10" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent"
                                   placeholder="Tiempo de visualización en segundos">
                            <p id="duracionHelp" class="mt-1 text-xs text-gray-500">Entre 1 y 300 segundos</p>
                        </div>
                    </div>

                    <!-- Barra de progreso -->
                    <div id="uploadProgress" class="mt-4" style="display: none;">
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div class="bg-hospital-blue h-3 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
                        </div>
                        <p id="progressText" class="text-sm text-gray-600 mt-2 text-center">Preparando subida...</p>
                    </div>
                </form>

                <div class="mt-6 flex justify-center space-x-3">
                    <button onclick="closeUploadModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">
                        Cancelar
                    </button>
                    <button onclick="uploadFile()" id="uploadBtn" class="px-4 py-2 bg-hospital-blue text-white rounded-md text-sm font-medium focus:outline-none">
                        <span id="uploadBtnText">Subir Archivo</span>
                        <span id="uploadBtnLoading" class="hidden">Subiendo...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div id="deleteModal" class="fixed inset-0 modal-overlay z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-md">
            <div class="p-6">
                <div class="mb-4">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="mt-3 text-lg font-medium text-center text-gray-900">¿Eliminar este archivo?</h3>
                    <p class="mt-2 text-sm text-center text-gray-500">
                        Estás a punto de eliminar el archivo <span class="font-medium" id="deleteFileName"></span>.<br>
                        Esta acción no se puede deshacer.
                    </p>
                </div>

                <div class="mt-6 flex justify-center space-x-4">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition-colors cursor-pointer focus:outline-none">
                        Cancelar
                    </button>
                    <button onclick="deleteFile()" class="px-4 py-2 bg-red-600 text-white rounded cursor-pointer focus:outline-none">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de éxito de eliminación -->
    <div id="deleteSuccessModal" class="fixed inset-0 modal-overlay z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-md">
            <div class="p-6">
                <div class="mb-4">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-center text-gray-900 mt-2">¡Archivo eliminado!</h3>
                    <p class="mt-2 text-sm text-center text-gray-500">
                        El archivo se ha eliminado correctamente.
                    </p>
                </div>

                <div class="mt-6 flex justify-center">
                    <button onclick="closeDeleteSuccessModal()" class="px-4 py-2 bg-hospital-blue text-white rounded cursor-pointer focus:outline-none">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de error de eliminación -->
    <div id="deleteErrorModal" class="fixed inset-0 modal-overlay z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-md">
            <div class="p-6">
                <div class="mb-4">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-center text-gray-900 mt-2">Error al eliminar archivo</h3>
                    <p id="deleteErrorMessage" class="mt-2 text-sm text-center text-gray-500">
                        Ha ocurrido un error al eliminar el archivo.
                    </p>
                </div>

                <div class="mt-6 flex justify-center">
                    <button onclick="closeDeleteErrorModal()" class="px-4 py-2 bg-red-600 text-white rounded cursor-pointer focus:outline-none">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de error de archivo -->
    <div id="fileErrorModal" class="fixed inset-0 modal-overlay z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-md">
            <div class="p-6">
                <div class="mb-4">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-center text-gray-900 mt-2">Error de Archivo</h3>
                    <p id="fileErrorMessage" class="mt-2 text-sm text-center text-gray-500">
                        Ha ocurrido un error con el archivo seleccionado.
                    </p>
                </div>

                <div class="mt-6 flex justify-center">
                    <button onclick="closeFileErrorModal()" class="px-4 py-2 bg-red-600 text-white rounded cursor-pointer focus:outline-none">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
