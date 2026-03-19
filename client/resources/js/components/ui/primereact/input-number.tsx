import { InputNumber as PrimeInputNumber, InputNumberProps } from 'primereact/inputnumber';
import { cn } from '@/lib/utils';

export function InputNumber({ className, ...props }: InputNumberProps) {
    const theme = {
        inputnumber: {
            root: ({ props } : { props: InputNumberProps }) => ({ 
                className : cn(
                    "flex h-8 rounded-md border bg-background overflow-hidden",
                    '[&:focus-within]:outline-none [&:focus-within]:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:[&:focus-within]:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]',
                    'disabled:cursor-not-allowed disabled:opacity-50 border-input',
                    {
                        'border-red-500 hover:border-red-500/80 focus:border-red-500': props.invalid,
                    }
                )
            }),
            input: {
                root: ({ props } : { props: InputNumberProps }) => ({
                    className: cn(
                        'flex-1 min-w-0 h-8 px-3 py-2 text-sm bg-transparent border-none focus:ring-0 focus:outline-none focus:shadow-none',
                        {
                            'rounded-l-md': props.showButtons && props.buttonLayout == 'stacked',
                            'rounded-md': !props.showButtons
                        }
                    )
                })
            },
            buttongroup: ({ props } : { props: InputNumberProps }) => ({
                className: cn('flex flex-col shrink-0', {
                    'hidden': !props.showButtons || props.buttonLayout != 'stacked'
                })
            }),
            incrementbutton: ({ props } : { props: InputNumberProps }) => ({
                className: cn('flex items-center justify-center h-4 w-8 bg-slate-500 hover:bg-slate-500/90 text-primary-foreground dark:text-white/70 border-0 border-l border-input', {
                    'hidden': !props.showButtons || props.buttonLayout != 'stacked'
                })
            }),
            decrementbutton: ({ props } : { props: InputNumberProps }) => ({
                className: cn('flex items-center justify-center h-4 w-8 bg-slate-500 hover:bg-slate-500/90 text-primary-foreground dark:text-white/70 border-0 border-l border-t border-input', {
                    'hidden': !props.showButtons || props.buttonLayout != 'stacked'
                })
            })
        }
    }
    return (
        <div className={className}>
            <PrimeInputNumber
                pt={theme.inputnumber}
                className="w-full"
                {...props}
            />
        </div>
    );
}

export default InputNumber;
