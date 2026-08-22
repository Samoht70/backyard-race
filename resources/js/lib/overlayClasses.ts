export const overlayBackdrop =
    'fixed inset-0 z-50 bg-foreground/60 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0';

export const overlayPanel =
    'fixed top-1/2 left-1/2 z-50 grid w-full max-w-[calc(100%-2rem)] -translate-x-1/2 -translate-y-1/2 gap-4 rounded-sm border border-border border-t-2 border-t-primary bg-background p-6 outline-none data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0 sm:max-w-lg';

export const overlayRail =
    'fixed inset-y-0 left-0 z-50 flex h-full w-full max-w-80 flex-col rounded-r-sm border-r border-border border-l-2 border-l-primary bg-background outline-none transition ease-in-out data-[state=closed]:duration-300 data-[state=closed]:animate-out data-[state=closed]:slide-out-to-left data-[state=open]:duration-500 data-[state=open]:animate-in data-[state=open]:slide-in-from-left';

export const overlayDrawer =
    'fixed inset-y-0 right-0 z-50 flex h-full w-full flex-col rounded-l-sm border-l border-border border-t-2 border-t-primary bg-background outline-none transition ease-in-out data-[state=closed]:duration-300 data-[state=closed]:animate-out data-[state=closed]:slide-out-to-right data-[state=open]:duration-500 data-[state=open]:animate-in data-[state=open]:slide-in-from-right sm:max-w-xl';

export const overlayTitle = 'text-title';

export const overlayDescription = 'text-sm text-muted-foreground';

export const overlayFooter =
    'flex flex-col-reverse gap-2 sm:flex-row sm:justify-end';

export const overlayMenu =
    'z-50 min-w-52 rounded-sm border border-border border-t-2 border-t-primary bg-popover p-1 text-popover-foreground shadow-lg outline-none data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0';

export const overlayMenuItem =
    'flex min-h-11 cursor-pointer items-center gap-2 rounded-sm px-3 font-mono text-label uppercase outline-none select-none data-highlighted:bg-accent data-highlighted:text-accent-foreground lg:min-h-10';
