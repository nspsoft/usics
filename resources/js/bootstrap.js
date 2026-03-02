import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just
 * a simple convenience so we don't have to attach every token manually.
 */

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Custom Axios Interceptor to catch 419 Page Expired errors
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 419) {
            alert('Sesi Anda telah habis (Error 419) karena terlalu lama tidak ada aktivitas server. Halaman ini akan di-_refresh_ secara otomatis agar Anda dapat bekerja kembali.\n\nJika ada data form penting yang baru saja diketik, _copy_ terlebih dahulu sebelum klik OK.');
            window.location.reload();
        }
        return Promise.reject(error);
    }
);

// Keep-Alive Ping every 30 minutes to prevent 419 Error
// This requests an empty response from server just to keep session alive
setInterval(() => {
    window.axios.get('/sanctum/csrf-cookie').catch(() => {
        // Silent catch
    });
}, 30 * 60 * 1000); // 30 minutes

