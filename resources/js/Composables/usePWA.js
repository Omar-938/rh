import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Composable pour gérer l'installation PWA.
 * Expose l'état d'installation et la méthode pour déclencher la bannière native.
 */
export function usePWA() {
    const canInstall     = ref(false);
    const isInstalled    = ref(false);
    const isStandalone   = ref(false);
    const deferredPrompt = ref(null);

    function checkStandalone() {
        isStandalone.value =
            window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true;
    }

    function handleBeforeInstallPrompt(e) {
        e.preventDefault();
        deferredPrompt.value = e;
        canInstall.value = true;
    }

    function handleAppInstalled() {
        canInstall.value = false;
        isInstalled.value = true;
        deferredPrompt.value = null;
    }

    async function install() {
        if (!deferredPrompt.value) return false;
        deferredPrompt.value.prompt();
        const { outcome } = await deferredPrompt.value.userChoice;
        if (outcome === 'accepted') {
            isInstalled.value = true;
            canInstall.value = false;
        }
        deferredPrompt.value = null;
        return outcome === 'accepted';
    }

    function dismiss() {
        canInstall.value = false;
        // Stocker le refus pour ne plus afficher pendant 7 jours
        const expires = Date.now() + 7 * 24 * 60 * 60 * 1000;
        localStorage.setItem('pwa_install_dismissed', String(expires));
    }

    function wasDismissed() {
        const dismissed = localStorage.getItem('pwa_install_dismissed');
        if (!dismissed) return false;
        return Date.now() < Number(dismissed);
    }

    onMounted(() => {
        checkStandalone();
        if (!wasDismissed() && !isStandalone.value) {
            window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        }
        window.addEventListener('appinstalled', handleAppInstalled);
    });

    onUnmounted(() => {
        window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        window.removeEventListener('appinstalled', handleAppInstalled);
    });

    return { canInstall, isInstalled, isStandalone, install, dismiss };
}
