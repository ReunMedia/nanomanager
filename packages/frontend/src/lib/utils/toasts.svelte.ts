import { SvelteMap } from "svelte/reactivity";

/**
 * Toasts by ID
 */
const toasts = new SvelteMap<string, string>();

const showToast = (text: string) => {
  const id = crypto.randomUUID();
  toasts.set(id, text);

  window.setTimeout(() => {
    toasts.delete(id);
  }, 2000);
};

export { toasts, showToast };
