import { Column } from 'primereact/column';
import { DataTable, DataTablePageEvent, DataTableSortEvent } from 'primereact/datatable';
import { Expeditie, Paginator } from '@/types/expeditii';

interface Props {
    expeditii: Paginator;
    onPage: (page: number) => void;
    onSort: (col: string, dir: 'asc' | 'desc') => void;
    onRowDoubleClick?: (expeditie: Expeditie) => void;
}

const numFmt = new Intl.NumberFormat('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const HEADER_STYLE = { backgroundColor: '#1e3a5f', color: 'white', fontWeight: 600 };

function tipExpBadge(tip: string) {
    return tip === 'STD' ? (
        <span className="px-1.5 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">{tip}</span>
    ) : (
        <span className="px-1.5 py-0.5 rounded text-xs font-semibold bg-yellow-100 text-yellow-800">{tip}</span>
    );
}

function tipPlataBadge(tip: string) {
    if (tip === 'PL') return <span className="px-1.5 py-0.5 rounded text-xs bg-green-50 text-green-700">PL</span>;
    if (tip === 'TP') return <span className="px-1.5 py-0.5 rounded text-xs bg-amber-50 text-amber-700">TP</span>;
    return <span className="px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-600">{tip}</span>;
}

export default function ExpeditiiTable({ expeditii, onPage, onSort, onRowDoubleClick }: Props) {
    return (
        <div className="bg-white rounded-xl shadow-sm border overflow-hidden">
            <DataTable
                value={expeditii.data}
                lazy
                paginator
                rows={expeditii.per_page}
                totalRecords={expeditii.total}
                first={(expeditii.current_page - 1) * expeditii.per_page}
                onPage={(e: DataTablePageEvent) =>
                    onPage(Math.floor(e.first / (e.rows ?? expeditii.per_page)) + 1)
                }
                onSort={(e: DataTableSortEvent) =>
                    onSort(
                        String(e.sortField ?? ''),
                        e.sortOrder === 1 ? 'asc' : 'desc',
                    )
                }
                onRowDoubleClick={(e) => onRowDoubleClick?.(e.data as Expeditie)}
                scrollable
                scrollHeight="calc(100vh - 300px)"
                size="small"
                stripedRows
                emptyMessage="Nicio expeditie găsită. Aplicați un filtru pentru a căuta."
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                currentPageReportTemplate="{first}-{last} din {totalRecords} expeditii"
                pt={{
                    column: {
                        headerCell: { style: HEADER_STYLE },
                    },
                }}
            >
                <Column
                    field="expeditie"
                    header="Nr.Exp"
                    sortable
                    style={{ width: '78px' }}
                    body={(r: Expeditie) => (
                        <span className="text-blue-600 font-medium">{r.expeditie}</span>
                    )}
                />
                <Column
                    field="tip_exp"
                    header="Tip"
                    style={{ width: '52px' }}
                    body={(r: Expeditie) => tipExpBadge(r.tip_exp)}
                />
                <Column field="expeditor" header="Expeditor" sortable />
                <Column field="destinatar" header="Destinatar" sortable />
                <Column
                    field="expeditor_centru"
                    header="Centru Exp"
                    style={{ width: '90px' }}
                    bodyClassName="text-gray-500"
                />
                <Column
                    field="destinatar_centru"
                    header="Centru Dest"
                    style={{ width: '90px' }}
                    bodyClassName="text-gray-500"
                />
                <Column
                    field="data_expeditie"
                    header="Data"
                    sortable
                    style={{ width: '78px' }}
                    bodyClassName="text-gray-500"
                />
                <Column
                    header="Pl/Co/Pa"
                    style={{ width: '58px', textAlign: 'center' }}
                    body={(r: Expeditie) => `${r.plicuri}/${r.colete}/${r.paleti}`}
                />
                <Column
                    field="greutate"
                    header="Gr."
                    sortable
                    style={{ width: '58px', textAlign: 'right' }}
                    body={(r: Expeditie) => numFmt.format(r.greutate)}
                />
                <Column
                    field="ramburs"
                    header="Ramb."
                    sortable
                    style={{ width: '68px', textAlign: 'right' }}
                    body={(r: Expeditie) =>
                        r.ramburs ? (
                            <span className="text-red-600 font-medium">{numFmt.format(r.ramburs)}</span>
                        ) : (
                            ''
                        )
                    }
                />
                <Column
                    field="tip_plata"
                    header="Plată"
                    style={{ width: '52px', textAlign: 'center' }}
                    body={(r: Expeditie) => tipPlataBadge(r.tip_plata)}
                />
                <Column
                    field="valoare_totala_expeditie"
                    header="Val.Exp"
                    sortable
                    style={{ width: '76px', textAlign: 'right' }}
                    body={(r: Expeditie) => numFmt.format(r.valoare_totala_expeditie)}
                />
                <Column
                    field="observatii"
                    header="Obs."
                    bodyClassName="text-gray-400 italic"
                />
            </DataTable>
        </div>
    );
}
