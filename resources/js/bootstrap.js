import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Send selected branch to backend for admin branch context
window.axios.interceptors.request.use(config => {
    try {
        const stored = localStorage.getItem('selectedBranch');
        if (stored) {
            const branch = JSON.parse(stored);
            if (branch?.id) {
                config.headers['X-Branch-Id'] = branch.id;
            }
        }
    } catch {}
    return config;
});
