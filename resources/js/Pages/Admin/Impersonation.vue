<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";
import axios from "axios";

defineProps({
    users: Array,
});

const selectedUserId = ref(null);
const isLoading = ref(false);
const message = ref("");
const tokenResult = ref(null);

const impersonate = async () => {
    if (!selectedUserId.value) return;

    isLoading.value = true;
    message.value = "";
    tokenResult.value = null;

    try {
        const response = await axios.post("/impersonate", {
            user_id: selectedUserId.value,
        });

        tokenResult.value = response.data;
        message.value = "Impersonation token generated successfully!";

        // Copy token to clipboard
        navigator.clipboard
            .writeText(response.data.access_token)
            .then(() => {
                message.value += " Token copied to clipboard!";
            })
            .catch((err) => {
                console.error("Could not copy token: ", err);
            });
    } catch (error) {
        message.value = `Error: ${
            error.response?.data?.message || "Failed to impersonate user"
        }`;
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Head title="User Impersonation" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                User Impersonation
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-4">
                                Select a user to impersonate
                            </h3>
                            <select
                                v-model="selectedUserId"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            >
                                <option :value="null">Select a user</option>
                                <option
                                    v-for="user in users"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ user.name }} ({{ user.email }})
                                </option>
                            </select>
                        </div>

                        <div class="flex items-center mb-6">
                            <button
                                @click="impersonate"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                                :disabled="!selectedUserId || isLoading"
                            >
                                <span v-if="isLoading">Processing...</span>
                                <span v-else>Generate Impersonation Token</span>
                            </button>
                        </div>

                        <div
                            v-if="message"
                            class="mt-4 p-4 rounded"
                            :class="
                                tokenResult
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'
                            "
                        >
                            {{ message }}
                        </div>

                        <div
                            v-if="tokenResult"
                            class="mt-6 border rounded p-4 bg-gray-50 dark:bg-gray-700"
                        >
                            <h4 class="font-medium mb-2">
                                Authentication Tokens
                            </h4>
                            <div class="mb-4">
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Access Token:
                                </p>
                                <div class="mt-1 flex">
                                    <input
                                        type="text"
                                        readonly
                                        class="flex-1 rounded-md border-gray-300 bg-gray-100 dark:bg-gray-600"
                                        :value="tokenResult.access_token"
                                    />
                                </div>
                            </div>

                            <div class="mb-2">
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    ID Token:
                                </p>
                                <div class="mt-1 flex">
                                    <input
                                        type="text"
                                        readonly
                                        class="flex-1 rounded-md border-gray-300 bg-gray-100 dark:bg-gray-600"
                                        :value="tokenResult.id_token"
                                    />
                                </div>
                            </div>

                            <div
                                class="mt-4 p-3 bg-blue-50 dark:bg-blue-900 rounded text-sm"
                            >
                                <h5 class="font-medium mb-1">
                                    How to use this token with Shopify:
                                </h5>
                                <ol class="list-decimal list-inside">
                                    <li>Copy the access token</li>
                                    <li>
                                        Use it in API requests as a Bearer token
                                        in the Authorization header
                                    </li>
                                    <li>
                                        For Shopify admin access, use it with
                                        the Shopify API
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
