<template>
  <div class="min-h-screen bg-slate-900 text-slate-100 flex flex-col">
    <ToastContainer />
    <header class="border-b border-slate-800 px-6 py-4 flex items-center justify-between">
      <h1 class="text-xl font-semibold">Mini Exchange</h1>
      <div v-if="user">
        <span class="text-sm mr-4">Hi, {{ user.name }} | Balance: {{ formatMoney(profile?.balance) }} USD</span>
        <button
          class="px-3 py-1 rounded bg-red-500 hover:bg-red-600 text-sm cursor-pointer"
          @click="logout"
        >
          Logout
        </button>
      </div>
    </header>

    <main class="flex-1 p-6">
      <div v-if="loading" class="flex justify-center items-center h-64 text-slate-400">
        Loading...
      </div>
      <div v-else-if="!user" class="max-w-sm mx-auto">
        <LoginForm @logged-in="handleLoggedIn" />
      </div>
      <div v-else class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-1 space-y-6">
          <LimitOrderForm
            :profile="profile"
            @order-placed="reloadData"
          />
          <RecentTrades :user="user" />
        </div>
        <div class="md:col-span-2">
          <OrdersAndWallet
            :profile="profile"
            :orders="orders"
            :user="user"
            @refresh="reloadData"
          />
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import LoginForm from './LoginForm.vue';
import LimitOrderForm from './LimitOrderForm.vue';
import OrdersAndWallet from './OrdersAndWallet.vue';
import RecentTrades from './RecentTrades.vue';
import ToastContainer from './ToastContainer.vue';
import { formatMoney } from '../utils';

const loading = ref(true);
const user = ref(null);
const profile = ref(null);
const orders = ref([]);

const fetchProfile = async () => {
  const { data } = await axios.get('/api/profile');
  profile.value = data;
  user.value = { id: data.id, name: data.name };
};

const fetchOrders = async () => {
  const { data } = await axios.get('/api/my-orders');
  orders.value = data.data;
};

const reloadData = async () => {
  await Promise.all([fetchProfile(), fetchOrders()]);
};

const handleLoggedIn = async () => {
  await reloadData();
  subscribeToEvents();
};

const subscribeToEvents = () => {
  if (!window.Echo || !user.value) return;

  window.Echo.private(`user.${user.value.id}`)
    .listen('.OrderMatched', (e) => {
      // Update balances from payload
      if (profile.value) {
        if (e.buyer && e.buyer.id === user.value.id) {
          profile.value.balance = e.buyer.balance;
        } else if (e.seller && e.seller.id === user.value.id) {
          profile.value.balance = e.seller.balance;
        }
      }

      // Refresh orders & profile for accuracy
      reloadData();
    });
};

const logout = async () => {
  await axios.post('/logout');
  user.value = null;
  profile.value = null;
  orders.value = [];
};

onMounted(async () => {
  try {
    await reloadData();
    subscribeToEvents();
  } catch (e) {
    // Not logged in, ignore
  } finally {
    loading.value = false;
  }
});
</script>
