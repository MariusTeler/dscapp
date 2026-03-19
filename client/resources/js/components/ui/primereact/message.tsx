import { Message as PrimeMessage, MessageProps as PrimeMessageProps } from 'primereact/message';
import { cn } from '@/lib/utils';

export interface MessageProps extends Omit<PrimeMessageProps, 'pt' | 'unstyled'> {
    severity?: 'success' | 'info' | 'warn' | 'error';
    text?: string;
    className?: string;
}

export const Message = ({
    severity = 'info',
    text,
    className,
    ...props
}: MessageProps) => {
    const theme = {
        root: ({ props }: { props: { severity?: string } }) => ({
            className: cn(
                'inline-flex items-center justify-start',
                'p-3 my-2 rounded-md border',
                {
                    'bg-blue-50/90 dark:bg-blue-900/90 border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-100':
                        props.severity === 'info',
                    'bg-green-50/90 dark:bg-green-900/90 border-green-200 dark:border-green-700 text-green-700 dark:text-green-100':
                        props.severity === 'success',
                    'bg-orange-50/90 dark:bg-orange-900/90 border-orange-200 dark:border-orange-700 text-orange-700 dark:text-orange-100':
                        props.severity === 'warn',
                    'bg-red-50/90 dark:bg-red-900/90 border-red-200 dark:border-red-700 text-red-700 dark:text-red-100':
                        props.severity === 'error',
                },
                className
            )
        }),
        wrapper: {
            className: 'flex items-center'
        },
        icon: {
            className: 'text-base mr-2'
        },
        text: {
            className: 'text-sm font-normal'
        },
        closeButton: {
            className: cn(
                'flex items-center justify-center',
                'ml-auto relative',
                'w-6 h-6 rounded-full',
                'transition duration-200 ease-in-out',
                'hover:bg-black/10 dark:hover:bg-white/10',
                'focus:outline-none focus:ring-2 focus:ring-offset-0'
            )
        },
        closeIcon: {
            className: 'w-3 h-3'
        },
        transition: {
            enterFromClass: 'opacity-0',
            enterActiveClass: 'transition-opacity duration-200',
            leaveFromClass: 'max-h-40',
            leaveActiveClass: 'overflow-hidden transition-all duration-200 ease-in',
            leaveToClass: 'max-h-0 opacity-0 !mb-0'
        }
    };

    return (
        <PrimeMessage
            severity={severity}
            text={text}
            pt={theme}
            {...props}
        />
    );
};

export default Message;

// Helper components for specific message types
export const SuccessMessage = ({
    text,
    className,
    ...props
}: Omit<MessageProps, 'severity'>) => (
    <Message
        severity="success"
        text={text}
        className={className}
        {...props}
    />
);

export const ErrorMessage = ({
    text,
    className,
    ...props
}: Omit<MessageProps, 'severity'>) => (
    <Message
        severity="error"
        text={text}
        className={className}
        {...props}
    />
);

export const WarnMessage = ({
    text,
    className,
    ...props
}: Omit<MessageProps, 'severity'>) => (
    <Message
        severity="warn"
        text={text}
        className={className}
        {...props}
    />
);

export const InfoMessage = ({
    text,
    className,
    ...props
}: Omit<MessageProps, 'severity'>) => (
    <Message
        severity="info"
        text={text}
        className={className}
        {...props}
    />
);
