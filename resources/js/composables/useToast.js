import { ref } from 'vue';

const toasts = ref([]);

let id = 0;

export function useToast() {
    const addToast = (message, type = 'success', duration = 3000) => {
        const toastId = id++;
        toasts.value.push({ id: toastId, message, type });

        setTimeout(() => {
            removeToast(toastId);
        }, duration);
    };

    const removeToast = (id) => {
        toasts.value = toasts.value.filter(t => t.id !== id);
    };

    return {
        toasts,
        addToast,
        removeToast
    };
}
