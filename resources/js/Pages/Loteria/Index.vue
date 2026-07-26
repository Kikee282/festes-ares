<script setup>
import { computed, ref } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const page = usePage();

const props = defineProps({
    loterias: Array,
    anioSeleccionado: Number,
    aniosDisponibles: Array,
});

const anioActual = new Date().getFullYear();
const conceptoPorDefecto = `Lotería Festes ${anioActual}`;

// Formulario de creación
const form = useForm({
    fecha: new Date().toISOString().substr(0, 10),
    nombre: "",
    cantidad: 1,
    concepto: conceptoPorDefecto,
    tipo_operacion: "venta", // compra / venta
    metodo_pago: "metalico", // metalico / bizum
    estado_pago: "pagado", // pagado / pendiente
});

// Importe dinámico en tiempo real (Compra = 20€ / Venta = 23€)
const importeCalculado = computed(() => {
    const cant = parseInt(form.cantidad) || 0;
    const precio = form.tipo_operacion === "compra" ? 20 : 23;
    return cant * precio;
});

// Modal de edición
const mostrandoModal = ref(false);
const loteriaEditandoId = ref(null);

const editForm = useForm({
    fecha: "",
    nombre: "",
    cantidad: 1,
    concepto: "",
    tipo_operacion: "venta",
    metodo_pago: "metalico",
    estado_pago: "pagado",
});

const editImporteCalculado = computed(() => {
    const cant = parseInt(editForm.cantidad) || 0;
    const precio = editForm.tipo_operacion === "compra" ? 20 : 23;
    return cant * precio;
});

const guardarLoteria = () => {
    if (!form.concepto || !form.concepto.trim()) {
        form.concepto = conceptoPorDefecto;
    }

    form.post(route("loteria.store"), {
        onSuccess: () => {
            form.reset("nombre", "cantidad");
            form.concepto = conceptoPorDefecto;
            form.tipo_operacion = "venta";
            form.metodo_pago = "metalico";
            form.estado_pago = "pagado";
        },
    });
};

const abrirEditar = (item) => {
    loteriaEditandoId.value = item.id;
    editForm.fecha = item.fecha;
    editForm.nombre = item.nombre;
    editForm.cantidad = item.cantidad;
    editForm.concepto = item.concepto;
    editForm.tipo_operacion = item.tipo_operacion;
    editForm.metodo_pago = item.metodo_pago;
    editForm.estado_pago = item.estado_pago;
    mostrandoModal.value = true;
};

const guardarEdicion = () => {
    editForm.put(route("loteria.update", loteriaEditandoId.value), {
        onSuccess: () => {
            mostrandoModal.value = false;
        },
    });
};

const eliminarLoteria = (id) => {
    if (confirm("¿Estás seguro de eliminar este registro de lotería?")) {
        router.delete(route("loteria.destroy", id));
    }
};

const cambiarAnio = (e) => {
    router.get(route("loteria.index"), { anio: e.target.value });
};

// CÁLCULO DE ESTADÍSTICAS Y GRÁFICO
const totalComprados = computed(() => {
    return props.loterias
        .filter((item) => item.tipo_operacion === "compra")
        .reduce((sum, item) => sum + parseInt(item.cantidad || 0), 0);
});

const totalVendidos = computed(() => {
    return props.loterias
        .filter((item) => item.tipo_operacion === "venta")
        .reduce((sum, item) => sum + parseInt(item.cantidad || 0), 0);
});

const vendidosPagados = computed(() => {
    return props.loterias
        .filter(
            (item) =>
                item.tipo_operacion === "venta" &&
                item.estado_pago === "pagado",
        )
        .reduce((sum, item) => sum + parseInt(item.cantidad || 0), 0);
});

const vendidosPendientes = computed(() => {
    return props.loterias
        .filter(
            (item) =>
                item.tipo_operacion === "venta" &&
                item.estado_pago === "pendiente",
        )
        .reduce((sum, item) => sum + parseInt(item.cantidad || 0), 0);
});

