/**
 * Async helper to `await` for a HTML event
 */
export async function waitForEvent<T extends HTMLElement>(
  element: T,
  eventName: keyof HTMLElementEventMap,
) {
  return new Promise((resolve) => {
    element.addEventListener(
      eventName,
      (e) => {
        resolve(e);
      },
      { once: true },
    );
  });
}
