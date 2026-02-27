/**
 * Formateurs divers pour SimpliRH
 */

/**
 * Formate un montant en euros
 */
export function formatCurrency(amount, currency = 'EUR') {
    if (amount === null || amount === undefined) return '';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
    }).format(amount);
}

/**
 * Formate un nombre avec séparateurs français
 */
export function formatNumber(value, decimals = 0) {
    if (value === null || value === undefined) return '';
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(value);
}

/**
 * Formate une taille de fichier
 */
export function formatFileSize(bytes) {
    if (!bytes) return '0 o';
    const units = ['o', 'Ko', 'Mo', 'Go'];
    let i = 0;
    let size = bytes;
    while (size >= 1024 && i < units.length - 1) {
        size /= 1024;
        i++;
    }
    return `${Math.round(size * 10) / 10} ${units[i]}`;
}

/**
 * Formate un numéro de téléphone français
 */
export function formatPhone(phone) {
    if (!phone) return '';
    const cleaned = phone.replace(/\D/g, '');
    if (cleaned.length === 10) {
        return cleaned.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5');
    }
    return phone;
}

/**
 * Initiales d'un nom complet
 */
export function getInitials(name) {
    if (!name) return '?';
    return name
        .split(' ')
        .filter(Boolean)
        .map(part => part[0].toUpperCase())
        .slice(0, 2)
        .join('');
}

/**
 * Capitalise la première lettre
 */
export function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

/**
 * Tronque un texte avec ellipsis
 */
export function truncate(str, length = 50) {
    if (!str) return '';
    if (str.length <= length) return str;
    return str.slice(0, length) + '…';
}

/**
 * Couleur de fond pour badge de statut
 */
export function statusColor(status) {
    const colors = {
        pending:   { bg: 'bg-warning-100', text: 'text-warning-700', dot: 'bg-warning-500' },
        approved:  { bg: 'bg-success-100', text: 'text-success-700', dot: 'bg-success-500' },
        rejected:  { bg: 'bg-danger-100',  text: 'text-danger-700',  dot: 'bg-danger-500'  },
        cancelled: { bg: 'bg-gray-100',    text: 'text-gray-600',    dot: 'bg-gray-400'    },
        active:    { bg: 'bg-success-100', text: 'text-success-700', dot: 'bg-success-500' },
        inactive:  { bg: 'bg-gray-100',    text: 'text-gray-600',    dot: 'bg-gray-400'    },
        trial:     { bg: 'bg-primary-100', text: 'text-primary-700', dot: 'bg-primary-500' },
    };
    return colors[status] ?? colors.cancelled;
}

/**
 * Pluralise un mot en français
 */
export function pluralize(count, singular, plural) {
    return count <= 1 ? singular : plural;
}

/**
 * Génère une couleur aléatoire depuis une liste de couleurs prédéfinies
 */
export function colorFromString(str) {
    const colors = [
        '#2E86C1', '#1B4F72', '#27AE60', '#F39C12',
        '#8E44AD', '#C0392B', '#16A085', '#D35400',
        '#2980B9', '#27AE60', '#E74C3C', '#F39C12',
    ];
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    return colors[Math.abs(hash) % colors.length];
}
