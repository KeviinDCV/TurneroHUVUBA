@extends('layouts.admin')

@section('title', 'Gestión de Usuarios')

@section('styles')
    <!-- Para el modal -->
    <style>
        /* Eliminar anillo de resaltado en inputs */
        input:focus, select:focus {
            outline: none !important;
            box-shadow: none !important;
            ring: 0 !important;
            --tw-ring-offset-width: 0 !important;
            --tw-ring-offset-color: transparent !important;
            --tw-ring-color: transparent !important;
            --tw-ring-offset-shadow: none !important;
            --tw-ring-shadow: none !important;
        }

        /* Estilo natural para inputs */
        input, select {
            transition: border-color 0.2s ease-in-out;
        }

        input:focus, select:focus {
            border-color: #064b9e !important;
        }

        /* Estilos para el campo de búsqueda */
        .search-container {
            transition: border-color 0.2s ease-in-out;
        }

        .search-container:focus-within {
            border-color: #064b9e !important;
            box-shadow: none !important;
            outline: none !important;
        }
    </style>
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow-md p-6 max-w-7xl mx-auto">
                @if (session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    {{ session('success') }}
                </div>
                @endif

                <!-- Aplicación Alpine.js para búsqueda en tiempo real y modal -->
                <div x-data="{
                    search: '{{ $search ?? '' }}',
                    users: {{ json_encode($users->items()) }},
                    allUsers: {{ json_encode($users->items()) }},
                    openModal: {{ $errors->any() ? 'true' : 'false' }}, // Mantener modal abierto si hay errores

                    init() {
                        this.$watch('search', value => {
                            if (value === '') {
                                this.users = this.allUsers;
                                return;
                            }

                            value = value.toLowerCase();
                            this.users = this.allUsers.filter(user => {
                                return user.nombre_completo.toLowerCase().includes(value) ||
                                       user.cedula.toLowerCase().includes(value) ||
                                       user.correo_electronico.toLowerCase().includes(value) ||
                                       user.nombre_usuario.toLowerCase().includes(value) ||
                                       user.rol.toLowerCase().includes(value);
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
                    <!-- Header con título y botón -->
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">Gestión de Usuarios</h1>
                        <button @click="openModal = true" class="bg-hospital-blue text-white px-4 py-2 rounded hover:bg-hospital-blue-hover transition-colors cursor-pointer">
                            Nuevo Usuario
                        </button>
                    </div>

                    <!-- Modal para crear usuario -->
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
                            class="bg-white rounded-lg shadow-2xl w-full max-w-3xl overflow-y-auto max-h-[90vh]"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 transform scale-100"
                            x-transition:leave-end="opacity-0 transform scale-95"
                        >
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-xl font-bold text-gray-800">Crear Nuevo Usuario</h2>
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

                                <form action="{{ route('admin.users.store') }}" method="POST">
                                    @csrf

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Nombre Completo -->
                                        <div>
                                            <label for="nombre_completo" class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
                                            <input
                                                type="text"
                                                id="nombre_completo"
                                                name="nombre_completo"
                                                value="{{ old('nombre_completo') }}"
                                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                                                required
                                            >
                                        </div>

                                        <!-- Cédula -->
                                        <div>
                                            <label for="cedula" class="block text-sm font-medium text-gray-700 mb-1">Cédula</label>
                                            <input
                                                type="text"
                                                id="cedula"
                                                name="cedula"
                                                value="{{ old('cedula') }}"
                                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                                            >
                                        </div>

                                        <!-- Correo Electrónico -->
                                        <div>
                                            <label for="correo_electronico" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                                            <input
                                                type="text"
                                                id="correo_electronico"
                                                name="correo_electronico"
                                                value="{{ old('correo_electronico') }}"
                                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                                            >
                                        </div>

                                        <!-- Nombre de Usuario -->
                                        <div>
                                            <label for="nombre_usuario" class="block text-sm font-medium text-gray-700 mb-1">Nombre de Usuario</label>
                                            <input
                                                type="text"
                                                id="nombre_usuario"
                                                name="nombre_usuario"
                                                value="{{ old('nombre_usuario') }}"
                                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                                                required
                                            >
                                        </div>

                                        <!-- Rol -->
                                        <div>
                                            <label for="rol" class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                                            <select
                                                id="rol"
                                                name="rol"
                                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                                                required
                                            >
                                                <option value="">Seleccionar rol</option>
                                                <option value="Administrador" {{ old('rol') === 'Administrador' ? 'selected' : '' }}>Administrador</option>
                                                <option value="Asesor" {{ old('rol') === 'Asesor' ? 'selected' : '' }}>Asesor</option>
                                            </select>
                                        </div>

                                        <!-- Contraseña -->
                                        <div>
                                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                                            <input
                                                type="password"
                                                id="password"
                                                name="password"
                                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                                            >
                                        </div>

                                        <!-- Confirmar Contraseña -->
                                        <div>
                                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña</label>
                                            <input
                                                type="password"
                                                id="password_confirmation"
                                                name="password_confirmation"
                                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                                            >
                                        </div>
                                    </div>

                                    <div class="mt-8 flex justify-end space-x-3">
                                        <button type="button" @click="openModal = false" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition-colors cursor-pointer">
                                            Cancelar
                                        </button>
                                        <button type="submit" class="bg-hospital-blue text-white px-6 py-2 rounded hover:bg-hospital-blue-hover transition-colors cursor-pointer">
                                            Guardar Usuario
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

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
                                placeholder="Buscar por nombre, cédula, correo, usuario o rol..."
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

                    <!-- Tabla de Usuarios -->
                    <div class="overflow-x-auto flex justify-center">
                        <table class="w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
                            <thead>
                                <tr class="bg-hospital-blue text-white">
                                    <th class="py-3 px-4 text-left font-semibold">NOMBRE</th>
                                    <th class="py-3 px-4 text-left font-semibold">CÉDULA</th>
                                    <th class="py-3 px-4 text-left font-semibold">CORREO</th>
                                    <th class="py-3 px-4 text-left font-semibold">USUARIO</th>
                                    <th class="py-3 px-4 text-left font-semibold">ROL</th>
                                    <th class="py-3 px-4 text-center font-semibold">OPCIONES</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <template x-if="users.length === 0">
                                    <tr>
                                        <td colspan="6" class="py-4 text-center text-gray-500">
                                            No se encontraron usuarios.
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="(user, index) in users" :key="index">
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4 whitespace-nowrap" x-text="user.nombre_completo"></td>
                                        <td class="py-3 px-4 whitespace-nowrap" x-text="user.cedula"></td>
                                        <td class="py-3 px-4 whitespace-nowrap" x-text="user.correo_electronico"></td>
                                        <td class="py-3 px-4 whitespace-nowrap" x-text="user.nombre_usuario"></td>
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <span
                                                class="px-2 py-1 rounded text-sm"
                                                :class="user.rol === 'Administrador' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                                                x-text="user.rol">
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <div class="flex justify-center space-x-2">
                                                <button class="p-1 text-blue-600 hover:text-blue-800 transition-colors cursor-pointer"
                                                        title="Editar"
                                                        @click="$store.modals.editUser.openModal(user.id)">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                    </svg>
                                                </button>
                                                <button class="p-1 text-red-600 hover:text-red-800 transition-colors cursor-pointer"
                                                        title="Eliminar"
                                                        @click="$store.modals.deleteUser.openModal(user.id, user.nombre_completo)">
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

                    <!-- Para paginación completa, necesitaremos implementar una solución del lado del servidor -->
                    <div class="mt-4">
                        <form id="searchForm" action="{{ route('admin.users') }}" method="GET" class="hidden">
                            <input type="text" name="search" :value="search">
                        </form>
                        {{ $users->withQueryString()->links() }}
                    </div>
                </div>
            </div>
            </div>
        </main>
    </div>

    <!-- Modal para Editar Usuario -->
    <div
        x-data="editUserModal()"
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
                class="bg-white rounded-lg shadow-2xl w-full max-w-3xl overflow-y-auto max-h-[90vh]"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
            >
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Editar Usuario</h2>
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
                                <!-- Nombre Completo -->
                                <div>
                                    <label for="edit_nombre_completo" class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
                                    <input
                                        type="text"
                                        id="edit_nombre_completo"
                                        x-model="userData.nombre_completo"
                                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none"
                                        required
                                    >
                                </div>

                                <!-- Cédula -->
                                <div>
                                    <label for="edit_cedula" class="block text-sm font-medium text-gray-700 mb-1">Cédula</label>
                                    <input
                                        type="text"
                                        id="edit_cedula"
                                        x-model="userData.cedula"
                                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none"
                                    >
                                </div>

                                <!-- Correo Electrónico -->
                                <div>
                                    <label for="edit_correo_electronico" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                                    <input
                                        type="text"
                                        id="edit_correo_electronico"
                                        x-model="userData.correo_electronico"
                                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none"
                                    >
                                </div>

                                <!-- Nombre de Usuario -->
                                <div>
                                    <label for="edit_nombre_usuario" class="block text-sm font-medium text-gray-700 mb-1">Nombre de Usuario</label>
                                    <input
                                        type="text"
                                        id="edit_nombre_usuario"
                                        x-model="userData.nombre_usuario"
                                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none"
                                        required
                                    >
                                </div>

                                <!-- Rol -->
                                <div>
                                    <label for="edit_rol" class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                                    <select
                                        id="edit_rol"
                                        x-model="userData.rol"
                                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none"
                                        required
                                    >
                                        <option value="">Seleccionar rol</option>
                                        <option value="Administrador">Administrador</option>
                                        <option value="Asesor">Asesor</option>
                                    </select>
                                </div>

                                <!-- Cambiar Contraseña -->
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" x-model="showPassword" class="form-checkbox h-4 w-4 text-hospital-blue">
                                        <span class="ml-2 text-sm text-gray-700">Cambiar Contraseña</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Campos de contraseña (condicionales) -->
                            <div x-show="showPassword" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Contraseña -->
                                <div>
                                    <label for="edit_password" class="block text-sm font-medium text-gray-700 mb-1">Nueva Contraseña</label>
                                    <input
                                        type="password"
                                        id="edit_password"
                                        x-model="userData.password"
                                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none"
                                    >
                                </div>

                                <!-- Confirmar Contraseña -->
                                <div>
                                    <label for="edit_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña</label>
                                    <input
                                        type="password"
                                        id="edit_password_confirmation"
                                        x-model="userData.password_confirmation"
                                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none"
                                    >
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end space-x-3">
                                <button type="button" @click="isOpen = false" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition-colors cursor-pointer">
                                    Cancelar
                                </button>
                                <button type="submit" class="bg-hospital-blue text-white px-6 py-2 rounded hover:bg-hospital-blue-hover transition-colors cursor-pointer">
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Confirmar Eliminación -->
    <div
        x-data="deleteUserModal()"
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
                        <h3 class="mt-3 text-lg font-medium text-center text-gray-900">¿Eliminar este usuario?</h3>
                        <p class="mt-2 text-sm text-center text-gray-500">
                            Estás a punto de eliminar al usuario <span class="font-medium" x-text="userName"></span>.<br>
                            Esta acción no se puede deshacer.
                        </p>
                    </div>

                    <div class="mt-6 flex justify-center space-x-4">
                        <button @click="isOpen = false" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button @click="deleteUser()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors cursor-pointer">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts para los modales -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('modals', {
                editUser: {
                    isOpen: false,
                    userId: null,
                    openModal(userId) {
                        this.userId = userId;
                        this.isOpen = true;
                    }
                },
                deleteUser: {
                    isOpen: false,
                    userId: null,
                    userName: '',
                    openModal(userId, userName) {
                        this.userId = userId;
                        this.userName = userName;
                        this.isOpen = true;
                    }
                }
            });

            Alpine.data('editUserModal', () => ({
                isOpen: false,
                userId: null,
                userData: {
                    nombre_completo: '',
                    cedula: '',
                    correo_electronico: '',
                    nombre_usuario: '',
                    rol: '',
                    password: '',
                    password_confirmation: ''
                },
                showPassword: false,
                loading: false,
                errors: {},

                init() {
                    this.$watch('$store.modals.editUser.isOpen', value => {
                        if (value) {
                            this.userId = Alpine.store('modals').editUser.userId;
                            this.isOpen = true;
                            this.loadUserData();
                        } else {
                            this.isOpen = false;
                        }
                    });

                    this.$watch('isOpen', value => {
                        if (!value) {
                            this.resetForm();
                            Alpine.store('modals').editUser.isOpen = false;
                        }
                    });
                },

                resetForm() {
                    this.userId = null;
                    this.userData = {
                        nombre_completo: '',
                        cedula: '',
                        correo_electronico: '',
                        nombre_usuario: '',
                        rol: '',
                        password: '',
                        password_confirmation: ''
                    };
                    this.showPassword = false;
                    this.errors = {};
                },

                loadUserData() {
                    this.loading = true;
                    fetch(`/admin/usuarios/${this.userId}`)
                        .then(response => response.json())
                        .then(data => {
                            this.userData = {
                                nombre_completo: data.nombre_completo,
                                cedula: data.cedula,
                                correo_electronico: data.correo_electronico,
                                nombre_usuario: data.nombre_usuario,
                                rol: data.rol,
                                password: '',
                                password_confirmation: ''
                            };
                            this.loading = false;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            this.loading = false;
                        });
                },

                submitForm() {
                    // Crear un nuevo FormData y token CSRF
                    const formData = new FormData();
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    // Añadir los campos al FormData
                    formData.append('nombre_completo', this.userData.nombre_completo);
                    formData.append('cedula', this.userData.cedula);
                    formData.append('correo_electronico', this.userData.correo_electronico);
                    formData.append('nombre_usuario', this.userData.nombre_usuario);
                    formData.append('rol', this.userData.rol);

                    // Añadir contraseña solo si se ha cambiado
                    if (this.userData.password) {
                        formData.append('password', this.userData.password);
                        formData.append('password_confirmation', this.userData.password_confirmation);
                    }

                    // Añadir el método PUT para Laravel
                    formData.append('_method', 'PUT');

                    // Enviar la petición
                    fetch(`/admin/usuarios/${this.userId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                throw err;
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        this.isOpen = false;
                        // Recargar la página para mostrar la actualización
                        window.location.reload();
                    })
                    .catch(error => {
                        if (error.errors) {
                            this.errors = error.errors;
                        }
                    });
                }
            }));

            Alpine.data('deleteUserModal', () => ({
                isOpen: false,
                userId: null,
                userName: '',

                init() {
                    this.$watch('$store.modals.deleteUser.isOpen', value => {
                        if (value) {
                            this.userId = Alpine.store('modals').deleteUser.userId;
                            this.userName = Alpine.store('modals').deleteUser.userName;
                            this.isOpen = true;
                        } else {
                            this.isOpen = false;
                        }
                    });

                    this.$watch('isOpen', value => {
                        if (!value) {
                            Alpine.store('modals').deleteUser.isOpen = false;
                        }
                    });
                },

                deleteUser() {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    // Verificar que tenemos el userId
                    if (!this.userId) {
                        console.error('Error: userId is null or undefined');
                        return;
                    }

                    console.log('Deleting user with ID:', this.userId);

                    fetch(`/admin/usuarios/${this.userId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json().then(data => {
                            if (response.ok) {
                                // Cerrar el modal
                                this.isOpen = false;
                                // Recargar la página para mostrar los cambios
                                window.location.reload();
                            } else {
                                console.error('Error response:', response.status, data.message);
                                alert('Error: ' + (data.message || 'No se pudo eliminar el usuario'));
                            }
                        });
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        alert('Error de conexión. Inténtalo de nuevo.');
                    });
                }
            }));
        });
    </script>

@endsection