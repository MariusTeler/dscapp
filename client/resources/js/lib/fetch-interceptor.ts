/**
 * Global fetch interceptor to add CSRF token and handle authentication errors
 */

const originalFetch = window.fetch;

window.fetch = async (...args) => {
    const [url, options = {}] = args;
    
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
    
    // Add default headers including CSRF token
    const headers = new Headers(options.headers);
    if (csrfToken) {
        headers.set('X-CSRF-TOKEN', csrfToken);
    }
    headers.set('X-Requested-With', 'XMLHttpRequest');
    headers.set('credentials', 'same-origin');
    
    // Merge headers back into options
    const modifiedOptions = {
        ...options,
        headers,
    };
    
    const response = await originalFetch(url, modifiedOptions);
    
    // Handle authentication errors
    if (response.status === 419) {
        // Session expired - reload page to redirect to login
        // window.location.reload();
        return response;
    }
    
    if (response.status === 401) {
        // Unauthenticated - redirect to login
        window.location.href = '/login';
        return response;
    }
    
    return response;
};

export {};
