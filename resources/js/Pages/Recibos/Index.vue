<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import { ref } from "vue";

const page = usePage();

const props = defineProps({
    recibos: Array,
    anioSeleccionado: Number,
    aniosDisponibles: Array,
});

// Formulario para crear un nuevo recibo
const form = useForm({
    numero: "",
    nombre: "",
    telefono: "",
    cantidad: "",
    concepto: "",
    fecha: new Date().toISOString().substr(0, 10),
});

// Formulario y estado para editar
const mostrandoModal = ref(false);
const reciboEditandoId = ref(null);

const editForm = useForm({
    numero: "",
    nombre: "",
    telefono: "",
    cantidad: "",
    concepto: "",
    fecha: "",
});

const submit = () => {
    form.post(route("recibos.store"), {
        onSuccess: () => {
            form.reset("numero", "nombre", "telefono", "cantidad", "concepto");
        },
    });
};

const abrirEditar = (recibo) => {
    reciboEditandoId.value = recibo.id;
    editForm.numero = recibo.numero;
    editForm.nombre = recibo.nombre;
    editForm.telefono = recibo.telefono || "";
    editForm.cantidad = recibo.cantidad;
    editForm.concepto = recibo.concepto;
    editForm.fecha = recibo.fecha;
    mostrandoModal.value = true;
};

