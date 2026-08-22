const surface =
    'w-full rounded-sm border border-input bg-card text-base transition-colors outline-none lg:text-sm';

const ink =
    'caret-primary placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground';

const focus =
    'focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-ring/35 focus-visible:shadow-[inset_2px_0_0_0_var(--primary)]';

const focusWithin =
    'focus-within:border-primary focus-within:ring-2 focus-within:ring-ring/35 focus-within:shadow-[inset_2px_0_0_0_var(--primary)]';

const invalid =
    'aria-invalid:border-destructive aria-invalid:shadow-[inset_2px_0_0_0_var(--destructive)]';

const idle =
    'disabled:pointer-events-none disabled:opacity-50 data-disabled:opacity-50';

const height = 'h-11 lg:h-10';

export const fieldControl = `${surface} ${ink} ${focus} ${invalid} ${idle} ${height} px-3`;

export const fieldArea = `${surface} ${ink} ${focus} ${invalid} ${idle} field-sizing-content min-h-24 px-3 py-2`;

export const fieldShell = `${surface} ${focusWithin} ${invalid} ${idle} ${height} flex items-stretch overflow-hidden`;

export const fieldSegment =
    'rounded-sm px-0.5 tabular-nums outline-none data-[placeholder]:text-muted-foreground focus:bg-primary focus:text-primary-foreground';

export const fieldStepper =
    'flex w-9 shrink-0 items-center justify-center border-l border-input text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground active:bg-primary active:text-primary-foreground disabled:opacity-40';
