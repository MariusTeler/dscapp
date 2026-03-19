import { ConfirmDialog as PrimeConfirmDialog, confirmDialog as primeConfirmDialog } from 'primereact/confirmdialog';
import { cn } from '@/lib/utils';

interface ConfirmDialogProps {
    className?: string;
}

export const ConfirmDialog = ({ className }: ConfirmDialogProps) => {
    const theme = {
        root: {
            className: cn(
                'bg-white text-gray-700 border-0 rounded-md shadow-lg',
                'z-40 transform origin-center',
                'mt-3 absolute left-0 top-0',
                'before:absolute before:w-0 before:-top-3 before:h-0 before:border-transparent before:border-solid before:ml-6 before:border-x-[0.75rem] before:border-b-[0.75rem] before:border-t-0 before:border-b-white dark:before:border-b-gray-900',
                'dark:border dark:border-blue-900/40 dark:bg-gray-900  dark:text-white/80'
                , className
            )
        },
        header: {
            className: cn(
                'flex items-center justify-between shrink-0',
                'bg-background',
                'text-foreground',
                'p-5 rounded-t-lg',
                'font-semibold text-lg'
            )
        },
        headerTitle: {
            className: 'font-semibold text-lg'
        },
        headerIcons: {
            className: 'flex items-center'
        },
        closeButton: {
            className: cn(
                'flex items-center justify-center overflow-hidden relative',
                'w-8 h-8 text-muted-foreground border-0 bg-transparent rounded-full transition duration-200 ease-in-out',
                'hover:text-foreground hover:bg-muted',
                'focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ring'
            )
        },
        closeButtonIcon: {
            className: 'w-4 h-4'
        },
        content: {
            className: cn(
                'overflow-y-auto',
                'bg-background text-foreground',
                'px-5 pb-5 pt-0'
            )
        },
        footer: {
            className: cn(
                'flex gap-2 justify-end align-center text-right px-5 py-5 pt-0',
                'bg-background',
                'rounded-b-lg'
            )
        },
        mask: {
            className: 'bg-black/40 backdrop-blur-sm'
        },
        icon: {
            className: 'text-4xl mb-4'
        },
        message: {
            className: 'text-base leading-relaxed mb-4'
        },
        transition: {
            classNames: {
                enter: 'opacity-0 scale-75',
                enterActive: 'transition-all duration-200 ease-out',
                leaveActive: 'transition-all duration-200 ease-in',
                leaveTo: 'opacity-0 scale-75'
            },
            addEndListener: (node: HTMLElement, done: () => void) => {
                node.addEventListener('transitionend', done, { once: true });
            }
        }
    };

    return <PrimeConfirmDialog pt={theme} />;
};

export default ConfirmDialog;

// Helper function for confirm dialogs
export interface ConfirmOptions {
    message: string;
    header?: string;
    icon?: string;
    defaultFocus?: 'accept' | 'reject';
    acceptLabel?: string;
    rejectLabel?: string;
    acceptClassName?: string;
    rejectClassName?: string;
    accept?: () => void;
    reject?: () => void;
}

export const confirm = (options: ConfirmOptions) => {
    const defaultOptions = {
        header: 'Confirmare',
        icon: 'pi pi-exclamation-triangle',
        defaultFocus: 'reject' ,
        acceptLabel: 'Yes',
        rejectLabel: 'No',
        acceptClassName: cn(
            'px-4 py-2 rounded-md text-sm font-medium',
            'bg-primary text-primary-foreground',
            'hover:bg-primary/90',
            'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
            'transition-colors'
        ),
        rejectClassName: cn(
            'px-4 py-2 rounded-md text-sm font-medium',
            'bg-secondary text-secondary-foreground',
            'hover:bg-secondary/80',
            'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
            'transition-colors'
        ),
    };

    primeConfirmDialog({
        ...defaultOptions,
        ...options,
        acceptClassName: options.acceptClassName || defaultOptions.acceptClassName,
        rejectClassName: options.rejectClassName || defaultOptions.rejectClassName,
    });
};

// Helper for delete confirmation
export const confirmDelete = (options: {
    message?: string;
    header?: string;
    defaultFocus?: 'accept' | 'reject';
    accept?: () => void;
    reject?: () => void;
}) => {
    confirm({
        message: options.message || 'Are you sure you want to delete this item? This action cannot be undone.',
        header: options.header || 'Delete Confirmation',
        icon: 'pi pi-trash',
        defaultFocus: 'reject' ,
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        acceptClassName: cn(
            'px-4 py-2 rounded-md text-sm font-medium',
            'bg-destructive text-destructive-foreground',
            'hover:bg-destructive/90',
            'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
            'transition-colors'
        ),
        rejectClassName: cn(
            'px-4 py-2 rounded-md text-sm font-medium',
            'bg-secondary text-secondary-foreground',
            'hover:bg-secondary/80',
            'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
            'transition-colors'
        ),
        accept: options.accept,
        reject: options.reject,
    });
};

// Helper for warning confirmation
export const confirmWarn = (options: {
    message: string;
    header?: string;
    defaultFocus?: 'accept' | 'reject';
    accept?: () => void;
    reject?: () => void;
}) => {
    confirm({
        message: options.message,
        header: options.header || 'Warning',
        icon: 'pi pi-exclamation-triangle',
        defaultFocus: 'reject' ,
        acceptLabel: 'Continue',
        rejectLabel: 'Cancel',
        acceptClassName: cn(
            'px-4 py-2 rounded-md text-sm font-medium',
            'bg-orange-600 text-white',
            'hover:bg-orange-700',
            'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
            'transition-colors'
        ),
        rejectClassName: cn(
            'px-4 py-2 rounded-md text-sm font-medium',
            'bg-secondary text-secondary-foreground',
            'hover:bg-secondary/80',
            'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
            'transition-colors'
        ),
        accept: options.accept,
        reject: options.reject,
    });
};

// Helper for success confirmation
export const confirmSuccess = (options: {
    message: string;
    header?: string;
    icon?: string;
    defaultFocus?: 'accept' | 'reject';
    accept?: () => void;
    reject?: () => void;
}) => {
    confirm({
        message: options.message,
        header: options.header || 'Success',
        icon: 'pi pi-check-circle',
        defaultFocus: 'accept',
        acceptLabel: 'OK',
        rejectLabel: 'Cancel',
        acceptClassName: cn(
            'px-4 py-2 rounded-md text-sm font-medium',
            'bg-green-600 text-white',
            'hover:bg-green-700',
            'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
            'transition-colors'
        ),
        rejectClassName: cn(
            'px-4 py-2 rounded-md text-sm font-medium',
            'bg-secondary text-secondary-foreground',
            'hover:bg-secondary/80',
            'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
            'transition-colors'
        ),
        accept: options.accept,
        reject: options.reject,
    });
};
