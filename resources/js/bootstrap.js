import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 419) {
            const isAuthPage = ['/login', '/register'].includes(window.location.pathname);
            
            if (!isAuthPage) {
                alert('Sesi Anda telah habis (Error 419) karena terlalu lama tidak ada aktivitas server. Halaman ini akan di-_refresh_ secara otomatis agar Anda dapat bekerja kembali.\n\nJika ada data form penting yang baru saja diketik, _copy_ terlebih dahulu sebelum klik OK.');
            }
            
            window.location.reload();
        }
        return Promise.reject(error);
    }
);
