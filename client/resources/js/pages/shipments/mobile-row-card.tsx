import { useState, type ReactNode } from 'react';
import { ChevronRight, ChevronDown } from 'lucide-react';
import type { AwbData, UserPrefs } from '@/types';
import { formatDate } from '@/lib/formatters';
import { cn } from '@/lib/utils';

interface MobileRowCardProps {
    row: AwbData;
    selected: boolean;
    onSelect: (id: number) => void;
    /** Slot for action buttons in expanded zone */
    actions?: (row: AwbData) => ReactNode;
}

function statusPillClass(tipObj: number | undefined): string {
    if (tipObj === 1) return 'bg-indigo-100 text-indigo-700';
    if (tipObj === 2) return 'bg-emerald-100 text-emerald-700';
    if (tipObj === 3) return 'bg-amber-100 text-amber-700';
    return 'bg-slate-100 text-slate-700';
}

function tipObjLabel(tipObj: number | undefined): string {
    if (tipObj === 1) return 'Plic';
    if (tipObj === 2) return 'Colet';
    if (tipObj === 3) return 'Palet';
    return '—';
}

export function MobileRowCard({ row, selected, onSelect, actions }: MobileRowCardProps) {
    const [expanded, setExpanded] = useState(false);
    const Chevron = expanded ? ChevronDown : ChevronRight;

    return (
        <div
            className={cn(
                'border rounded-lg bg-background mb-2',
                selected ? 'border-primary ring-1 ring-primary/30' : 'border-border'
            )}
        >
            <div
                onClick={() => setExpanded((e) => !e)}
                className="flex items-center gap-2 p-3 cursor-pointer"
            >
                <input
                    type="checkbox"
                    checked={selected}
                    onChange={(e) => {
                        e.stopPropagation();
                        if (typeof row.id === 'number') onSelect(row.id);
                    }}
                    onClick={(e) => e.stopPropagation()}
                />
                <Chevron className="h-4 w-4 text-muted-foreground shrink-0" />
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                        <span className="font-mono font-bold text-sm">{row.awb ?? '—'}</span>
                        <span className={cn('px-2 py-0.5 rounded-full text-[10px] font-semibold', statusPillClass(row.tip_obj))}>
                            {tipObjLabel(row.tip_obj)}
                        </span>
                    </div>
                    <div className="text-xs font-medium text-foreground mt-0.5 truncate">
                        {row.destinatar_nume ?? '—'}
                    </div>
                    <div className="text-[11px] text-muted-foreground truncate">
                        {row.destinatar_localitate ?? '—'}
                    </div>
                </div>
                <div className="text-right shrink-0">
                    {row.ramburs && Number(row.ramburs) > 0 ? (
                        <span className="font-mono font-bold text-sm text-emerald-600">
                            {Number(row.ramburs).toLocaleString('ro-RO')} RON
                        </span>
                    ) : (
                        <span className="text-muted-foreground">—</span>
                    )}
                </div>
            </div>

            {expanded && (
                <div className="px-3 pb-3 pt-1 border-t border-border bg-muted/20">
                    <div className="grid grid-cols-2 gap-x-3 gap-y-1 text-[11px] mt-2">
                        <div><span className="text-muted-foreground">Data:</span> {row.data_expeditie ? formatDate(row.data_expeditie) : '—'}</div>
                        <div><span className="text-muted-foreground">Greutate:</span> {row.greutate ?? 0} kg</div>
                        <div><span className="text-muted-foreground">Piese:</span> {row.piese ?? '—'}</div>
                        <div><span className="text-muted-foreground">Km:</span> {row.km_exteriori ?? '—'}</div>
                        <div className="col-span-2"><span className="text-muted-foreground">Expeditor:</span> {row.expeditor_nume ?? '—'}</div>
                        <div className="col-span-2"><span className="text-muted-foreground">Adresă:</span> {row.destinatar_adresa ?? '—'}</div>
                    </div>
                    {actions && (
                        <div className="flex flex-wrap gap-2 mt-3">
                            {actions(row)}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

// Suppress unused import warning if UserPrefs not needed
export type { UserPrefs };
