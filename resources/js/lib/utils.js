import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export function formatCurrency(amount, currency = 'BDT') {
    const num = parseFloat(amount || 0);
    if (currency === 'BDT') {
        return '৳' + num.toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(num);
}

export function formatDate(date, options = {}) {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-BD', {
        year: 'numeric', month: 'short', day: 'numeric', ...options,
    });
}

export function formatDateTime(date) {
    if (!date) return '—';
    return new Date(date).toLocaleString('en-BD', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

export function formatTime(date) {
    if (!date) return '—';
    return new Date(date).toLocaleTimeString('en-BD', { hour: '2-digit', minute: '2-digit' });
}

export function timeAgo(date) {
    if (!date) return '—';
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    if (seconds < 60) return `${seconds}s ago`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    return `${Math.floor(seconds / 86400)}d ago`;
}

export function minutesDiff(from, to = new Date()) {
    return Math.floor((new Date(to) - new Date(from)) / 60000);
}

export function statusColor(status) {
    const map = {
        active: 'green', inactive: 'gray', pending: 'yellow',
        confirmed: 'blue', preparing: 'orange', ready: 'green',
        served: 'purple', completed: 'green', cancelled: 'red',
        void: 'red', paid: 'green', draft: 'gray', approved: 'green',
        rejected: 'red', present: 'green', absent: 'red', late: 'yellow',
        half_day: 'orange', leave: 'purple', holiday: 'blue',
        available: 'green', occupied: 'red', reserved: 'yellow', maintenance: 'gray',
    };
    return map[status] || 'gray';
}

export function badgeClass(status) {
    const color = statusColor(status);
    const map = {
        green: 'bg-green-100 text-green-800',
        red: 'bg-red-100 text-red-800',
        yellow: 'bg-yellow-100 text-yellow-800',
        orange: 'bg-orange-100 text-orange-800',
        blue: 'bg-blue-100 text-blue-800',
        purple: 'bg-purple-100 text-purple-800',
        gray: 'bg-gray-100 text-gray-800',
    };
    return map[color] || map.gray;
}

export function debounce(fn, ms = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), ms);
    };
}

export function groupBy(arr, key) {
    return arr.reduce((acc, item) => {
        const k = typeof key === 'function' ? key(item) : item[key];
        (acc[k] = acc[k] || []).push(item);
        return acc;
    }, {});
}
