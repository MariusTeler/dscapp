import { useState } from 'react';
import { Button } from 'primereact/button';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';

export interface Filters {
    operatiune: number;
    search: string;
    data_start: string;
    data_final: string;
    operator: string;
    livrare: number;
    expeditor_localitate: string;
    expeditor: string;
    destinatar_localitate: string;
    destinatar: string;
    curier_preluare: string;
    curier_livrare: string;
    expeditii: string;
}

interface Props {
    initialFilters: Partial<Filters>;
    onSearch: (filters: Filters) => void;
    onExport: () => void;
    onPrint?: () => void;
}

const OPERATIUNI = [
    { label: 'Nr. Expeditie', value: 1 },
    { label: 'Factura', value: 3 },
];

const LIVRARE_OPTIONS = [
    { label: 'Toate', value: 1 },
    { label: 'Sambata', value: 2 },
    { label: 'Sediu', value: 4 },
];

const DEFAULT_FILTERS: Filters = {
    operatiune: 1,
    search: '',
    data_start: '',
    data_final: '',
    operator: '',
    livrare: 1,
    expeditor_localitate: '',
    expeditor: '',
    destinatar_localitate: '',
    destinatar: '',
    curier_preluare: '',
    curier_livrare: '',
    expeditii: '',
};

// Câmpuri care nu sunt primare (operatiune, search)
const ADVANCED_KEYS: (keyof Filters)[] = [
    'data_start', 'data_final', 'operator', 'livrare',
    'expeditor_localitate', 'expeditor',
    'destinatar_localitate', 'destinatar',
    'curier_preluare', 'curier_livrare', 'expeditii',
];

function hasActiveAdvancedFilters(filters: Partial<Filters>): boolean {
    return ADVANCED_KEYS.some((key) => {
        const val = filters[key];
        if (key === 'livrare') return val !== undefined && val !== 1;
        return val !== undefined && val !== '';
    });
}

export default function ExpeditiiFilters({ initialFilters, onSearch, onExport, onPrint }: Props) {
    const [f, setF] = useState<Filters>({ ...DEFAULT_FILTERS, ...initialFilters });
    const [showAdvanced, setShowAdvanced] = useState(() => hasActiveAdvancedFilters(initialFilters));

    const upd = (key: keyof Filters, value: string | number) =>
        setF((prev) => ({ ...prev, [key]: value }));

    return (
        <div className="bg-white rounded-xl shadow-sm border overflow-hidden mb-4">
            {/* Rândul primar — mereu vizibil */}
            <div className="flex items-center gap-2 p-3 flex-wrap">
                <Dropdown
                    value={f.operatiune}
                    options={OPERATIUNI}
                    onChange={(e) => upd('operatiune', e.value as number)}
                    className="w-40"
                />
                <InputText
                    value={f.search}
                    onChange={(e) => upd('search', e.target.value)}
                    placeholder="Valoare căutată"
                    className="w-48"
                    onKeyDown={(e) => e.key === 'Enter' && onSearch(f)}
                />
                <Button
                    label="Caută"
                    icon="pi pi-search"
                    onClick={() => onSearch(f)}
                />
                <Button
                    label="Export Excel"
                    icon="pi pi-file-excel"
                    severity="success"
                    outlined
                    onClick={onExport}
                />
                <Button
                    label="Printare Multiplă"
                    icon="pi pi-print"
                    severity="secondary"
                    outlined
                    onClick={onPrint}
                    disabled={!onPrint}
                />
                <Button
                    label={showAdvanced ? 'Filtre avansate ▲' : 'Filtre avansate ▼'}
                    icon="pi pi-sliders-h"
                    outlined
                    className="ml-auto"
                    onClick={() => setShowAdvanced((v) => !v)}
                />
            </div>

            {/* Panoul avansat — expandabil */}
            {showAdvanced && (
                <div className="border-t bg-gray-50 p-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

                        {/* Perioada */}
                        <div>
                            <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                Perioada
                            </label>
                            <div className="flex gap-1">
                                <InputText
                                    value={f.data_start}
                                    onChange={(e) => upd('data_start', e.target.value)}
                                    placeholder="dd.mm.yyyy"
                                    className="w-full text-sm"
                                />
                                <InputText
                                    value={f.data_final}
                                    onChange={(e) => upd('data_final', e.target.value)}
                                    placeholder="dd.mm.yyyy"
                                    className="w-full text-sm"
                                />
                            </div>
                        </div>

                        {/* Operator */}
                        <div>
                            <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                Operator
                            </label>
                            <InputText
                                value={f.operator}
                                onChange={(e) => upd('operator', e.target.value)}
                                placeholder="Nume operator"
                                className="w-full text-sm"
                            />
                        </div>

                        {/* Livrare */}
                        <div>
                            <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                Livrare
                            </label>
                            <Dropdown
                                value={f.livrare}
                                options={LIVRARE_OPTIONS}
                                onChange={(e) => upd('livrare', e.value as number)}
                                className="w-full"
                            />
                        </div>

                        {/* Curier */}
                        <div>
                            <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                Curier
                            </label>
                            <div className="flex gap-1">
                                <InputText
                                    value={f.curier_preluare}
                                    onChange={(e) => upd('curier_preluare', e.target.value)}
                                    placeholder="Preluare"
                                    className="w-full text-sm"
                                />
                                <InputText
                                    value={f.curier_livrare}
                                    onChange={(e) => upd('curier_livrare', e.target.value)}
                                    placeholder="Livrare"
                                    className="w-full text-sm"
                                />
                            </div>
                        </div>

                        {/* Expeditor */}
                        <div>
                            <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                Expeditor
                            </label>
                            <div className="flex gap-1">
                                <InputText
                                    value={f.expeditor_localitate}
                                    onChange={(e) => upd('expeditor_localitate', e.target.value)}
                                    placeholder="Localitate"
                                    className="w-28 text-sm"
                                />
                                <InputText
                                    value={f.expeditor}
                                    onChange={(e) => upd('expeditor', e.target.value)}
                                    placeholder="Nume client"
                                    className="flex-1 text-sm"
                                />
                            </div>
                        </div>

                        {/* Destinatar */}
                        <div>
                            <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                Destinatar
                            </label>
                            <div className="flex gap-1">
                                <InputText
                                    value={f.destinatar_localitate}
                                    onChange={(e) => upd('destinatar_localitate', e.target.value)}
                                    placeholder="Localitate"
                                    className="w-28 text-sm"
                                />
                                <InputText
                                    value={f.destinatar}
                                    onChange={(e) => upd('destinatar', e.target.value)}
                                    placeholder="Nume client"
                                    className="flex-1 text-sm"
                                />
                            </div>
                        </div>

                        {/* Expeditii bulk — 2 coloane pe lg */}
                        <div className="lg:col-span-2">
                            <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                Expeditii (bulk)
                            </label>
                            <InputTextarea
                                value={f.expeditii}
                                onChange={(e) => upd('expeditii', e.target.value)}
                                placeholder="Numere separate cu , sau Enter"
                                rows={2}
                                className="w-full text-sm"
                            />
                        </div>

                    </div>
                </div>
            )}
        </div>
    );
}
