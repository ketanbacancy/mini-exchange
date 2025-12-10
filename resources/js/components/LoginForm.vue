<template>
  <form @submit.prevent="submit" class="bg-slate-800 p-6 rounded-xl shadow">
    <h2 class="text-lg font-semibold mb-4">Login</h2>
    <div class="space-y-3">
      <div>
        <label class="block text-sm mb-1">Email</label>
        <input
          v-model="form.email"
          type="email"
          class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700 text-sm"
          required
        />
      </div>
      <div>
        <label class="block text-sm mb-1">Password</label>
        <input
          v-model="form.password"
          type="password"
          class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700 text-sm"
          required
        />
      </div>
      <button
        type="submit"
        class="w-full mt-2 px-3 py-2 rounded bg-emerald-500 hover:bg-emerald-600 text-sm font-medium"
        :disabled="loading"
      >
        <span v-if="loading">Logging in...</span>
        <span v-else>Login</span>
      </button>
      <p v-if="error" class="text-sm text-red-400 mt-2">{{ error }}</p>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';

const emit = defineEmits(['logged-in']);

const form = reactive({
  email: '',
  password: '',
});

const loading = ref(false);
const error = ref('');

const submit = async () => {
  loading.value = true;
  error.value = '';
  try {
    await axios.get('/sanctum/csrf-cookie');
    await axios.post('/login', form);
    emit('logged-in');
  } catch (e) {
    error.value = 'Invalid credentials';
  } finally {
    loading.value = false;
  }
};
</script>
