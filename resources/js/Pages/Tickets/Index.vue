<script setup>
import { ref } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const page = usePage();

const props = defineProps({
    tickets: Array,
    anioSeleccionado: Number,
    aniosDisponibles: Array,
});

// Control de modo Edición / Creación
const editando = ref(false);
const ticketIdEdicion = ref(null);
const fileInput = ref(null);

const form = useForm({
    nombre: "",
    importe: "",
    concepto: "",
    fecha: new Date().toISOString().substr(0, 10),
    imagen: null,
});

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.imagen = file; // Asigna el objeto File de JavaScript
    } else {
        form.imagen = null;
    }
};

// Cargar datos en el formulario para editar
const editarTicket = (ticket) => {
    editando.value = true;
    ticketIdEdicion.value = ticket.id;
    
    form.nombre = ticket.nombre;
    form.importe = ticket.importe;
    form.concepto = ticket.concepto;
    form.fecha = ticket.fecha;
    form.imagen = null;

    if (fileInput.value) {
        fileInput.value.value = "";
    }
    
    // Scroll suave hacia el formulario superior
    window.scrollTo({ top: 0, behavior: "smooth" });
};

// Cancelar edición y volver a modo creación
const cancelarEdicion = () => {
    editando.value = false;
    ticketIdEdicion.value = null;
    form.reset();
    form.fecha = new Date().toISOString().substr(0, 10);
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

const guardarTicket = () => {
    if (editando.value) {
        // Al usar router.post para simular un PUT con multipart/form-data
        router.post(
            route("tickets.update", ticketIdEdicion.value),
            {
                _method: "put",
                nombre: form.nombre,
                importe: form.importe,
                concepto: form.concepto,
                fecha: form.fecha,
                imagen: form.imagen,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => cancelarEdicion(),
            }
        );
    } else {
        // Al crear un ticket nuevo con el Helper de form
        form.post(route("tickets.store"), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => cancelarEdicion(),
        });
    }
};

const cambiarAnio = (e) => {
    router.get(route("tickets.index"), { anio: e.target.value });
};

