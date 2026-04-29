import { useMemo, useState, useCallback } from 'react';
import { Printer } from 'lucide-react';
import { nepredate } from '@/routes/shipments';
import {
    baseColumns as nepredateBaseColumns,
    actionsColumns as nepredateActionsColumns,
} from '@/services/shipments/nepredate-service';
import type { AwbData, UserPrefs } from '@/types';
import type { ShipmentsFilters } from '@/types/shipments';
import type { ColumnDefinition } from 'tabulator-tables';
import { AwbTable } from '@/pages/shipments/awb-table';
import { ShipmentsBulkBar } from '@/pages/shipments/shipments-bulk-bar';
import { Dialog } from '@/components/ui/primereact/dialog';
import { InfoAwb } from '@/components/info-awb';
import { formatDate } from '@/lib/formatters';

interface NepredateTabProps {
    filters: ShipmentsFilters;
    prefs?: UserPrefs;
    isPrinting: boolean;
    createUpdateDeleteRow?: Parameters<typeof AwbTable>[0]['createUpdateDeleteRow'];
    printRows?: Parameters<typeof AwbTable>[0]['printRows'];
    onEditAwb: (awb: AwbData) => void;
    onPrintRows: (rowIds: number[]) => void;
    onDeleteAwb: (awb: AwbData) => void;
}

export function NepredateTab({
    filters,
    prefs,
    isPrinting,
    createUpdateDeleteRow,
    printRows,
    onEditAwb,
    onPrintRows,
    onDeleteAwb,
}: NepredateTabProps) {
    const [selectedRows, setSelectedRows] = useState<AwbData[]>([]);
    const [viewAwb, setViewAwb] = useState<AwbData | null>(null);

    const handleView = useCallback((awb: AwbData) => setViewAwb(awb), []);

    const columns: ColumnDefinition[] = useMemo(() => {
        const base = nepredateBaseColumns().map((col) => {
            if (col.field === 'piese') {
                return {
                    ...col,
                    bottomCalc: 'sum' as const,
                    bottomCalcFormatter: (cell: { getValue: () => number }) => `<strong>${cell.getValue()}</strong>`,
                };
            }
            if (col.field === 'greutate') {
                return {
                    ...col,
                    bottomCalc: 'sum' as const,
                    bottomCalcFormatter: (cell: { getValue: () => number }) => `<strong>${cell.getValue().toFixed(2)} kg</strong>`,
                };
            }
            return col;
        });
        const actions = nepredateActionsColumns({
            onEdit: onEditAwb,
            onPrint: (ids) => onPrintRows(ids),
            onDelete: onDeleteAwb,
        });
        return [...base, actions];
    }, [onEditAwb, onPrintRows, onDeleteAwb]);

    const handlePrintSelected = () => {
        const ids = selectedRows.map((r) => r.id).filter((id): id is number => typeof id === 'number');
        if (ids.length > 0) onPrintRows(ids);
    };

    const handleClearSelection = () => setSelectedRows([]);

    return (
        <>
            {/* Filter bar primary action: + AWB nou */}
            {/* Notă: butonul "+ AWB nou" e injectat via slot din index.tsx în ShipmentsFilterBar — vezi Task 15. */}

            <ShipmentsBulkBar selectedCount={selectedRows.length} onClear={handleClearSelection}>
                <button
                    onClick={handlePrintSelected}
                    disabled={isPrinting}
                    className="px-2.5 py-0.5 text-xs bg-background text-foreground rounded font-semibold hover:bg-background/90 disabled:opacity-50"
                >
                    <Printer className="inline h-3 w-3 mr-1" />
                    {isPrinting ? 'Se printează...' : 'Print'}
                </button>
                {/* Borderou: în Plan 1 doar Nepredate; pentru moment, folosim flux existent dacă e disponibil */}
            </ShipmentsBulkBar>

            <AwbTable
                endpoint={nepredate().url}
                columns={columns}
                filters={filters}
                extraParams={{ swapped: '1' }}
                prefs={prefs}
                createUpdateDeleteRow={createUpdateDeleteRow}
                printRows={printRows}
                onSelectionChange={setSelectedRows}
                onRowDblClick={handleView}
            />

            {viewAwb && (
                <Dialog
                    visible={!!viewAwb}
                    position="top"
                    draggable={false}
                    onHide={() => setViewAwb(null)}
                    header={
                        <div className="flex flex-wrap items-center gap-3">
                            <span>{viewAwb.awb}</span>
                            {viewAwb.status && viewAwb.data_status && (
                                <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-800">
                                    {viewAwb.status} la {formatDate(viewAwb.data_status)}
                                </span>
                            )}
                        </div>
                    }
                    modal
                    style={{ width: '50vw' }}
                    breakpoints={{ '960px': '75vw', '641px': '100vw' }}
                >
                    <InfoAwb
                        selectedAwb={viewAwb}
                        onEdit={(awb) => { setViewAwb(null); onEditAwb(awb); }}
                        onPrint={(rowIds) => { setViewAwb(null); onPrintRows(rowIds); }}
                        onDelete={(awb) => { setViewAwb(null); onDeleteAwb(awb); }}
                        onClose={() => setViewAwb(null)}
                    />
                </Dialog>
            )}
        </>
    );
}

// Slot suplimentar — renunțat la Borderou bulk în Plan 1 pentru simplitate (Plan 2 reintroduce dacă e folosit)
// Dacă mode='borderouri' din awb-nepredate.tsx vechi e necesar și aici, se va trata în Plan 2 când reintegrăm /borderouri.
