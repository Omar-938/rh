import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Composable pour gérer les permissions et rôles côté client.
 * Les permissions sont basées sur le rôle de l'utilisateur authentifié.
 */
export function usePermissions() {
    const page = usePage();
    const user = computed(() => page.props.auth?.user ?? null);
    const role = computed(() => user.value?.role ?? null);

    const isAdmin    = computed(() => role.value === 'admin');
    const isManager  = computed(() => role.value === 'manager');
    const isEmployee = computed(() => role.value === 'employee');

    const canManageCompany      = computed(() => isAdmin.value);
    const canApproveRequests    = computed(() => isAdmin.value || isManager.value);
    const canViewAllEmployees   = computed(() => isAdmin.value || isManager.value);
    const canManagePayroll      = computed(() => isAdmin.value);
    const canManageDocuments    = computed(() => isAdmin.value || isManager.value);
    const canViewOwnData        = computed(() => !!user.value);

    function hasRole(...roles) {
        return roles.includes(role.value);
    }

    function can(permission) {
        const permissions = {
            'manage-company':    isAdmin.value,
            'approve-leaves':    canApproveRequests.value,
            'approve-overtime':  canApproveRequests.value,
            'view-all-employees':canViewAllEmployees.value,
            'manage-payroll':    canManagePayroll.value,
            'manage-documents':  canManageDocuments.value,
            'manage-settings':   isAdmin.value,
            'manage-billing':    isAdmin.value,
        };
        return permissions[permission] ?? false;
    }

    return {
        user,
        role,
        isAdmin,
        isManager,
        isEmployee,
        canManageCompany,
        canApproveRequests,
        canViewAllEmployees,
        canManagePayroll,
        canManageDocuments,
        canViewOwnData,
        hasRole,
        can,
    };
}
