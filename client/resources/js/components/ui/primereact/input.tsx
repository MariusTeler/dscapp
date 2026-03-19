import { InputText as PrimeInputText, InputTextProps } from 'primereact/inputtext';
import { cn } from "@/lib/utils"

export function Input({ ...props }: InputTextProps) {

    const theme = {
      inputtext: {
          root: ({ props, context } : { props: InputTextProps; context: { disabled: boolean; iconPosition: 'left' | 'right' } }) => ({
              className: cn(
                  'flex h-8 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none',
                  'font-sans text-gray-600 dark:text-white/80 bg-white dark:bg-gray-900 border transition-colors duration-200 appearance-none rounded-lg',
                  '[&:focus]:outline-none [&:focus]:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:[&:focus]:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]',
                  {
                      'focus:outline-none focus:outline-offset-0 focus:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:focus:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]':
                          !context.disabled,
                      'hover:border-blue-500': !props.invalid && !context.disabled,
                      'opacity-60 select-none pointer-events-none cursor-default': context.disabled,
                      'border-gray-300 dark:border-blue-900/40': !props.invalid,
                      'border-red-500 hover:border-red-500/80 focus:border-red-500': props.invalid && !context.disabled,
                      'border-red-500 [&:focus-within]:shadow-[0_0_0_0.2rem_rgba(254,202,202,1)]': props.invalid && context.disabled,
                  },
                  {
                      'text-lg px-4 py-4': props.size === 'large',
                      'text-xs px-2 py-2': props.size === 'small',
                      'p-3 text-base': !props.size || typeof props.size === 'number'
                  },
                  {
                      'pl-8': context.iconPosition === 'left',
                      'pr-8': context.iconPosition === 'right'
                  },
                  props.className
              ),
          }),
      }
  }

  return (
    <PrimeInputText
      pt={theme.inputtext}
      {...props}
    />
  )
}

export default Input;