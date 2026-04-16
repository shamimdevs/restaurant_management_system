import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
    withXSRFToken: true,
});

api.interceptors.response.use(
    res => res,
    err => {
        if (err.response?.status === 401) {
            window.location.href = '/login';
        }
        return Promise.reject(err);
    }
);

export default api;
