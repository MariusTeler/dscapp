import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { PrimeReactProvider } from 'primereact/api';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import './lib/axios';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const primeReactConfig = {
    appendTo: 'self' as const,
    cssTransition: true,
    hideOverlaysOnDocumentScrolling: false,
};

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        // Initialize theme after DOM is ready but before React renders
        initializeTheme();
        
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <PrimeReactProvider value={primeReactConfig}>
                    <App {...props} />
                </PrimeReactProvider>
            </StrictMode>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});
