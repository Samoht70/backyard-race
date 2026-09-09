import { describe, expect, it } from 'vitest';
import { actionButtonVariants } from '@/lib/actionButton';

describe('actionButtonVariants', () => {
    it('keeps every size on the forty-four pixel touch floor', () => {
        expect(actionButtonVariants({ size: 'touch' })).toContain('min-h-11');
        expect(actionButtonVariants({ size: 'icon' })).toContain('size-11');
    });

    it('gives a race board gesture no wider target than any other action', () => {
        expect(actionButtonVariants({ size: 'touch' })).not.toContain('min-w-');
    });
});
