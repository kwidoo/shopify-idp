<script setup lang = "ts" >
import { ref, onMounted } from 'vue';
import axios from 'axios';

const users = ref<any[]>([]);
const selectedUserId = ref<string | null>(null);
const token = ref<string | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

const fetchUsers = async () => {
    try {
        const response = await axios.get('/api/users'); // must return impersonatable users
        users.value = response.data;
    } catch (e) {
        error.value = 'Failed to load users';
    }
};

const impersonate = async () => {
    if (!selectedUserId.value) return;
    try {
        loading.value = true;
        const response = await axios.post('/impersonate', {
            user_id: selectedUserId.value
        });
        token.value = response.data.access_token;
    } catch (e) {
        error.value = 'Impersonation failed';
    } finally {
        loading.value = false;
    }
};

onMounted(fetchUsers);
</script>

    < template >
    <div class="p-4 rounded-xl shadow-lg bg-white" >
        <h2 class="text-xl font-bold mb-4" > Impersonate a User </h2>

            < div class="mb-4" >
                <label class="block text-sm font-medium mb-1" > Select User </label>
                    < select v - model="selectedUserId" class="w-full p-2 border rounded" >
                        <option disabled value = "" > --Choose a user-- </option>
                            < option v -for= "user in users" : key = "user.id" : value = "user.id" >
                                {{ user.name }} ({{ user.email }})
</option>
    </select>
    </div>

    < button @click="impersonate" : disabled = "loading || !selectedUserId" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" >
        {{ loading ? 'Impersonating...' : 'Impersonate' }}
</button>

    < div v -if= "token" class="mt-4" >
        <label class="block text-sm font-medium" > Access Token: </label>
            < textarea readonly class="w-full mt-1 p-2 border rounded h-32" > {{ token }}</textarea>
                </div>

                < p v -if= "error" class="text-red-500 mt-4" > {{ error }}</p>
                    </div>
                    </template>

                    < style scoped >
                        select, textarea {
    font - family: monospace;
}
</style>
