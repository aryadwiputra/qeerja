import { useCallback, useEffect, useRef } from 'react';

type SimpleShortcut = {
    key: string;
    ctrl?: boolean;
    meta?: boolean;
    alt?: boolean;
    shift?: boolean;
    handler: () => void;
    enabled?: boolean;
    description?: string;
};

type SequenceShortcut = {
    sequence: string[];
    handler: () => void;
    enabled?: boolean;
    description?: string;
    timeout?: number;
};

type Shortcut = SimpleShortcut | SequenceShortcut;

function isSimple(s: Shortcut): s is SimpleShortcut {
    return 'key' in s;
}

function isInputTarget(el: EventTarget | null): boolean {
    if (!(el instanceof HTMLElement)) {
        return false;
    }

    return (
        el instanceof HTMLInputElement ||
        el instanceof HTMLTextAreaElement ||
        el instanceof HTMLSelectElement ||
        el.isContentEditable
    );
}

export function useKeyboardShortcuts(shortcuts: Shortcut[]) {
    const buffer = useRef<string[]>([]);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const clearBuffer = useCallback(() => {
        buffer.current = [];

        if (timer.current) {
            clearTimeout(timer.current);
            timer.current = null;
        }
    }, []);

    useEffect(() => {
        const seqShortcuts = shortcuts.filter(
            (s): s is SequenceShortcut => !isSimple(s),
        );
        const simpleShortcuts = shortcuts.filter(isSimple);

        function onKeyDown(e: KeyboardEvent) {
            if (isInputTarget(e.target)) {
                return;
            }

            for (const s of simpleShortcuts) {
                if (s.enabled === false) {
                    continue;
                }

                const matchKey = e.key.toLowerCase() === s.key.toLowerCase();
                const matchCtrl = s.ctrl
                    ? e.ctrlKey || e.metaKey
                    : !e.ctrlKey && !e.metaKey;
                const matchAlt = s.alt ? e.altKey : !e.altKey;
                const matchShift = s.shift ? e.shiftKey : !e.shiftKey;

                if (matchKey && matchCtrl && matchAlt && matchShift) {
                    e.preventDefault();
                    s.handler();

                    return;
                }
            }

            if (e.ctrlKey || e.metaKey || e.altKey || e.shiftKey) {
                clearBuffer();

                return;
            }

            const seq = seqShortcuts.find((s) => {
                const next = s.sequence[buffer.current.length];

                if (!next) {
                    return false;
                }

                return e.key.toLowerCase() === next.toLowerCase();
            });

            if (seq) {
                e.preventDefault();
                buffer.current.push(e.key.toLowerCase());

                if (buffer.current.length === seq.sequence.length) {
                    seq.handler();
                    clearBuffer();
                } else {
                    if (timer.current) {
                        clearTimeout(timer.current);
                    }

                    timer.current = setTimeout(
                        clearBuffer,
                        seq.timeout ?? 1500,
                    );
                }
            } else {
                clearBuffer();
            }
        }

        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            clearBuffer();
        };
    }, [shortcuts, clearBuffer]);
}
