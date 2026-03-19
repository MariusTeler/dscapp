import * as React from 'react';
import { Stepper as PrimeStepper, StepperProps as PrimeStepperProps } from 'primereact/stepper';
import { StepperPanel as PrimeStepperPanel, StepperPanelProps as PrimeStepperPanelProps } from 'primereact/stepperpanel';
import { cn } from '@/lib/utils';

export interface StepperProps extends PrimeStepperProps {
    className?: string;
}

export interface StepperPanelProps extends PrimeStepperPanelProps {
    className?: string;
    children?: React.ReactNode;
}

interface StepperPanelContext {
    index: number;
    count: number;
    first: boolean;
    last: boolean;
    active: boolean;
    highlighted: boolean;
    disabled: boolean;
}

const stepperTheme = {
    root: {
        className: 'flex flex-col',
    },
    nav: {
        className: 'flex flex-row p-2',
    },
    panelContainer: {
        className: 'w-full',
    },
};

const getStepperPanelTheme = (className?: string) => ({
    root: {
        className: cn("relative", className)
    },

    header: ({ context }: { context: StepperPanelContext }) => ({
        className: cn(
            "flex items-center ml-3 gap-3 cursor-pointer select-none p-2 rounded-md transition-colors w-full",
            context.disabled && "opacity-50 cursor-not-allowed"
        )
    }),

    action: {
        className: "flex items-center gap-3 focus:outline-none"
    },

    number: ({ context }: { context: StepperPanelContext }) => ({
        className: cn(
            "flex items-center justify-center w-10 h-10 rounded-full border-2 text-sm font-semibold transition-all",
            context.highlighted || context.active
                ? 'bg-blue-600 border-blue-600 text-white dark:bg-blue-500 dark:border-blue-500'
                : 'bg-white border-gray-300 text-gray-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400',
            context.active && "shadow-[0_0_0_4px_rgba(59,130,246,0.3)] dark:shadow-[0_0_0_4px_rgba(96,165,250,0.3)]"
        )
    }),

    title: ({ context }: { context: StepperPanelContext }) => ({
        className: cn(
            "text-sm font-medium transition-colors",
            context.highlighted || context.active
                ? "text-gray-900 dark:text-white"
                : "text-gray-600 dark:text-gray-400"
        )
    }),

    separator: {
        className: cn("flex-1 h-0.5 bg-gray-300 dark:bg-gray-600 mx-2")
    },

    content: ({ context }: { context: StepperPanelContext }) => ({
        className: cn(
            "mt-4 pl-2",
            context.active ? "block" : "hidden"
        )
    }),

    panel: {
        className: "w-full"
    },

    toggleableContent: {
        className: "transition-all duration-300 ease-in-out"
    },

    transition: {
        timeout: 300,
        classNames: {
            enter: "opacity-0 max-h-0",
            enterActive: "opacity-100 max-h-screen transition-all duration-300",
            exit: "opacity-100 max-h-screen",
            exitActive: "opacity-0 max-h-0 transition-all duration-300"
        }
    },
});

const Stepper = React.forwardRef<React.ComponentRef<typeof PrimeStepper>, StepperProps>(
    ({ className, children, ...props }, ref) => {
        // Clone children and inject pt theme into StepperPanel components
        const enhancedChildren = React.Children.map(children, (child) => {
            if (React.isValidElement(child) && child.type === StepperPanel) {
                const childClassName = (child.props as StepperPanelProps).className;
                const theme = getStepperPanelTheme(childClassName);
                return React.cloneElement(child, { pt: theme } as Partial<StepperPanelProps>);
            }
            return child;
        });

        return (
            <PrimeStepper
                ref={ref}
                {...props}
                className={className}
                pt={stepperTheme}
            >
                {enhancedChildren}
            </PrimeStepper>
        );
    }
);

Stepper.displayName = 'Stepper';

const StepperPanel: React.FC<StepperPanelProps> = ({ header, children, ...props }) => {
    return (
        <PrimeStepperPanel
            header={header}
            {...props}
        >
            {children}
        </PrimeStepperPanel>
    );
};

StepperPanel.displayName = 'StepperPanel';

export { Stepper, StepperPanel };
