import { Checkbox as PrimeCheckbox, CheckboxProps } from 'primereact/checkbox';
import { cn } from "@/lib/utils"

export interface CustomCheckboxProps extends Omit<CheckboxProps, 'className'> {
  className?: string;
}

function Checkbox({
  className,
  ...props
}: CustomCheckboxProps) {

  const theme = {
    checkbox: {
        root: {
            className: cn(
              'cursor-pointer inline-flex relative select-none align-bottom', 'w-6 h-6 rounded-[6px]',
              '[&:focus-within]:outline-none [&:focus-within]:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:[&:focus-within]:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]',
            )
        },
        input: {
            className: cn('absolute appearance-none top-0 left-0 size-full p-0 m-0 opacity-0 z-10 outline-none cursor-pointer')
        },
        box: ({ props, context }: { props?: CheckboxProps; context?: { checked?: boolean } }) => ({
            className: cn(
                'flex items-center justify-center',
                'border-2 w-6 h-6 text-gray-600 rounded-[6px] transition-colors duration-200',
                {
                    'border-gray-300 bg-white dark:border-blue-900/40 dark:bg-gray-900': !context?.checked,
                    'border-blue-500 bg-blue-500 dark:border-blue-400 dark:bg-blue-400': context?.checked
                },
                {
                    'hover:border-blue-500 dark:hover:border-blue-400 focus:outline-none focus:outline-offset-0 focus:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:focus:shadow-[inset_0_0_0_0.2rem_rgba(147,197,253,0.5)]': !props?.disabled,
                    'cursor-default opacity-60': props?.disabled
                }
            )
        }),
        icon: {
            className: 'w-4 h-4 transition-all duration-200 text-white text-base dark:text-gray-900'
        }
    }
}

  return (
    <PrimeCheckbox
      className={cn(
        'peer size-4 shrink-0',
        className
      )}
      pt={theme.checkbox}
      {...props}
    />
  )
}

export { Checkbox }
