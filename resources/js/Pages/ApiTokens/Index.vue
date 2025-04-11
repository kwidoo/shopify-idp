<template>
    <AppLayout title="API Tokens">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                API Tokens
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- API Token Manager -->
                <div
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
                >
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900">
                            Create API Token
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            API tokens allow third-party services to
                            authenticate with our application on your behalf.
                        </p>
                    </div>

                    <div
                        v-if="status"
                        class="mb-4 font-medium text-sm text-green-600"
                    >
                        {{ status }}
                    </div>

                    <!-- Token Creation Form -->
                    <form @submit.prevent="createToken">
                        <!-- Token Name -->
                        <div class="mb-4">
                            <InputLabel for="name" value="Token Name" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                autofocus
                                placeholder="My App Token"
                            />
                            <InputError :message="errors.name" class="mt-2" />
                        </div>

                        <!-- Token Permissions -->
                        <div class="mb-4">
                            <InputLabel value="Permissions" />

                            <div
                                class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4"
                            >
                                <div
                                    v-for="(
                                        description, scope
                                    ) in availableScopes"
                                    :key="scope"
                                    class="flex items-center"
                                >
                                    <Checkbox
                                        :id="'scope_' + scope"
                                        :value="scope"
                                        v-model:checked="form.scopes"
                                    />
                                    <label
                                        :for="'scope_' + scope"
                                        class="ml-2 text-sm text-gray-600"
                                    >
                                        {{ description }}
                                    </label>
                                </div>
                            </div>

                            <InputError :message="errors.scopes" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <PrimaryButton
                                type="submit"
                                :class="{ 'opacity-25': processing }"
                                :disabled="processing"
                            >
                                Create API Token
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Display Newly Created Token -->
                <div
                    v-if="token && refresh_token"
                    class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
                >
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <div class="font-semibold text-sm text-gray-600 mb-2">
                            Please copy your new API token. For your security,
                            it won't be shown again.
                        </div>

                        <div>
                            <InputLabel class="mb-1" value="Access Token" />
                            <textarea
                                class="block w-full mt-1 px-3 py-2 text-gray-700 border rounded-md focus:outline-none"
                                rows="1"
                                readonly
                                @click="copyToClipboard"
                                ref="accessTokenField"
                                >{{ token }}</textarea
                            >
                        </div>

                        <div class="mt-4">
                            <InputLabel class="mb-1" value="Refresh Token" />
                            <textarea
                                class="block w-full mt-1 px-3 py-2 text-gray-700 border rounded-md focus:outline-none"
                                rows="1"
                                readonly
                                @click="copyToClipboard"
                                ref="refreshTokenField"
                                >{{ refresh_token }}</textarea
                            >
                        </div>

                        <div class="mt-4 text-sm text-gray-600">
                            <p>
                                Store these tokens securely. The refresh token
                                can be used to generate new access tokens when
                                they expire.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Existing Tokens List -->
                <div
                    v-if="tokens.length > 0"
                    class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
                >
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Your API Tokens
                    </h3>

                    <div class="space-y-6">
                        <div
                            v-for="token in tokens"
                            :key="token.id"
                            class="flex items-center justify-between"
                        >
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ token.name }}
                                </div>

                                <div class="text-xs text-gray-500">
                                    Created {{ formatDate(token.created_at) }} •
                                    Expires {{ formatDate(token.expires_at) }}
                                </div>

                                <div
                                    v-if="
                                        token.scopes && token.scopes.length > 0
                                    "
                                    class="mt-1"
                                >
                                    <span
                                        v-for="scope in token.scopes"
                                        :key="scope"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1"
                                    >
                                        {{ scope }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <DangerButton
                                    @click="confirmTokenDeletion(token.id)"
                                >
                                    Revoke
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Documentation -->
                <div
                    class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
                >
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        API Usage Instructions
                    </h3>

                    <div class="mt-4 text-sm text-gray-600 space-y-4">
                        <div>
                            <h4 class="font-medium">Authentication</h4>
                            <p>
                                Use your access token in API requests by
                                including it in the Authorization header:
                            </p>
                            <pre
                                class="bg-gray-100 rounded p-2 mt-1 overflow-x-auto"
                            >
Authorization: Bearer YOUR_ACCESS_TOKEN</pre
                            >
                        </div>

                        <div>
                            <h4 class="font-medium">Refreshing Tokens</h4>
                            <p>
                                When your access token expires, use your refresh
                                token to get a new one:
                            </p>
                            <pre
                                class="bg-gray-100 rounded p-2 mt-1 overflow-x-auto"
                            >
POST /api/tokens/refresh
Content-Type: application/json

{
    "refresh_token": "YOUR_REFRESH_TOKEN"
}</pre
                            >
                        </div>

                        <div>
                            <h4 class="font-medium">Revoking Tokens</h4>
                            <p>To revoke a refresh token:</p>
                            <pre
                                class="bg-gray-100 rounded p-2 mt-1 overflow-x-auto"
                            >
POST /api/tokens/revoke
Content-Type: application/json

{
    "refresh_token": "YOUR_REFRESH_TOKEN"
}</pre
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Token Confirmation Modal -->
        <Modal :show="confirmingTokenDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Are you sure you want to revoke this token?
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Once a token is revoked, it cannot be used for API requests
                    anymore.
                </p>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"
                        >Cancel</SecondaryButton
                    >

                    <DangerButton
                        class="ml-3"
                        :class="{ 'opacity-25': processing }"
                        :disabled="processing"
                        @click="revokeToken"
                    >
                        Revoke Token
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<script>
import { ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Checkbox from "@/Components/Checkbox.vue";
import Modal from "@/Components/Modal.vue";

export default {
    components: {
        AppLayout,
        InputLabel,
        TextInput,
        InputError,
        PrimaryButton,
        DangerButton,
        SecondaryButton,
        Checkbox,
        Modal,
    },

    props: {
        tokens: Array,
        availableScopes: Object,
        token: String,
        refresh_token: String,
        status: String,
    },

    setup(props) {
        const processing = ref(false);
        const confirmingTokenDeletion = ref(false);
        const tokenIdToDelete = ref(null);
        const page = usePage();
        const errors = ref({});

        const form = useForm({
            name: "",
            scopes: [],
        });

        const accessTokenField = ref(null);
        const refreshTokenField = ref(null);

        const createToken = () => {
            form.post(route("api-tokens.store"), {
                onStart: () => {
                    processing.value = true;
                },
                onFinish: () => {
                    processing.value = false;
                    form.reset();
                },
            });
        };

        const confirmTokenDeletion = (tokenId) => {
            tokenIdToDelete.value = tokenId;
            confirmingTokenDeletion.value = true;
        };

        const closeModal = () => {
            confirmingTokenDeletion.value = false;
            tokenIdToDelete.value = null;
            processing.value = false;
        };

        const revokeToken = () => {
            processing.value = true;

            window.axios
                .delete(route("api-tokens.destroy", tokenIdToDelete.value))
                .then(() => {
                    window.location.reload();
                })
                .catch(() => {
                    processing.value = false;
                });
        };

        const formatDate = (dateString) => {
            return new Date(dateString).toLocaleString();
        };

        const copyToClipboard = (event) => {
            event.target.select();
            document.execCommand("copy");
        };

        return {
            form,
            processing,
            confirmingTokenDeletion,
            tokenIdToDelete,
            errors,
            accessTokenField,
            refreshTokenField,
            createToken,
            confirmTokenDeletion,
            closeModal,
            revokeToken,
            formatDate,
            copyToClipboard,
        };
    },
};
</script>
