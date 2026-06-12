import { useConnectionStatus } from '@laravel/echo-react';
import { Wifi, WifiOff } from 'lucide-react';

export function ConnectionStatus() {
    const status = useConnectionStatus();

    if (status === 'connected') {
        return null;
    }

    return (
        <div className="flex items-center gap-1.5 px-3 py-1.5 text-xs text-muted-foreground">
            {status === 'disconnected' || status === 'failed' ? (
                <>
                    <WifiOff className="size-3 text-destructive" />
                    <span>Disconnected</span>
                </>
            ) : (
                <>
                    <Wifi className="size-3 text-amber-500" />
                    <span>Reconnecting...</span>
                </>
            )}
        </div>
    );
}
