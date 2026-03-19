import { useRef, useImperativeHandle, forwardRef } from 'react';
import { Toast as PrimeToast, ToastMessage, ToastState } from 'primereact/toast';
import { cn } from '@/lib/utils';

export interface ToastMessageState {
    severity: string;
    summary: string;
    detail?:string;
}

export interface ToastRef {
    show: (message: ToastMessage | ToastMessage[]) => void;
    clear: () => void;
    replace: (message: ToastMessage | ToastMessage[]) => void;
}

interface ToastProps {
    onShow?: () => void;
    position?: 'top-left' | 'top-center' | 'top-right' | 'bottom-left' | 'bottom-center' | 'bottom-right' | 'center';
    className?: string;
}

export const Toast = forwardRef<ToastRef, ToastProps>(({ position = 'top-right', className, onShow }, ref) => {
    const toastRef = useRef<PrimeToast>(null);

    useImperativeHandle(ref, () => ({
        show: (message: ToastMessage | ToastMessage[]) => {
            toastRef.current?.show(message);
            if (onShow) {
                onShow();
            }
        },
        clear: () => {
            toastRef.current?.clear();
        },
        replace: (message: ToastMessage | ToastMessage[]) => {
            toastRef.current?.replace(message);
            if (onShow) {
                onShow();
            }
        }
    }));

    const theme = {
        root: {
            className: cn('w-96', 'opacity-90', className)
        },
        message: ({ state, index }: { state: ToastState; index: number }) => ({
            className: cn('my-4 rounded-md w-full', {
                'bg-blue-100 border-solid border-0 border-l-4 border-blue-500 text-blue-700': state.messages[index] && state.messages[index].message.severity == 'info',
                'bg-green-100 border-solid border-0 border-l-4 border-green-500 text-green-700': state.messages[index] && state.messages[index].message.severity == 'success',
                'bg-orange-100 border-solid border-0 border-l-4 border-orange-500 text-orange-700': state.messages[index] && state.messages[index].message.severity == 'warn',
                'bg-red-100 border-solid border-0 border-l-4 border-red-500 text-red-700': state.messages[index] && state.messages[index].message.severity == 'error'
            })
        }),
        content: { className: 'flex items-center py-5 px-7' },
        icon: {
            className: cn('w-6 h-6', 'text-lg mr-2')
        },
        text: { className: 'text-base font-normal flex flex-col flex-1 grow shrink ml-4' },
        summary: { className: 'font-bold block' },
        detail: { className: 'mt-1 block' },
        closebutton: {
            className: cn('w-8 h-8 rounded-full bg-transparent transition duration-200 ease-in-out', 'ml-auto overflow-hidden relative', 'flex items-center justify-center', 'hover:bg-white/30')
        },
        transition: {
            classNames: {
                enter: 'opacity-0 translate-x-0 translate-y-2/4 translate-z-0',
                enterActive: 'transition-transform transition-opacity duration-300',
                exit: 'max-h-40',
                exitActive: 'transition-all duration-500 ease-in',
                exitDone: 'max-h-0 opacity-0 mb-0 overflow-hidden'
            },
            addEndListener: (node: HTMLElement, done: () => void) => {
                node.addEventListener('transitionend', done, { once: true });
            }
        }
    };

    return (
        <PrimeToast
            ref={toastRef}
            position={position}
            pt={theme}
        />
    );
});

Toast.displayName = 'Toast';

export default Toast;

// Helper functions for common toast types
export const showSuccess = (toastRef: React.RefObject<ToastRef | null>, summary: string, detail?: string, life = 3000) => {
    toastRef?.current?.clear(); // Clear existing toasts before showing error
    toastRef?.current?.show({
        severity: 'success',
        summary,
        detail,
        life
    });
};

export const showError = (toastRef: React.RefObject<ToastRef | null>, summary: string, detail?: string, life = 3000) => {
    toastRef?.current?.clear(); // Clear existing toasts before showing error
    toastRef?.current?.show({
        severity: 'error',
        summary,
        detail,
        life
    });
};

export const showWarn = (toastRef: React.RefObject<ToastRef | null>, summary: string, detail?: string, life = 3000) => {
    toastRef?.current?.clear(); // Clear existing toasts before showing error
    toastRef?.current?.show({
        severity: 'warn',
        summary,
        detail,
        life
    });
};

export const showInfo = (toastRef: React.RefObject<ToastRef | null>, summary: string, detail?: string, life = 3000) => {
    toastRef?.current?.clear(); // Clear existing toasts before showing error
    toastRef?.current?.show({
        severity: 'info',
        summary,
        detail,
        life
    });
};