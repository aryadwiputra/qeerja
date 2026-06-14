import { router } from '@inertiajs/react';
import { useState } from 'react';
import { StepInvite } from '@/components/onboarding/step-invite';
import { StepProfile } from '@/components/onboarding/step-profile';
import { StepProject } from '@/components/onboarding/step-project';
import { StepWorkspace } from '@/components/onboarding/step-workspace';
import { Stepper } from '@/components/onboarding/stepper';
import { dashboard } from '@/routes';

interface Props {
    hasWorkspace: boolean;
    hasProject: boolean;
    currentWorkspace: { id: number; name: string; slug: string } | null;
}

const steps = [
    { id: 1, title: 'Workspace' },
    { id: 2, title: 'Project', optional: true },
    { id: 3, title: 'Invite', optional: true },
    { id: 4, title: 'Profile', optional: true },
];

export default function OnboardingIndex({
    hasWorkspace,
    hasProject,
    currentWorkspace,
}: Props) {
    const getInitialStep = () => {
        if (!hasWorkspace) {
            return 1;
        }

        if (!hasProject) {
            return 2;
        }

        return 3;
    };

    const [currentStep, setCurrentStep] = useState(getInitialStep);
    const [workspace, setWorkspace] = useState(currentWorkspace);

    const goToDashboard = () => {
        router.visit(dashboard());
    };

    return (
        <div className="flex flex-col gap-8">
            <Stepper steps={steps} currentStep={currentStep} />

            {currentStep === 1 && (
                <StepWorkspace
                    onCreated={(w) => {
                        setWorkspace(w);
                        setCurrentStep(2);
                    }}
                />
            )}

            {currentStep === 2 && workspace && (
                <StepProject
                    workspaceSlug={workspace.slug}
                    onSkip={() => setCurrentStep(3)}
                    onCreated={() => setCurrentStep(3)}
                />
            )}

            {currentStep === 3 && workspace && (
                <StepInvite
                    workspaceSlug={workspace.slug}
                    onSkip={() => setCurrentStep(4)}
                    onDone={() => setCurrentStep(4)}
                />
            )}

            {currentStep === 4 && (
                <StepProfile onSkip={goToDashboard} onDone={goToDashboard} />
            )}

            <div className="text-center">
                <button
                    type="button"
                    className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                    onClick={goToDashboard}
                >
                    Skip all and go to dashboard →
                </button>
            </div>
        </div>
    );
}
