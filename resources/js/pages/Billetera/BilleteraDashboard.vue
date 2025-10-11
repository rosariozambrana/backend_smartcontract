<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { createPropietario, getAccount, initBlockchain, getPropietario, getPropietarioCounter } from '@/services/blockchainService';
import type { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { IdCard, User } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const { props } = usePage<{ auth: { user: { id: string } } }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billetera',
        href: '/billetera',
    },
];
const nombre = ref('');
const numId = ref('');
const userId = ref('');
const account = ref('');
const cantidad = ref('');

const guardarContrato = async () => {
    try {
        await createPropietario(nombre.value, numId.value, userId.value);
        window.location.reload();
    } catch (error) {
        console.error('Error al registrar propietario:', error);
    }
};
const loadCounterPropietario = async () => {
    try {
        cantidad.value = await getPropietarioCounter();
    } catch (error) {
        console.error('Error al cargar el contador de propietarios:', error);
    }
};
const loadPropietarios = async () => {
    const propietariosList = document.getElementById('propietarios-lists');
    if (propietariosList) {
        propietariosList.innerHTML = '';
        for(let i = 1; i <= cantidad.value; i++) {
            const propietario = await getPropietario(i);
            console.log(propietario);
            const propietarioDiv = document.createElement('div');
            propietarioDiv.className = 'p-2';
            propietarioDiv.innerHTML = `<p>${propietario['nombre']} - ${propietario['numId']}</p>`;
            propietariosList.appendChild(propietarioDiv);
        }
    }
};

onMounted(async () => {
    await initBlockchain();
    account.value = await getAccount();
    await loadCounterPropietario();
    await loadPropietarios();
    if (props.auth.user.id) {
        userId.value = props.auth.user.id;
    }
});
</script>

<template>
    <Head title="Billetera" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="flex-none rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h2 class="text-lg font-extrabold dark:text-white">Block Chain</h2>
                    <p class="text-gray-500 dark:text-gray-400">Registra propietarios</p>
                    <p class="text-gray-500 dark:text-gray-400">
                        <b>Wallet:</b> <small>{{ account }}</small>
                    </p>
                    <p class="text-gray-500 dark:text-gray-400">
                        <b>Nro Contratos:</b> <small>{{ cantidad }}</small>
                    </p>
                    <div class="pt-2 md:max-w-sm">
                        <div class="mt-2 w-full">
                            <label for="name" class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Nombre: </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
                                    <User />
                                </div>
                                <input
                                    type="text"
                                    id="name"
                                    v-model="nombre"
                                    autocomplete="name"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                                    placeholder="Fernando Pinto Lino"
                                />
                            </div>
                        </div>
                        <div class="my-2">
                            <label for="ci" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Carnet Identidad:</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 start-0 mr-2 flex items-center ps-3.5">
                                    <IdCard />
                                </div>
                                <input
                                    type="text"
                                    id="ci"
                                    v-model="numId"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                                    placeholder="8956887"
                                />
                            </div>
                        </div>
                        <button
                            type="submit"
                            @click="guardarContrato"
                            class="my-2 mb-2 me-2 inline-flex items-center rounded-lg bg-gray-100 px-5 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-500"
                        >
                            <svg
                                class="-ms-1 me-2 h-4 w-4 text-[#626890]"
                                aria-hidden="true"
                                focusable="false"
                                data-prefix="fab"
                                data-icon="ethereum"
                                role="img"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 320 512"
                            >
                                <path
                                    fill="currentColor"
                                    d="M311.9 260.8L160 353.6 8 260.8 160 0l151.9 260.8zM160 383.4L8 290.6 160 512l152-221.4-152 92.8z"
                                ></path>
                            </svg>
                            Guardar Contrato
                        </button>
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border md:col-span-2">
                    <div id="propietarios-lists"></div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
