import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import AppLogo from '@/components/app-logo';
import { dashboard } from '@/routes';

export default function OnboardingLayout({ children }: PropsWithChildren) {
    return (
        <>
            <Head title="Get Started" />
            <div className="flex min-h-svh flex-col items-center justify-center bg-muted/30 p-6">
                <Link
                    href={dashboard()}
                    className="mb-8 flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                >
                    <AppLogo />
                </Link>
                <div className="w-full max-w-lg">{children}</div>
            </div>
        </>
    );
}
