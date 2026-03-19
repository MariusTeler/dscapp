import { InputTextarea as PrimeInputTextarea, InputTextareaProps } from 'primereact/inputtextarea';
import { cn } from "@/lib/utils"

export interface CustomTextareaProps extends Omit<InputTextareaProps, 'className'> {
  className?: string;
  invalid?: boolean;
}

function Textarea({
  className,
  invalid,
  ...props
}: CustomTextareaProps) {
  return (
    <PrimeInputTextarea
      className={cn(
        "border-input file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground flex min-h-[32px] w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none resize-vertical disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm",
        '[&:focus]:outline-none [&:focus]:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:[&:focus]:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]',
        invalid && "border-red-500 focus:shadow-[0_0_0_0.2rem_rgba(239,68,68,0.5)]",
        className
      )}
      {...props}
    />
  )
}

export { Textarea }
