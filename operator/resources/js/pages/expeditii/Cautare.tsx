import { router } from '@inertiajs/react';
import { Dialog } from 'primereact/dialog';
import { useState } from 'react';
import ExpeditiiFilters, { Filters } from '@/components/expeditii/ExpeditiiFilters';
import ExpeditiiTable from '@/components/expeditii/ExpeditiiTable';
import OperatorLayout from '@/layouts/OperatorLayout';
import { Expeditie, Paginator } from '@/types/expeditii';

interface Props {
    expeditii: Paginator;
    filters: Partial<Filters>;
}

const numFmt = new Intl.NumberFormat('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const DIALOG_STYLE = { width: '450px' };

export default function Cautare({ expeditii, filters }: Props) {
    const [detailsRow, setDetailsRow] = useState<Expeditie | null>(null);

    function handleSearch(newFilters: Filters) {
        router.get(
            '/expeditii/cautare',
            { ...newFilters, page: 1 },
            { preserveState: true, preserveScroll: true },
        );
    }

    function handlePage(page: number) {
        router.get(
            '/expeditii/cautare',
            { ...filters, page },
            { preserveState: true, preserveScroll: true },
        );
    }

    function handleSort(col: string, dir: 'asc' | 'desc') {
        router.get(
            '/expeditii/cautare',
            { ...filters, sort_col: col || 'ep.data_expeditie', sort_dir: dir, page: 1 },
            { preserveState: true, preserveScroll: true },
        );
    }

    function handleExport() {
        window.location.href =
            '/expeditii/export?' +
            new URLSearchParams(
                Object.fromEntries(
                    Object.entries(filters).map(([k, v]) => [k, String(v)])
                )
            ).toString();
    }

    return (
        <OperatorLayout title="Căutare Expeditii">
            <ExpeditiiFilters
                initialFilters={filters}
                onSearch={handleSearch}
                onExport={handleExport}
            />

            <ExpeditiiTable
                expeditii={expeditii}
                onPage={handlePage}
                onSort={handleSort}
                onRowDoubleClick={(exp) => setDetailsRow(exp)}
            />

            <Dialog
                header={`Expeditia #${detailsRow?.expeditie}`}
                visible={detailsRow !== null}
                onHide={() => setDetailsRow(null)}
                style={DIALOG_STYLE}
            >
                {detailsRow && (
                    <div className="grid grid-cols-2 gap-y-3 text-sm">
                        <span className="text-gray-500 font-medium">Curier Preluare</span>
                        <span>{detailsRow.curier_preluare ?? '—'}</span>

                        <span className="text-gray-500 font-medium">Curier Livrare</span>
                        <span>{detailsRow.curier_livrare ?? '—'}</span>

                        <span className="text-gray-500 font-medium">Km Preluare</span>
                        <span>{detailsRow.km_preluare ?? '—'}</span>

                        <span className="text-gray-500 font-medium">Km Livrare</span>
                        <span>{detailsRow.km_livrare ?? '—'}</span>

                        <span className="text-gray-500 font-medium">Val. Asigurată</span>
                        <span>
                            {detailsRow.valoare_asigurata != null
                                ? numFmt.format(detailsRow.valoare_asigurata)
                                : '—'}
                        </span>
                    </div>
                )}
            </Dialog>
        </OperatorLayout>
    );
}
