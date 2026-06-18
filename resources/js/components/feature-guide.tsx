import { HelpCircle } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
} from '@/components/ui/sheet';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface GuideSection {
    title: string;
    content: string;
}

interface GuideItem {
    term: string;
    description: string;
}

export interface GuideContent {
    title: string;
    description: string;
    sections: GuideSection[];
    items?: {
        heading: string;
        data: GuideItem[];
    }[];
    tips?: string[];
}

interface FeatureGuideProps {
    content: GuideContent;
}

export function FeatureGuide({ content }: FeatureGuideProps) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <Button
                variant="ghost"
                size="icon"
                className="size-8 text-muted-foreground"
                onClick={() => setOpen(true)}
            >
                <HelpCircle className="size-4" />
            </Button>

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent side="right" className="w-full sm:max-w-md">
                    <SheetHeader>
                        <SheetTitle className="flex items-center gap-2">
                            <HelpCircle className="size-5" />
                            {content.title}
                        </SheetTitle>
                        <SheetDescription>
                            {content.description}
                        </SheetDescription>
                    </SheetHeader>

                    <div className="flex-1 overflow-y-auto px-4 pb-4">
                        <div className="space-y-6">
                            {content.sections.map((section) => (
                                <div key={section.title}>
                                    <h3 className="mb-2 text-sm font-semibold">
                                        {section.title}
                                    </h3>
                                    <p className="text-sm leading-relaxed text-muted-foreground">
                                        {section.content}
                                    </p>
                                </div>
                            ))}

                            {content.items?.map((group) => (
                                <div key={group.heading}>
                                    <h3 className="mb-2 text-sm font-semibold">
                                        {group.heading}
                                    </h3>
                                    <div className="space-y-2">
                                        {group.data.map((item) => (
                                            <div
                                                key={item.term}
                                                className="rounded-md border p-3"
                                            >
                                                <dt className="text-sm font-medium">
                                                    {item.term}
                                                </dt>
                                                <dd className="mt-1 text-sm text-muted-foreground">
                                                    {item.description}
                                                </dd>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}

                            {content.tips && content.tips.length > 0 && (
                                <div>
                                    <h3 className="mb-2 text-sm font-semibold">
                                        Tips
                                    </h3>
                                    <ul className="space-y-1.5">
                                        {content.tips.map((tip, i) => (
                                            <li
                                                key={i}
                                                className="flex items-start gap-2 text-sm text-muted-foreground"
                                            >
                                                <span className="mt-0.5 text-primary">
                                                    •
                                                </span>
                                                <span>{tip}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </div>
                    </div>
                </SheetContent>
            </Sheet>
        </>
    );
}

interface InlineTooltipProps {
    content: string;
}

export function InlineTooltip({ content }: InlineTooltipProps) {
    return (
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger asChild>
                    <button
                        type="button"
                        className="inline-flex size-4 items-center justify-center rounded-full text-muted-foreground hover:text-foreground"
                    >
                        <HelpCircle className="size-3" />
                    </button>
                </TooltipTrigger>
                <TooltipContent side="top" className="max-w-xs">
                    <p className="text-xs">{content}</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}
