import './bootstrap';
import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import 'primereact/resources/themes/lara-light-blue/theme.css';
import 'primeicons/primeicons.css';

createInertiaApp({
    title: (title) => `${title} — DSC Operator`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: { color: '#3b82f6' },
});
