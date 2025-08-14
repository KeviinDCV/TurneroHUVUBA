@extends('layouts.admin')

@section('title', 'Gestión de Servicios')

@section('content')
                <div class="bg-white rounded-lg shadow-md p-4 md:p-6 max-w-7xl mx-auto">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4" x-data="{ openModal: false }">
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Gestión de Servicios</h1>
                        <button @click="openModal = true" class="bg-hospital-blue text-white px-4 py-2 rounded hover:bg-hospital-blue-hover transition-colors cursor-pointer w-full sm:w-auto">
                            Nuevo Servicio
                        </button>

                        <!-- Modal Crear Servicio -->
                        <div x-show="openModal"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed inset-0 modal-overlay z-50 flex items-center justify-center p-4"
                             style="display: none;">
                            <div @click.away="openModal = false" class="bg-white rounded-lg shadow-2xl w-full max-w-2xl overflow-y-auto max-h-[90vh]">
                                <div class="p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-medium text-gray-900">Crear Nuevo Servicio</h3>
                                        <button @click="openModal = false" class="text-gray-400 hover:text-gray-600">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    <form id="createServicioForm" method="POST" action="{{ route('admin.servicios.store') }}"
                                          x-data="{ nivel: 'servicio' }">
                                        @csrf
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                                                <input type="text" id="nombre" name="nombre" required
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                                            </div>

                                            <div>
                                                <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                                                <input type="text" id="codigo" name="codigo"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                                            </div>

                                            <div>
                                                <label for="nivel" class="block text-sm font-medium text-gray-700 mb-1">Nivel *</label>
                                                <select id="nivel" name="nivel" required x-model="nivel"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                                                    <option value="servicio">Servicio</option>
                                                    <option value="subservicio">Subservicio</option>
                                                </select>
                                            </div>

                                            <!-- Campo servicio padre que aparece solo para subservicios -->
                                            <div x-show="nivel === 'subservicio'">
                                                <label for="servicio_padre_id" class="block text-sm font-medium text-gray-700 mb-1">Servicio Padre *</label>
                                                <select id="servicio_padre_id" name="servicio_padre_id"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                                                    <option value="">Seleccionar servicio padre</option>
                                                    @foreach($serviciosPrincipales as $servicioPadre)
                                                        <option value="{{ $servicioPadre->id }}">{{ $servicioPadre->nombre }}</option>
                                                    @endforeach
                                                </select>
                                                <p class="text-xs text-gray-500 mt-1">El subservicio estará asociado a este servicio principal.</p>
                                            </div>

                                            <div>
                                                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                                                <select id="estado" name="estado" required
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                                                    <option value="activo">Activo</option>
                                                    <option value="inactivo">Inactivo</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label for="orden" class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                                                <input type="number" id="orden" name="orden" min="0"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                                                <p class="text-xs text-gray-500 mt-1">Orden de aparición en menús y listas (opcional).</p>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <div class="flex items-center">
                                                <input type="checkbox" id="ocultar_turno" name="ocultar_turno" value="1"
                                                       class="h-4 w-4 text-hospital-blue focus:ring-hospital-blue border-gray-300 rounded">
                                                <label for="ocultar_turno" class="ml-2 block text-sm text-gray-700">
                                                    Ocultar turno
                                                </label>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Si está activado, los turnos de este servicio no se mostrarán en el TV ni se llamarán automáticamente.</p>
                                        </div>

                                        <div class="mt-4">
                                            <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                            <textarea id="descripcion" name="descripcion" rows="3"
                                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent"></textarea>
                                        </div>

                                        <div class="flex justify-end space-x-3 mt-6">
                                            <button type="button" @click="openModal = false"
                                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors">
                                                Cancelar
                                            </button>
                                            <button type="submit"
                                                    class="px-4 py-2 bg-hospital-blue text-white rounded hover:bg-hospital-blue-hover transition-colors">
                                                Crear Servicio
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aplicación Alpine.js para búsqueda en tiempo real -->
                    <div x-data="{
                        search: '{{ $search ?? '' }}',
                        servicios: {{ json_encode($servicios->items()) }},
                        allServicios: {{ json_encode($servicios->items()) }},

                        init() {
                            this.$watch('search', value => {
                                if (value === '') {
                                    this.servicios = this.allServicios;
                                    return;
                                }

                                value = value.toLowerCase();
                                this.servicios = this.allServicios.filter(servicio => {
                                    return servicio.nombre.toLowerCase().includes(value) ||
                                           servicio.descripcion?.toLowerCase().includes(value) ||
                                           servicio.codigo?.toLowerCase().includes(value) ||
                                           servicio.nivel.toLowerCase().includes(value) ||
                                           servicio.estado.toLowerCase().includes(value) ||
                                           (servicio.servicio_padre && servicio.servicio_padre.nombre.toLowerCase().includes(value));
                                });
                            });
                        },

                        async editServicio(id) {
                            try {
                                const response = await fetch(`/servicios/${id}`, {
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json'
                                    }
                                });
                                const servicio = await response.json();

                                // Disparar evento para abrir modal de edición
                                window.dispatchEvent(new CustomEvent('edit-servicio', {
                                    detail: servicio
                                }));
                            } catch (error) {
                                console.error('Error al cargar servicio:', error);
                                window.dispatchEvent(new CustomEvent('show-error', {
                                    detail: {
                                        title: 'Error al cargar servicio',
                                        message: 'No se pudieron cargar los datos del servicio. Verifique su conexión a internet.'
                                    }
                                }));
                            }
                        },

                        deleteServicio(id, nombre) {
                            // Disparar evento para abrir modal de eliminación
                            window.dispatchEvent(new CustomEvent('delete-servicio', {
                                detail: { id, nombre }
                            }));
                        }
                    }">
                        <!-- Buscador -->
                        <div class="mb-6">
                            <div class="flex items-center border border-gray-300 rounded-md overflow-hidden shadow-sm search-container">
                                <div class="px-3 py-2 bg-gray-50">
                                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    x-model="search"
                                    placeholder="Buscar por nombre, código, nivel, estado o descripción..."
                                    class="w-full px-4 py-2 focus:outline-none focus:border-hospital-blue"
                                >
                                <template x-if="search">
                                    <button @click="search = ''" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Tabla de Servicios -->
                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="bg-hospital-blue text-white">
                                        <th class="py-3 px-4 text-left font-semibold">SERVICIO</th>
                                        <th class="py-3 px-4 text-left font-semibold">NIVEL</th>
                                        <th class="py-3 px-4 text-left font-semibold hidden md:table-cell">CÓDIGO</th>
                                        <th class="py-3 px-4 text-left font-semibold hidden lg:table-cell">ESTADO</th>
                                        <th class="py-3 px-4 text-center font-semibold">OPCIONES</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-if="servicios.length === 0">
                                        <tr>
                                            <td colspan="5" class="py-8 text-center text-gray-500">
                                                <div class="flex flex-col items-center">
                                                    <svg class="h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                                    </svg>
                                                    <p class="text-lg font-medium">No se encontraron servicios</p>
                                                    <p class="text-sm">Intenta con otros términos de búsqueda</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-for="(servicio, index) in servicios" :key="index">
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <span x-show="servicio.nivel === 'subservicio'" class="text-gray-500 mr-2">└─</span>
                                                    <span x-text="servicio.nombre"></span>
                                                    <!-- Indicador de subservicios -->
                                                    <template x-if="servicio.nivel === 'servicio'">
                                                        <span
                                                            x-data="{ count: 0, init() { fetch(`/servicios/${servicio.id}`, { headers: { 'Accept': 'application/json' } }) .then(r => r.json()) .then(data => { this.count = data.subservicios ? data.subservicios.length : 0; }); } }"
                                                            x-show="count > 0"
                                                            class="ml-2 px-1.5 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full"
                                                            x-text="`${count} subservicio${count > 1 ? 's' : ''}`"></span>
                                                    </template>
                                                </div>
                                                <div x-show="servicio.descripcion" class="text-xs text-gray-500 mt-1" x-text="servicio.descripcion?.substring(0, 50) + (servicio.descripcion?.length > 50 ? '...' : '')"></div>
                                                <div x-show="servicio.nivel === 'subservicio' && servicio.servicio_padre" class="text-xs text-blue-600 mt-1" x-text="servicio.servicio_padre?.nombre"></div>
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <span class="px-2 py-1 rounded text-sm"
                                                      :class="servicio.nivel === 'servicio' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                                                      x-text="servicio.nivel.charAt(0).toUpperCase() + servicio.nivel.slice(1)">
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap hidden md:table-cell text-sm text-gray-900" x-text="servicio.codigo || '-'"></td>
                                            <td class="py-3 px-4 whitespace-nowrap hidden lg:table-cell">
                                                <span class="px-2 py-1 rounded text-sm"
                                                      :class="servicio.estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                      x-text="servicio.estado.charAt(0).toUpperCase() + servicio.estado.slice(1)">
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <div class="flex justify-center space-x-2">
                                                    <button class="p-1 text-blue-600 hover:text-blue-800 transition-colors cursor-pointer"
                                                            title="Editar"
                                                            @click="editServicio(servicio.id)">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                        </svg>
                                                    </button>
                                                    <button class="p-1 text-red-600 hover:text-red-800 transition-colors cursor-pointer"
                                                            title="Eliminar"
                                                            @click="deleteServicio(servicio.id, servicio.nombre)">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    @if($servicios->hasPages())
                        <div class="mt-6">
                            {{ $servicios->links() }}
                        </div>
                    @endif
                </div>
