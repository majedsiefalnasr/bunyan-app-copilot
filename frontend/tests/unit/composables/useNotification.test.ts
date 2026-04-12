import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

import { useNotification } from '../../../composables/useNotification';

// Mock the underlying useToast composable
const mockShowSuccess = vi.fn().mockReturnValue('id-success');
const mockShowError = vi.fn().mockReturnValue('id-error');
const mockShowWarning = vi.fn().mockReturnValue('id-warning');
const mockShowInfo = vi.fn().mockReturnValue('id-info');
const mockRemoveToast = vi.fn();

vi.mock('../../../composables/useToast', () => ({
    useToast: () => ({
        showSuccess: mockShowSuccess,
        showError: mockShowError,
        showWarning: mockShowWarning,
        showInfo: mockShowInfo,
        removeToast: mockRemoveToast,
        toasts: { value: [] },
    }),
}));

describe('useNotification composable', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('notifySuccess delegates to useToast().showSuccess()', () => {
        const { notifySuccess } = useNotification();
        const id = notifySuccess('Operation succeeded');
        expect(mockShowSuccess).toHaveBeenCalledWith('Operation succeeded', undefined);
        expect(id).toBe('id-success');
    });

    it('notifyError delegates to useToast().showError()', () => {
        const { notifyError } = useNotification();
        const id = notifyError('An error occurred');
        expect(mockShowError).toHaveBeenCalledWith('An error occurred', undefined);
        expect(id).toBe('id-error');
    });

    it('notifyWarning delegates to useToast().showWarning()', () => {
        const { notifyWarning } = useNotification();
        const id = notifyWarning('Proceed with caution');
        expect(mockShowWarning).toHaveBeenCalledWith('Proceed with caution', undefined);
        expect(id).toBe('id-warning');
    });

    it('notifyInfo delegates to useToast().showInfo()', () => {
        const { notifyInfo } = useNotification();
        const id = notifyInfo('Here is some info');
        expect(mockShowInfo).toHaveBeenCalledWith('Here is some info', undefined);
        expect(id).toBe('id-info');
    });

    it('dismiss delegates to useToast().removeToast()', () => {
        const { dismiss } = useNotification();
        dismiss('some-toast-id');
        expect(mockRemoveToast).toHaveBeenCalledWith('some-toast-id');
    });

    it('notifySuccess passes duration when provided', () => {
        const { notifySuccess } = useNotification();
        notifySuccess('Done', 5000);
        expect(mockShowSuccess).toHaveBeenCalledWith('Done', 5000);
    });

    it('notifyError passes duration when provided', () => {
        const { notifyError } = useNotification();
        notifyError('Failed', 8000);
        expect(mockShowError).toHaveBeenCalledWith('Failed', 8000);
    });
});
