<template>
  <div class="bg-slate-800 p-6 rounded-xl shadow space-y-4">
    <h2 class="text-lg font-semibold">Place Limit Order</h2>

    <div class="space-y-3">
      <div>
        <label class="block text-sm mb-1">Symbol</label>
        <select
          v-model="symbol"
          class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700 text-sm cursor-pointer"
        >
          <option value="BTC">BTC</option>
          <option value="ETH">ETH</option>
        </select>
      </div>

      <div>
        <label class="block text-sm mb-1">Side</label>
        <div class="flex gap-2">
          <button
            type="button"
            class="flex-1 px-3 py-2 rounded text-sm font-medium cursor-pointer"
            :class="side === 'buy' ? 'bg-emerald-500 text-white' : 'bg-slate-900 border border-slate-700'"
            @click="side = 'buy'"
          >
            Buy
          </button>
          <button
            type="button"
            class="flex-1 px-3 py-2 rounded text-sm font-medium cursor-pointer"
            :class="side === 'sell' ? 'bg-red-500 text-white' : 'bg-slate-900 border border-slate-700'"
            @click="side = 'sell'"
          >
            Sell
          </button>
        </div>
      </div>

      <div>
        <label class="block text-sm mb-1">Price (USD)</label>
        <input
          v-model.number="price"
          type="number"
          step="0.01"
          min="0"
          class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700 text-sm"
        />
      </div>

      <div>
        <label class="block text-sm mb-1">Amount ({{ symbol }})</label>
        <input
          v-model.number="amount"
          type="number"
          step="0.00000001"
          min="0"
          class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700 text-sm"
        />
      </div>

      <div class="text-xs text-slate-300">
        <p>Volume: {{ formatMoney(volume) }} USD</p>
        <p>Commission (1.5%): {{ formatMoney(fee) }} USD</p>
        <p v-if="side === 'buy'">
          Total cost: {{ formatMoney(total) }} USD
        </p>
      </div>

      <button
        class="w-full mt-2 px-3 py-2 rounded bg-emerald-500 hover:bg-emerald-600 text-sm font-medium cursor-pointer"
        @click="submit"
        :disabled="loading"
        :class="{'opacity-50 cursor-not-allowed': loading}"
      >
        <span v-if="loading">Placing...</span>
        <span v-else>Place Order</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { formatMoney } from '../utils';
import { useToast } from '../composables/useToast';

const emit = defineEmits(['order-placed']);

const props = defineProps({
  profile: Object,
});

const { addToast } = useToast();

const symbol = ref('BTC');
const side = ref('buy');
const price = ref(0);
const amount = ref(0);
const loading = ref(false);

const volume = computed(() => (price.value || 0) * (amount.value || 0));
const fee = computed(() => volume.value * 0.015);
const total = computed(() => volume.value + fee.value);

const submit = async () => {
  loading.value = true;

  try {
    await axios.post('/api/orders', {
      symbol: symbol.value,
      side: side.value,
      price: price.value,
      amount: amount.value,
    });

    addToast('Order placed successfully', 'success');
    emit('order-placed');
  } catch (e) {
    const msg = e.response?.data?.message || 'Failed to place order';
    addToast(msg, 'error');
  } finally {
    loading.value = false;
  }
};
</script>
