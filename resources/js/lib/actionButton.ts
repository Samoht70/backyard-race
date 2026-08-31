import { cva } from 'class-variance-authority';

export const actionButtonVariants = cva(
    'inline-flex shrink-0 touch-manipulation items-center justify-center gap-2 rounded-sm border font-bold tracking-wider uppercase transition-colors outline-none select-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none aria-disabled:opacity-50',
    {
        variants: {
            block: {
                true: 'w-full',
                false: 'w-full sm:w-auto',
            },
            size: {
                touch: 'min-h-11 px-4 text-xs lg:min-h-10',
                icon: 'size-11 shrink-0 lg:size-10',
            },
            tone: {
                primary:
                    'border-primary bg-primary text-primary-foreground hover:bg-primary/90',
                danger: 'border-destructive bg-destructive text-destructive-foreground hover:bg-destructive/90',
                quiet: 'border-foreground bg-transparent text-foreground hover:bg-accent hover:text-accent-foreground',
                ghost: 'border-transparent bg-transparent text-muted-foreground hover:bg-accent hover:text-accent-foreground',
            },
        },
    },
);
