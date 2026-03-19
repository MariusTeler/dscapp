import { Calendar as PrimeCalendar, CalendarProps } from 'primereact/calendar';
import { cn } from "@/lib/utils"

export function Calendar({ ...props }: CalendarProps) {
    const TRANSITIONS = {
        overlay: {
            timeout: 150,
            cn: {
                enter: 'opacity-0 scale-75',
                enterActive: 'opacity-100 !scale-100 transition-transform transition-opacity duration-150 ease-in',
                exit: 'opacity-100',
                exitActive: '!opacity-0 transition-opacity duration-150 ease-linear'
            }
        }
    };
    const theme = {
        calendar: {
            root: ({ props } : { props: CalendarProps }) => ({
                className: cn('relative inline-flex',
                    {
                        'opacity-60 select-none pointer-events-none cursor-default': props.disabled
                    },
                    props.className
                )
            }),
            input: ({ props } : { props: CalendarProps }) => ({
                root: {
                    className: cn(
                        'h-8 px-3 py-2 text-sm rounded-md border bg-background',
                        'font-sans text-gray-600 dark:text-white/80 bg-white dark:bg-gray-900 border-gray-300 dark:border-blue-900/40 transition-colors duration-200 appearance-none',
                        '[&:focus-within]:outline-none [&:focus-within]:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:[&:focus-within]:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]',
                        'hover:border-blue-500', 
                        {
                            'rounded-md': !props.showIcon,
                            'rounded-l-md': props.showIcon
                        },
                        props.invalid ? "border-red-500 [&:focus-within]:shadow-[0_0_0_0.2rem_rgba(254,202,202,1)]" : "border-input",
                    )
                }
            }),
            dropdownButton: {
                root: {
                    className: cn(
                        'h-8 px-3 flex items-center justify-center',
                        'border border-l-0 border-gray-300 dark:border-blue-900/40',
                        'bg-white dark:bg-gray-900 rounded-r-md',
                        'hover:bg-gray-50 dark:hover:bg-gray-800',
                        'text-gray-600 dark:text-white/70',
                        'transition-colors duration-200 cursor-pointer'
                    )
                }
            },
            panel: ({ props } : { props: CalendarProps }) => ({
                className: cn(
                    'bg-white dark:bg-gray-900 border border-gray-300 dark:border-blue-900/40 rounded-md shadow-lg',
                    'mt-1 z-[1000]',
                    {
                        'absolute w-auto min-w-[50px]': !props.inline,
                        'inline-block overflow-x-auto p-2': props.inline
                    }
                )
            }),
            header: {
                className: cn('flex items-center justify-between', 'p-2 text-gray-700 dark:text-white/80 bg-white dark:bg-gray-900 font-semibold m-0 border-b border-gray-300 dark:border-blue-900/40 rounded-t-lg')
            },
            previousButton: {
                className: cn(
                    'flex items-center justify-center cursor-pointer overflow-hidden relative',
                    'w-8 h-8 text-gray-600 dark:text-white/70 border-0 bg-transparent rounded-full transition-colors duration-200 ease-in-out',
                    'hover:text-gray-700 dark:hover:text-white/80 hover:border-transparent hover:bg-gray-200 dark:hover:bg-gray-800/80 '
                )
            },
            title: { className: 'leading-8 mx-auto flex gap-2' },
            monthTitle: {
                className: cn('text-gray-700 dark:text-white/80 transition duration-200 font-semibold p-2', 'hover:text-blue-500')
            },
            yearTitle: {
                className: cn('text-gray-700 dark:text-white/80 transition duration-200 font-semibold p-2', 'hover:text-blue-500')
            },
            nextButton: {
                className: cn(
                    'flex items-center justify-center cursor-pointer overflow-hidden relative',
                    'w-8 h-8 text-gray-600 dark:text-white/70 border-0 bg-transparent rounded-full transition-colors duration-200 ease-in-out',
                    'hover:text-gray-700 dark:hover:text-white/80 hover:border-transparent hover:bg-gray-200 dark:hover:bg-gray-800/80 '
                )
            },
            table: {
                className: cn('border-collapse w-full', 'my-2')
            },
            tableHeaderCell: { className: 'p-2' },
            weekday: { className: 'text-gray-600 dark:text-white/70' },
            day: { className: 'p-2' },
            dayLabel: ({ context } : { context: { selected: boolean; disabled: boolean } }) => ({
                className: cn(
                    'w-10 h-10 rounded-full transition-shadow duration-200 border-transparent border',
                    'flex items-center justify-center mx-auto overflow-hidden relative',
                    'focus:outline-none focus:outline-offset-0 focus:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:focus:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]',
                    {
                        'opacity-60 cursor-default': context.disabled,
                        'cursor-pointer': !context.disabled
                    },
                    {
                        'text-gray-600 dark:text-white/70 bg-transprent hover:bg-gray-200 dark:hover:bg-gray-800/80': !context.selected && !context.disabled,
                        'text-blue-700 bg-blue-100 hover:bg-blue-200': context.selected && !context.disabled
                    }
                )
            }),
            monthPicker: { className: 'my-2' },
            month: ({ context } : { context: { selected: boolean; disabled: boolean } }) => ({
                className: cn(
                    'w-1/3 inline-flex items-center justify-center cursor-pointer overflow-hidden relative',
                    'p-2 transition-shadow duration-200 rounded-lg',
                    'focus:outline-none focus:outline-offset-0 focus:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:focus:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]',
                    { 'text-gray-600 dark:text-white/70 bg-transprent hover:bg-gray-200 dark:hover:bg-gray-800/80': !context.selected && !context.disabled, 'text-blue-700 bg-blue-100 hover:bg-blue-200': context.selected && !context.disabled }
                )
            }),
            yearPicker: {
                className: cn('my-2')
            },
            year: ({ context } : { context: { selected: boolean; disabled: boolean } }) => ({
                className: cn(
                    'w-1/2 inline-flex items-center justify-center cursor-pointer overflow-hidden relative',
                    'p-2 transition-shadow duration-200 rounded-lg',
                    'focus:outline-none focus:outline-offset-0 focus:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:focus:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]',
                    {
                        'text-gray-600 dark:text-white/70 bg-transprent hover:bg-gray-200 dark:hover:bg-gray-800/80': !context.selected && !context.disabled,
                        'text-blue-700 bg-blue-100 hover:bg-blue-200': context.selected && !context.disabled
                    }
                )
            }),
            timePicker: {
                className: cn('flex justify-center items-center', 'border-t-1 border-solid border-gray-300 p-2')
            },
            separatorContainer: { className: 'flex items-center flex-col px-2' },
            separator: { className: 'text-xl' },
            hourPicker: { className: 'flex items-center flex-col px-2' },
            minutePicker: { className: 'flex items-center flex-col px-2' },
            ampmPicker: { className: 'flex items-center flex-col px-2' },
            incrementButton: {
                className: cn(
                    'flex items-center justify-center cursor-pointer overflow-hidden relative',
                    'w-8 h-8 text-gray-600 dark:text-white/70 border-0 bg-transparent rounded-full transition-colors duration-200 ease-in-out',
                    'hover:text-gray-700 dark:hover:text-white/80 hover:border-transparent hover:bg-gray-200 dark:hover:bg-gray-800/80 '
                )
            },
            decrementButton: {
                className: cn(
                    'flex items-center justify-center cursor-pointer overflow-hidden relative',
                    'w-8 h-8 text-gray-600 dark:text-white/70 border-0 bg-transparent rounded-full transition-colors duration-200 ease-in-out',
                    'hover:text-gray-700 dark:hover:text-white/80 hover:border-transparent hover:bg-gray-200 dark:hover:bg-gray-800/80 '
                )
            },
            groupContainer: { className: 'flex' },
            group: {
                className: cn('flex-1', 'border-l border-gray-300 pr-0.5 pl-0.5 pt-0 pb-0', 'first:pl-0 first:border-l-0')
            },
            buttonbar: {
                className: cn(
                    'flex justify-between items-center',
                    'py-3 px-4 border-t border-gray-300 dark:border-blue-900/40',
                    'bg-gray-50 dark:bg-gray-800'
                )
            },
            todayButton: {
                root: {
                    className: cn(
                        'inline-flex items-center justify-center',
                        'px-4 py-2 rounded-md text-sm font-medium',
                        'bg-blue-500 text-white border border-blue-500',
                        'hover:bg-blue-600 hover:border-blue-600',
                        'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                        'transition-colors duration-200 cursor-pointer'
                    )
                }
            },
            clearButton: {
                root: {
                    className: cn(
                        'inline-flex items-center justify-center',
                        'px-4 py-2 rounded-md text-sm font-medium',
                        'bg-white dark:bg-gray-900 text-gray-700 dark:text-white/80',
                        'border border-gray-300 dark:border-blue-900/40',
                        'hover:bg-gray-50 dark:hover:bg-gray-800',
                        'focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2',
                        'transition-colors duration-200 cursor-pointer'
                    )
                }
            },
            transition: TRANSITIONS.overlay
        }
    };

    return (
        <PrimeCalendar
        pt={theme.calendar}
        appendTo="self"
        {...props}
        />
    )
}

export default Calendar;
