import { Download, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    fileName: string;
    url: string;
    downloadUrl: string;
    isImage: boolean;
    isPdf: boolean;
}

export function AttachmentPreviewDialog({
    open,
    onOpenChange,
    fileName,
    url,
    downloadUrl,
    isImage,
    isPdf,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-4xl">
                <DialogTitle className="sr-only">{fileName}</DialogTitle>
                <div className="flex items-center justify-between">
                    <span className="truncate text-sm font-medium">
                        {fileName}
                    </span>
                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => window.open(downloadUrl, '_blank')}
                        >
                            <Download className="size-4" />
                            <span>Download</span>
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            onClick={() => onOpenChange(false)}
                        >
                            <X className="size-4" />
                        </Button>
                    </div>
                </div>
                <div className="flex max-h-[70vh] items-start justify-center overflow-auto rounded-md border bg-muted/30">
                    {isImage && (
                        <img
                            src={url}
                            alt={fileName}
                            className="max-h-[70vh] max-w-full object-contain"
                        />
                    )}
                    {isPdf && (
                        <iframe
                            src={url}
                            title={fileName}
                            className="h-[70vh] w-full"
                        />
                    )}
                    {!isImage && !isPdf && (
                        <div className="flex flex-col items-center gap-4 py-20">
                            <p className="text-sm text-muted-foreground">
                                Preview not available for this file type.
                            </p>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() =>
                                    window.open(downloadUrl, '_blank')
                                }
                            >
                                <Download className="size-4" />
                                <span>Download file</span>
                            </Button>
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