// Porcentajes para la barra del gráfico
const porcentajePagados = computed(() => {
    if (totalVendidos.value === 0) return 0;
    return Math.round((vendidosPagados.value / totalVendidos.value) * 100);
});

const porcentajePendientes = computed(() => {
    if (totalVendidos.value === 0) return 0;
    return 100 - porcentajePagados.value;
});

const faltanPorVender = computed(() => {
    const restantes = totalComprados.value - totalVendidos.value;
    return restantes > 0 ? restantes : 0;
});

// Filtro de estado de pago: 'todos' | 'pagado' | 'pendiente'
const filtroEstado = ref("todos");

// Listado filtrado dinámicamente según la opción seleccionada
const loteriasFiltradas = computed(() => {
    if (filtroEstado.value === "todos") {
        return props.loterias;
    }
    return props.loterias.filter(
        (item) => item.estado_pago === filtroEstado.value,
    );
});
</script>

<template>
    <Head title="Gestión de Lotería" />

    <AuthenticatedLayout :user="page.props.auth?.user">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Lotería - Fiestas
            </h2>
        </template>

        <div class="max-w-6xl mx-auto p-4 sm:p-6">
            <!-- CABECERA -->
            <div
                class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4 mb-6"
            >
                <h1
                    class="text-2xl font-bold text-gray-800 text-center sm:text-left"
                >
                    Registro de Lotería
                </h1>

                <div
                    class="flex items-center justify-between sm:justify-start w-full sm:w-auto gap-2"
                >
                    <label class="font-semibold text-gray-700 text-sm"
                        >Año:</label
                    >
                    <div
                        class="inline-flex bg-gray-200 p-1 rounded-xl shadow-inner text-xs font-semibold"
                    >
                        <button
                            type="button"
                            @click="filtroEstado = 'todos'"
                            :class="
                                filtroEstado === 'todos'
                                    ? 'bg-white text-gray-800 shadow'
                                    : 'text-gray-600 hover:text-gray-900'
                            "
                            class="px-3 py-1.5 rounded-lg transition-all cursor-pointer"
                        >
                            Todos
                        </button>
                        <button
                            type="button"
                            @click="filtroEstado = 'pagado'"
                            :class="
                                filtroEstado === 'pagado'
                                    ? 'bg-emerald-600 text-white shadow'
                                    : 'text-gray-600 hover:text-gray-900'
                            "
                            class="px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1"
                        >
                            <span>✅ Pagados</span>
                        </button>
                        <button
                            type="button"
                            @click="filtroEstado = 'pendiente'"
                            :class="
                                filtroEstado === 'pendiente'
                                    ? 'bg-rose-600 text-white shadow'
                                    : 'text-gray-600 hover:text-gray-900'
                            "
                            class="px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1"
                        >
                            <span>⏳ Pendientes</span>
                        </button>
                    </div>
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

            <!-- SECCIÓN DE ESTADÍSTICAS Y GRÁFICO -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <!-- Tarjeta 1: Décimos Comprados -->
                <div
                    class="bg-white p-5 rounded-xl shadow-md border-l-4 border-amber-500 flex justify-between items-center"
                >
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
                        >
                            Décimos Comprados
                        </p>
                        <p class="text-3xl font-extrabold text-gray-900 mt-1">
                            {{ totalComprados }}
                        </p>
                        <p class="text-xs text-amber-600 font-semibold mt-1">
                            Inversión: {{ totalComprados * 20 }} €
                        </p>
                    </div>
                    <div class="bg-amber-50 p-3 rounded-full text-amber-600">
                        📥
                    </div>
                </div>

                <!-- Tarjeta 2: Décimos Vendidos -->
                <div
                    class="bg-white p-5 rounded-xl shadow-md border-l-4 border-indigo-500 flex justify-between items-center"
                >
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
                        >
                            Décimos Vendidos
                        </p>
                        <p class="text-3xl font-extrabold text-gray-900 mt-1">
                            {{ totalVendidos }}
                            <span class="text-sm text-gray-400 font-normal"
                                >/ {{ faltanPorVender }} por vender</span
                            >
                        </p>
                        <p class="text-xs text-indigo-600 font-semibold mt-1">
                            Total Ventas: {{ totalVendidos * 23 }} €
                        </p>
                    </div>
                    <div class="bg-indigo-50 p-3 rounded-full text-indigo-600">
                        📤
                    </div>
                </div>

                <!-- Tarjeta 3: Estado de Ventas (Pendientes vs Pagados) -->
                <div
                    class="bg-white p-5 rounded-xl shadow-md border-l-4 border-rose-500 flex justify-between items-center"
                >
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
                        >
                            Pendientes de Cobro
                        </p>
                        <p class="text-3xl font-extrabold text-rose-600 mt-1">
                            {{ vendidosPendientes }}
                            <span class="text-sm text-gray-400 font-normal"
                                >/ {{ totalVendidos }} décimos</span
                            >
                        </p>
                        <p class="text-xs text-rose-500 font-semibold mt-1">
                            Por cobrar: {{ vendidosPendientes * 23 }} €
                        </p>
                    </div>
                    <div class="bg-rose-50 p-3 rounded-full text-rose-600">
                        ⏳
                    </div>
                </div>
            </div>

            <!-- GRÁFICO VISUAL DE ESTADO DE VENTAS -->
            <div
                v-if="totalVendidos > 0"
                class="bg-white p-5 rounded-xl shadow-md mb-8 space-y-3"
            >
                <div class="flex justify-between items-center">
                    <h3
                        class="text-sm font-bold text-gray-800 uppercase tracking-wider"
                    >
                        📊 Estado de Cobro de Décimos Vendidos
                    </h3>
                    <span class="text-xs font-semibold text-gray-500">
                        {{ totalVendidos }} décimos totales
                    </span>
                </div>

                <!-- Barra de Gráfico Proporcional -->
                <div
                    class="w-full bg-gray-200 rounded-full h-4 flex overflow-hidden shadow-inner"
                >
                    <!-- Fragmento Pagado (Verde) -->
                    <div
                        :style="{ width: porcentajePagados + '%' }"
                        class="bg-emerald-500 h-full transition-all duration-500 flex items-center justify-center text-[10px] text-white font-bold"
                        :title="`Pagados: ${vendidosPagados} décimos (${porcentajePagados}%)`"
                    >
                        <span v-if="porcentajePagados > 10"
                            >{{ porcentajePagados }}%</span
                        >
                    </div>

                    <!-- Fragmento Pendiente (Rojo/Rosa) -->
                    <div
                        :style="{ width: porcentajePendientes + '%' }"
                        class="bg-rose-500 h-full transition-all duration-500 flex items-center justify-center text-[10px] text-white font-bold"
                        :title="`Pendientes: ${vendidosPendientes} décimos (${porcentajePendientes}%)`"
                    >
                        <span v-if="porcentajePendientes > 10"
                            >{{ porcentajePendientes }}%</span
                        >
                    </div>
                </div>

                <!-- Leyenda del Gráfico -->
                <div class="flex justify-between items-center text-xs pt-1">
                    <div class="flex items-center gap-2">
                        <span
                            class="w-3 h-3 rounded-full bg-emerald-500 inline-block"
                        ></span>
                        <span class="font-semibold text-gray-700"
                            >✅ Pagados: {{ vendidosPagados }} décimos ({{
                                vendidosPagados * 23
                            }}
                            €)</span
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="w-3 h-3 rounded-full bg-rose-500 inline-block"
                        ></span>
                        <span class="font-semibold text-gray-700"
                            >⏳ Pendientes: {{ vendidosPendientes }} décimos ({{
                                vendidosPendientes * 23
                            }}
                            €)</span
                        >
                    </div>
                </div>
            </div>
            <!-- FORMULARIO DE REGISTRO EN EL ORDEN SOLICITADO -->
            <form
                @submit.prevent="guardarLoteria"
                class="bg-white p-4 sm:p-6 rounded-xl shadow-md mb-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
            >
                <!-- 1. Fecha -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1"
                        >1. Fecha</label
                    >
                    <input
                        v-model="form.fecha"
                        type="date"
                        required
                        class="w-full border-gray-300 rounded-lg text-sm"
                    />
                </div>

                <!-- 2. Nombre -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1"
                        >2. Nombre (Comprador/Vendedor)</label
                    >
                    <input
                        v-model="form.nombre"
                        type="text"
                        required
                        placeholder="Ej: Juan Pérez"
                        class="w-full border-gray-300 rounded-lg text-sm"
                    />
                </div>

                <!-- 3. Cantidad de Décimos -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1"
                        >3. Cantidad Décimos</label
                    >
                    <input
                        v-model="form.cantidad"
                        type="number"
                        min="1"
                        required
                        placeholder="1"
                        class="w-full border-gray-300 rounded-lg text-sm"
                    />
                </div>

                <!-- 4. Concepto -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1"
                        >4. Concepto</label
                    >
                    <input
                        v-model="form.concepto"
                        type="text"
                        required
                        class="w-full border-gray-300 rounded-lg text-sm"
                    />
                </div>

                <!-- 5. Tipo de Operación (Compra / Venta) -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1"
                        >5. Operación</label
                    >
                    <select
                        v-model="form.tipo_operacion"
                        class="w-full border-gray-300 rounded-lg text-sm"
                    >
                        <option value="venta">📤 Venta (23€/décimo)</option>
                        <option value="compra">📥 Compra (20€/décimo)</option>
                    </select>
                </div>

                <!-- 6. Método de Pago (Metálico / Bizum) -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1"
                        >6. Método de Pago</label
                    >
                    <select
                        v-model="form.metodo_pago"
                        class="w-full border-gray-300 rounded-lg text-sm"
                    >
                        <option value="metalico">💵 Metálico</option>
                        <option value="bizum">📱 Bizum</option>
                    </select>
                </div>

                <!-- 7. Estado de Pago (Pagado / Pendiente) -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1"
                        >7. Estado del Pago</label
                    >
                    <select
                        v-model="form.estado_pago"
                        class="w-full border-gray-300 rounded-lg text-sm"
                    >
                        <option value="pagado">✅ Pagado</option>
                        <option value="pendiente">⏳ Pendiente</option>
                    </select>
                </div>

                <!-- 8. Importe (Cálculo Automático) -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1"
                        >8. Importe Total</label
                    >
                    <div
                        class="w-full bg-gray-100 border border-gray-300 rounded-lg py-2 px-3 text-sm font-bold text-gray-800"
                    >
                        {{ importeCalculado }} €
                        <span class="text-[10px] font-normal text-gray-500">
                            ({{ form.cantidad || 0 }} x
                            {{
                                form.tipo_operacion === "compra" ? "20" : "23"
                            }}€)
                        </span>
                    </div>
                </div>

                <div class="md:col-span-2 lg:col-span-4 flex justify-end mt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-lg shadow transition-colors text-sm cursor-pointer"
                    >
                        Guardar Registro
                    </button>
                </div>
            </form>

            <!-- VISTA MÓVIL (TARJETAS) -->
            <div class="block md:hidden space-y-4 mb-8">
                <div
                    v-for="item in loteriasFiltradas"
                    :key="'card-' + item.id"
                    class="bg-white p-4 rounded-xl shadow-md border-l-4 space-y-3"
                    :class="
                        item.tipo_operacion === 'compra'
                            ? 'border-amber-500'
                            : 'border-indigo-500'
                    "
                >
                    >
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold">{{
                                item.fecha
                            }}</span>
                            <h3
                                class="font-bold text-gray-900 text-base leading-tight"
                            >
                                {{ item.nombre }}
                            </h3>
                        </div>
                        <div class="text-right">
                            <span
                                class="font-extrabold text-base"
                                :class="
                                    item.tipo_operacion === 'compra'
                                        ? 'text-amber-600'
                                        : 'text-indigo-600'
                                "
                            >
                                {{ item.tipo_operacion === "compra" ? "-" : "+"
                                }}{{ item.importe }} €
                            </span>
                            <div
                                class="text-[10px] text-gray-500 font-semibold"
                            >
                                {{ item.cantidad }} décimo(s)
                            </div>
                        </div>
                    </div>

                    <div
                        class="text-xs text-gray-600 space-y-1 bg-gray-50 p-2.5 rounded-lg"
                    >
                        <p><strong>Concepto:</strong> {{ item.concepto }}</p>
                        <div class="flex gap-2 pt-1">
                            <span
                                :class="
                                    item.tipo_operacion === 'compra'
                                        ? 'bg-amber-100 text-amber-800'
                                        : 'bg-indigo-100 text-indigo-800'
                                "
                                class="px-2 py-0.5 rounded-full font-bold uppercase text-[10px]"
                            >
                                {{ item.tipo_operacion }}
                            </span>
                            <span
                                :class="
                                    item.metodo_pago === 'bizum'
                                        ? 'bg-sky-100 text-sky-800'
                                        : 'bg-emerald-100 text-emerald-800'
                                "
                                class="px-2 py-0.5 rounded-full font-bold uppercase text-[10px]"
                            >
                                {{ item.metodo_pago }}
                            </span>
                            <span
                                :class="
                                    item.estado_pago === 'pagado'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-rose-100 text-rose-800'
                                "
                                class="px-2 py-0.5 rounded-full font-bold uppercase text-[10px]"
                            >
                                {{ item.estado_pago }}
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button
                            @click="abrirEditar(item)"
                            class="bg-amber-100 text-amber-800 font-semibold py-1.5 px-3 rounded-lg text-xs"
                        >
                            ✏️ Editar
                        </button>
                        <button
                            @click="eliminarLoteria(item.id)"
                            class="bg-red-100 text-red-700 font-semibold py-1.5 px-3 rounded-lg text-xs"
                        >
                            🗑️ Eliminar
                        </button>
                    </div>
                </div>

                <div
                    v-if="loterias.length === 0"
                    class="bg-white p-6 text-center text-gray-500 rounded-xl shadow-md"
                >
                    No hay registros de lotería para el año
                    {{ anioSeleccionado }}.
                </div>
            </div>

            <div
                v-if="loteriasFiltradas.length === 0"
                class="bg-white p-6 text-center text-gray-500 rounded-xl shadow-md"
            >
                No hay registros de lotería que coincidan con el filtro
                seleccionado.
            </div>

            <!-- VISTA ESCRITORIO (TABLA) -->
            <div
                class="hidden md:block bg-white rounded-xl shadow-md overflow-hidden"
            >
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                        <tr>
                            <th class="p-4">Fecha</th>
                            <th class="p-4">Nombre</th>
                            <th class="p-4 text-center">Décimos</th>
                            <th class="p-4">Concepto</th>
                            <th class="p-4">Operación</th>
                            <th class="p-4">Pago</th>
                            <th class="p-4">Estado</th>
                            <th class="p-4">Importe</th>
                            <th class="p-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <tr
                            v-for="item in loteriasFiltradas"
                            :key="item.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="p-4 text-gray-600">{{ item.fecha }}</td>
                            <td class="p-4 font-medium text-gray-900">
                                {{ item.nombre }}
                            </td>
                            <td class="p-4 text-center font-bold">
                                {{ item.cantidad }}
                            </td>
                            <td class="p-4 text-gray-600">
                                {{ item.concepto }}
                            </td>
                            <td class="p-4">
                                <span
                                    :class="
                                        item.tipo_operacion === 'compra'
                                            ? 'bg-amber-100 text-amber-800'
                                            : 'bg-indigo-100 text-indigo-800'
                                    "
                                    class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase"
                                >
                                    {{ item.tipo_operacion }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span
                                    :class="
                                        item.metodo_pago === 'bizum'
                                            ? 'bg-sky-100 text-sky-800'
                                            : 'bg-emerald-100 text-emerald-800'
                                    "
                                    class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                >
                                    {{
                                        item.metodo_pago === "bizum"
                                            ? "📱 Bizum"
                                            : "💵 Metálico"
                                    }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span
                                    :class="
                                        item.estado_pago === 'pagado'
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-rose-100 text-rose-800'
                                    "
                                    class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                >
                                    {{
                                        item.estado_pago === "pagado"
                                            ? "✅ Pagado"
                                            : "⏳ Pendiente"
                                    }}
                                </span>
                            </td>
                            <td
                                class="p-4 font-bold"
                                :class="
                                    item.tipo_operacion === 'compra'
                                        ? 'text-amber-600'
                                        : 'text-indigo-600'
                                "
                            >
                                {{ item.tipo_operacion === "compra" ? "-" : "+"
                                }}{{ item.importe }} €
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <button
                                        @click="abrirEditar(item)"
                                        class="bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold py-1 px-2.5 rounded-lg text-xs"
                                    >
                                        ✏️ Editar
                                    </button>
                                    <button
                                        @click="eliminarLoteria(item.id)"
                                        class="text-red-600 hover:text-red-800 font-semibold text-xs"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        v-if="loteriasFiltradas.length === 0">
                        <td colspan="9" class="p-6 text-center text-gray-500">
                            No hay registros de lotería que coincidan con el
                            filtro seleccionado.
                        </td>
                    </tbody>
                </table>
            </div>

            <!-- MODAL DE EDICIÓN -->
            <div
                v-if="mostrandoModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 overflow-y-auto"
            >
                <div
                    class="bg-white rounded-xl shadow-xl w-full max-w-lg p-5 sm:p-6 my-8 relative"
                >
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        Editar Registro de Lotería
                    </h3>

                    <form @submit.prevent="guardarEdicion" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-700 mb-1"
                                    >Fecha</label
                                >
                                <input
                                    v-model="editForm.fecha"
                                    type="date"
                                    required
                                    class="w-full border-gray-300 rounded-lg text-sm"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-700 mb-1"
                                    >Cantidad Décimos</label
                                >
                                <input
                                    v-model="editForm.cantidad"
                                    type="number"
                                    min="1"
                                    required
                                    class="w-full border-gray-300 rounded-lg text-sm"
                                />
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-medium text-gray-700 mb-1"
                                >Nombre</label
                            >
                            <input
                                v-model="editForm.nombre"
                                type="text"
                                required
                                class="w-full border-gray-300 rounded-lg text-sm"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-xs font-medium text-gray-700 mb-1"
                                >Concepto</label
                            >
                            <input
                                v-model="editForm.concepto"
                                type="text"
                                required
                                class="w-full border-gray-300 rounded-lg text-sm"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-700 mb-1"
                                    >Operación</label
                                >
                                <select
                                    v-model="editForm.tipo_operacion"
                                    class="w-full border-gray-300 rounded-lg text-sm"
                                >
                                    <option value="venta">Venta (23€)</option>
                                    <option value="compra">Compra (20€)</option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-700 mb-1"
                                    >Método</label
                                >
                                <select
                                    v-model="editForm.metodo_pago"
                                    class="w-full border-gray-300 rounded-lg text-sm"
                                >
                                    <option value="metalico">Metálico</option>
                                    <option value="bizum">Bizum</option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-700 mb-1"
                                    >Estado</label
                                >
                                <select
                                    v-model="editForm.estado_pago"
                                    class="w-full border-gray-300 rounded-lg text-sm"
                                >
                                    <option value="pagado">Pagado</option>
                                    <option value="pendiente">Pendiente</option>
                                </select>
                            </div>
                        </div>

                        <div
                            class="bg-gray-50 p-3 rounded-lg flex justify-between items-center text-sm font-bold"
                        >
                            <span>Importe Total Recalculado:</span>
                            <span class="text-blue-600"
                                >{{ editImporteCalculado }} €</span
                            >
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="mostrandoModal = false"
                                class="bg-gray-200 text-gray-800 py-2 px-4 rounded-lg text-sm"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg text-sm"
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
