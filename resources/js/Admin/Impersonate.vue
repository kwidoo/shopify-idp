<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/inertia-vue3'
import { Inertia } from '@inertiajs/inertia'

const users = ref([])
const selectedUserId = ref(null)

const form = useForm({
    user_id: null,
})

const fetchUsers = async () => {
    const response = await axios.get('/api/admin/users') // You need to implement this backend route
    users.value = response.data
}

const impersonate = () => {
    form.user_id = selectedUserId.value
    form.post('/impersonate', {
        onSuccess: () => {
            alert('Impersonation started. You’ll be redirected...')
            // Redirect to Shopify if needed
        },
    })
}

fetchUsers()
</script>

<template>
    <div class="p-6">
        <h1 class="text-xl font-bold mb-4">Impersonate a User</h1>
        <select v-model="selectedUserId" class="border rounded p-2 mb-4 w-full">
            <option disabled value="">Select a user</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }} ({{ user.email }})
            </option>
        </select>
        <button @click="impersonate" class="bg-blue-600 text-white px-4 py-2 rounded" :disabled="!selectedUserId">
            Impersonate
        </button>
    </div>
</template>
