import { useMemo, useState, useCallback, useRef } from 'react';
import { Download } from 'lucide-react';
import { retururi } from '@/routes/shipments';
import { useMediaQuery } from '@/hooks/use-media-query';
import { AwbMobileList } from '@/pages/shipments/awb-mobile-list';
import {
    baseColumns as retururiBaseColumns,
    exportRetururiCsv,
} from '@/services/shipments/retururi-service';
import type { AwbData, UserPrefs } from '@/types';
import type { ShipmentsFilters } from '@/types/shipments';
import type { ColumnDefinition } from 'tabulator-tables';
import { AwbTable } from '@/pages/shipments/awb-table';
import { ShipmentsBulkBar } from '@/pages/shipments/shipments-bulk-bar';
import { Dialog } from '@/components/ui/primereact/dialog';
import { InfoAwb } from '@/components/info-awb';
import { formatDate } from '@/lib/formatters';
import Toast, { ToastRef, showSuccess, showError } from '@/components/ui/primereact/toast';

interface RetururiTabProps {
    filters: ShipmentsFilters;
    prefs?: UserPrefs;
}

export function RetururiTab({ filters, prefs }: RetururiTabProps) {
    const isMobile = useMediaQuery('(max-width: 1023px)');
    const [selectedRows, setSelectedRows] = useState<AwbData[]>([]);
    const [viewAwb, setViewAwb] = useState<AwbData | null>(null);
    const toast = useRef<ToastRef>(null);

    const handleView = useCallback((awb: AwbData) => setViewAwb(awb), []);

    const columns: ColumnDefinition[] = useMemo(() => retururiBaseColumns(), []);

    const handleClearSelection = () => setSelectedRows([]);

    return (
        <>
            <Toast ref={toast} />

            <ShipmentsBulkBar selectedCount={selectedRows.length} onClear={handleClearSelection}>
                {/* No bulk actions yet for Retururi — read-only tab */}
            </ShipmentsBulkBar>

            {isMobile ? (
                <AwbMobileList
                    endpoint={retururi().url}
                    filters={filters}
                    onSelectionChange={setSelectedRows}
                />
            ) : (
                <AwbTable
                    endpoint={retururi().url}
                    columns={columns}
                    filters={filters}
                    prefs={prefs}
                    onSelectionChange={setSelectedRows}
                    onRowDblClick={handleView}
                />
            )}

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
                    <InfoAwb selectedAwb={viewAwb} onClose={() => setViewAwb(null)} />
                </Dialog>
            )}
        </>
    );
}

/**
 * Standalone export action — used as filter-bar action slot from index.tsx.
 * Returns a button that triggers CSV export with current filters.
 */
export function RetururiExportButton({ filters }: { filters: ShipmentsFilters }) {
    const toast = useRef<ToastRef>(null);

    const handleExport = async () => {
        try {
            await exportRetururiCsv([], filters.dateStart, filters.dateEnd);
            showSuccess(toast, 'Succes', 'Fișier CSV exportat cu succes!');
        } catch (e) {
            showError(toast, 'Eroare', e instanceof Error ? e.message : 'Eroare la exportul CSV');
        }
    };

    return (
        <>
            <Toast ref={toast} />
            <button
                onClick={handleExport}
                className="px-2 py-1 text-xs bg-foreground text-background rounded font-semibold hover:opacity-90 inline-flex items-center gap-1"
            >
                <Download className="h-3.5 w-3.5" />
                Export CSV
            </button>
        </>
    );
}
