import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate, formatDistance, formatLocalitate, formatTipObject, formatTipPlata, formatWeight } from '@/lib/formatters';
import type { AwbData } from '@/types';
import { Edit, Trash2, Printer, LucideShieldClose } from 'lucide-react';

interface InfoAwbProps {
    selectedAwb: AwbData;
    onEdit?: (awb: AwbData) => void;
    onPrint?: (rowIds: number[]) => void;
    onDelete?: (awb: AwbData) => void;
    onClose: () => void;
}

export function InfoAwb({ selectedAwb, onEdit, onPrint, onDelete, onClose }: InfoAwbProps) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div className="space-y-2">
                <h3 className="text-lg font-semibold border-b pb-2">Informatii generale</h3>
                <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                        <span className="font-medium">Numar AWB:</span>
                        <span className="font-mono font-semibold">{selectedAwb.awb}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="font-medium">Data:</span>
                        <span>{formatDate(selectedAwb.data_expeditie)}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="font-medium">Nr. piese:</span>
                        <span>{selectedAwb.piese}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="font-medium">Greutate:</span>
                        <span>{formatWeight(selectedAwb.greutate)}</span>
                    </div>
                    {selectedAwb.tip_obj > 1 && selectedAwb.greutate_vol !== null && selectedAwb.greutate_vol !== undefined && selectedAwb.greutate_vol > 0 && (
                        <div className="flex justify-between">
                            <span className="font-medium">Greutate volumetrica:</span>
                            <span>{formatWeight(selectedAwb.greutate_vol)}</span>
                        </div>
                    )}
                    <div className="flex justify-between">
                        <span className="font-medium">Tip object:</span>
                        <span className={`px-2 py-1 text-xs rounded-full ${formatTipObject(selectedAwb.tip_obj)?.colorClass || 'bg-gray-100 text-gray-800'}`}>
                            {formatTipObject(selectedAwb.tip_obj)?.label || ''}
                        </span>
                    </div>
                    <div className="flex justify-between">
                        <span className="font-medium">Km exteriori:</span>
                        <span>{formatDistance(selectedAwb.km_exteriori)}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="font-medium">Valoare declarata:</span>
                        <span>{formatCurrency(selectedAwb.asigurare ?? 0.00)}</span>
                    </div>
                </div>
            </div>

            <div className="space-y-2">
                <h3 className="text-lg font-semibold border-b pb-2">Expeditor si destinatar</h3>
                <div className="space-y-3">
                    <div>
                        <div className="font-medium text-sm">Expeditor:</div>
                        <div className="text-sm">{selectedAwb.expeditor_nume}</div>
                        <div className="text-sm">{formatLocalitate(selectedAwb.expeditor_judet, selectedAwb.expeditor_localitate)}</div>
                        <div className="text-xs text-gray-600">{selectedAwb.expeditor_adresa}</div>
                        <div className="text-xs text-gray-600">Km. preluare: {formatDistance(selectedAwb.km_preluare)}</div>
                        {selectedAwb.expeditor_contact && (
                            <div className="text-xs text-gray-600">Contact: {selectedAwb.expeditor_contact}</div>
                        )}
                        {selectedAwb.expeditor_telefon && (
                            <div className="text-xs text-gray-600">Telefon: {selectedAwb.expeditor_telefon}</div>
                        )}
                        {selectedAwb.expeditor_email && (
                            <div className="text-xs text-gray-600">Email: {selectedAwb.expeditor_email}</div>
                        )}
                    </div>
                    <div>
                        <div className="font-medium text-sm">Destinatar:</div>
                        <div className="text-sm">{selectedAwb.destinatar_nume}</div>
                        <div className="text-sm">{formatLocalitate(selectedAwb.destinatar_judet, selectedAwb.destinatar_localitate)}</div>
                        <div className="text-xs text-gray-600">{selectedAwb.destinatar_adresa}</div>
                        <div className="text-xs text-gray-600">Km. livrare: {formatDistance(selectedAwb.km_livrare)}</div>
                        {selectedAwb.destinatar_contact && (
                            <div className="text-xs text-gray-600">Contact: {selectedAwb.destinatar_contact}</div>
                        )}
                        {selectedAwb.destinatar_telefon && (
                            <div className="text-xs text-gray-600">Telefon: {selectedAwb.destinatar_telefon}</div>
                        )}
                        {selectedAwb.destinatar_email && (
                            <div className="text-xs text-gray-600">Email: {selectedAwb.destinatar_email}</div>
                        )}
                    </div>
                </div>
            </div>

            <div className="space-y-4 md:col-span-2">
                <div className="flex gap-2 text-sm border-b pb-2">
                    <span className="font-semibold">
                        {[
                            selectedAwb.ret_nt && 'Retur NT',
                            selectedAwb.ret_nc && 'Retur Nota',
                            selectedAwb.ret_doc && 'Retur Doc',
                            selectedAwb.ret_amb && 'Retur Amb',
                            selectedAwb.ret_colet && 'Retur Colet',
                            selectedAwb.sms && 'SMS livrare',
                            selectedAwb.copen && 'Deschidere colet',
                            selectedAwb.liv_samb && 'Livrare sambata',
                            selectedAwb.liv_sed && 'Livrare sediu'
                        ]
                            .filter(Boolean)
                            .join(' | ') || 'Niciun serviciu aditional selectat'}
                    </span>
                </div>
            </div>

            <div className="space-y-2 md:col-span-2">
                <h3 className="text-lg font-semibold border-b pb-2">Informatii financiare : <span className="text-sm font-normal">Plata la</span> {selectedAwb.platitor == 2 ? 'Destinatar' : 'Expeditor'}</h3>
                <div className="grid grid-cols-4 gap-4 text-sm">
                    <div>
                        <div className="font-medium">Valoare fara TVA:</div>
                        <div className="text-lg">{formatCurrency(selectedAwb.valoare_fara_tva ?? 0.00)}</div>
                    </div>
                    <div>
                        <div className="font-medium">TVA:</div>
                        <div className="text-lg">{formatCurrency(selectedAwb.valoare_tva ?? 0.00)}</div>
                    </div>
                    <div>
                        <div className="font-medium">Ramburs:</div>
                        <div className="text-lg">{formatCurrency(selectedAwb.ramburs ?? 0.00)}</div>
                    </div>
                    <div>
                        <div className="font-medium">Tip plata:</div>
                        <div className="text-lg">{formatTipPlata(selectedAwb.tip_plata ?? 0)}</div>
                    </div>
                </div>
            </div>

            <div className="md:col-span-2 flex gap-2 pt-4 border-t">
                {onDelete && (
                    <Button
                        onClick={() => onDelete(selectedAwb)}
                        variant="destructive"
                        className="flex items-center gap-2"
                    >
                        <Trash2 className="h-4 w-4" />
                        Sterge
                    </Button>
                )}
                {selectedAwb.can_update && onEdit && (
                    <Button onClick={() => onEdit(selectedAwb)} className="flex items-center gap-2">
                        <Edit className="h-4 w-4" />
                        Editeaza
                    </Button>
                )}
                {onPrint && (
                    <Button onClick={() => onPrint([selectedAwb.id as number])} variant="outline" className="flex items-center gap-2 ml-auto">
                        <Printer className="h-4 w-4" />
                        Print
                    </Button>
                )}
                <Button onClick={onClose} variant="outline" className={!onPrint ? 'ml-auto' : ''}>
                    <LucideShieldClose className="h-4 w-4" />
                    Inchide
                </Button>
            </div>
        </div>
    );
}
