import { Dialog } from '@/components/ui/primereact/dialog';
import { Button } from '@/components/ui/button';
import OrderForm from '@/pages/comenzi/order-form';
import { useState } from 'react';
import { deleteOrder} from '@/services/comenzi';
import type { TabulatorFull as Tabulator } from 'tabulator-tables';
import { Edit, LucideShieldClose, Trash2 } from 'lucide-react';
import type { OrderData, SharedData } from '@/types';
import { formatDate, formatLocalitate, formatWeight } from '@/lib/formatters';

// View Details Dialog Props
interface ViewOrderDialogProps {
    visible: boolean;
    onVisibleChange: (visible: boolean) => void;
    order: OrderData | null;
    onEdit?: (order: OrderData) => void;
    onDelete?: (order: OrderData) => void;
}

// Edit Dialog Props
interface EditOrderDialogProps {
    auth: SharedData['auth'];
    pcs: SharedData['pcs'];
    prefs?: SharedData['prefs'];
    order: OrderData | null;
    onSave: (data: OrderData) => void;
    visible: boolean;
    onVisibleChange: (visible: boolean) => void;
}

// Delete Dialog Props
interface DeleteOrderDialogProps {
    visible: boolean;
    onVisibleChange: (visible: boolean) => void;
    order: OrderData | null;
    onSuccess?: () => void;
    onFailed?: (message: string) => void;
    tabulatorRef?: React.RefObject<Tabulator | null>;
}

// View Order Details Dialog
export function ViewOrderDialog({ visible, onVisibleChange, order, onEdit, onDelete }: ViewOrderDialogProps) {
    if (!order) return null;

    const footerContent = (
        <div className="flex justify-end gap-2">
            <Button variant="destructive" onClick={() => { onVisibleChange(false); onDelete?.(order); }}>
                <Trash2 className="h-4 w-4" />
                Sterge
            </Button>
            <Button onClick={() => { onVisibleChange(false); onEdit?.(order); }}>
                <Edit className="h-4 w-4" />
                Editeaza
            </Button>
            <Button variant="outline" onClick={() => onVisibleChange(false)}>
                <LucideShieldClose className="h-4 w-4" />
                Inchide
            </Button>
        </div>
    );

    return (
        <Dialog
            visible={visible}
            onHide={() => onVisibleChange(false)}
            header={`Detalii Comanda #${order.id}`}
            footer={footerContent}
            modal={true}
            style={{ width: '50vw' }}
            breakpoints={{ '960px': '75vw', '641px': '100vw' }}
        >
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 py-4">
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold border-b pb-2">Informatii generale</h3>
                    <div className="grid grid-cols-2 gap-2 text-sm">
                        <div className="font-medium">Nr. Comanda:</div>
                        <div>#{order.id}</div>
                        <div className="font-medium">Ridica de la:</div>
                        <div>{order.expeditor_nume}</div>
                        <div className="font-medium">Localitate:</div>
                        <div>{formatLocalitate(order.expeditor_judet, order.expeditor_localitate)}</div>
                        <div className="font-medium">Adresa:</div>
                        <div>{order.expeditor_adresa}</div>
                        <div className="font-medium">Colete:</div>
                        <div>{order.colete}</div>
                        <div className="font-medium">Paleti:</div>
                        <div>{order.paleti}</div>
                        <div className="font-medium">Greutate:</div>
                        <div>{formatWeight(order.greutate)}</div>
                    </div>
                </div>

                <div className="space-y-4">
                    <h3 className="text-lg font-semibold border-b pb-2">Informatii sistem</h3>
                    <div className="grid grid-cols-2 gap-2 text-sm">
                        <div className="font-medium">Data colectare:</div>
                        <div>{formatDate(order.collected_at)}</div>
                        <div className="font-medium">Data creare:</div>
                        <div>{formatDate(order.created_at)}</div>
                        <div className="font-medium">Creat de:</div>
                        <div>{order.created_by || '-'}</div>
                        <div className="font-medium">Ultima modificare:</div>
                        <div>{formatDate(order.updated_at)}</div>
                        <div className="font-medium">Modificat de:</div>
                        <div>{order.updated_by || '-'}</div>
                        <div className="font-medium">Data stergere:</div>
                        <div>{formatDate(order.deleted_at)}</div>
                        <div className="font-medium">Sters de:</div>
                        <div>{order.deleted_by || '-'}</div>
                    </div>
                </div>
            </div>
        </Dialog>
    );
}

