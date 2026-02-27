import { ref } from 'vue';

const toasts = ref([]);
let nextId = 0;

/**
 * Composable global pour les notifications toast.
 * Utiliser dans n'importe quel composant.
 */
export function useToast() {
    function show(message, type = 'success', duration = 4000) {
        const id = ++nextId;
        toasts.value.push({ id, message, type });
        if (duration > 0) {
            setTimeout(() => dismiss(id), duration);
        }
        return id;
    }

    function dismiss(id) {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index !== -1) toasts.value.splice(index, 1);
    }

    function success(message, duration = 4000) {
        return show(message, 'success', duration);
    }

    function error(message, duration = 6000) {
        return show(message, 'error', duration);
    }

    function warning(message, duration = 5000) {
        return show(message, 'warning', duration);
    }

    function info(message, duration = 4000) {
        return show(message, 'info', duration);
    }

    function clear() {
        toasts.value = [];
    }

    return { toasts, show, dismiss, success, error, warning, info, clear };
}
