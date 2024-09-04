import { showError } from "./toast-notificaltion"

export function showErrorResponse(err: unknown) {
    if (Array.isArray(err)) {
        for (const message of err as string[]) {
            showError(message)
        }
    } else {
        showError((err as Error).message)
    }
}

export function myDebounce<T>(func: () => Promise<T>, delay: number) {

    let time: any;

    return function () {
        clearTimeout(time);

        setTimeout(() => func(), delay);
    }
}