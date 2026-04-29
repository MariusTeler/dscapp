import type { ReactNode } from 'react';

interface ShipmentsBulkBarProps {
    selectedCount: number;
    onClear: () => void;
    /** Slot pentru butoane bulk (Print, Borderou, Export, etc.) */
    children?: ReactNode;
}

export function ShipmentsBulkBar({ selectedCount, onClear, children }: ShipmentsBulkBarProps) {
    if (selectedCount === 0) return null;

    return (
        <div className="flex items-center gap-2.5 px-4 py-1.5 bg-foreground text-background">
            <span className="font-semibold text-xs">{selectedCount} selectate</span>
            <div className="flex gap-1.5">{children}</div>
            <div className="flex-1" />
            <button
                onClick={onClear}
                className="px-2.5 py-0.5 text-xs border border-background/30 rounded hover:bg-background/10"
            >
                Deselectează
            </button>
        </div>
    );
}
