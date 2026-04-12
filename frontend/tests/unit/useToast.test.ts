import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { useToast } from '~/composables/useToast';

describe('useToast composable', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('shows error toast', () => {
    const { showError, toasts } = useToast();
    const id = showError('Error message');

    expect(toasts.value).toHaveLength(1);
    expect(toasts.value[0]).toMatchObject({
      message: 'Error message',
      type: 'error',
    });
    expect(id).toBeDefined();
  });

  it('shows warning toast', () => {
    const { showWarning, toasts } = useToast();
    showWarning('Warning message');

    expect(toasts.value).toHaveLength(1);
    expect(toasts.value[0]).toMatchObject({
      message: 'Warning message',
      type: 'warning',
    });
  });

  it('shows success toast', () => {
    const { showSuccess, toasts } = useToast();
    showSuccess('Success message');

    expect(toasts.value).toHaveLength(1);
    expect(toasts.value[0]).toMatchObject({
      message: 'Success message',
      type: 'success',
    });
  });

  it('shows info toast', () => {
    const { showInfo, toasts } = useToast();
    showInfo('Info message');

    expect(toasts.value).toHaveLength(1);
    expect(toasts.value[0]).toMatchObject({
      message: 'Info message',
      type: 'info',
    });
  });

  it('removes toast by id', () => {
    const { showToast, removeToast, toasts } = useToast();
    const id1 = showToast('Toast 1', 'info', 0);
    showToast('Toast 2', 'info', 0);

    expect(toasts.value).toHaveLength(2);

    removeToast(id1);
    expect(toasts.value).toHaveLength(1);
    expect(toasts.value[0].message).toBe('Toast 2');
  });

  it('auto-dismisses toasts after duration', async () => {
    const { showToast, toasts } = useToast();
    showToast('Auto-dismiss test', 'info', 100);

    expect(toasts.value).toHaveLength(1);

    await new Promise((resolve) => setTimeout(resolve, 150));
    expect(toasts.value).toHaveLength(0);
  });

  it('does not auto-dismiss if duration is 0', async () => {
    const { showToast, toasts } = useToast();
    showToast('No auto-dismiss', 'info', 0);

    expect(toasts.value).toHaveLength(1);

    await new Promise((resolve) => setTimeout(resolve, 100));
    expect(toasts.value).toHaveLength(1);
  });

  it('stacks multiple toasts', () => {
    const { showToast, toasts } = useToast();
    showToast('Toast 1', 'error', 0);
    showToast('Toast 2', 'warning', 0);
    showToast('Toast 3', 'success', 0);

    expect(toasts.value).toHaveLength(3);
  });

  it('generates unique toast ids', () => {
    const { showToast } = useToast();
    const id1 = showToast('Toast 1', 'info', 0);
    const id2 = showToast('Toast 2', 'info', 0);

    expect(id1).not.toBe(id2);
  });
});