const guardarEdicion = () => {
    editForm.put(route("recibos.update", reciboEditandoId.value), {
        onSuccess: () => {
            mostrandoModal.value = false;
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

const cambiarAnio = (e) => {
    router.get(
        route("recibos.index"),
        { anio: e.target.value },
        { preserveState: true }
    );
};

const exportarExcel = () => {
    window.location.href = route("recibos.export", {
        anio: props.anioSeleccionado,
    });
};

const enviarPorWhatsapp = (recibo) => {
    if (!recibo.telefono) {
        alert("Este recibo no tiene ningún número de teléfono registrado.");
        return;
    }

    const telefonoLimpio = recibo.telefono.replace(/\D/g, "");

    if (!telefonoLimpio) {
        alert("El número de teléfono registrado no es válido.");
        return;
    }

    const numeroFinal = telefonoLimpio.startsWith("34")
        ? telefonoLimpio
        : `34${telefonoLimpio}`;

    const urlPdf = recibo.url_pdf;
    const mensaje = `Hola!, adjunte el rebut del pagament de les festes d'Ares:\n${urlPdf}\n\nmoltes gràcies!`;
    const urlWhatsapp = `https://wa.me/${numeroFinal}?text=${encodeURIComponent(mensaje)}`;

    const nuevaVentana = window.open(urlWhatsapp, "_blank");

    if (!nuevaVentana || nuevaVentana.closed || typeof nuevaVentana.closed === "undefined") {
        window.location.href = urlWhatsapp;
    }
};
</script>

<template>
    <Head title="Gestión de Recibos" />

    <AuthenticatedLayout :user="page.props.auth?.user">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Recibos - Fiestas
            </h2>
        </template>

        <div class="max-w-6xl mx-auto p-4 sm:p-6">
            <!-- CABECERA: TÍTULO, BOTÓN Y FILTRO (RESPONSIVE) -->
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4 mb-6">
                <h1 class="text-2xl font-bold text-gray-800 text-center sm:text-left">Historial de Recibos</h1>

                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <button
                        @click="exportarExcel"
                        type="button"
                        class="w-full sm:w-auto justify-center inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow text-sm transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                        </svg>
                        <span>Exportar Excel</span>
                    </button>

                    <div class="flex items-center justify-between sm:justify-start w-full sm:w-auto gap-2">
                        <label class="font-semibold text-gray-700 text-sm">Año:</label>
                        <select
                            :value="anioSeleccionado"
                            @change="cambiarAnio"
                            class="border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm py-2 px-3 flex-1 sm:flex-initial"
                        >
                            <option v-for="a in aniosDisponibles" :key="a" :value="a">
                                {{ a }}
                            </option>
                            <option
                                v-if="!aniosDisponibles.includes(new Date().getFullYear())"
                                :value="new Date().getFullYear()"
                            >
                                {{ new Date().getFullYear() }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- FORMULARIO DE REGISTRO (RESPONSIVE GRID) -->
            <form
                @submit.prevent="submit"
                class="bg-white p-4 sm:p-6 rounded-xl shadow-md mb-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
            >
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nº Recibo</label>
                    <input
                        v-model="form.numero"
                        type="text"
                        required
                        placeholder="Ej: 1"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                    <span v-if="form.errors.numero" class="text-xs text-red-500">{{ form.errors.numero }}</span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre / Casa</label>
                    <input
                        v-model="form.nombre"
                        type="text"
                        required
                        placeholder="Ej: Casa Pepito"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                    <span v-if="form.errors.nombre" class="text-xs text-red-500">{{ form.errors.nombre }}</span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono (WhatsApp)</label>
                    <input
                        v-model="form.telefono"
                        type="tel"
                        placeholder="Ej: 612345678"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                    <span v-if="form.errors.telefono" class="text-xs text-red-500">{{ form.errors.telefono }}</span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad (€)</label>
                    <input
                        v-model="form.cantidad"
                        type="number"
                        step="0.01"
                        required
                        placeholder="50.00"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                    <span v-if="form.errors.cantidad" class="text-xs text-red-500">{{ form.errors.cantidad }}</span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Concepto</label>
                    <input
                        v-model="form.concepto"
                        type="text"
                        required
                        placeholder="Ej: Cuota Fiestas 2026"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                    <span v-if="form.errors.concepto" class="text-xs text-red-500">{{ form.errors.concepto }}</span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                    <input
                        v-model="form.fecha"
                        type="date"
                        required
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                    <span v-if="form.errors.fecha" class="text-xs text-red-500">{{ form.errors.fecha }}</span>
                </div>

                <div class="md:col-span-2 lg:col-span-3 flex justify-end mt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-lg shadow transition-colors cursor-pointer text-sm"
                    >
                        Guardar Recibo
                    </button>
                </div>
            </form>

            <!-- VISTA DE MÓVIL: TARJETAS (SE MUESTRA SOLO EN PANTALLAS PEQUEÑAS) -->
            <div class="block md:hidden space-y-4 mb-8">
                <div 
                    v-for="recibo in recibos" 
                    :key="'card-' + recibo.id" 
                    class="bg-white p-4 rounded-xl shadow-md border-l-4 border-blue-500 space-y-3"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-bold text-gray-400">#{{ recibo.numero }}</span>
                            <h3 class="font-bold text-gray-900 text-base leading-tight">{{ recibo.nombre }}</h3>
                        </div>
                        <span class="font-extrabold text-green-600 text-base">+{{ recibo.cantidad }} €</span>
                    </div>

                    <div class="text-xs text-gray-600 space-y-1 bg-gray-50 p-2.5 rounded-lg">
                        <p><strong>Concepto:</strong> {{ recibo.concepto }}</p>
                        <p><strong>Fecha:</strong> {{ recibo.fecha }}</p>
                        <p><strong>Teléfono:</strong> {{ recibo.telefono || 'Sin registrar' }}</p>
                    </div>

                    <!-- Botones de Acción en Móvil -->
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button
                            @click="enviarPorWhatsapp(recibo)"
                            :disabled="!recibo.telefono"
                            :class="recibo.telefono ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                            class="inline-flex items-center justify-center gap-1.5 font-semibold py-2 px-3 rounded-lg text-xs"
                        >
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                            </svg>
                            <span>WhatsApp</span>
                        </button>

                        <a
                            :href="recibo.url_pdf"
                            target="_blank"
                            class="inline-flex items-center justify-center gap-1.5 bg-blue-100 text-blue-800 font-semibold py-2 px-3 rounded-lg text-xs"
                        >
                            <svg class="w-3.5 h-3.5 fill-current text-blue-600" viewBox="0 0 24 24">
                                <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                            </svg>
                            <span>Ver PDF</span>
                        </a>

                        <button
                            @click="abrirEditar(recibo)"
                            class="bg-amber-100 text-amber-800 font-semibold py-2 px-3 rounded-lg text-xs text-center"
                        >
                            ✏️ Editar
                        </button>

                        <button
                            @click="eliminarRecibo(recibo.id)"
                            class="bg-red-100 text-red-700 font-semibold py-2 px-3 rounded-lg text-xs text-center"
                        >
                            🗑️ Eliminar
                        </button>
                    </div>
                </div>

                <div v-if="recibos.length === 0" class="bg-white p-6 text-center text-gray-500 rounded-xl shadow-md">
                    No hay recibos registrados para el año {{ anioSeleccionado }}.
                </div>
            </div>

            <!-- VISTA DE ESCRITORIO: TABLA (SE MUESTRA DE MD EN ADELANTE) -->
            <div class="hidden md:block bg-white rounded-xl shadow-md overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                        <tr>
                            <th class="p-4">Nº Recibo</th>
                            <th class="p-4">Nombre / Casa</th>
                            <th class="p-4">Teléfono</th>
                            <th class="p-4">Concepto</th>
                            <th class="p-4">Fecha</th>
                            <th class="p-4">Cantidad</th>
                            <th class="p-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <tr v-for="recibo in recibos" :key="recibo.id" class="hover:bg-gray-50">
                            <td class="p-4 font-bold text-gray-900">#{{ recibo.numero }}</td>
                            <td class="p-4 font-medium text-gray-800">{{ recibo.nombre }}</td>
                            <td class="p-4 text-gray-600">{{ recibo.telefono || '-' }}</td>
                            <td class="p-4 text-gray-600">{{ recibo.concepto }}</td>
                            <td class="p-4 text-gray-600">{{ recibo.fecha }}</td>
                            <td class="p-4 font-bold text-green-600">+{{ recibo.cantidad }} €</td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        @click="abrirEditar(recibo)"
                                        title="Editar recibo"
                                        class="inline-flex items-center gap-1 bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold py-1.5 px-2.5 rounded-lg text-xs transition-colors cursor-pointer"
                                    >
                                        ✏️ Editar
                                    </button>

                                    <button
                                        @click="enviarPorWhatsapp(recibo)"
                                        :disabled="!recibo.telefono"
                                        :title="recibo.telefono ? 'Enviar por WhatsApp' : 'Sin teléfono registrado'"
                                        :class="recibo.telefono ? 'bg-emerald-100 hover:bg-emerald-200 text-emerald-800' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                                        class="inline-flex items-center gap-1.5 font-semibold py-1.5 px-2.5 rounded-lg text-xs transition-colors cursor-pointer"
                                    >
                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                                        </svg>
                                        <span>WhatsApp</span>
                                    </button>

                                    <a
                                        :href="recibo.url_pdf"
                                        target="_blank"
                                        title="Ver en PDF"
                                        class="inline-flex items-center gap-1.5 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-1.5 px-2.5 rounded-lg text-xs transition-colors"
                                    >
                                        <svg class="w-4 h-4 fill-current text-blue-600" viewBox="0 0 24 24">
                                            <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                                        </svg>
                                        <span>PDF</span>
                                    </a>

                                    <button
                                        @click="eliminarRecibo(recibo.id)"
                                        class="text-red-600 hover:text-red-800 font-semibold px-2 py-1 text-xs transition-colors cursor-pointer"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="recibos.length === 0">
                            <td colspan="7" class="p-6 text-center text-gray-500">
                                No hay recibos registrados para el año {{ anioSeleccionado }}.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- MODAL DE EDICIÓN RESPONSIVE -->
            <div
                v-if="mostrandoModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 overflow-y-auto"
            >
                <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-5 sm:p-6 my-8 relative">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Editar Recibo</h3>

                    <form @submit.prevent="guardarEdicion" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nº Recibo</label>
                                <input v-model="editForm.numero" type="text" required class="w-full border-gray-300 rounded-lg text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Cantidad (€)</label>
                                <input v-model="editForm.cantidad" type="number" step="0.01" required class="w-full border-gray-300 rounded-lg text-sm" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Nombre / Casa</label>
                            <input v-model="editForm.nombre" type="text" required class="w-full border-gray-300 rounded-lg text-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Teléfono (WhatsApp)</label>
                            <input v-model="editForm.telefono" type="tel" class="w-full border-gray-300 rounded-lg text-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Concepto</label>
                            <input v-model="editForm.concepto" type="text" required class="w-full border-gray-300 rounded-lg text-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Fecha</label>
                            <input v-model="editForm.fecha" type="date" required class="w-full border-gray-300 rounded-lg text-sm" />
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="mostrandoModal = false"
                                class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg text-sm transition-colors text-center"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg text-sm transition-colors text-center"
                            >
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>