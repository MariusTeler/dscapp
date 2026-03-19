import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { PrimeReactProvider } from 'primereact/api';
import ReactDOMServer from 'react-dom/server';
import './lib/axios';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const primeReactConfig = {
    appendTo: 'self' as const,
    cssTransition: true,
    hideOverlaysOnDocumentScrolling: false,
};

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: (name) =>
            resolvePageComponent(
                `./pages/${name}.tsx`,
                import.meta.glob('./pages/**/*.tsx'),
            ),
        setup: ({ App, props }) => {
            return (
                <PrimeReactProvider value={primeReactConfig}>
                    <App {...props} />
                </PrimeReactProvider>
            );
        },
    }),
);
