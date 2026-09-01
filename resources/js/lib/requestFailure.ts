import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { t } from '@/lib/i18n';

export function initializeRequestFailureToast(): void {
    router.on('exception', () => {
        toast.error(t('ui.state.unreachable'));
    });
}
