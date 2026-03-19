import { Dialog as PrimeDialog, type DialogProps, type DialogState } from 'primereact/dialog';
import { cn } from "@/lib/utils"

  function Dialog({ ...props }: DialogProps) {
    
    const theme = {
      dialog: {
          root: ({ state }: { state: DialogState }) => ({
              className: cn(
                'rounded-lg bg-white border-0 shadow-2xl',
                'max-h-[90%] transform scale-100',
                'm-0 w-[50vw]',
                'dark:border dark:border-blue-900/40',
                {
                  'transition-none transform-none !w-screen !h-screen !max-h-full !top-0 !left-0': state.maximized
                },
              )
          }),
          header: {
              className: cn('flex items-center justify-between shrink-0', 'bg-white text-gray-800 border-t-0  rounded-tl-lg rounded-tr-lg p-6', 'dark:bg-gray-900  dark:text-white/80')
          },
          headerTitle: { className: 'font-bold text-lg' },
          headerIcons: { className: 'flex items-center' },
          closeButton: {
              className: cn(
                  'flex items-center justify-center overflow-hidden relative',
                  'w-8 h-8 text-gray-500 border-0 bg-transparent rounded-full transition duration-200 ease-in-out mr-2 last:mr-0',
                  'hover:text-gray-700 hover:border-transparent hover:bg-gray-200',
                  'focus:outline-none focus:outline-offset-0 focus:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)]', // focus
                  'dark:hover:text-white/80 dark:hover:border-transparent dark:hover:bg-gray-800/80 dark:focus:shadow-[inset_0_0_0_0.2rem_rgba(147,197,253,0.5)]'
              )
          },
          closeButtonIcon: { className: 'w-4 h-4 inline-block' },
          content: ({ state }: { state: DialogState }) => ({
              className: cn('overflow-y-auto', 'bg-white text-gray-700 px-6 pb-8 pt-0', 'rounded-bl-lg rounded-br-lg', 'dark:bg-gray-900  dark:text-white/80 ', {
                  grow: state.maximized
              })
          }),
          footer: {
              className: cn('shrink-0 ', 'border-t-0 bg-white text-gray-700 px-6 pb-6 text-right rounded-b-lg', 'dark:bg-gray-900  dark:text-white/80')
          },
          mask: ({ state }: { state: DialogState }) => ({
              className: cn('transition duration-200 bg-black/40', {
                  'opacity-100': state.containerVisible,
                  'opacity-0': !state.containerVisible
              })
          }),
      },
      transitions: ({ props }: { props: DialogProps }) => {
        if (props.position === 'top') {
          return {
            classNames: {
              enter: 'opacity-0 scale-75 translate-x-0 -translate-y-full translate-z-0',
              enterActive: 'transition-all duration-200 ease-out',
              exitActive: 'transition-all duration-200 ease-out',
              exit: 'opacity-0 scale-75 translate-x-0 -translate-y-full translate-z-0'
            },
            addEndListener: () => {}
          };
        } else if (props.position === 'bottom') {
          return {
            classNames: {
              enter: 'opacity-0 scale-75 translate-y-full',
              enterActive: 'transition-all duration-200 ease-out',
              exitActive: 'transition-all duration-200 ease-out',
              exit: 'opacity-0 scale-75 translate-x-0 translate-y-full translate-z-0'
            },
            addEndListener: () => {}
          };
        } else if (
          props.position === 'left' ||
          props.position === 'top-left' ||
          props.position === 'bottom-left'
        ) {
          return {
            classNames: {
              enter: 'opacity-0 scale-75 -translate-x-full translate-y-0 translate-z-0',
              enterActive: 'transition-all duration-200 ease-out',
              exitActive: 'transition-all duration-200 ease-out',
              exit: 'opacity-0 scale-75  -translate-x-full translate-y-0 translate-z-0'
            },
            addEndListener: () => {}
          };
        } else if (
          props.position === 'right' ||
          props.position === 'top-right' ||
          props.position === 'bottom-right'
        ) {
          return {
            classNames: {
              enter: 'opacity-0 scale-75 translate-x-full translate-y-0 translate-z-0',
              enterActive: 'transition-all duration-200 ease-out',
              exitActive: 'transition-all duration-200 ease-out',
              exit: 'opacity-0 scale-75 opacity-0 scale-75 translate-x-full translate-y-0 translate-z-0'
            },
            addEndListener: () => {}
          };
        } else {
          return {
            classNames: {
              enter: 'opacity-0 scale-75',
              enterActive: 'transition-all duration-200 ease-out',
              exitActive: 'transition-all duration-200 ease-out',
              exit: 'opacity-0 scale-75'
            },
            addEndListener: () => {}
          };
        }
      }
  };

  return (
    <PrimeDialog
      pt={theme.dialog}
      transitionOptions={theme.transitions({ props })}
      {...props}
    >
    </PrimeDialog>
  );
}

// Re-export PrimeDialog directly
export { Dialog };
export type { DialogProps };