@endsection

@section('scripts')

    <!-- Modal Editar Servicio -->
    <div x-data="{
            showEditModal: false,
            editingServicio: {
                id: null,
                nombre: '',
                codigo: '',
                nivel: 'servicio',
                servicio_padre_id: '',
                estado: 'activo',
                orden: '',
                descripcion: '',
                ocultar_turno: false
            },
            init() {
                this.$watch('showEditModal', value => {
                    if (!value) {
                        this.resetForm();
                    }
                });
            },
            resetForm() {
                this.editingServicio = {
                    id: null,
                    nombre: '',
                    codigo: '',
                    nivel: 'servicio',
                    servicio_padre_id: '',
                    estado: 'activo',
                    orden: '',
                    descripcion: '',
                    ocultar_turno: false
                };
            }
         }"
         x-show="showEditModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 modal-overlay z-50 flex items-center justify-center p-4"
         style="display: none;"
         @edit-servicio.window="showEditModal = true; editingServicio = $event.detail">
        <div @click.away="showEditModal = false" class="bg-white rounded-lg shadow-2xl w-full max-w-2xl overflow-y-auto max-h-[90vh]">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Editar Servicio</h3>
                    <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Mensaje de carga -->
                <div x-show="!editingServicio.id" class="text-center py-4">
                    <div class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-hospital-blue" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Cargando datos del servicio...
                    </div>
                </div>

                <form x-show="editingServicio.id" method="POST" :action="`/servicios/${editingServicio.id}`">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                            <input type="text" id="edit_nombre" name="nombre" required
                                   x-model="editingServicio.nombre"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                        </div>

                        <div>
                            <label for="edit_codigo" class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                            <input type="text" id="edit_codigo" name="codigo"
                                   x-model="editingServicio.codigo"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                        </div>

                        <div>
                            <label for="edit_nivel" class="block text-sm font-medium text-gray-700 mb-1">Nivel *</label>
                            <select id="edit_nivel" name="nivel" required x-model="editingServicio.nivel"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                                <option value="servicio">Servicio</option>
                                <option value="subservicio">Subservicio</option>
                            </select>
                        </div>

                        <div x-show="editingServicio.nivel === 'subservicio'">
                            <label for="edit_servicio_padre_id" class="block text-sm font-medium text-gray-700 mb-1">Servicio Padre *</label>
                            <select id="edit_servicio_padre_id" name="servicio_padre_id"
                                    x-model="editingServicio.servicio_padre_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                                <option value="">Seleccionar servicio padre</option>
                                @foreach($serviciosPrincipales as $servicioPadre)
                                    <option value="{{ $servicioPadre->id }}">{{ $servicioPadre->nombre }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">El subservicio estará asociado a este servicio principal.</p>
                        </div>

                        <div>
                            <label for="edit_estado" class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                            <select id="edit_estado" name="estado" required x-model="editingServicio.estado"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>

                        <div>
                            <label for="edit_orden" class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                            <input type="number" id="edit_orden" name="orden" min="0"
                                   x-model="editingServicio.orden"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Orden de aparición en menús y listas (opcional).</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="edit_ocultar_turno" name="ocultar_turno" value="1"
                                   x-bind:checked="editingServicio.ocultar_turno"
                                   class="h-4 w-4 text-hospital-blue focus:ring-hospital-blue border-gray-300 rounded">
                            <label for="edit_ocultar_turno" class="ml-2 block text-sm text-gray-700">
                                Ocultar turno
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Si está activado, los turnos de este servicio no se mostrarán en el TV ni se llamarán automáticamente.</p>
                    </div>

                    <div class="mt-4">
                        <label for="edit_descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea id="edit_descripcion" name="descripcion" rows="3"
                                  x-model="editingServicio.descripcion"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" @click="showEditModal = false"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-hospital-blue text-white rounded hover:bg-hospital-blue-hover transition-colors">
                            Actualizar Servicio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Servicio -->
    <div
        x-data="deleteModalData()"
        x-cloak
        @keydown.escape.window="showDeleteModal = false"
        @delete-servicio.window="showDeleteModal = true; deletingServicio = $event.detail"
    >
        <div
            x-show="showDeleteModal"
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
                @click.away="showDeleteModal = false"
                class="bg-white rounded-lg shadow-2xl w-full max-w-md"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
            >
                <div class="p-6">
                    <div class="mb-4">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="mt-3 text-lg font-medium text-center text-gray-900">¿Eliminar este servicio?</h3>
                        <p class="mt-2 text-sm text-center text-gray-500">
                            Estás a punto de eliminar el servicio <span class="font-medium" x-text="deletingServicio.nombre"></span>.<br>
                            Esta acción no se puede deshacer.
                        </p>
                    </div>

                    <div class="mt-6 flex justify-center space-x-4">
                        <button @click="showDeleteModal = false" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button @click="confirmDeleteServicio()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors cursor-pointer">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Error -->
    <div
        x-data="errorModalData()"
        x-cloak
        @keydown.escape.window="showErrorModal = false"
    >
        <div
            x-show="showErrorModal"
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
                @click.away="showErrorModal = false"
                class="bg-white rounded-lg shadow-2xl w-full max-w-md"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
            >
                <div class="p-6">
                    <div class="mb-4">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="mt-3 text-lg font-medium text-center text-gray-900" x-text="errorTitle"></h3>
                        <p class="mt-2 text-sm text-center text-gray-500" x-text="errorMessage"></p>
                    </div>

                    <div class="mt-6 flex justify-center">
                        <button @click="showErrorModal = false" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition-colors cursor-pointer">
                            Entendido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para el modal de error
        function errorModalData() {
            return {
                showErrorModal: false,
                errorTitle: '',
                errorMessage: '',
                init() {
                    // Escuchar evento global para mostrar errores
                    window.addEventListener('show-error', (event) => {
                        this.errorTitle = event.detail.title;
                        this.errorMessage = event.detail.message;
                        this.showErrorModal = true;
                    });
                }
            }
        }

        // Función para el modal de eliminación
        function deleteModalData() {
            return {
                showDeleteModal: false,
                deletingServicio: {
                    id: null,
                    nombre: ''
                },
                async confirmDeleteServicio() {
                    if (!this.deletingServicio.id) return;

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const response = await fetch(`/servicios/${this.deletingServicio.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            window.location.reload();
                        } else {
                            // Mostrar mensaje de error personalizado
                            if (response.status === 400 && result.message && result.message.includes('subservicios asociados')) {
                                this.showErrorMessage(
                                    'No se puede eliminar este servicio',
                                    'El servicio "' + this.deletingServicio.nombre + '" tiene subservicios asociados. Para eliminarlo, primero debe eliminar todos sus subservicios.'
                                );
                            } else {
                                this.showErrorMessage('Error', result.message || 'Error al eliminar el servicio');
                            }
                        }
                    } catch (error) {
                        console.error('Error al eliminar servicio:', error);
                        this.showErrorMessage('Error de conexión', 'No se pudo conectar con el servidor. Verifique su conexión a internet.');
                    } finally {
                        this.showDeleteModal = false;
                    }
                },
                showErrorMessage(title, message) {
                    // Disparar evento global para mostrar error
                    window.dispatchEvent(new CustomEvent('show-error', {
                        detail: { title, message }
                    }));
                }
            }
        }

        // Configurar CSRF token para peticiones AJAX
        document.addEventListener('DOMContentLoaded', function() {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Configurar headers por defecto para fetch solo para peticiones JSON
            const originalFetch = window.fetch;
            window.fetch = function(resource, config = {}) {
                if (config.method && config.method !== 'GET' && config.headers && config.headers['Content-Type'] === 'application/json') {
                    config.headers = {
                        ...config.headers,
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    };
                }
                return originalFetch.apply(this, arguments);
            };
        });

        // Manejar envío del formulario de crear servicio
        document.getElementById('createServicioForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Debug: mostrar los datos que se van a enviar
            console.log('Datos del formulario:');
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Error response:', errorText);

                    try {
                        const errorJson = JSON.parse(errorText);
                        if (errorJson.errors) {
                            // Manejar errores de validación específicos
                            let errorTitle = 'Error de validación';
                            let errorMessage = '';

                            for (const [field, messages] of Object.entries(errorJson.errors)) {
                                if (field === 'codigo' && messages.includes('The codigo has already been taken.')) {
                                    errorTitle = 'Código duplicado';
                                    errorMessage = 'Ya existe un servicio con este código. Por favor, utiliza un código diferente.';
                                } else if (field === 'nombre' && messages.some(msg => msg.includes('has already been taken'))) {
                                    errorTitle = 'Nombre duplicado';
                                    errorMessage = 'Ya existe un servicio con este nombre. Por favor, utiliza un nombre diferente.';
                                } else {
                                    // Para otros errores de validación
                                    const fieldNames = {
                                        'nombre': 'Nombre',
                                        'codigo': 'Código',
                                        'nivel': 'Nivel',
                                        'servicio_padre_id': 'Servicio Padre',
                                        'estado': 'Estado',
                                        'orden': 'Orden',
                                        'descripcion': 'Descripción'
                                    };
                                    const fieldName = fieldNames[field] || field;
                                    errorMessage += `${fieldName}: ${messages.join(', ')}\n`;
                                }
                            }

                            if (!errorMessage) {
                                errorMessage = 'Por favor, revisa los datos ingresados.';
                            }

                            // Disparar evento para mostrar modal de error
                            window.dispatchEvent(new CustomEvent('show-error', {
                                detail: { title: errorTitle, message: errorMessage }
                            }));
                        } else {
                            // Error general
                            window.dispatchEvent(new CustomEvent('show-error', {
                                detail: {
                                    title: 'Error al crear servicio',
                                    message: errorJson.message || 'Error desconocido'
                                }
                            }));
                        }
                    } catch (parseError) {
                        window.dispatchEvent(new CustomEvent('show-error', {
                            detail: {
                                title: 'Error de conexión',
                                message: 'Error al crear el servicio. Código: ' + response.status
                            }
                        }));
                    }
                    return;
                }

                const result = await response.json();

                if (result.success) {
                    window.location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('show-error', {
                        detail: {
                            title: 'Error al crear servicio',
                            message: result.message || 'Error desconocido'
                        }
                    }));
                }
            } catch (error) {
                console.error('Error completo:', error);
                window.dispatchEvent(new CustomEvent('show-error', {
                    detail: {
                        title: 'Error de conexión',
                        message: 'No se pudo conectar con el servidor. Verifique su conexión a internet.'
                    }
                }));
            }
        });
    </script>
@endsection
