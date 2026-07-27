<script setup>
import { ref, computed } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const page = usePage();

const props = defineProps({
    notas: Array,
    anioSeleccionado: Number,
    aniosDisponibles: Array,
});

const busqueda = ref("");
const editando = ref(false);
const notaIdEdicion = ref(null);

const form = useForm({
    titulo: "",
    contenido: "",
    fijada: false,
});

const abrirEditar = (nota) => {
    editando.value = true;
    notaIdEdicion.value = nota.id;
    form.titulo = nota.titulo;
    form.contenido = nota.contenido || "";
    form.fijada = nota.fijada;
    window.scrollTo({ top: 0, behavior: "smooth" });
};

const cancelarEdicion = () => {
    editando.value = false;
    notaIdEdicion.value = null;
    form.reset();
};

const guardarNota = () => {
    if (editando.value) {
        form.put(route("notas.update", notaIdEdicion.value), {
            preserveScroll: true,
            onSuccess: () => cancelarEdicion(),
        });
    } else {
        form.post(route("notas.store"), {
            preserveScroll: true,
            onSuccess: () => cancelarEdicion(),
        });
    }
};

const togglePin = (id) => {
    router.patch(route("notas.pin", id), {}, { preserveScroll: true });
};

const eliminarNota = (id) => {
    if (confirm("¿Seguro que quieres borrar esta nota?")) {
        router.delete(route("notas.destroy", id), { preserveScroll: true });
    }
};

const cambiarAnio = (e) => {
    router.get(route("notas.index"), { anio: e.target.value });
};

// Filtro de búsqueda en tiempo real
const notasFiltradas = computed(() => {
    if (!busqueda.value.trim()) return props.notas;
    const q = busqueda.value.toLowerCase();
    return props.notas.filter(
        (n) =>
            n.titulo.toLowerCase().includes(q) ||
            (n.contenido && n.contenido.toLowerCase().includes(q))
    );
});
</script>

<template>
    <Head title="Bloc de Notas" />

    <AuthenticatedLayout :user="page.props.auth?.user">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📝 Bloc de Notas y Listas
            </h2>
        </template>

        <div class="max-w-6xl mx-auto p-4 sm:p-6">
            <!-- CABECERA Y BUSCADOR -->
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4 mb-6">
                <div class="relative flex-1 max-w-md">
                    <input
                        v-model="busqueda"
                        type="text"
                        placeholder="🔍 Buscar en las notas..."
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm pl-3 pr-4 py-2"
                    />
                </div>

                <div class="flex items-center justify-end gap-2">
                    <label class="font-semibold text-gray-700 text-sm">Año:</label>
                    <select
                        :value="anioSeleccionado"
                        @change="cambiarAnio"
                        class="border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm py-2 px-3"
                    >
                        <option v-for="a in aniosDisponibles" :key="a" :value="a">
                            {{ a }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- FORMULARIO DE CREACIÓN / EDICIÓN -->
            <form
                @submit.prevent="guardarNota"
                class="bg-amber-50/50 p-4 sm:p-6 rounded-xl shadow-md mb-8 border-l-4 transition-all"
                :class="editando ? 'border-amber-600 bg-amber-100/40' : 'border-amber-400'"
            >
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-bold text-gray-800 text-base">
                        {{ editando ? '✏️ Editar Nota' : '📌 Añadir Nueva Nota / Lista' }}
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

                <div class="space-y-3">
                    <input
                        v-model="form.titulo"
                        type="text"
                        required
                        placeholder="Título (ej: Compra para la cena, Tareas pendientes...)"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm font-semibold"
                    />

                    <textarea
                        v-model="form.contenido"
                        rows="3"
                        placeholder="Escribe los detalles, lista de productos, anotaciones..."
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm"
                    ></textarea>

                    <div class="flex justify-between items-center pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-gray-700">
                            <input
                                v-model="form.fijada"
                                type="checkbox"
                                class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                            />
                            <span>📌 Fijar nota arriba</span>
                        </label>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-5 rounded-lg shadow text-sm transition-colors cursor-pointer disabled:opacity-50"
                        >
                            {{ editando ? 'Actualizar Nota' : 'Guardar Nota' }}
                        </button>
                    </div>
                </div>
            </form>

            <!-- REJILLA DE NOTAS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="nota in notasFiltradas"
                    :key="nota.id"
                    class="bg-white p-5 rounded-xl shadow-md border-t-4 flex flex-col justify-between transition-all hover:shadow-lg relative"
                    :class="nota.fijada ? 'border-amber-500 bg-amber-50/20' : 'border-gray-300'"
                >
                    <div>
                        <div class="flex justify-between items-start gap-2 mb-2">
                            <h4 class="font-bold text-gray-900 text-base leading-snug">
                                {{ nota.titulo }}
                            </h4>
                            <button
                                @click="togglePin(nota.id)"
                                class="text-base cursor-pointer p-1 rounded hover:bg-gray-100 transition-colors"
                                :title="nota.fijada ? 'Desfijar' : 'Fijar arriba'"
                            >
                                {{ nota.fijada ? '📌' : '📍' }}
                            </button>
                        </div>

                        <p
                            v-if="nota.contenido"
                            class="text-sm text-gray-700 whitespace-pre-line leading-relaxed mb-4"
                        >
                            {{ nota.contenido }}
                        </p>
                    </div>

                    <div class="flex justify-between items-center pt-3 border-t border-gray-100 text-xs text-gray-400">
                        <span>{{ new Date(nota.updated_at).toLocaleDateString() }}</span>

                        <div class="flex gap-2">
                            <button
                                @click="abrirEditar(nota)"
                                class="text-amber-700 hover:text-amber-900 font-semibold cursor-pointer"
                            >
                                ✏️ Editar
                            </button>
                            <button
                                @click="eliminarNota(nota.id)"
                                class="text-red-600 hover:text-red-800 font-semibold cursor-pointer"
                            >
                                🗑️ Borrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SIN RESULTADOS -->
            <div
                v-if="notasFiltradas.length === 0"
                class="bg-white p-8 text-center text-gray-500 rounded-xl shadow-md mt-4"
            >
                No hay notas o listas registradas para mostrar.
            </div>
        </div>
    </AuthenticatedLayout>
</template>