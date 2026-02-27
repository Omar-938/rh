/**
 * Utilitaires de dates pour SimpliRH (locale française)
 */

const LOCALE = 'fr-FR';

/**
 * Formate une date en format lisible français
 * @param {string|Date} date
 * @param {object} options
 */
export function formatDate(date, options = {}) {
    if (!date) return '';
    const d = new Date(date);
    if (isNaN(d)) return '';
    return d.toLocaleDateString(LOCALE, {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        ...options,
    });
}

/**
 * Formate une date en format court (JJ/MM/AAAA)
 */
export function formatDateShort(date) {
    if (!date) return '';
    const d = new Date(date);
    if (isNaN(d)) return '';
    return d.toLocaleDateString(LOCALE, { day: '2-digit', month: '2-digit', year: 'numeric' });
}

/**
 * Formate une heure (HH:MM)
 */
export function formatTime(date) {
    if (!date) return '';
    const d = new Date(date);
    if (isNaN(d)) return '';
    return d.toLocaleTimeString(LOCALE, { hour: '2-digit', minute: '2-digit' });
}

/**
 * Formate une date et heure complète
 */
export function formatDateTime(date) {
    if (!date) return '';
    const d = new Date(date);
    if (isNaN(d)) return '';
    return d.toLocaleString(LOCALE, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Nom du mois en français
 */
export function getMonthName(monthIndex) {
    const d = new Date(2000, monthIndex, 1);
    return d.toLocaleDateString(LOCALE, { month: 'long' });
}

/**
 * Retourne le nom du jour en français
 */
export function getDayName(date, short = false) {
    const d = new Date(date);
    return d.toLocaleDateString(LOCALE, { weekday: short ? 'short' : 'long' });
}

/**
 * Calcule le nombre de jours ouvrés entre deux dates
 * (exclut samedis et dimanches)
 */
export function workingDaysBetween(start, end) {
    let count = 0;
    const s = new Date(start);
    const e = new Date(end);
    s.setHours(0, 0, 0, 0);
    e.setHours(0, 0, 0, 0);
    const cur = new Date(s);
    while (cur <= e) {
        const day = cur.getDay();
        if (day !== 0 && day !== 6) count++;
        cur.setDate(cur.getDate() + 1);
    }
    return count;
}

/**
 * Formate une durée en heures/minutes (ex: "2h30")
 */
export function formatDuration(totalMinutes) {
    if (!totalMinutes && totalMinutes !== 0) return '';
    const h = Math.floor(Math.abs(totalMinutes) / 60);
    const m = Math.abs(totalMinutes) % 60;
    const sign = totalMinutes < 0 ? '-' : '';
    if (h === 0) return `${sign}${m}min`;
    if (m === 0) return `${sign}${h}h`;
    return `${sign}${h}h${String(m).padStart(2, '0')}`;
}

/**
 * Vérifie si une date est aujourd'hui
 */
export function isToday(date) {
    const d = new Date(date);
    const today = new Date();
    return (
        d.getDate() === today.getDate() &&
        d.getMonth() === today.getMonth() &&
        d.getFullYear() === today.getFullYear()
    );
}

/**
 * Retourne la date du lundi de la semaine courante
 */
export function getWeekStart(date = new Date()) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(d.setDate(diff));
}

/**
 * Formate une date pour input type="date" (YYYY-MM-DD)
 */
export function toInputDate(date) {
    if (!date) return '';
    const d = new Date(date);
    if (isNaN(d)) return '';
    return d.toISOString().split('T')[0];
}

/**
 * Temps relatif en français ("il y a 3 minutes", "dans 2 jours")
 */
export function timeAgo(date) {
    const d = new Date(date);
    const now = new Date();
    const diffMs = now - d;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffSec < 60) return 'à l\'instant';
    if (diffMin < 60) return `il y a ${diffMin} min`;
    if (diffHour < 24) return `il y a ${diffHour}h`;
    if (diffDay === 1) return 'hier';
    if (diffDay < 7) return `il y a ${diffDay} jours`;
    return formatDateShort(date);
}
