import { Card, CardContent } from '@/components/ui/primereact/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/primereact/input';
import { InputNumber } from '@/components/ui/primereact/input-number';
import { Calendar } from '@/components/ui/primereact/calendar';
import { Textarea } from '@/components/ui/primereact/textarea';
import { Label } from '@/components/ui/label';
import Autocomplete from '@/components/ui/primereact/autocomplete';
import { Select } from '@/components/ui/primereact/select';
import { type PunctDeLucru, type SharedData, type LocalitateAutocompleteOption, type OrderData, emailRegex, romanianMobileRegex } from '@/types';
import { useForm } from '@inertiajs/react';
import { useState, useCallback, useMemo } from 'react';
import { createOrder } from '@/services/comenzi';

interface OrderFormProps {
    auth: SharedData['auth'];
    pcs: SharedData['pcs'];
    prefs?: SharedData['prefs'];
    initialData?: Partial<OrderData>;
    mode: 'create' | 'edit';
    onSuccess?: () => void;
    onFailed?: () => void;
    onSave?: (data: OrderData) => void;
    onCancel?: () => void;
}

function OrderForm({ auth, pcs, initialData, mode, onSuccess, onFailed, onSave, onCancel }: OrderFormProps) {
    //STATUS_COMANDA = [1=>'Initiala', 2=>'Transmisa', 3=>'Distribuita', 4=>'Acceptata', 5=>'Refuzata', 6=>'Colectata', 7=>'Anulata'];
    const puncteDeLucru: PunctDeLucru[] = useMemo(() => (pcs as PunctDeLucru[]) || [], [pcs]);
    const selectedPunctDeLucru = puncteDeLucru.find(punct => punct.id === (mode === 'edit' ? initialData?.expeditor_id : auth.user.expeditor_id));
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 1);
    const currentHour = today.getHours();
    const min_date = currentHour > 16 ? tomorrow : today;
    const max_date = new Date(new Date().setMonth(new Date().getMonth() + 1));

    // Custom form errors
    const [customErrors, setCustomErrors] = useState<{[key: string]: string}>({});
    // Autocomplete state
    const [localitateSearch, setLocalitateSearch] = useState(
        mode === 'edit' && initialData?.expeditor_localitate
            ? initialData.expeditor_localitate
            : selectedPunctDeLucru?.localitate || ''
    );

    // Form state
    const defaultFormData: OrderData = {
        expeditor_id: selectedPunctDeLucru?.id ?? 0,
        expeditor_nume: selectedPunctDeLucru?.nume ?? '',
        expeditor_pc: '',
        data_colectare: min_date,
        interval_colectare_start: currentHour >= 9 && currentHour <= 16 ? currentHour : 9,
        interval_colectare_end: 17,
        ridica_de_la: null,
        expeditor_localitate_id: selectedPunctDeLucru?.localitate_id ?? 0,
        expeditor_localitate: selectedPunctDeLucru?.localitate ?? '',
        expeditor_contact: selectedPunctDeLucru?.contact ?? null,
        expeditor_telefon: selectedPunctDeLucru?.telefon ?? '',
        expeditor_email: selectedPunctDeLucru?.email ?? null,
        expeditor_adresa: selectedPunctDeLucru?.adresa ?? '',
        colete: null,
        paleti: null,
        greutate: null,
        volum: null,
        observatii: null
    };

    const { data: formData, setData: setFormData, processing } = useForm<OrderData>({
        ...defaultFormData,
        ...initialData,
    });

    const handleExpeditorSelect = useCallback((value: number) => {
        const selectedPunct = puncteDeLucru.find(punct => punct.id === value);
        if (selectedPunct) {
            setFormData(prev => ({
                ...prev,
                expeditor_id: selectedPunct.id,
                expeditor_nume: selectedPunct.nume,
                expeditor_localitate_id: selectedPunct.localitate_id,
                expeditor_localitate: selectedPunct.localitate,
                expeditor_contact: selectedPunct.contact ?? null,
                expeditor_telefon: selectedPunct.telefon ?? '',
                expeditor_email: selectedPunct.email ?? null,
                expeditor_adresa: selectedPunct.adresa ?? ''
            }));
            setLocalitateSearch(selectedPunct.localitate);
        }
    }, [puncteDeLucru, setFormData]);

    const handleLocalitateSelect = useCallback((option: LocalitateAutocompleteOption) => {
        setFormData(prev => ({
            ...prev,
            expeditor_localitate_id: option.value as number,
            expeditor_localitate: option.text
        }));
    }, [setFormData]);

    const handleLocalitateNoResults = useCallback(() => {
        setFormData(prev => ({
            ...prev,
            expeditor_localitate_id: 0,
            expeditor_localitate: localitateSearch
        }));
    }, [localitateSearch, setFormData]);

    // Email validation function
    const validateEmail = (email: string) => {
        setCustomErrors(prev => ({ ...prev, email: '' }));
        if (!email || email.trim() === '') {
            return true;
        }

        if (!emailRegex.test(email)) {
            setCustomErrors(prev => ({ ...prev, email: 'Adresa de email nu este valida' }));
            //console.log('Invalid email:', email);
            return false;
        }
        return true;
    };

    // Phone validation function for Romanian mobile numbers
    const validatePhone = (phone: string) => {
        setCustomErrors(prev => ({ ...prev, telefon: '' }));
        if (!phone || phone.trim() === '') {
            setCustomErrors(prev => ({ ...prev, telefon: 'Telefonul este obligatoriu' }));
            return false;
        }

        // Remove spaces and format for validation
        const cleanedPhone = phone.replace(/\s/g, '');

        if (phone !== '' && !romanianMobileRegex.test(cleanedPhone)) {
            setCustomErrors(prev => ({ ...prev, telefon: 'Numarul de telefon nu este valid' }));
            return false;
        }
        return true;
    };

    const validateForm = useCallback(() => {
        const errors: {[key: string]: string} = {};
        
        if (!formData.expeditor_id || formData.expeditor_id <= 0) errors.expeditor = 'Expeditorul este obligatoriu';
        if (!formData.data_colectare) errors.data_colectare = 'Data colectarii este obligatorie';
        
        // Check if data_colectare is today and interval_colectare_start is in the past
        if (formData.data_colectare) {
            const today = new Date();
            const colectareDate = new Date(formData.data_colectare);
            
            // Set both dates to midnight for comparison
            today.setHours(0, 0, 0, 0);
            colectareDate.setHours(0, 0, 0, 0);

            if(formData.interval_colectare_end <= formData.interval_colectare_start)
                    errors.interval_colectare = 'Interval orar gresit';
            else if(colectareDate.getTime() === today.getTime() && formData.interval_colectare_start < (new Date()).getHours())
                errors.interval_colectare = 'Interval gresit';
        }
        if (!formData.expeditor_localitate_id || formData.expeditor_localitate_id <= 0) errors.expeditor_localitate_id = 'Alege localitatea din lista';
        if (!formData.expeditor_adresa.trim()) errors.expeditor_adresa = 'Adresa este obligatorie';
        //if (!formData.expeditor_contact) errors.expeditor_contact = 'Contactul este obligatoriu';
        if (!formData.expeditor_telefon) errors.expeditor_telefon = 'Telefonul este obligatoriu';
        else if (formData.expeditor_telefon && !validatePhone(formData.expeditor_telefon)) errors.expeditor_telefon = 'Telefonul este invalid';
        if (formData.expeditor_email && !validateEmail(formData.expeditor_email)) errors.expeditor_email = 'Emailul este invalid';
        if ((formData.colete == null || formData.colete <= 0) && (formData.paleti == null || formData.paleti <= 0)) errors.colete_paleti = 'Numarul de colete sau paleti este obligatoriu';
        if (formData.greutate == null || formData.greutate <= 0) errors.greutate = 'Greutatea este obligatorie';

        //console.log('Validation errors:', errors);
        return errors;
    }, [formData]);

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();

        const errors = validateForm();
        if (Object.keys(errors).length > 0) {
            setCustomErrors(errors);
            
            // Focus on first error field
            const errorFieldMap: Record<string, string> = {
                expeditor: 'expeditor',
                data_colectare: 'data_colectare',
                interval_colectare: 'interval_colectare_start',
                expeditor_localitate_id: 'localitate',
                expeditor_adresa: 'adresa',
                //expeditor_contact: 'contact',
                expeditor_telefon: 'telefon',
                expeditor_email: 'email',
                colete_paleti: 'colete',
                greutate: 'greutate'
            };
            
            const firstErrorKey = Object.keys(errors)[0];
            const inputId = errorFieldMap[firstErrorKey];
            
            if (inputId) {
                // Use setTimeout to ensure the error state is rendered first
                setTimeout(() => {
                    const element = document.getElementById(inputId);
                    if (element) {
                        element.focus();
                        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 100);
            }
            
            return;
        }
        // Transform data for server
        const submitData = {
            ...formData,
            data_colectare_string: formData.data_colectare instanceof Date 
                ? formData.data_colectare.toISOString().split('T')[0]
                : formData.data_colectare
        };
        console.log('Submitting order:', submitData);

        if (mode === 'edit' && onSave) {
            onSave(submitData);
        } else {
            const result = await createOrder(submitData);
            if (result.success) {
                // Reset form to default values
                setFormData(defaultFormData);
                setLocalitateSearch(selectedPunctDeLucru?.localitate || '');
                setCustomErrors({});
                
                if (onSuccess) {
                    onSuccess();
                }
            } else {
                if (result.errors) {
                    const formattedErrors: {[key: string]: string} = {};
                    Object.entries(result.errors).forEach(([key, messages]) => {
                        formattedErrors[key] = Array.isArray(messages) ? messages[0] : messages;
                    });
                    console.log('Create order failed:', formattedErrors);
                    setCustomErrors(formattedErrors);
                }
                else {
                    if (onFailed)
                        onFailed();
                }
            }
        }
    };

    return (
        <Card>
            <CardContent>
                <form onSubmit={submit} className="grid gap-2 py-2">
                    {/* Expeditor */}
                    <div className="grid grid-cols-4 items-start gap-4">
                        <Label htmlFor="expeditor" className="text-left pt-2">
                            Punct de lucru*
                        </Label>
                        <div className="col-span-3">
                            <Select
                                id="expeditor"
                                value={formData.expeditor_id}
                                onChange={(e) => handleExpeditorSelect(e.value)}
                                options={puncteDeLucru.map((punct) => ({
                                    label: punct.nume,
                                    value: punct.id
                                }))}
                                placeholder="Selecteaza expeditor"
                                className="w-full"
                                disabled={puncteDeLucru.length === 1}
                            />
                        </div>
                    </div>

                    {/* Data colectarii */}
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="data_colectare" className="text-left">
                            Data colectarii*
                        </Label>
                        <Calendar
                            id="data_colectare"
                            value={formData.data_colectare}
                            onChange={(e) => setFormData(prev => ({ ...prev, data_colectare: e.value || new Date() }))}
                            readOnlyInput
                            showIcon
                            dateFormat="dd.mm.yy"
                            placeholder="Data start"
                            className="w-auto"
                            invalid={!!customErrors.data_colectare}
                            minDate={min_date}
                            maxDate={max_date}
                        />
                        {customErrors.data_colectare && (
                            <p className="text-red-500 text-sm mt-1 col-start-2 col-span-3">{customErrors.data_colectare}</p>
                        )}
                    </div>

                    {/* Interval colectare */}
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label className="text-left">
                            Interval colectare
                        </Label>
                        <div className="col-span-3 flex items-center gap-3">
                            <InputNumber
                                id="interval_colectare_start"
                                min={0}
                                max={16}
                                step={1}
                                value={formData.interval_colectare_start}
                                onChange={(e) => setFormData(prev => ({ ...prev, interval_colectare_start: e.value || 0 }))}
                                className="w-20"
                                showButtons
                                invalid={!!customErrors.interval_colectare}
                                buttonLayout="stacked"
                            />
                            <span>-</span>
                            <InputNumber
                                id="interval_colectare_end"
                                min={0}
                                max={17}
                                step={1}
                                value={formData.interval_colectare_end}
                                onChange={(e) => setFormData(prev => ({ ...prev, interval_colectare_end: e.value || 0 }))}
                                className="w-20"
                                showButtons
                                invalid={!!customErrors.interval_colectare}
                                buttonLayout="stacked"
                            />
                            {customErrors.interval_colectare && (
                                <p className="text-red-500 text-sm mt-1">{customErrors.interval_colectare}</p>
                            )}
                        </div>
                    </div>

                    {/* Ridica de la */}
                    <div className="grid grid-cols-4 items-start gap-4">
                        <Label htmlFor="adresa" className="text-left pt-2">
                            Ridica de la
                        </Label>
                        <div className="col-span-3">
                            <Input
                                id="ridica_de_la"
                                value={formData.ridica_de_la}
                                onChange={(e) => setFormData(prev => ({ ...prev, ridica_de_la: e.target.value }))}
                                placeholder="Nume sau firma de la care se ridica (optional)"
                            />
                        </div>
                    </div>


                    {/* Localitate*/}
                    <div className="grid grid-cols-4 items-start gap-4">
                        <Label htmlFor="localitate" className="text-left pt-2">
                            Localitate*
                        </Label>
                        <div className="col-span-3">
                            <Autocomplete
                                id="localitate"
                                text={localitateSearch}
                                onChange={setLocalitateSearch}
                                onSelect={handleLocalitateSelect}
                                onOpen={handleLocalitateNoResults}
                                onNoResults={handleLocalitateNoResults}
                                searchUrl="/commons/localitati"
                                placeholder="Caută localitate..."
                                minSearchLength={3}
                                invalid={!!customErrors.expeditor_localitate_id}
                            />
                            {customErrors.expeditor_localitate_id && (
                                <p className="text-red-500 text-sm mt-1">{customErrors.expeditor_localitate_id}</p>
                            )}
                        </div>
                    </div>

                    {/* Adresa */}
                    <div className="grid grid-cols-4 items-start gap-4">
                        <Label htmlFor="adresa" className="text-left pt-2">
                            Adresa*
                        </Label>
                        <div className="col-span-3">
                            <Input
                                id="adresa"
                                value={formData.expeditor_adresa}
                                onChange={(e) => setFormData(prev => ({ ...prev, expeditor_adresa: e.target.value }))}
                                invalid={!!customErrors.expeditor_adresa}
                                placeholder="Introduceti adresa"
                            />
                            {customErrors.expeditor_adresa && (
                                <p className="text-red-500 text-sm mt-1">{customErrors.expeditor_adresa}</p>
                            )}
                        </div>
                    </div>

                    {/* Contact */}
                    <div className="grid grid-cols-4 items-start gap-4">
                        <Label htmlFor="contact" className="text-left pt-2">
                            Contact
                        </Label>
                        <div className="col-span-3">
                            <Input
                                id="contact"
                                value={formData.expeditor_contact}
                                onChange={(e) => setFormData(prev => ({ ...prev, expeditor_contact: e.target.value }))}
                                invalid={!!customErrors.expeditor_contact}
                                placeholder="Introduceti persoana de contact"
                            />
                            {customErrors.expeditor_contact && (
                                <p className="text-red-500 text-sm mt-1">{customErrors.expeditor_contact}</p>
                            )}
                        </div>
                    </div>

                    {/* Telefon */}
                    <div className="grid grid-cols-4 items-start gap-4">
                        <Label htmlFor="telefon" className="text-left pt-2">
                            Telefon*
                        </Label>
                        <div className="col-span-3">
                            <Input
                                id="telefon"
                                value={formData.expeditor_telefon}
                                onChange={(e) => setFormData(prev => ({ ...prev, expeditor_telefon: e.target.value }))}
                                invalid={!!customErrors.expeditor_telefon}
                                placeholder="Introduceti numarul de telefon"
                            />
                            {customErrors.expeditor_telefon && (
                                <p className="text-red-500 text-sm mt-1">{customErrors.expeditor_telefon}</p>
                            )}
                        </div>
                    </div>
                    {/* Email */}
                    <div className="grid grid-cols-4 items-start gap-4">
                        <Label htmlFor="email" className="text-left pt-2">
                            Email
                        </Label>
                        <div className="col-span-3">
                            <Input
                                id="email"
                                value={formData.expeditor_email}
                                onChange={(e) => setFormData(prev => ({ ...prev, expeditor_email: e.target.value }))}
                                invalid={!!customErrors.expeditor_email}
                                placeholder="Introduceti emailul"
                            />
                            {customErrors.expeditor_email && (
                                <p className="text-red-500 text-sm mt-1">{customErrors.expeditor_email}</p>
                            )}
                        </div>
                    </div>

                    {/* Colete & Paleti */}
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label className="text-left">Colete*</Label>
                        <div className="col-span-1">
                            <InputNumber
                                id="colete"
                                value={formData.colete}
                                onChange={(e) => setFormData(prev => ({ ...prev, colete: e.value || null }))}
                                min={0}
                                invalid={!!customErrors.colete_paleti}
                            />
                            {customErrors.colete_paleti && (
                                <p className="text-red-500 text-sm mt-1">{customErrors.colete_paleti}</p>
                            )}
                        </div>
                        <Label className="text-right">Paleti</Label>
                        <div className="col-span-1">
                            <InputNumber
                                id="paleti"
                                value={formData.paleti}
                                onChange={(e) => setFormData(prev => ({ ...prev, paleti: e.value || null }))}
                                min={0}
                                className="col-span-1"
                                invalid={!!customErrors.colete_paleti}
                            />
                        </div>
                    </div>

                    {/* Greutate & Volum */}
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label className="text-left">Greutate totala (kg)*</Label>
                        <div className="col-span-1">
                            <InputNumber
                                id="greutate"
                                value={formData.greutate}
                                onChange={(e) => setFormData(prev => ({ ...prev, greutate: e.value || null }))}
                                min={0}
                                invalid={!!customErrors.greutate}
                                className="col-span-1"
                            />
                            {customErrors.greutate && (
                                <p className="text-red-500 text-sm mt-1">{customErrors.greutate}</p>
                            )}
                        </div>
                        <Label className="text-right">Volum (m³)</Label>
                        <div className="col-span-1">
                            <InputNumber
                                id="volum"
                                value={formData.volum}
                                onChange={(e) => setFormData(prev => ({ ...prev, volum: e.value || null }))}
                                min={0}
                                className="col-span-1"
                            />
                        </div>
                    </div>

                    {/* Observatii */}
                    <div className="grid grid-cols-4 items-start gap-4">
                        <Label htmlFor="observatii" className="text-left pt-2">
                            Observatii
                        </Label>
                        <Textarea
                            id="observatii"
                            value={formData.observatii ?? ''}
                            onChange={(e) => setFormData(prev => ({ ...prev, observatii: e.target.value }))}
                            className="col-span-3"
                            placeholder="observatii..."
                            rows={4}
                        />
                    </div>
                    {/* Action Buttons */}
                    <div className="flex justify-between pt-2">
                        <div className="flex gap-2">
                            {mode === 'edit' && onCancel && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={onCancel}
                                >
                                    Anuleaza
                                </Button>
                            )}
                        </div>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Processing...' : 'Salveaza'}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
            
    );
}
export default OrderForm;