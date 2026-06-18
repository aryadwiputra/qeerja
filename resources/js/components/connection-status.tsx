import { useConnectionStatus } from '@laravel/echo-react';
import { Wifi, WifiOff } from 'lucide-react';
import { useSyncExternalStore } from 'react';
import { useTranslation } from 'react-i18next';

const emptySubscribe = () => () => {};

export function ConnectionStatus() {
    const isClient = useSyncExternalStore(
        emptySubscribe,
        () => true,
        () => false,
    );

    if (!isClient) {
        return null;
    }

    return <ConnectionStatusInner />;
}

function ConnectionStatusInner() {
    const { t } = useTranslation();
    const status = useConnectionStatus();

    if (status === 'connected') {
        return null;
    }

    return (
        <div className="flex items-center gap-1.5 px-3 py-1.5 text-xs text-muted-foreground">
            {status === 'disconnected' || status === 'failed' ? (
                <>
                    <WifiOff className="size-3 text-destructive" />
                    <span>{t('connection.disconnected')}</span>
                </>
            ) : (
                <>
                    <Wifi className="size-3 text-amber-500" />
                    <span>{t('connection.reconnecting')}</span>
                </>
            )}
        </div>
    );
}
