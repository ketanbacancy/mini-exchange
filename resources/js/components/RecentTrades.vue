<template>
  <div class="bg-slate-800 p-6 rounded-xl shadow space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold">Recent Trades</h2>
      <select
        v-model="symbol"
        class="px-2 py-1 rounded bg-slate-900 border border-slate-700 text-xs cursor-pointer"
        @change="fetchTrades"
      >
        <option value="BTC">BTC</option>
        <option value="ETH">ETH</option>
      </select>
    </div>

    <div class="border border-slate-700 rounded-lg overflow-hidden max-h-60 overflow-y-auto">
      <table class="w-full text-xs">
        <thead class="bg-slate-900 sticky top-0">
          <tr>
            <th class="px-2 py-1 text-right">Price</th>
            <th class="px-2 py-1 text-right">Amount</th>
            <th class="px-2 py-1 text-right">Time</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="trade in trades"
            :key="trade.id"
            class="border-t border-slate-700"
          >
            <td class="px-2 py-1 text-right text-emerald-400 font-mono">{{ formatMoney(trade.price) }}</td>
            <td class="px-2 py-1 text-right font-mono">{{ formatMoney(trade.amount, 8) }}</td>
            <td class="px-2 py-1 text-right text-slate-400">
              {{ new Date(trade.created_at).toLocaleTimeString() }}
            </td>
          </tr>
          <tr v-if="!trades.length">
            <td class="px-2 py-2 text-center text-slate-400" colspan="3">
              No trades yet.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { formatMoney } from '../utils';

const props = defineProps({
  user: Object,
});

const symbol = ref('BTC');
const trades = ref([]);

const fetchTrades = async () => {
  try {
    const { data } = await axios.get(`/api/trades?symbol=${symbol.value}`);
    trades.value = data.data;
  } catch (e) {
    console.error(e);
  }
};

const subscribeToTrades = () => {
  if (!window.Echo || !props.user) return;

  // Since OrderMatched is private-user.{id}, we can only catch matches 
  // involved with the logged-in user unless we make a public channel.
  // Requirement says "Deliver to both parties", so we rely on that.
  // For a generic "Recent Trades" widget, we would typically need a public channel.
  // However, we can listen to the same user channel to at least update *our* trades.
  
  window.Echo.private(`user.${props.user.id}`)
    .listen('.OrderMatched', (e) => {
      // Add to list if symbol matches
      if (e.trade && e.trade.symbol === symbol.value) {
        trades.value.unshift({
           ...e.trade,
           created_at: new Date().toISOString() // Payload might not have created_at
        });
        if (trades.value.length > 20) trades.value.pop();
      }
    });
};

watch(() => props.user, () => {
  if (props.user) subscribeToTrades();
});

onMounted(() => {
  fetchTrades();
  subscribeToTrades();
});
</script>
