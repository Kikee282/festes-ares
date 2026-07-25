<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    recibos: Array,
    anioSeleccionado: Number,
    aniosDisponibles: Array,
});

// Formulario para registrar un nuevo recibo
const form = useForm({
    numero: "",
    nombre: "",
    cantidad: "",
    concepto: "",
    fecha: new Date().toISOString().substr(0, 10), // Fecha de hoy por defecto
});

const submit = () => {
    form.post(route("recibos.store"), {
        onSuccess: () => {
            form.reset("numero", "nombre", "cantidad", "concepto");
            // Mantenemos la fecha por comodidad
        },
    });
};

const eliminarRecibo = (id) => {
    if (confirm("¿Estás seguro de que quieres eliminar este recibo?")) {
        router.delete(route("recibos.destroy", id), {
            preserveScroll: true,
        });
    }
};

// Cambiar el filtro de año
const cambiarAnio = (e) => {
    router.get(
        route("recibos.index"),
        { anio: e.target.value },
        { preserveState: true },
    );
};

// Función para descargar el Excel del año seleccionado
const exportarExcel = () => {
    window.location.href = route("recibos.export", {
        anio: props.anioSeleccionado,
    });
};

const enviarPorWhatsapp = (recibo) => {
    // 1. Pedimos el número mediante el prompt
    const numeroEntrado = prompt(
        "Introduce el número de teléfono para enviar el recibo (ej: 612345678):",
    );

    // Si le da a cancelar o lo deja vacío, nos salimos
    if (!numeroEntrado) return;

    // 2. Limpiamos espacios y letras
    const telefonoLimpio = numeroEntrado.replace(/\D/g, "");

    if (!telefonoLimpio) {
        alert("Por favor, introduce un número válido.");
        return;
    }

    // 3. Formateamos el número (+34 España)
    const numeroFinal = telefonoLimpio.startsWith("34")
        ? telefonoLimpio
        : `34${telefonoLimpio}`;

    // 4. Construimos la URL completa del PDF (usamos la ruta de tu Laravel)
    const baseUrl = window.location.origin;
    const urlPdf = recibo.url_pdf;

    // 5. Redactamos el texto del mensaje
    const mensaje = `Hola!, adjunte el rebut del pagament de les festes d'Ares:\n${urlPdf}\n\nmoltes gràcies!`;

    // 6. Creamos el enlace wa.me
    const urlWhatsapp = `https://wa.me/${numeroFinal}?text=${encodeURIComponent(mensaje)}`;

    // 7. Intentamos abrir en pestaña nueva. Si el navegador lo bloquea, redirigimos en la misma.
    const nuevaVentana = window.open(urlWhatsapp, "_blank");

    if (
        !nuevaVentana ||
        nuevaVentana.closed ||
        typeof nuevaVentana.closed === "undefined"
    ) {
        // Si el bloqueo de pop-ups bloqueó window.open, enviamos en la pestaña actual:
        window.location.href = urlWhatsapp;
    }
};
</script>

