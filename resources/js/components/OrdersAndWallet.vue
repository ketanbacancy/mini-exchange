<template>
  <div class="bg-slate-800 p-6 rounded-xl shadow space-y-4">
    <h2 class="text-lg font-semibold mb-2">Wallet & Orders</h2>

    <section class="space-y-2">
      <h3 class="text-sm font-semibold text-slate-300">USD Balance</h3>
      <p class="text-2xl font-mono">{{ formatMoney(profile?.balance) }} USD</p>
    </section>

    <section class="space-y-2">
      <h3 class="text-sm font-semibold text-slate-300">Assets</h3>
      <div v-if="profile?.assets?.length" class="space-y-1">
        <div
          v-for="asset in profile.assets"
          :key="asset.symbol"
          class="flex justify-between text-sm"
        >
          <span>{{ asset.symbol }}</span>
          <span>{{ formatMoney(asset.amount, 8) }} (locked: {{ formatMoney(asset.locked_amount, 8) }})</span>
        </div>
      </div>
      <p v-else class="text-sm text-slate-400">No assets yet.</p>
    </section>

    <section class="space-y-2">
      <div class="flex items-center justify-between flex-wrap gap-2">
        <h3 class="text-sm font-semibold text-slate-300">My Orders</h3>
        <div class="flex gap-2">
          <select
            v-model="filterSymbol"
            class="px-2 py-1 rounded bg-slate-900 border border-slate-700 text-xs cursor-pointer"
          >
            <option value="">All Symbols</option>
            <option value="BTC">BTC</option>
            <option value="ETH">ETH</option>
          </select>
          <select
            v-model="filterSide"
            class="px-2 py-1 rounded bg-slate-900 border border-slate-700 text-xs cursor-pointer"
          >
            <option value="">All Sides</option>
            <option value="buy">Buy</option>
            <option value="sell">Sell</option>
          </select>
          <select
            v-model="filterStatus"
            class="px-2 py-1 rounded bg-slate-900 border border-slate-700 text-xs cursor-pointer"
          >
            <option value="">All Status</option>
            <option value="1">Open</option>
            <option value="2">Filled</option>
            <option value="3">Cancelled</option>
          </select>
        </div>
      </div>
      <div class="border border-slate-700 rounded-lg overflow-hidden">
        <table class="w-full text-xs">
          <thead class="bg-slate-900">
            <tr>
              <th class="px-2 py-1 text-left">ID</th>
              <th class="px-2 py-1 text-left">Symbol</th>
              <th class="px-2 py-1 text-left">Side</th>
              <th class="px-2 py-1 text-right">Price</th>
              <th class="px-2 py-1 text-right">Amount</th>
              <th class="px-2 py-1 text-left">Status</th>
              <th class="px-2 py-1 text-right"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="order in filteredOrders"
              :key="order.id"
              class="border-t border-slate-700"
            >
              <td class="px-2 py-1">{{ order.id }}</td>
              <td class="px-2 py-1">{{ order.symbol }}</td>
              <td class="px-2 py-1">
                <span
                  :class="order.side === 'buy' ? 'text-emerald-400' : 'text-red-400'"
                >
                  {{ order.side.toUpperCase() }}
                </span>
              </td>
              <td class="px-2 py-1 text-right">{{ formatMoney(order.price) }}</td>
              <td class="px-2 py-1 text-right">{{ formatMoney(order.amount, 8) }}</td>
              <td class="px-2 py-1">
                <span :class="statusClass(order.status)">
                  {{ statusLabel(order.status) }}
                </span>
              </td>
              <td class="px-2 py-1 text-right">
                <button
                  v-if="order.status === 1"
                  class="px-2 py-1 text-xs rounded bg-red-500 hover:bg-red-600 cursor-pointer"
                  @click="cancel(order)"
                >
                  Cancel
                </button>
              </td>
            </tr>
            <tr v-if="!filteredOrders.length">
              <td class="px-2 py-2 text-center text-slate-400" colspan="7">
                No orders yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import { formatMoney } from '../utils';
import { useToast } from '../composables/useToast';

const props = defineProps({
  profile: Object,
  orders: Array,
  user: Object,
});

const emit = defineEmits(['refresh']);
const { addToast } = useToast();

const filterSymbol = ref('');
const filterSide = ref('');
const filterStatus = ref('');

const filteredOrders = computed(() => {
  let res = props.orders ?? [];
  
  if (filterSymbol.value) {
    res = res.filter(o => o.symbol === filterSymbol.value);
  }
  if (filterSide.value) {
    res = res.filter(o => o.side === filterSide.value);
  }
  if (filterStatus.value) {
    res = res.filter(o => o.status === parseInt(filterStatus.value));
  }
  
  return res;
});

const statusLabel = (status) => {
  if (status === 1) return 'Open';
  if (status === 2) return 'Filled';
  if (status === 3) return 'Cancelled';
  return 'Unknown';
};

const statusClass = (status) => {
  if (status === 1) return 'text-yellow-300';
  if (status === 2) return 'text-emerald-400';
  if (status === 3) return 'text-slate-400 line-through';
  return 'text-slate-300';
};

const cancel = async (order) => {
  if (!confirm(`Cancel order #${order.id}?`)) return;

  try {
    await axios.post(`/api/orders/${order.id}/cancel`);
    addToast('Order cancelled', 'info');
    emit('refresh');
  } catch (e) {
    addToast('Failed to cancel order', 'error');
  }
};
</script>
