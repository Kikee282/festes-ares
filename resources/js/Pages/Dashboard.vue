<script setup>
import { ref, watch } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const page = usePage();

const props = defineProps({
    anioSeleccionado: {
        type: Number,
        default: () => new Date().getFullYear(),
    },
    aniosDisponibles: {
        type: Array,
        default: () => [new Date().getFullYear()],
    },
    saldoInicial: {
        type: [Number, String, Object],
        default: null,
    },
    totalIngresos: {
        type: Number,
        default: 0,
    },
    totalGastos: {
        type: Number,
        default: 0,
    },
    saldoActual: {
        type: [Number, String, Object],
        default: null,
    },
});

const montoInput = ref(props.saldoInicial ?? "");
const editandoSaldo = ref(false);

// Sincronizar input cuando cambien los datos del servidor
watch(
    () => props.saldoInicial,
    (nuevoSaldo) => {
        montoInput.value = nuevoSaldo ?? "";
    },
    { immediate: true }
);

watch(
    () => props.anioSeleccionado,
    () => {
        montoInput.value = props.saldoInicial ?? "";
        editandoSaldo.value = false;
    }
);

const enviarSaldo = () => {
    if (montoInput.value === "" || montoInput.value === null) return;

    router.post(
        route("saldo.guardar"),
        {
            anio: props.anioSeleccionado,
            monto: parseFloat(montoInput.value),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                editandoSaldo.value = false;
            },
        }
    );
};

const cambiarAnio = (e) => {
    router.get(route("dashboard"), { anio: e.target.value });
};
</script>

<template>
    <Head title="Dashboard - Balance General" />

    <AuthenticatedLayout :user="page.props.auth?.user">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Resumen Financiero
            </h2>
        </template>

        <div class="max-w-6xl mx-auto p-4 sm:p-6">
            <!-- CABECERA CON SELECCIÓN DE EJERCICIO -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Estado de Cuentas</h1>
                <div class="flex items-center gap-2">
                    <label class="font-semibold text-gray-700 text-sm">Ejercicio:</label>
                    <select
                        :value="anioSeleccionado"
                        @change="cambiarAnio"
                        class="border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 text-sm py-1.5 px-3 cursor-pointer"
                    >
                        <option v-for="a in aniosDisponibles" :key="a" :value="a">
                            {{ a }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- FORMULARIO / ALERTA DE SALDO INICIAL -->
            <div v-if="saldoInicial === null || editandoSaldo" class="bg-amber-50 border-l-4 border-amber-500 p-6 rounded-xl shadow-md mb-8">
                <div class="flex items-start gap-4">
                    <div class="text-amber-600 text-2xl">⚠️</div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-amber-900">
                            {{ saldoInicial === null ? 'Establecer Ahorros / Saldo Inicial' : 'Editar Saldo Inicial' }} (Año {{ anioSeleccionado }})
                        </h3>
                        <p class="text-xs text-amber-700 mt-1 mb-4">
                            Introduce el remanente que se posee del ejercicio anterior para iniciar la contabilidad.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                            <input
                                v-model="montoInput"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="Ej: 1500.00"
                                class="border-gray-300 rounded-lg text-sm w-full sm:w-48"
                            />
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    @click="enviarSaldo"
                                    class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-4 rounded-lg text-sm transition-colors cursor-pointer"
                                >
                                    Guardar Saldo Inicial
                                </button>
                                <button
                                    v-if="editandoSaldo"
                                    type="button"
                                    @click="editandoSaldo = false"
                                    class="bg-gray-200 text-gray-700 py-2 px-4 rounded-lg text-sm cursor-pointer"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TARJETAS DE BALANCE GENERAL -->
            <div v-if="saldoInicial !== null" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <!-- 1. Saldo Inicial -->
                <div class="bg-white p-5 rounded-xl shadow-md border-l-4 border-amber-500 relative">
                    <button
                        @click="editandoSaldo = true"
                        title="Editar saldo inicial"
                        class="absolute top-3 right-3 text-xs text-gray-400 hover:text-amber-600 font-semibold cursor-pointer"
                    >
                        ✏️ Editar
                    </button>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ahorros Iniciales</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">
                        +{{ Number(saldoInicial || 0).toFixed(2) }} €
                    </p>
                    <p class="text-[10px] text-gray-400 mt-1">Base ejercicio {{ anioSeleccionado }}</p>
                </div>

                <!-- 2. Ingresos -->
                <div class="bg-white p-5 rounded-xl shadow-md border-l-4 border-emerald-500">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ingresos (Recibos)</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">
                        +{{ Number(totalIngresos || 0).toFixed(2) }} €
                    </p>
                    <p class="text-[10px] text-gray-400 mt-1">Cuotas cobradas</p>
                </div>

                <!-- 3. Gastos -->
                <div class="bg-white p-5 rounded-xl shadow-md border-l-4 border-rose-500">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Gastos (Tickets)</p>
                    <p class="text-2xl font-bold text-rose-600 mt-1">
                        -{{ Number(totalGastos || 0).toFixed(2) }} €
                    </p>
                    <p class="text-[10px] text-gray-400 mt-1">Facturas y compras</p>
                </div>

                <!-- 4. Saldo Actual -->
                <div 
                    class="bg-white p-5 rounded-xl shadow-md border-l-4"
                    :class="(saldoActual || 0) >= 0 ? 'border-blue-600' : 'border-red-600'"
                >
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Dinero Actual</p>
                    <p 
                        class="text-3xl font-extrabold mt-1"
                        :class="(saldoActual || 0) >= 0 ? 'text-blue-600' : 'text-red-600'"
                    >
                        {{ Number(saldoActual || 0).toFixed(2) }} €
                    </p>
                    <p class="text-[10px] text-gray-500 mt-1 font-medium">
                        Balance Neto disponible
                    </p>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>