<template>
    <Head title="Gestión de Recibos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Recibos - Fiestas
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- FORMULARIO DE REGISTRO -->
                <div class="p-6 bg-white shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Nuevo Recibo de Pago
                    </h3>

                    <form
                        @submit.prevent="submit"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4"
                    >
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Nº Recibo</label
                            >
                            <input
                                v-model="form.numero"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Ej: 1"
                            />
                            <span
                                v-if="form.errors.numero"
                                class="text-xs text-red-500"
                                >{{ form.errors.numero }}</span
                            >
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Nombre / Casa</label
                            >
                            <input
                                v-model="form.nombre"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Ej: Casa Pepito"
                            />
                            <span
                                v-if="form.errors.nombre"
                                class="text-xs text-red-500"
                                >{{ form.errors.nombre }}</span
                            >
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Cantidad (€)</label
                            >
                            <input
                                v-model="form.cantidad"
                                type="number"
                                step="0.01"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Ej: 50.00"
                            />
                            <span
                                v-if="form.errors.cantidad"
                                class="text-xs text-red-500"
                                >{{ form.errors.cantidad }}</span
                            >
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Concepto</label
                            >
                            <input
                                v-model="form.concepto"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Ej: Cuota Fiestas 2026"
                            />
                            <span
                                v-if="form.errors.concepto"
                                class="text-xs text-red-500"
                                >{{ form.errors.concepto }}</span
                            >
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Fecha</label
                            >
                            <input
                                v-model="form.fecha"
                                type="date"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                            <span
                                v-if="form.errors.fecha"
                                class="text-xs text-red-500"
                                >{{ form.errors.fecha }}</span
                            >
                        </div>

                        <div
                            class="md:col-span-2 lg:col-span-5 flex justify-end"
                        >
                            <button
                                type="submit"
                                :disabled="form.processing"
                                style="
                                    background-color: #4f46e5;
                                    color: #ffffff;
                                "
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:opacity-90 active:opacity-100 transition ease-in-out duration-150 cursor-pointer"
                            >
                                Guardar Recibo
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SECCIÓN DE PAGADOS, FILTRO DE AÑO Y EXCEL -->
                <div class="p-6 bg-white shadow sm:rounded-lg">
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4"
                    >
                        <h3 class="text-lg font-medium text-gray-900">
                            Historial de Pagados
                        </h3>

                        <div class="flex items-center space-x-4">
                            <!-- Buscador/Filtro por año -->
                            <div class="flex items-center space-x-2">
                                <label class="text-sm font-medium text-gray-700"
                                    >Año:</label
                                >
                                <select
                                    :value="anioSeleccionado"
                                    @change="cambiarAnio"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option
                                        v-for="a in aniosDisponibles"
                                        :key="a"
                                        :value="a"
                                    >
                                        {{ a }}
                                    </option>
                                    <option
                                        v-if="
                                            !aniosDisponibles.includes(
                                                new Date().getFullYear(),
                                            )
                                        "
                                        :value="new Date().getFullYear()"
                                    >
                                        {{ new Date().getFullYear() }}
                                    </option>
                                </select>
                            </div>

                            <!-- Botón para descargar Excel -->
                            <button
                                @click="exportarExcel"
                                type="button"
                                style="
                                    background-color: #059669;
                                    color: #ffffff;
                                "
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:opacity-90 active:opacity-100 transition ease-in-out duration-150 cursor-pointer"
                            >
                                Descargar Excel ({{ anioSeleccionado }})
                            </button>
                        </div>
                    </div>

                    <!-- TABLA DE RECIBOS -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Nº Recibo
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Nombre / Casa
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Cantidad
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Concepto
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Fecha
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="recibo in recibos" :key="recibo.id">
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900"
                                    >
                                        #{{ recibo.numero }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ recibo.nombre }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-bold"
                                    >
                                        {{ recibo.cantidad }} €
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                    >
                                        {{ recibo.concepto }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                    >
                                        {{ recibo.fecha }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                    >
                                        <button
                                            @click="eliminarRecibo(recibo.id)"
                                            type="button"
                                            style="
                                                background-color: #dc2626;
                                                color: #ffffff;
                                            "
                                            class="inline-flex items-center px-3 py-1 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:opacity-90 active:opacity-100 transition ease-in-out duration-150 cursor-pointer"
                                        >
                                            Eliminar
                                        </button>
                                    </td>
                                    <td class="px-4 py-2 border-b text-center">
                                        <!-- Botón pequeño en la tabla -->
                                        <button
                                            @click="enviarPorWhatsapp(recibo)"
                                            title="Enviar recibo por WhatsApp"
                                            class="inline-flex items-center gap-1.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-semibold py-1.5 px-3 rounded-lg text-xs transition-colors"
                                        >
                                            <svg
                                                class="w-4 h-4 fill-current text-emerald-600"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"
                                                />
                                            </svg>
                                            <span>WhatsApp</span>
                                        </button>
                                    </td>
                                    <td>
                                        <!-- Botón para Abrir/Ver PDF directamente -->
                                        <a
                                            :href="recibo.url_pdf"
                                            target="_blank"
                                            title="Ver en PDF"
                                            class="inline-flex items-center gap-1.5 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-1.5 px-3 rounded-lg text-xs transition-colors"
                                        >
                                            <!-- Icono de documento / PDF -->
                                            <svg
                                                class="w-4 h-4 fill-current text-blue-600"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"
                                                />
                                            </svg>
                                            <span>Ver PDF</span>
                                        </a>
                                    </td>
                                </tr>
                                <tr v-if="recibos.length === 0">
                                    <td
                                        colspan="5"
                                        class="px-6 py-4 text-center text-sm text-gray-500"
                                    >
                                        No hay recibos registrados para el año
                                        {{ anioSeleccionado }}.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
