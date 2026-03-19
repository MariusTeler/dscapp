import axios from 'axios';

axios.defaults.withCredentials = true;

// Set common headers
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['credentials'] = 'same-origin';

// Handle authentication errors
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 419) {
            // Session expired - reload page to redirect to login
            //window.location.reload();
        } else if (error.response?.status === 401) {
            // Unauthenticated - redirect to login
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default axios;
