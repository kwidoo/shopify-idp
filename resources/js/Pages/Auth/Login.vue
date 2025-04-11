<script setup lang="ts">
import Checkbox from "@/Components/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => {
            form.reset("password");
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400"
                        >Remember me</span
                    >
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>
        </form>

        <!-- Shopify OIDC Login Button -->
        <div class="mt-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div
                        class="w-full border-t border-gray-300 dark:border-gray-700"
                    ></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span
                        class="bg-white px-2 text-gray-500 dark:bg-gray-900 dark:text-gray-400"
                        >Or continue with</span
                    >
                </div>
            </div>

            <div class="mt-6">
                <Link
                    :href="route('auth.shopify')"
                    class="flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <svg
                        class="h-5 w-5 mr-2"
                        viewBox="0 0 64 64"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M49.1718 14.3812C49.1471 14.1124 48.9401 14 48.785 14C48.6299 14 42.6291 14 42.6291 14C42.6291 14 36.7024 8.07812 36.4211 7.79688C36.1399 7.51562 35.6688 7.57031 35.4882 7.63281C35.4882 7.63281 34.3653 8.00781 32.6512 8.625C32.4954 8.34375 32.2883 8 32.0071 7.65625C31.1391 6.5625 29.9173 5.75 28.2774 5.75C28.2032 5.75 28.1291 5.75 28.055 5.75C27.214 4.625 26.0662 4 24.9434 4C21.3129 4 17.6331 7.48438 15.9684 12.625C14.8456 16.1719 14.6632 19.125 15.4818 20.9688C15.2996 21.0312 15.1445 21.0938 15.0704 21.1562C13.7991 21.7188 10.4281 23.3438 9.90576 28.0625C9.5257 31.6562 13.3773 36.4062 16.1473 38.4688C18.5734 40.2969 20.8437 41.2188 20.8437 41.2188L38.9232 45.7188V45.6562L39.0784 45.7188L39.0784 14.9375L49.1718 14.3812ZM33.0248 9.93281L29.9173 11.2969C29.9173 11.0625 29.9173 10.8281 29.9173 10.5312C29.9173 7.96875 30.7359 5.9375 31.8587 5.9375C32.7268 5.9375 33.0248 7.60938 33.0248 9.93281ZM28.0056 6.65625C28.5526 6.65625 29.0236 6.78125 29.4701 7.03125C28.1989 7.8125 27.0514 9.44531 26.4057 11.6016L23.8048 12.7031C24.6727 9.5 26.514 6.65625 28.0056 6.65625ZM24.9434 5C25.514 5 26.0117 5.3125 26.3839 5.75C24.2893 6.0625 22.1453 8.9375 21.3129 13.0312L18.2053 14.3438C19.1723 9.21875 22.2511 5 24.9434 5Z"
                            fill="#95BF47"
                        />
                        <path
                            d="M48.785 14C48.6299 14 42.6291 14 42.6291 14C42.6291 14 36.7024 8.07812 36.4211 7.79688C36.2907 7.66644 36.1228 7.59482 35.9499 7.57275L39.0781 45.7188L39.0781 14.9375L49.1715 14.3812C49.1468 14.1124 48.9401 14 48.785 14Z"
                            fill="#5E8E3E"
                        />
                        <path
                            d="M28.2774 21.9375L26.3345 28.4375C26.3345 28.4375 24.7439 27.6875 22.8751 27.6875C20.0805 27.6875 20.006 29.4062 20.006 29.7812C20.006 32.4375 27.4588 33.4375 27.4588 39.9062C27.4588 45.0625 24.003 48.125 19.5724 48.125C14.4484 48.125 11.5054 44.4688 11.5054 44.4688L12.8754 40.625C12.8754 40.625 15.9336 43.1875 18.3351 43.1875C19.9997 43.1875 20.526 41.8125 20.526 40.9375C20.526 37.5 14.5719 37.4375 14.5719 31.4375C14.5719 26.5625 18.1064 20.75 25.952 20.75C28.5529 20.75 28.2774 21.9375 28.2774 21.9375Z"
                            fill="white"
                        />
                        <path
                            d="M54.5 18.0001H53.3V24.3001C53.3 24.3001 51.9 25.2001 50.8 25.2001V18.0001H49.6V25.2001C48.5 25.2001 47.1 24.3001 47.1 24.3001V18.0001H45.9V24.3001C45.9 24.3001 42.9 26.1001 40.7 28.3001C38.5 30.6001 39.1 32.1001 39.1 40.2001C39.1 48.3001 39.4 50.0001 39.4 50.0001H40.6V40.0001H41.8V50.0001H43V40.0001H44.2V50.0001H45.4V40.0001H46.6V50.0001H47.8V40.0001H49V50.0001H50.2V40.0001H51.4V50.0001H52.6V40.0001H53.8C53.8 40.0001 54.3 40.0001 54.5 39.7001C54.8 39.4001 54.8 39.0001 54.8 39.0001V18.0001H54.5Z"
                            fill="#FF9900"
                        />
                    </svg>
                    Login with Shopify
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
