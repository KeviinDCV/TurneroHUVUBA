@extends('layouts.admin')

@section('title', 'Gestión de Cajas')

@section('content')

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-7xl mx-auto">
                <div class="flex justify-between items-center mb-6" x-data="{ openModal: false }">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-hospital-blue">Administración</p>
                        <h1 class="text-2xl font-bold text-gray-900 mt-1">Gestión de Cajas</h1>
                    </div>
                    <button @click="openModal = true" class="inline-flex items-center gap-2 bg-hospital-blue text-white px-4 py-2 rounded-lg hover:bg-hospital-blue-hover transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Nueva Caja
                    </button>

                    <!-- Modal para crear caja -->
                    <div
                        x-show="openModal"
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
                            @click.away="openModal = false"
                            class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-y-auto max-h-[90vh]"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 transform scale-100"
                            x-transition:leave-end="opacity-0 transform scale-95"
                        >
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-xl font-bold text-gray-800">Crear Nueva Caja</h2>
                                    <button @click="openModal = false" class="text-gray-500 hover:text-gray-700 cursor-pointer">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>

                                @if ($errors->any())
                                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                                    <div class="font-bold">Por favor corrige los siguientes errores:</div>
                                    <ul class="list-disc ml-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif

                                <form action="{{ route('admin.cajas.store') }}" method="POST">
                                    @csrf

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Nombre -->
                                        <div>
                                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Caja</label>
                                            <input
                                                type="text"
                                                id="nombre"
                                                name="nombre"
                                                value="{{ old('nombre') }}"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                                                placeholder="Ej: Caja Principal"
                                                required
                                            >
                                        </div>

                                        <!-- Número de Caja -->
                                        <div>
                                            <label for="numero_caja" class="block text-sm font-medium text-gray-700 mb-1">Número de Caja</label>
                                            <input
                                                type="number"
                                                id="numero_caja"
                                                name="numero_caja"
                                                value="{{ old('numero_caja') }}"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                                                placeholder="1"
                                                min="1"
                                                required
                                            >
                                        </div>

                                        <!-- Ubicación -->
                                        <div>
                                            <label for="ubicacion" class="block text-sm font-medium text-gray-700 mb-1">Ubicación</label>
                                            <input
                                                type="text"
                                                id="ubicacion"
                                                name="ubicacion"
                                                value="{{ old('ubicacion') }}"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                                                placeholder="Ej: Primer piso - Área de facturación"
                                            >
                                        </div>

                                        <!-- Estado -->
                                        <div>
                                            <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                            <select
                                                id="estado"
                                                name="estado"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                                                required
                                            >
                                                <option value="activa" {{ old('estado') === 'activa' ? 'selected' : '' }}>Activa</option>
                                                <option value="inactiva" {{ old('estado') === 'inactiva' ? 'selected' : '' }}>Inactiva</option>
                                            </select>
                                        </div>

                                        <!-- Descripción -->
                                        <div class="md:col-span-2">
                                            <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                            <textarea
                                                id="descripcion"
                                                name="descripcion"
                                                rows="3"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                                                placeholder="Descripción opcional de la caja..."
                                            >{{ old('descripcion') }}</textarea>
                                        </div>
                                    </div>

                                    <div class="mt-8 flex justify-end space-x-3">
                                        <button type="button" @click="openModal = false" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors cursor-pointer">
                                            Cancelar
                                        </button>
                                        <button type="submit" class="bg-hospital-blue text-white px-6 py-2 rounded-lg hover:bg-hospital-blue-hover transition-colors cursor-pointer">
                                            Guardar Caja
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                @if (session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    {{ session('success') }}
                </div>
                @endif

                <!-- Aplicación Alpine.js para búsqueda en tiempo real y modal -->
                <div x-data="{
                    search: '{{ $search ?? '' }}',
                    cajas: {{ json_encode($cajas->items()) }},
                    allCajas: {{ json_encode($cajas->items()) }},

                    init() {
                        this.$watch('search', value => {
                            if (value === '') {
                                this.cajas = this.allCajas;
                                return;
                            }

                            value = value.toLowerCase();
                            this.cajas = this.allCajas.filter(caja => {
                                return caja.nombre.toLowerCase().includes(value) ||
                                       caja.descripcion?.toLowerCase().includes(value) ||
                                       caja.ubicacion?.toLowerCase().includes(value) ||
                                       caja.numero_caja.toString().includes(value) ||
                                       caja.estado.toLowerCase().includes(value);
                            });
                        });

                        // Abrir modal automáticamente si hay errores de validación
                        @if($errors->any())
                            this.$nextTick(() => {
                                this.$dispatch('open-modal');
                            });
                        @endif
                    }
                }">
                    <!-- Buscador -->
                    <div class="mb-6">
                        <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden shadow-sm search-container">
                            <div class="px-3 py-2 bg-gray-50">
                                <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input
                                type="text"
                                x-model="search"
                                placeholder="Buscar por nombre, número, ubicación, estado o descripción..."
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

                    <!-- Tabla de Cajas -->
                    <div class="overflow-x-auto flex justify-center">
                        <table class="w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
                            <thead>
                                <tr class="bg-[#f6f8fc] text-gray-500 border-b border-gray-200">
                                    <th class="py-3 px-4 text-left font-semibold">NÚMERO</th>
                                    <th class="py-3 px-4 text-left font-semibold">NOMBRE</th>
                                    <th class="py-3 px-4 text-left font-semibold">UBICACIÓN</th>
                                    <th class="py-3 px-4 text-left font-semibold">ESTADO</th>
                                    <th class="py-3 px-4 text-left font-semibold">DESCRIPCIÓN</th>
                                    <th class="py-3 px-4 text-center font-semibold">OPCIONES</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <template x-if="cajas.length === 0">
                                    <tr>
                                        <td colspan="6" class="py-4 text-center text-gray-500">
                                            No se encontraron cajas.
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="(caja, index) in cajas" :key="index">
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4 whitespace-nowrap font-medium" x-text="caja.numero_caja"></td>
                                        <td class="py-3 px-4 whitespace-nowrap" x-text="caja.nombre"></td>
                                        <td class="py-3 px-4 whitespace-nowrap" x-text="caja.ubicacion || '-'"></td>
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <span
                                                class="px-2 py-1 rounded text-sm"
                                                :class="caja.estado === 'activa' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                x-text="caja.estado === 'activa' ? 'Activa' : 'Inactiva'">
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 max-w-xs truncate" x-text="caja.descripcion || '-'"></td>
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <div class="flex justify-center space-x-2">
                                                <button class="p-1 text-blue-600 hover:text-blue-800 transition-colors cursor-pointer"
                                                        title="Editar"
                                                        @click="$store.modals.editCaja.openModal(caja.id)">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                    </svg>
                                                </button>
                                                <button class="p-1 text-red-600 hover:text-red-800 transition-colors cursor-pointer"
                                                        title="Eliminar"
                                                        @click="$store.modals.deleteCaja.openModal(caja.id, caja.nombre)">
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

                    <!-- Paginación -->
                    <div class="mt-4">
                        <form id="searchForm" action="{{ route('admin.cajas') }}" method="GET" class="hidden">
                            <input type="text" name="search" :value="search">
                        </form>
                        {{ $cajas->withQueryString()->links() }}
                    </div>
                </div>
            </div>

    <!-- Modal para Editar Caja -->
    <div
        x-data="editCajaModal()"
        x-cloak
        @keydown.escape.window="isOpen = false"
    >
        <div
            x-show="isOpen"
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
                @click.away="isOpen = false"
                class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-y-auto max-h-[90vh]"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
            >
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Editar Caja</h2>
                        <button @click="isOpen = false" class="text-gray-500 hover:text-gray-700 cursor-pointer">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Indicador de Carga -->
                    <div x-show="loading" class="flex justify-center items-center py-4">
                        <svg class="animate-spin h-8 w-8 text-hospital-blue" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Errores de Validación -->
                    <div x-show="Object.keys(errors).length > 0" class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <div class="font-bold">Por favor corrige los siguientes errores:</div>
                        <ul class="list-disc ml-5">
                            <template x-for="(messages, field) in errors" :key="field">
                                <template x-for="(message, i) in messages" :key="i">
                                    <li x-text="message"></li>
                                </template>
                            </template>
                        </ul>
                    </div>

                    <!-- Formulario -->
                    <div x-show="!loading" class="mt-4">
                        <form @submit.prevent="submitForm">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Nombre -->
                                <div>
                                    <label for="edit_nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Caja</label>
                                    <input
                                        type="text"
                                        id="edit_nombre"
                                        x-model="cajaData.nombre"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none"
                                        required
                                    >
                                </div>

                                <!-- Número de Caja -->
                                <div>
                                    <label for="edit_numero_caja" class="block text-sm font-medium text-gray-700 mb-1">Número de Caja</label>
                                    <input
                                        type="number"
                                        id="edit_numero_caja"
                                        x-model="cajaData.numero_caja"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none"
                                        min="1"
                                        required
                                    >
                                </div>

                                <!-- Ubicación -->
                                <div>
                                    <label for="edit_ubicacion" class="block text-sm font-medium text-gray-700 mb-1">Ubicación</label>
                                    <input
                                        type="text"
                                        id="edit_ubicacion"
                                        x-model="cajaData.ubicacion"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none"
                                    >
                                </div>

                                <!-- Estado -->
                                <div>
                                    <label for="edit_estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                    <select
                                        id="edit_estado"
                                        x-model="cajaData.estado"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none"
                                        required
                                    >
                                        <option value="activa">Activa</option>
                                        <option value="inactiva">Inactiva</option>
                                    </select>
                                </div>

                                <!-- Descripción -->
                                <div class="md:col-span-2">
                                    <label for="edit_descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                    <textarea
                                        id="edit_descripcion"
                                        x-model="cajaData.descripcion"
                                        rows="3"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none"
                                    ></textarea>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end space-x-3">
                                <button type="button" @click="isOpen = false" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors cursor-pointer">
                                    Cancelar
                                </button>
                                <button type="submit" :disabled="loading" class="bg-hospital-blue text-white px-6 py-2 rounded-lg hover:bg-hospital-blue-hover transition-colors cursor-pointer disabled:opacity-50">
                                    <span x-show="!loading">Guardar Cambios</span>
                                    <span x-show="loading">Guardando...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Eliminar Caja -->
    <div
        x-data="deleteCajaModal()"
        x-cloak
        @keydown.escape.window="isOpen = false"
    >
        <div
            x-show="isOpen"
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
                @click.away="isOpen = false"
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
                        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="mt-3 text-lg font-medium text-center text-gray-900">¿Eliminar esta caja?</h3>
                        <p class="mt-2 text-sm text-center text-gray-500">
                            Estás a punto de eliminar la caja <span class="font-medium" x-text="cajaNombre"></span>.<br>
                            Esta acción no se puede deshacer.
                        </p>
                    </div>

                    <div class="mt-6 flex justify-center space-x-4">
                        <button @click="isOpen = false" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button @click="deleteCaja()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors cursor-pointer">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts de Alpine.js -->
    <script>
        // Store global para los modales
        document.addEventListener('alpine:init', () => {
            Alpine.store('modals', {
                editCaja: {
                    openModal(cajaId) {
                        // Buscar la caja en los datos
                        const cajaData = @json($cajas->items());
                        const caja = cajaData.find(c => c.id === cajaId);

                        if (caja) {
                            // Disparar evento para abrir modal de edición
                            window.dispatchEvent(new CustomEvent('open-edit-caja-modal', {
                                detail: { caja }
                            }));
                        }
                    }
                },
                deleteCaja: {
                    openModal(cajaId, cajaNombre) {
                        // Disparar evento para abrir modal de eliminación
                        window.dispatchEvent(new CustomEvent('open-delete-caja-modal', {
                            detail: { cajaId, cajaNombre }
                        }));
                    }
                }
            });
        });

        // Componente para el modal de edición
        function editCajaModal() {
            return {
                isOpen: false,
                loading: false,
                errors: {},
                cajaId: null,
                cajaData: {
                    nombre: '',
                    numero_caja: '',
                    ubicacion: '',
                    estado: 'activa',
                    descripcion: ''
                },

                init() {
                    // Escuchar evento para abrir modal
                    window.addEventListener('open-edit-caja-modal', (event) => {
                        const { caja } = event.detail;
                        this.cajaId = caja.id;
                        this.cajaData = { ...caja };
                        this.errors = {};
                        this.isOpen = true;
                    });
                },

                submitForm() {
                    // Limpiar errores previos
                    this.errors = {};
                    this.loading = true;

                    // Crear FormData
                    const formData = new FormData();
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    // Añadir los campos al FormData
                    formData.append('nombre', this.cajaData.nombre);
                    formData.append('numero_caja', this.cajaData.numero_caja);
                    formData.append('ubicacion', this.cajaData.ubicacion || '');
                    formData.append('estado', this.cajaData.estado);
                    formData.append('descripcion', this.cajaData.descripcion || '');
                    formData.append('_method', 'PUT');

                    // Enviar la petición
                    fetch(`/cajas/${this.cajaId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        this.loading = false;
                        if (!response.ok) {
                            return response.json().then(err => {
                                throw err;
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            this.isOpen = false;
                            window.location.reload();
                        } else {
                            console.error('Error en la respuesta:', data);
                        }
                    })
                    .catch(error => {
                        this.loading = false;
                        console.error('Error completo:', error);
                        if (error.errors) {
                            this.errors = error.errors;
                        } else if (error.message) {
                            this.errors = { general: [error.message] };
                        } else {
                            this.errors = { general: ['Error al actualizar la caja'] };
                        }
                    });
                }
            }
        }

        // Componente para el modal de eliminación
        function deleteCajaModal() {
            return {
                isOpen: false,
                cajaId: null,
                cajaNombre: '',

                init() {
                    // Escuchar evento para abrir modal
                    window.addEventListener('open-delete-caja-modal', (event) => {
                        const { cajaId, cajaNombre } = event.detail;
                        this.cajaId = cajaId;
                        this.cajaNombre = cajaNombre;
                        this.isOpen = true;
                    });
                },

                deleteCaja() {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    fetch(`/cajas/${this.cajaId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => {
                        if (response.ok) {
                            return response.json();
                        } else {
                            throw new Error('Error al eliminar caja');
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'Error al eliminar caja');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al eliminar caja');
                    });
                }
            }
        }
    </script>
@endsection