const eliminarTicket = (id) => {
    if (confirm("¿Seguro que quieres eliminar este ticket?")) {
        router.delete(route("tickets.destroy", id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Gestión de Tickets" />

    <AuthenticatedLayout :user="page.props.auth?.user">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Tickets - Fiestas
            </h2>
        </template>

        <div class="max-w-6xl mx-auto p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4 mb-6">
                <a
                    :href="route('tickets.exportar', anioSeleccionado)"
                    class="w-full sm:w-auto justify-center inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow text-sm transition-colors"
                >
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z" />
                    </svg>
                    <span>Exportar Excel</span>
                </a>

                <div class="flex items-center justify-between sm:justify-start w-full sm:w-auto gap-2">
                    <label class="font-semibold text-gray-700 text-sm">Año:</label>
                    <select
                        :value="anioSeleccionado"
                        @change="cambiarAnio"
                        class="border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm py-2 px-3 flex-1 sm:flex-initial"
                    >
                        <option
                            v-for="a in aniosDisponibles"
                            :key="a"
                            :value="a"
                        >
                            {{ a }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Formulario de creación / edición -->
            <form
                @submit.prevent="guardarTicket"
                class="bg-white p-4 sm:p-6 rounded-xl shadow-md mb-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 border-l-4"
                :class="editando ? 'border-amber-500' : 'border-blue-600'"
            >
                <div class="lg:col-span-3 flex justify-between items-center mb-1">
                    <h3 class="font-bold text-gray-800 text-base">
                        {{ editando ? '✏️ Editar Ticket' : '➕ Añadir Nuevo Ticket' }}
                    </h3>
                    <button
                        v-if="editando"
                        type="button"
                        @click="cancelarEdicion"
                        class="text-xs text-gray-500 hover:text-gray-700 underline font-semibold cursor-pointer"
                    >
                        Cancelar edición
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >Nombre / Establecimiento</label
                    >
                    <input
                        v-model="form.nombre"
                        type="text"
                        required
                        placeholder="Ej: Bar El Compás"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >Importe (€)</label
                    >
                    <input
                        v-model="form.importe"
                        type="number"
                        step="0.01"
                        required
                        placeholder="0.00"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >Concepto</label
                    >
                    <input
                        v-model="form.concepto"
                        type="text"
                        required
                        placeholder="Ej: Compra de refrescos"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >Fecha</label
                    >
                    <input
                        v-model="form.fecha"
                        type="date"
                        required
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >Imagen del Ticket {{ editando ? '(Opcional para reemplazar)' : '(Opcional)' }}</label
                    >
                    <input
                        ref="fileInput"
                        type="file"
                        @change="handleFileChange"
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    />
                </div>

                <div class="md:col-span-2 lg:col-span-1 flex items-end gap-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full text-white font-bold py-2.5 px-4 rounded-lg shadow transition-colors text-sm cursor-pointer disabled:opacity-50"
                        :class="editando ? 'bg-amber-600 hover:bg-amber-700' : 'bg-blue-600 hover:bg-blue-700'"
                    >
                        {{ editando ? 'Actualizar Ticket' : 'Guardar Ticket' }}
                    </button>
                    <button
                        v-if="editando"
                        type="button"
                        @click="cancelarEdicion"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2.5 px-3 rounded-lg text-sm transition-colors cursor-pointer"
                    >
                        X
                    </button>
                </div>
            </form>

            <!-- VISTA MÓVIL (TARJETAS) -->
            <div class="block md:hidden space-y-4 mb-8">
                <div 
                    v-for="ticket in tickets" 
                    :key="'card-' + ticket.id"
                    class="bg-white p-4 rounded-xl shadow-md border-l-4 border-red-500 space-y-2"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs text-gray-500 font-semibold">{{ ticket.fecha }}</span>
                            <h3 class="font-bold text-gray-900 text-base leading-tight">{{ ticket.nombre }}</h3>
                        </div>
                        <span class="font-extrabold text-red-600 text-base">-{{ ticket.importe }} €</span>
                    </div>

                    <p class="text-xs text-gray-600 bg-gray-50 p-2 rounded-lg"><strong>Concepto:</strong> {{ ticket.concepto }}</p>

                    <div class="flex justify-between items-center pt-2">
                        <a
                            v-if="ticket.imagen_path"
                            :href="ticket.imagen_path"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-xs text-blue-600 font-semibold bg-blue-50 px-2.5 py-1.5 rounded-lg"
                        >
                            📷 Ver Foto
                        </a>
                        <span v-else class="text-xs text-gray-400">Sin foto</span>

                        <div class="flex items-center gap-2">
                            <button
                                @click="editarTicket(ticket)"
                                class="bg-amber-100 text-amber-800 font-semibold py-1.5 px-3 rounded-lg text-xs cursor-pointer"
                            >
                                ✏️ Editar
                            </button>
                            <button
                                @click="eliminarTicket(ticket.id)"
                                class="bg-red-100 text-red-700 font-semibold py-1.5 px-3 rounded-lg text-xs cursor-pointer"
                            >
                                🗑️ Eliminar
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="tickets.length === 0" class="bg-white p-6 text-center text-gray-500 rounded-xl shadow-md">
                    No hay tickets registrados en este año.
                </div>
            </div>

            <!-- VISTA ESCRITORIO (TABLA) -->
            <div class="hidden md:block bg-white rounded-xl shadow-md overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                        <tr>
                            <th class="p-4">Fecha</th>
                            <th class="p-4">Establecimiento</th>
                            <th class="p-4">Concepto</th>
                            <th class="p-4">Importe</th>
                            <th class="p-4 text-center">Imagen</th>
                            <th class="p-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <tr
                            v-for="ticket in tickets"
                            :key="ticket.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="p-4 font-medium">{{ ticket.fecha }}</td>
                            <td class="p-4">{{ ticket.nombre }}</td>
                            <td class="p-4 text-gray-600">
                                {{ ticket.concepto }}
                            </td>
                            <td class="p-4 font-bold text-red-600">
                                -{{ ticket.importe }} €
                            </td>
                            <td class="p-4 text-center">
                                <a
                                    v-if="ticket.imagen_path"
                                    :href="ticket.imagen_path"
                                    target="_blank"
                                    class="inline-flex items-center text-blue-600 hover:underline"
                                >
                                    📷 Ver
                                </a>
                                <span v-else class="text-gray-400"
                                    >Sin foto</span
                                >
                            </td>
                            <td class="p-4 text-center space-x-3">
                                <button
                                    @click="editarTicket(ticket)"
                                    class="text-amber-600 hover:text-amber-800 font-semibold cursor-pointer"
                                >
                                    Editar
                                </button>
                                <button
                                    @click="eliminarTicket(ticket.id)"
                                    class="text-red-600 hover:text-red-800 font-semibold cursor-pointer"
                                >
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                        <tr v-if="tickets.length === 0">
                            <td
                                colspan="6"
                                class="p-6 text-center text-gray-500"
                            >
                                No hay tickets registrados en este año.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>