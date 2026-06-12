import { AlertTriangle } from 'lucide-react';
import type { ReactNode } from 'react';
import { Component } from 'react';
import { Button } from '@/components/ui/button';

interface Props {
    children: ReactNode;
    fallback?: ReactNode;
}

interface State {
    hasError: boolean;
    error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
    constructor(props: Props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    handleRetry = () => {
        this.setState({ hasError: false, error: null });
    };

    render() {
        if (this.state.hasError) {
            if (this.props.fallback) {
                return this.props.fallback;
            }

            return (
                <div className="flex min-h-[320px] flex-col items-center justify-center gap-4 rounded-xl border bg-card p-12 text-center text-card-foreground shadow-sm">
                    <div className="rounded-full bg-destructive/10 p-3 text-destructive">
                        <AlertTriangle className="size-6" />
                    </div>
                    <h2 className="text-lg font-semibold">
                        Something went wrong
                    </h2>
                    <p className="max-w-md text-sm text-muted-foreground">
                        {this.state.error?.message ||
                            'An unexpected error occurred.'}
                    </p>
                    <Button variant="outline" onClick={this.handleRetry}>
                        Try again
                    </Button>
                </div>
            );
        }

        return this.props.children;
    }
}
