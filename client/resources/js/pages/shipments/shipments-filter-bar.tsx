import { Search } from 'lucide-react';
import { Calendar } from '@/components/ui/primereact/calendar';
import type { ShipmentsTab, ShipmentsFilters, TipObj } from '@/types/shipments';
import { JUDETE } from '@/lib/judete';
import { useStatusOptions } from '@/pages/shipments/hooks/use-status-options';
import { useState, useEffect, useRef, type ReactNode } from 'react';

interface ShipmentsFilterBarProps {
    tab: ShipmentsTab;
    filters: ShipmentsFilters;
    onChange: (next: ShipmentsFilters) => void;
    /** Slot pentru acțiuni primare (dreapta), ex: <Button>+ AWB nou</Button> */
    actions?: ReactNode;
}

export function ShipmentsFilterBar({ tab, filters, onChange, actions }: ShipmentsFilterBarProps) {
    const statusOptions = useStatusOptions(tab);

    // Search debounce (400ms)
    const [searchValue, setSearchValue] = useState(filters.q);
    const searchTimer = useRef<number | null>(null);

    useEffect(() => {
        const t = window.setTimeout(() => setSearchValue(filters.q), 0);
        return () => window.clearTimeout(t);
    }, [filters.q]);

    const handleSearchChange = (value: string) => {
        setSearchValue(value);
        if (searchTimer.current) window.clearTimeout(searchTimer.current);
        searchTimer.current = window.setTimeout(() => {
            onChange({ ...filters, q: value });
        }, 400);
    };

    const handleDateChange = (which: 'start' | 'end', e: unknown) => {
        const date = (e as { value: Date | null }).value;
        if (!date) return;
        const ymd = formatLocalYmd(date);
        if (which === 'start') {
            onChange({ ...filters, dateStart: ymd });
        } else {
            onChange({ ...filters, dateEnd: ymd });
        }
    };

    return (
        <div className="flex flex-wrap items-center gap-1.5 px-4 py-2 bg-muted/30 border-b border-border">
            <div className="relative flex-1 min-w-[180px]">
                <Search className="absolute left-2 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
                <input
                    value={searchValue}
                    onChange={(e) => handleSearchChange(e.target.value)}
                    placeholder="Caută AWB, destinatar, expeditor, localitate..."
                    className="w-full pl-7 pr-2 py-1 text-xs border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                />
            </div>

            <Calendar
                value={parseYmd(filters.dateStart)}
                onChange={(e) => handleDateChange('start', e)}
                readOnlyInput
                showIcon
                dateFormat="dd.mm.yy"
                placeholder="De la"
                maxDate={parseYmd(filters.dateEnd) ?? undefined}
                className="w-auto text-xs"
                showButtonBar
            />
            <Calendar
                value={parseYmd(filters.dateEnd)}
                onChange={(e) => handleDateChange('end', e)}
                readOnlyInput
                showIcon
                dateFormat="dd.mm.yy"
                placeholder="Până la"
                minDate={parseYmd(filters.dateStart) ?? undefined}
                maxDate={new Date()}
                className="w-auto text-xs"
                showButtonBar
            />

            {tab !== 'nepredate' && (
                <select
                    value={filters.status ?? ''}
                    onChange={(e) => onChange({ ...filters, status: e.target.value || null })}
                    className="px-2 py-1 text-xs border border-border rounded bg-background"
                >
                    <option value="">Toate statusurile</option>
                    {statusOptions.map((s) => (
                        <option key={s} value={s}>{s}</option>
                    ))}
                </select>
            )}

            <select
                value={filters.tipObj ?? ''}
                onChange={(e) => {
                    const v = e.target.value;
                    onChange({ ...filters, tipObj: v ? (Number(v) as TipObj) : null });
                }}
                className="px-2 py-1 text-xs border border-border rounded bg-background"
            >
                <option value="">Toate tipurile</option>
                <option value="1">Plic</option>
                <option value="2">Colet</option>
                <option value="3">Palet</option>
            </select>

            <select
                value={filters.judet ?? ''}
                onChange={(e) => onChange({ ...filters, judet: e.target.value || null })}
                className="px-2 py-1 text-xs border border-border rounded bg-background"
            >
                <option value="">Toate județele</option>
                {JUDETE.map((j) => (
                    <option key={j.code} value={j.code}>
                        {j.name}
                    </option>
                ))}
            </select>

            <div className="flex-1" />

            {actions}
        </div>
    );
}

function formatLocalYmd(d: Date): string {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

function parseYmd(s: string | null | undefined): Date | null {
    if (!s) return null;
    const [y, m, d] = s.split('-').map(Number);
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
}
