import { cn } from '@/lib/utils';

interface Step {
    id: number;
    title: string;
    optional?: boolean;
}

interface StepperProps {
    steps: Step[];
    currentStep: number;
}

export function Stepper({ steps, currentStep }: StepperProps) {
    return (
        <div className="flex items-center justify-center gap-2">
            {steps.map((step, index) => (
                <div key={step.id} className="flex items-center gap-2">
                    <div className="flex items-center gap-2">
                        <div
                            className={cn(
                                'flex size-8 shrink-0 items-center justify-center rounded-full border-2 text-sm font-medium transition-colors',
                                currentStep > step.id
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : currentStep === step.id
                                      ? 'border-primary bg-primary/10 text-primary'
                                      : 'border-muted-foreground/30 text-muted-foreground',
                            )}
                        >
                            {currentStep > step.id ? (
                                <svg
                                    className="size-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    strokeWidth={2.5}
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            ) : (
                                step.id
                            )}
                        </div>
                        <span
                            className={cn(
                                'hidden text-sm font-medium sm:inline',
                                currentStep === step.id
                                    ? 'text-foreground'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {step.title}
                        </span>
                    </div>
                    {index < steps.length - 1 && (
                        <div
                            className={cn(
                                'mx-1 h-0.5 w-8 sm:w-12',
                                currentStep > step.id
                                    ? 'bg-primary'
                                    : 'bg-muted-foreground/20',
                            )}
                        />
                    )}
                </div>
            ))}
        </div>
    );
}
