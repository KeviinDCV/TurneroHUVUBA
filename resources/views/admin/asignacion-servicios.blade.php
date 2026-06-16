@extends('layouts.admin')

@section('title', 'Asignación de Servicios')

@section('styles')
    <style>
        /* Service item hover effects */
        .service-item {
            transition: all 0.2s ease;
        }

        .service-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Loading animation - CSS Spinner */
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--hospital-blue);
            border-radius: 50%;
            width: 32px;
            height: 32px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
    </style>
@endsection

@section('content')

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-7xl mx-auto" x-data="asignacionData()">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-hospital-blue">Operación</p>
                <h1 class="text-2xl font-bold text-gray-900 mt-1">Asignación de Servicios</h1>
            </div>
            <p class="text-sm text-gray-500">Gestiona la asignación de servicios a los usuarios asesores</p>
        </div>

                <!-- Selector de usuario -->
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Seleccionar Usuario</h2>
                    <div class="max-w-md">
                        <label for="usuario_select" class="block text-sm font-medium text-gray-700 mb-2">Usuario Asesor</label>
                        <select id="usuario_select" x-model="selectedUserId" @change="loadUserServices()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                            <option value="">Seleccionar usuario...</option>
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->id }}">{{ $usuario->nombre_completo }} ({{ $usuario->cedula }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Contenedor de servicios (solo se muestra cuando hay un usuario seleccionado) -->
                <div x-show="selectedUserId && !loading" x-transition class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Servicios Disponibles -->
                    <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
                        <div class="p-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Servicios Disponibles</h3>
                            <p class="text-sm text-gray-600 mt-1">Servicios que pueden ser asignados al usuario</p>
                        </div>
                        <div class="p-4 max-h-96 overflow-y-auto">
                            <div x-show="serviciosDisponibles.length === 0" class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p>No hay servicios disponibles para asignar</p>
                            </div>
                            <div class="space-y-2">
                                <template x-for="servicio in serviciosDisponibles" :key="servicio.id">
                                    <div class="service-item p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-hospital-blue hover:bg-blue-50"
                                         @click="asignarServicio(servicio.id)">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-900" x-text="servicio.nombre"></h4>
                                                <p class="text-sm text-gray-500" x-show="servicio.servicio_padre" x-text="servicio.servicio_padre?.nombre"></p>
                                                <div class="flex items-center space-x-2 mt-1">
                                                    <span class="inline-block px-2 py-1 text-xs rounded-full"
                                                          :class="servicio.nivel === 'servicio' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                                                          x-text="servicio.nivel.charAt(0).toUpperCase() + servicio.nivel.slice(1)">
                                                    </span>
                                                    <span class="text-xs text-gray-400" x-show="servicio.codigo" x-text="'(' + servicio.codigo + ')'"></span>
                                                </div>
                                            </div>
                                            <button class="text-hospital-blue hover:text-hospital-blue-hover p-1 rounded-lg hover:bg-blue-100">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Servicios Asignados -->
                    <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
                        <div class="p-4 border-b border-gray-200 bg-hospital-blue text-white">
                            <h3 class="text-lg font-semibold">Servicios Asignados</h3>
                            <p class="text-sm opacity-90 mt-1">Servicios actualmente asignados al usuario</p>
                        </div>
                        <div class="p-4 max-h-96 overflow-y-auto">
                            <div x-show="serviciosAsignados.length === 0" class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p>No hay servicios asignados</p>
                            </div>
                            <div class="space-y-2">
                                <template x-for="servicio in serviciosAsignados" :key="servicio.id">
                                    <div class="service-item p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-red-400 hover:bg-red-50"
                                         @click="desasignarServicio(servicio.id)">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-900" x-text="servicio.nombre"></h4>
                                                <p class="text-sm text-gray-500" x-show="servicio.servicio_padre" x-text="servicio.servicio_padre?.nombre"></p>
                                                <div class="flex items-center space-x-2 mt-1">
                                                    <span class="inline-block px-2 py-1 text-xs rounded-full"
                                                          :class="servicio.nivel === 'servicio' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                                                          x-text="servicio.nivel.charAt(0).toUpperCase() + servicio.nivel.slice(1)">
                                                    </span>
                                                    <span class="text-xs text-gray-400" x-show="servicio.codigo" x-text="'(' + servicio.codigo + ')'"></span>
                                                </div>
                                            </div>
                                            <button class="text-red-600 hover:text-red-800 p-1 rounded-lg hover:bg-red-100">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

        <!-- Loading state con spinner CSS -->
        <div x-show="loading" x-transition class="text-center py-12">
            <div class="inline-flex items-center">
                <div class="spinner mr-3"></div>
                <span class="text-lg text-gray-600">Cargando servicios...</span>
            </div>
        </div>
    </div>

    <!-- Modal de Error/Éxito -->
    <div
        x-data="modalData()"
        x-cloak
        @keydown.escape.window="showModal = false"
    >
        <div
            x-show="showModal"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 modal-overlay z-50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div
                @click.away="showModal = false"
                class="bg-white rounded-xl shadow-2xl w-full max-w-md"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
            >
                <div class="p-6">
                    <div class="mb-4">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full"
                             :class="modalType === 'success' ? 'bg-green-100' : 'bg-red-100'">
                            <svg class="w-6 h-6" :class="modalType === 'success' ? 'text-green-600' : 'text-red-600'"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="modalType === 'success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                <path x-show="modalType === 'error'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="mt-3 text-lg font-medium text-center text-gray-900" x-text="modalTitle"></h3>
                        <p class="mt-2 text-sm text-center text-gray-500" x-text="modalMessage"></p>
                    </div>

                    <div class="mt-6 flex justify-center">
                        <button @click="showModal = false" class="px-4 py-2 bg-hospital-blue text-white rounded-lg hover:bg-hospital-blue-hover transition-colors cursor-pointer">
                            Entendido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Función para el modal de mensajes
        function modalData() {
            return {
                showModal: false,
                modalType: 'success', // 'success' o 'error'
                modalTitle: '',
                modalMessage: '',
                init() {
                    // Escuchar eventos globales para mostrar modales
                    window.addEventListener('show-success', (event) => {
                        this.modalType = 'success';
                        this.modalTitle = event.detail.title;
                        this.modalMessage = event.detail.message;
                        this.showModal = true;
                    });

                    window.addEventListener('show-error', (event) => {
                        this.modalType = 'error';
                        this.modalTitle = event.detail.title;
                        this.modalMessage = event.detail.message;
                        this.showModal = true;
                    });
                }
            }
        }

        // Función principal para la asignación de servicios
        function asignacionData() {
            return {
                selectedUserId: '',
                selectedUser: null,
                serviciosAsignados: [],
                serviciosDisponibles: [],
                loading: false,

                async loadUserServices() {
                    if (!this.selectedUserId) {
                        this.serviciosAsignados = [];
                        this.serviciosDisponibles = [];
                        this.selectedUser = null;
                        return;
                    }

                    this.loading = true;

                    try {
                        const response = await fetch(`/asignacion-servicios/usuario/${this.selectedUserId}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Error al cargar los servicios del usuario');
                        }

                        const data = await response.json();
                        this.selectedUser = data.usuario;
                        this.serviciosAsignados = data.serviciosAsignados;
                        this.serviciosDisponibles = data.serviciosDisponibles;

                    } catch (error) {
                        console.error('Error:', error);
                        window.dispatchEvent(new CustomEvent('show-error', {
                            detail: {
                                title: 'Error de conexión',
                                message: 'No se pudieron cargar los servicios del usuario. Verifique su conexión a internet.'
                            }
                        }));
                    } finally {
                        this.loading = false;
                    }
                },

                async asignarServicio(servicioId) {
                    if (!this.selectedUserId) return;

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const response = await fetch('/asignacion-servicios/asignar', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                user_id: this.selectedUserId,
                                servicio_id: servicioId
                            })
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            // Recargar los servicios para actualizar las listas
                            await this.loadUserServices();

                            window.dispatchEvent(new CustomEvent('show-success', {
                                detail: {
                                    title: 'Servicio asignado',
                                    message: result.message
                                }
                            }));
                        } else {
                            window.dispatchEvent(new CustomEvent('show-error', {
                                detail: {
                                    title: 'Error al asignar servicio',
                                    message: result.message || 'Error desconocido'
                                }
                            }));
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        window.dispatchEvent(new CustomEvent('show-error', {
                            detail: {
                                title: 'Error de conexión',
                                message: 'No se pudo asignar el servicio. Verifique su conexión a internet.'
                            }
                        }));
                    }
                },

                async desasignarServicio(servicioId) {
                    if (!this.selectedUserId) return;

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const response = await fetch('/asignacion-servicios/desasignar', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                user_id: this.selectedUserId,
                                servicio_id: servicioId
                            })
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            // Recargar los servicios para actualizar las listas
                            await this.loadUserServices();

                            window.dispatchEvent(new CustomEvent('show-success', {
                                detail: {
                                    title: 'Servicio desasignado',
                                    message: result.message
                                }
                            }));
                        } else {
                            window.dispatchEvent(new CustomEvent('show-error', {
                                detail: {
                                    title: 'Error al desasignar servicio',
                                    message: result.message || 'Error desconocido'
                                }
                            }));
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        window.dispatchEvent(new CustomEvent('show-error', {
                            detail: {
                                title: 'Error de conexión',
                                message: 'No se pudo desasignar el servicio. Verifique su conexión a internet.'
                            }
                        }));
                    }
                }
            }
        }
    </script>
@endsection
