/**
 * Format number to have max 1 decimal place, removed if .0
 * Examples: 10.0000 -> 10, 10.5000 -> 10.5
 */
export const formatNumber = (num) => {
    if (num === null || num === undefined) return '0';
    const val = parseFloat(num);
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(val);
};

export const formatNumberFixed = (num, fractionDigits = 2) => {
    if (num === null || num === undefined) return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits
    }).format(0);
    const val = parseFloat(num);
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits
    }).format(isNaN(val) ? 0 : val);
};

/**
 * Format currency to IDR with no decimal places
 * Examples: 150000.00 -> Rp 150.000
 */
export const formatCurrency = (num) => {
    if (num === null || num === undefined) return 'Rp 0';
    const val = parseFloat(num);
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(val);
};

/**
 * Format date to DD/MM/YYYY
 * Examples: 2026-01-28 -> 28/01/2026
 */
export const formatDate = (date) => {
    if (!date) return '-';
    try {
        const d = new Date(date);
        if (isNaN(d.getTime())) return date;
        return d.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    } catch (e) {
        return date;
    }
};

export const formatTime = (time) => {
    if (!time) return '-';
    try {
        const raw = String(time);
        const m = raw.match(/^(\d{2}):(\d{2})(?::\d{2})?$/);
        if (!m) return time;
        return `${m[1]}:${m[2]}`;
    } catch (e) {
        return time;
    }
};

export const formatDateTime = (dateTime) => {
    if (!dateTime) return '-';
    try {
        const d = new Date(dateTime);
        if (isNaN(d.getTime())) return dateTime;
        const date = d.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
        const time = d.toLocaleTimeString('en-GB', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
        return `${date} ${time}`;
    } catch (e) {
        return dateTime;
    }
};
