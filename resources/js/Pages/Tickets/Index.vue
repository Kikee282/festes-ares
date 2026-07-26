<script setup>
import { ref } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    tickets: Array,
    anioSeleccionado: Number,
    aniosDisponibles: Array,
});

const form = useForm({
    nombre: "",
    importe: "",
    concepto: "",
    fecha: new Date().toISOString().substr(0, 10),
    imagen: null,
});

const handleFileChange = (e) => {
    form.imagen = e.target.files[0];
};

const guardarTicket = () => {
    form.post(route("tickets.store"), {
        forceFormData: true, // Necesario para enviar archivos con Inertia
        onSuccess: () => {
            form.reset("nombre", "importe", "concepto", "imagen");
            // Limpiar el input file en el DOM
            const fileInput = document.getElementById("input-imagen");
            if (fileInput) fileInput.value = "";
        },
    });
};

const cambiarAnio = (e) => {
    router.get(route("tickets.index"), { anio: e.target.value });
};

const eliminarTicket = (id) => {
    if (confirm("¿Seguro que quieres eliminar este ticket?")) {
        router.delete(route("tickets.destroy", id));
    }
};
</script>

<template>
    <Head title="Gestión de Tickets" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Tickets - Fiestas
            </h2>
        </template>
        <div class="max-w-6xl mx-auto p-6">
            <div class="flex justify-between items-center mb-6">
                <a
                    :href="route('tickets.exportar', anioSeleccionado)"
                    class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow text-sm transition-colors"
                >
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z" />
                    </svg>
                    <span>Exportar Excel</span>
                </a>
                <!-- Filtro por año -->
                <div class="flex items-center gap-2">
                    <label class="font-semibold text-gray-700">Año:</label>
                    <select
                        :value="anioSeleccionado"
                        @change="cambiarAnio"
                        class="border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
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

            <!-- Formulario de creación -->
            <form
                @submit.prevent="guardarTicket"
                class="bg-white p-6 rounded-xl shadow-md mb-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
            >
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >Nombre / Establecimiento</label
                    >
                    <input
                        v-model="form.nombre"
                        type="text"
                        required
                        placeholder="Ej: Bar El Compás"
                        class="w-full border-gray-300 rounded-lg shadow-sm"
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
                        class="w-full border-gray-300 rounded-lg shadow-sm"
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
                        class="w-full border-gray-300 rounded-lg shadow-sm"
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
                        class="w-full border-gray-300 rounded-lg shadow-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >Imagen del Ticket (Opcional)</label
                    >
                    <input
                        id="input-imagen"
                        type="file"
                        @change="handleFileChange"
                        accept="image/*"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    />
                </div>

                <div class="md:col-span-2 lg:col-span-1 flex items-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow transition-colors"
                    >
                        Guardar Ticket
                    </button>
                </div>
            </form>

            <!-- Tabla de tickets -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
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
                            <td class="p-4 text-center">
                                <button
                                    @click="eliminarTicket(ticket.id)"
                                    class="text-red-600 hover:text-red-800 font-semibold"
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
