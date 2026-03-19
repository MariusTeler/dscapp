import { Dialog } from '@/components/ui/primereact/dialog';
import AwbForm from '@/pages/awb/awb-form';
import type { SharedData, AwbData } from '@/types';
import AwbInversForm from '@/pages/awb/awb-invers-form';

interface EditAwbDialogProps {
    visible: boolean;
    onVisibleChange: (visible: boolean) => void;
    auth: SharedData['auth'];
    pcs: SharedData['pcs'];
    prefs: SharedData['prefs'];
    awb: AwbData | null;
    onUpdate: (data: AwbData) => void;
}

export function EditAwbDialog({ visible, onVisibleChange, auth, pcs, prefs, awb, onUpdate }: EditAwbDialogProps) {
    const handleSave = (data: AwbData) => {
        onUpdate(data);
        onVisibleChange(false);
    };

    if (!awb || awb.id == 0) return null;

    // Map AWB data to form data format
    const initialData: Partial<AwbData> = {
        id: awb.id,
        awb: awb.awb,
        expeditor_id: awb.expeditor_id,
        expeditor_nume: awb.expeditor_nume,
        expeditor_judet: awb.expeditor_judet || null,
        expeditor_localitate_id: awb.expeditor_localitate_id,
        expeditor_localitate: awb.expeditor_localitate,
        expeditor_contact: awb.expeditor_contact || null,
        expeditor_telefon: awb.expeditor_telefon || null,
        expeditor_email: awb.expeditor_email || null,
        expeditor_adresa: awb.expeditor_adresa || null,

        // Recipient/Destinatar Information
        destinatar_id: awb.destinatar_id,
        destinatar_nume: awb.destinatar_nume,
        destinatar_judet: awb.destinatar_judet || null,
        destinatar_localitate_id: awb.destinatar_localitate_id,
        destinatar_localitate: awb.destinatar_localitate,
        destinatar_contact: awb.destinatar_contact || null,
        destinatar_telefon: awb.destinatar_telefon || null,
        destinatar_email: awb.destinatar_email || null,
        destinatar_adresa: awb.destinatar_adresa || null,

        platitor: awb.platitor,

        // Package content details
        tip_obj: awb.tip_obj,
        greutate: awb.greutate,
        piese: awb.piese,
        asigurare: awb.asigurare || null,
        ramburs: awb.ramburs || null,
        tip_plata: awb.tip_plata,
        volum1: awb.volum1 || null,
        volum2: awb.volum2 || null,
        volum3: awb.volum3 || null,

        //booleans
        ret_nt: awb.ret_nt,
        ret_doc: awb.ret_doc,
        ret_amb: awb.ret_amb,
        ret_colet: awb.ret_colet,
        liv_samb: awb.liv_samb,
        liv_sed: awb.liv_sed,
        sms: awb.sms,
        copen: awb.copen,
        ret_nc: awb.ret_nc || false,

        detalii_doc: awb.detalii_doc || null,
        observatii: awb.observatii || null,
        swapped: awb.swapped,
    };

    return (
        <Dialog 
            visible={visible}
            onHide={() => {if (!visible) return; onVisibleChange(false); }}
            header={`Editare expeditie ${awb.swapped ? 'inversa' : ''} - ${awb.awb}`}
            modal={true}
            maximized={true}
        >
        {awb.swapped ?  
            <AwbInversForm 
                auth={auth}
                pcs={pcs}
                prefs={prefs}
                mode="edit"
                initialData={initialData}
                onUpdate={handleSave}
                onCancel={() => onVisibleChange(false)}
            /> : 
            <AwbForm 
                auth={auth}
                pcs={pcs}
                prefs={prefs}
                mode="edit"
                initialData={initialData}
                onUpdate={handleSave}
                onCancel={() => onVisibleChange(false)}
            />}
        </Dialog>
    );
}