// Edit Order Dialog (uses OrderForm component)
export function EditOrderDialog({ visible, onVisibleChange, auth, pcs, prefs, order, onSave }: EditOrderDialogProps) {
    console.log('EditOrderDialog order:', order);
    if (!order || order.id == 0) return null;

    const handleSave = (data: OrderData) => {
        onSave(data);
        onVisibleChange(false);
    };

    const handleCancel = () => {
        onVisibleChange(false);
    };

    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 1);
    const currentHour = today.getHours();
    const min_date = currentHour > 16 ? tomorrow : today;
    const max_date = new Date(new Date().setMonth(new Date().getMonth() + 1));
    let edit_data_colectare = min_date;
    if(order && order.data_colectare_string){
        const parts = order.data_colectare_string.split('-');
        const initial_data_colectare = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        if(initial_data_colectare >= min_date && initial_data_colectare <= max_date){
            edit_data_colectare = initial_data_colectare;
        }
    }

    const initialData: Partial<OrderData> = {
        id: order.id,
        expeditor_id: order.expeditor_id,
        data_colectare: edit_data_colectare,
        data_colectare_string: edit_data_colectare.toISOString().split('T')[0],
        interval_colectare_start: currentHour >= 9 && currentHour <= 16 ? currentHour : 9,
        interval_colectare_end: 17,
        expeditor_localitate_id: order.expeditor_localitate_id,
        expeditor_localitate: order.expeditor_localitate,
        expeditor_contact: order.expeditor_contact || null,
        expeditor_telefon: order.expeditor_telefon,
        expeditor_email: order.expeditor_email || null,
        expeditor_adresa: order.expeditor_adresa,
        colete: order.colete ?? null,
        paleti: order.paleti ?? null,
        greutate: order.greutate ?? null,
        volum: order.volum ?? null,
        observatii: order.observatii || null,
        ridica_de_la: order.ridica_de_la || null,
    };

    return (
        <Dialog
            visible={visible}
            onHide={() => onVisibleChange(false)}
            header={`Editare Comanda #${order.id}`}
            modal={true}
            style={{ width: '80vw' }}
            breakpoints={{ '960px': '90vw', '641px': '100vw' }}
        >
            <div className="py-4">
                <OrderForm
                    auth={auth}
                    pcs={pcs}
                    prefs={prefs}
                    mode="edit"
                    initialData={initialData}
                    onSave={handleSave}
                    onCancel={handleCancel}
                />
            </div>
        </Dialog>
    );
}

// Delete Order Confirmation Dialog
export function DeleteOrderDialog({ visible, onVisibleChange, order, onSuccess, onFailed, tabulatorRef }: DeleteOrderDialogProps) {
    const [formLoading, setFormLoading] = useState(false);

    const handleConfirm = async () => {
        if (!order) return;

        setFormLoading(true);
        try {
            const success = await deleteOrder(order.id || 0);

            onVisibleChange(false);
            setFormLoading(false);

            if (success) {
                // Refresh table
                if (tabulatorRef?.current) {
                    tabulatorRef.current.setData();
                }
                onSuccess?.();
            } else {
                onFailed?.('Eroare la stergerea comenzii');
            }
        } catch (error) {
            console.error('Error deleting order:', error);
            onVisibleChange(false);
            setFormLoading(false);
            onFailed?.(error instanceof Error ? error.message : 'Eroare la stergerea comenzii');
        }
    };

    const footerContent = (
        <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => onVisibleChange(false)} disabled={formLoading}>
                Anuleaza
            </Button>
            <Button variant="destructive" onClick={handleConfirm} disabled={formLoading}>
                {formLoading ? 'Se sterge...' : 'Sterge'}
            </Button>
        </div>
    );

    return (
        <Dialog
            visible={visible}
            onHide={() => onVisibleChange(false)}
            header="Confirmare stergere"
            footer={footerContent}
            modal={true}
            style={{ width: '50vw' }}
            breakpoints={{ '960px': '75vw', '641px': '100vw' }}
        >
            <div>
                Sunteti sigur ca doriti sa stergeti comanda <strong>#{order?.id}</strong>?
                <br />
                Aceasta actiune nu poate fi anulata.
            </div>
        </Dialog>
    );
}
