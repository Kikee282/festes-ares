<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    tickets: Array,
    anioSeleccionado: Number,
    aniosDisponibles: Array,
});

// Formulario para subir imagen
const form = useForm({
    imagen: null,
});

// Estado para el modal del visor de fotos
const ticketSeleccionado = ref(null);

const abrirModal = (ticket) => {
    ticketSeleccionado.value = ticket;
};

const cerrarModal = () => {
    ticketSeleccionado.value = null;
};

const submit = () => {
    form.post(route('tickets.store'), {
        onSuccess: () => {
            form.reset('imagen');
            const fileInput = document.getElementById('input-imagen');
            if (fileInput) fileInput.value = '';
        },
    });
};

// Cambiar filtro por año
const cambiarAnio = (e) => {
    router.get(route('tickets.index'), { anio: e.target.value }, { preserveState: true });
};

// Eliminar ticket
const eliminarTicket = (id) => {
    if (confirm('¿Estás seguro de que deseas borrar esta imagen de ticket?')) {
        router.delete(route('tickets.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                if (ticketSeleccionado.value?.id === id) {
                    cerrarModal();
                }
            }
        });
    }
};
</script>

<template>
    <Head title="Tickets y Comprobantes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Galería de Tickets de Compra
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- SUBIDA DE TICKETS -->
                <div class="p-6 bg-white shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Subir nuevo Ticket / Factura</h3>

                    <form @submit.prevent="submit" class="flex flex-col sm:flex-row items-end gap-4">
                        <div class="flex-1 w-full">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Seleccionar foto (JPG, PNG, WEBP)</label>
                            <input 
                                id="input-imagen"
                                type="file" 
                                @input="form.imagen = $event.target.files[0]"
                                accept="image/*"
                                required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded-md cursor-pointer"
                            />
                            <span v-if="form.errors.imagen" class="text-xs text-red-500 mt-1 block">{{ form.errors.imagen }}</span>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            style="background-color: #4f46e5; color: #ffffff;"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:opacity-90 active:opacity-100 transition ease-in-out duration-150 cursor-pointer h-10"
                        >
                            Subir Foto
                        </button>
                    </form>
                </div>

                <!-- GALERÍA DE IMÁGENES TIPO GOOGLE DRIVE -->
                <div class="p-6 bg-white shadow sm:rounded-lg">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                        <h3 class="text-lg font-medium text-gray-900">Tickets Guardados</h3>

                        <!-- Filtro por año -->
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700">Año:</label>
                            <select :value="anioSeleccionado" @change="cambiarAnio" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option v-for="a in aniosDisponibles" :key="a" :value="a">{{ a }}</option>
                                <option v-if="!aniosDisponibles.includes(new Date().getFullYear())" :value="new Date().getFullYear()">{{ new Date().getFullYear() }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- CUADRÍCULA PEQUEÑA Y COMPACTA -->
                    <div v-if="tickets.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <div 
                            v-for="ticket in tickets" 
                            :key="ticket.id" 
                            class="group relative border rounded-lg overflow-hidden bg-white shadow-sm hover:shadow-md transition cursor-pointer border-gray-200"
                            @click="abrirModal(ticket)"
                        >
                            <!-- Miniatura pequeña tipo Drive -->
                            <div class="w-full h-28 bg-gray-100 overflow-hidden flex items-center justify-center relative">
                                <img 
                                    :src="ticket.ruta_archivo" 
                                    :alt="ticket.nombre_original" 
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-200" 
                                />
                                <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                    <span class="text-xs bg-black/70 text-white px-2 py-1 rounded shadow">Ver foto</span>
                                </div>
                            </div>

                            <!-- Nombre del archivo -->
                            <div class="p-2 bg-white flex items-center justify-between border-t border-gray-100">
                                <span class="text-xs text-gray-700 truncate font-medium w-full" :title="ticket.nombre_original">
                                    📄 {{ ticket.nombre_original }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        No hay imágenes de tickets subidas para el año {{ anioSeleccionado }}.
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL / LIGHTBOX PARA AMPLIAR LA IMAGEN -->
        <div 
            v-if="ticketSeleccionado" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 transition-opacity"
            @click.self="cerrarModal"
        >
            <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden">
                <!-- Cabecera del modal -->
                <div class="p-4 border-b flex justify-between items-center bg-gray-50">
                    <h4 class="text-sm font-semibold text-gray-800 truncate pr-4">
                        {{ ticketSeleccionado.nombre_original }}
                    </h4>
                    <div class="flex items-center space-x-2">
                        <button 
                            @click="eliminarTicket(ticketSeleccionado.id)" 
                            type="button"
                            style="background-color: #dc2626; color: #ffffff;"
                            class="px-3 py-1 text-xs rounded font-medium hover:opacity-90 cursor-pointer"
                        >
                            Borrar
                        </button>
                        <button 
                            @click="cerrarModal" 
                            type="button"
                            class="text-gray-500 hover:text-gray-800 font-bold text-xl px-2 leading-none"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Cuerpo del modal con la imagen completa -->
                <div class="p-4 flex-1 overflow-auto flex items-center justify-center bg-gray-900">
                    <img 
                        :src="ticketSeleccionado.ruta_archivo" 
                        :alt="ticketSeleccionado.nombre_original" 
                        class="max-w-full max-h-[70vh] object-contain rounded"
                    />
                </div>

                <!-- Pie del modal -->
                <div class="p-3 bg-gray-50 border-t flex justify-between items-center">
                    <a 
                        :href="ticketSeleccionado.ruta_archivo" 
                        target="_blank" 
                        class="text-xs text-indigo-600 hover:underline font-medium"
                    >
                        Abrir en pestaña nueva ↗
                    </a>
                    <button 
                        @click="cerrarModal" 
                        type="button"
                        class="px-4 py-1.5 bg-gray-200 text-gray-800 text-xs font-semibold rounded hover:bg-gray-300"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>