import { RadioButton as PrimeRadioButton, RadioButtonProps } from 'primereact/radiobutton';
import { cn } from "@/lib/utils"

export interface CustomRadioButtonProps extends Omit<RadioButtonProps, 'className'> {
  className?: string;
}

function RadioButton({
  className,
  ...props
}: CustomRadioButtonProps) {

  const theme = {
    radiobutton: {
        root: {
            className: cn(
              'cursor-pointer inline-flex relative select-none align-bottom', 'w-6 h-6 rounded-full',
              '[&:focus-within]:outline-none [&:focus-within]:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:[&:focus-within]:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]',
            )
        },
        input: {
            className: cn('absolute appearance-none top-0 left-0 size-full p-0 m-0 opacity-0 z-10 outline-none cursor-pointer')
        },
        box: ({ props }: { props?: RadioButtonProps}) => ({
            className: cn(
                'flex items-center justify-center',
                'border-2 w-6 h-6 text-gray-600 rounded-full transition-colors duration-200',
                {
                    'border-gray-300 bg-white dark:border-blue-900/40 dark:bg-gray-900': !props?.checked,
                    'border-blue-500 bg-blue-500 dark:border-blue-400 dark:bg-blue-400': props?.checked
                },
                {
                    'hover:border-blue-500 dark:hover:border-blue-400 focus:outline-none focus:outline-offset-0 focus:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:focus:shadow-[inset_0_0_0_0.2rem_rgba(147,197,253,0.5)]': !props?.disabled,
                    'cursor-default opacity-60': props?.disabled
                }
            )
        }),
        icon: {
            className: 'w-2 h-2 transition-all duration-200 bg-white rounded-full dark:bg-gray-900'
        }
    }
}

  return (
    <PrimeRadioButton
      className={cn(
        'peer',
        className
      )}
      pt={theme.radiobutton}
      {...props}
    />
  )
}

export { RadioButton }
