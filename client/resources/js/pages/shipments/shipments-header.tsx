import type { ShipmentsTab, ShipmentsStats } from '@/types/shipments';
import { cn } from '@/lib/utils';

interface ShipmentsHeaderProps {
    activeTab: ShipmentsTab;
    onTabChange: (tab: ShipmentsTab) => void;
    tabCounts: Record<ShipmentsTab, number | null>;
    stats: ShipmentsStats | null; // null → ascunde stats
}

const TABS: { value: ShipmentsTab; label: string }[] = [
    { value: 'nepredate', label: 'Nepredate' },
    { value: 'predate', label: 'Predate' },
    { value: 'retururi', label: 'Retururi' },
];

function formatNumber(n: number, opts?: Intl.NumberFormatOptions): string {
    return new Intl.NumberFormat('ro-RO', opts).format(n);
}

export function ShipmentsHeader({ activeTab, onTabChange, tabCounts, stats }: ShipmentsHeaderProps) {
    return (
        <div className="flex items-center gap-4 px-4 py-2 border-b border-border">
            <h2 className="text-base font-bold text-foreground shrink-0">Expediții</h2>

            <div className="inline-flex gap-0.5 p-0.5 bg-muted rounded-md">
                {TABS.map((tab) => {
                    const isActive = tab.value === activeTab;
                    const count = tabCounts[tab.value];
                    return (
                        <button
                            key={tab.value}
                            onClick={() => onTabChange(tab.value)}
                            className={cn(
                                'inline-flex items-center gap-1.5 px-2.5 py-1 text-xs rounded font-medium transition-colors',
                                isActive
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            )}
                        >
                            <span>{tab.label}</span>
                            {count != null && (
                                <span
                                    className={cn(
                                        'px-1.5 rounded-full text-[10px]',
                                        isActive ? 'bg-foreground text-background' : 'bg-border text-muted-foreground'
                                    )}
                                >
                                    {count}
                                </span>
                            )}
                        </button>
                    );
                })}
            </div>

            <div className="flex-1" />

            {stats && (
                <div className="flex gap-2">
                    <Stat label="AWB" value={formatNumber(stats.count)} />
                    <Stat
                        label="Greut."
                        value={`${formatNumber(stats.total_weight, { maximumFractionDigits: 1 })} kg`}
                    />
                    <Stat
                        label="Ramburs"
                        value={`${formatNumber(stats.total_ramburs, { maximumFractionDigits: 0 })} RON`}
                        accent="green"
                    />
                </div>
            )}
        </div>
    );
}

function Stat({ label, value, accent }: { label: string; value: string; accent?: 'green' }) {
    return (
        <div className="flex items-center gap-1.5 px-2.5 py-1 border border-border rounded-md bg-muted/30">
            <span className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
                {label}
            </span>
            <span
                className={cn(
                    'text-xs font-bold font-mono',
                    accent === 'green' ? 'text-emerald-600' : 'text-foreground'
                )}
            >
                {value}
            </span>
        </div>
    );
}
