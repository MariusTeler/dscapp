import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/primereact/checkbox';
import { Input } from '@/components/ui/primereact/input';
import { Label } from '@/components/ui/label';
import { RadioButton } from '@/components/ui/primereact/radio-button';
import { Select } from '@/components/ui/primereact/select';
import { Textarea } from '@/components/ui/primereact/textarea';
import Autocomplete from '@/components/ui/primereact/autocomplete';
import InputNumber from '@/components/ui/primereact/input-number';
import { autocomplete as recipientAutocomplete } from '@/routes/recipients';
import { autocomplete as localitateAutocomplete } from '@/routes/commons/localitati';
import { type PunctDeLucru, type UserPrefs, type SharedData, type LocalitateAutocompleteOption, type DestinatarAutocompleteOption, type AwbData, type CostEstimateResponse, emailRegex, romanianMobileRegex } from '@/types';
import { useForm } from '@inertiajs/react';
import { useState, useCallback, useMemo, useEffect, useRef } from 'react';
import { OverlayPanel } from 'primereact/overlaypanel';
import { estimateCost } from '@/services/awbs';
import { formatCurrency } from '@/lib/formatters';

interface AwbInversFormProps {
    auth: SharedData['auth'];
    pcs: SharedData['pcs'];
    prefs: SharedData['prefs'];
    initialData?: Partial<AwbData>;
    mode?: 'create' | 'edit';
    onCreate?: (data: AwbData) => void;
    onUpdate?: (data: AwbData) => void;
    onCancel?: () => void;
    onSearchError?: (error: string) => void;
}

// AWB Invers Creation/Edit Form Component (Expeditor and Destinatar fields are swapped)
function AwbInversForm({ auth, pcs, prefs, initialData, mode = 'create', onCreate, onUpdate, onCancel, onSearchError }: AwbInversFormProps) {
    // Get puncte de lucru from auth.user.pcs
    const puncteDeLucru: PunctDeLucru[] = useMemo(() => (pcs as PunctDeLucru[]) || [], [pcs]);
    const selectedPunctDeLucru = puncteDeLucru.find(punct => punct.id === (mode === 'edit' ? initialData?.destinatar_id : auth.user.expeditor_id));
    const cc: boolean = (prefs as UserPrefs)?.cc || false;
    const def_sms: boolean = (prefs as UserPrefs)?.def_sms || false;
    const preturi: boolean = (prefs as UserPrefs)?.preturi || false;
    const def_obsv: string | null = (prefs as UserPrefs)?.def_obsv || null;
    const def_retur_nc: boolean = (prefs as UserPrefs)?.def_retur_nc || false;

    const [emailError, setEmailError] = useState<string | null>(null);
    const [telefonError, setTelefonError] = useState<string | null>(null);
    const [expeditorError, setExpeditorError] = useState<string | null>(null);
    const [localitateError, setLocalitateError] = useState<string | null>(null);
    const [adresaError, setAdresaError] = useState<string | null>(null);
    const [pieseError, setPieseError] = useState<string | null>(null);
    const [greutateError, setGreutateError] = useState<string | null>(null);
    const [volumError, setVolumError] = useState<string | null>(null);
    const [isEstimating, setIsEstimating] = useState<boolean>(false);
    const [costEstimate, setCostEstimate] = useState<CostEstimateResponse | null>(null);
    const [estimateMessage, setEstimateMessage] = useState<string | null>(null);
    const [isEstimateOverlayVisible, setIsEstimateOverlayVisible] = useState<boolean>(false);
    const estimateOverlayRef = useRef<OverlayPanel>(null);

    const positionEstimateOverlay = useCallback((target: HTMLElement) => {
        const overlayElement = estimateOverlayRef.current?.getElement();

        if (!overlayElement) {
            return;
        }

        const targetRect = target.getBoundingClientRect();
        const overlayWidth = overlayElement.offsetWidth;
        const overlayHeight = overlayElement.offsetHeight;
        const viewportLeft = window.scrollX;
        const viewportTop = window.scrollY;
        const viewportRight = viewportLeft + window.innerWidth;
        const viewportBottom = viewportTop + window.innerHeight;
        const desiredTop = targetRect.top + viewportTop - overlayHeight - 8;
        const desiredLeft = targetRect.left + viewportLeft;

        const minLeft = viewportLeft + 8;
        const maxLeft = viewportRight - overlayWidth - 8;
        const left = Math.max(minLeft, Math.min(desiredLeft, maxLeft));

        const minTop = viewportTop + 8;
        const maxTop = viewportBottom - overlayHeight - 8;
        const top = Math.max(minTop, Math.min(desiredTop, maxTop));

        overlayElement.style.left = `${left}px`;
        overlayElement.style.top = `${top}px`;
    }, []);
    

    // Autocomplete expeditor state (swapped from destinatar)
    const [expeditorSearch, setExpeditorSearch] = useState(
        mode === 'edit' && initialData && initialData?.expeditor_nume
            ? initialData.expeditor_nume
            : null
    );

    // Autocomplete localitati state
    const [localitateSearch, setLocalitateSearch] = useState(
        mode === 'edit' && initialData && initialData?.expeditor_localitate
            ? initialData.expeditor_localitate
            : null
    );

    const defaultFormData: AwbData = {
        // Sender/Expeditor Information (now comes from destinatar autocomplete)
        expeditor_id: null,
        expeditor_client_id: null,
        expeditor_nume: null,
        expeditor_judet: null,
        expeditor_localitate_id: null,
        expeditor_localitate: null,
        expeditor_contact: null,
        expeditor_telefon: null,
        expeditor_email: null,
        expeditor_adresa: null,

        // Recipient/Destinatar Information (now comes from punct de lucru)
        destinatar_id: selectedPunctDeLucru?.id || null,
        destinatar_nume: selectedPunctDeLucru?.nume || null,
        destinatar_judet: selectedPunctDeLucru?.judet || null,
        destinatar_localitate_id: selectedPunctDeLucru?.localitate_id || null,
        destinatar_localitate: selectedPunctDeLucru?.localitate || null,
        destinatar_contact: selectedPunctDeLucru?.contact || null,
        destinatar_telefon: selectedPunctDeLucru?.telefon || null,
        destinatar_email: selectedPunctDeLucru?.email || null,
        destinatar_adresa: selectedPunctDeLucru?.adresa || null,

        platitor: 2, // 1 - Sender, 2 - Recipient

        // Package content details
        tip_obj: 2, // 1 - Plic, 2 - Colet, 3 - Palet
        greutate: null,
        piese: null,
        asigurare: null,
        ramburs: null,
        tip_plata: cc ? 3 : 0, // 0 - Cash, 1 - BO, 2 - CEC, 3 - Cont

        volum1: null,
        volum2: null,
        volum3: null,

        //booleans
        ret_nt: false,
        ret_doc: false,
        ret_amb: false,
        ret_colet: false,
        liv_samb: false,
        liv_sed: false,
        sms: def_sms,
        copen: false,
        ret_nc: false,

        detalii_doc: null,
        observatii: def_obsv || null,
        swapped: true,
    };

    const { data: formData, setData: setFormData, processing } = useForm<AwbData>({
        ...defaultFormData,
        ...initialData,
    });

    // Email validation function
    const validateEmail = (email: string | null) => {
        if (email === null || email === '') {
            setEmailError(null);
            return true;
        }

        if (!emailRegex.test(email)) {
            setEmailError('Adresa de email nu este valida');
            //console.log('Invalid email:', email);
            return false;
        }

        setEmailError(null);
        return true;
    };

    // Telefon validation function for Romanian mobile numbers
    const validateTelefon = (telefon: string | null, sms: boolean) => {
        if (sms && (telefon == null || telefon === '' || telefon.trim() === '')) {
            setTelefonError('Telefonul este obligatoriu');
            return false;
        }

        if (telefon != null && telefon.trim() !== '' && !romanianMobileRegex.test(telefon)) {
            setTelefonError('Numarul de telefon invalid');
            return false;
        }

        setTelefonError(null);
        return true;
    };

    const validateVolum = useCallback((volum1: number | null | undefined, volum2: number | null | undefined, volum3: number | null | undefined ) => {
        //Toate dimensiunile sunt obligatorii
        setVolumError(null);
        if((volum1 ?? 0) === 0 && (volum2 ?? 0) === 0 && (volum3 ?? 0) === 0) {
            return true; // Volum not provided, no error
        }
        if((volum1 ?? 0) > 0 && (volum2 ?? 0) > 0 && (volum3 ?? 0) > 0) {
            return true; // All dimensions provided, no error
        }

        if ((volum1 ?? 0) > 0 || (volum2 ?? 0) > 0 || (volum3 ?? 0) > 0) {
            setVolumError('Toate dimensiunile sunt obligatorii');
            return false;
        }
        return true;
    }, []);
    // Form validation function

    const validateForm = useCallback((tip: number) => {
        const isEmailValid = tip == 1 ? true : validateEmail(formData.expeditor_email);
        const isTelefonValid = tip == 1 ? true : validateTelefon(formData.expeditor_telefon, formData.sms);

        // Validate expeditor (swapped from destinatar)
        let isExpeditorValid = true;
        if (tip == 0 && (formData.expeditor_nume == null || formData.expeditor_nume.trim() === '')) {
            setExpeditorError('Expeditorul este obligatoriu');
            isExpeditorValid = false;
        } else {
            setExpeditorError(null);
        }

        // Validate expeditor_localitate_id
        let isLocalitateValid = true;
        if ((formData.expeditor_localitate_id ?? 0) <= 0) {
            setLocalitateError('Alege localitatea din lista');
            isLocalitateValid = false;
        } else {
            setLocalitateError(null);
        }

        // Validate expeditor_adresa
        let isAdresaValid = true;
        if (tip == 0 && (formData.expeditor_adresa == null || formData.expeditor_adresa.trim() === '')) {
            setAdresaError('Adresa este obligatorie');
            isAdresaValid = false;
        } else {
            setAdresaError(null);
        }

        // Package validation
        const greutate = formData.greutate || null;
        const piese = formData.piese || null;

        let isPieseValid = true;
        let isGreutateValid = true;
        const isVolumValid = validateVolum(formData.volum1, formData.volum2, formData.volum3);

        // Colet validation (tip_obj = 2)
        if (formData.tip_obj === 2) {
            if (tip == 0 && (piese ?? 0) <= 0) {
                setPieseError('Nr. piese obligatoriu');
                isPieseValid = false;
            } else {
                setPieseError(null);
            }
            if ((greutate ?? 0) <= 0) {
                setGreutateError('Greutate obligatorie');
                isGreutateValid = false;
            } else {
                setGreutateError(null);
            }
        } else {
            setPieseError(null);
            setGreutateError(null);
        }

        // Palet validation (tip_obj = 3)
        if (formData.tip_obj === 3) {
            if ((greutate ?? 0) < 10) {
                setGreutateError('Greutate obligatorie');
                isGreutateValid = false;
            } else {
                setGreutateError(null);
            }
        }
        const isValid = isEmailValid && isTelefonValid && isExpeditorValid && isLocalitateValid && isAdresaValid && isPieseValid && isGreutateValid && isVolumValid;
        
        // Focus on first error field if validation fails
        if (!isValid) {
            const errorFieldMap: Record<string, string> = {
                email: 'expeditor_email',
                telefon: 'expeditor_telefon',
                expeditor: 'expeditor',
                localitate: 'expeditor_localitate',
                piese: 'piese',
                greutate: 'greutate',
                volum: 'volum1'
            };
            
            // Determine which error occurred first (in order of priority)
            let firstErrorField: string | null = null;
            if (!isExpeditorValid) firstErrorField = 'expeditor';
            else if (!isLocalitateValid) firstErrorField = 'localitate';
            else if (!isAdresaValid) firstErrorField = 'adresa';
            else if (!isTelefonValid) firstErrorField = 'telefon';
            else if (!isEmailValid) firstErrorField = 'email';
            else if (!isPieseValid) firstErrorField = 'piese';
            else if (!isGreutateValid) firstErrorField = 'greutate';
            else if (!isVolumValid) firstErrorField = 'volum';
            
            if (firstErrorField && errorFieldMap[firstErrorField]) {
                const inputId = errorFieldMap[firstErrorField];
                setTimeout(() => {
                    const element = document.getElementById(inputId);
                    if (element) {
                        element.focus();
                        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 100);
            }
        }
        
        return isValid;
    }, [formData, validateVolum]);

    // Sync destinatarSearch, localitateSearch with form data whenever they change
    useEffect(() => {
        setFormData('expeditor_nume', expeditorSearch);
        setFormData('expeditor_localitate', localitateSearch);
    }, [expeditorSearch, localitateSearch, setFormData]);

    // Handle localitate selection (for expeditor, swapped from destinatar)
    const handleLocalitateSelect = useCallback((option: LocalitateAutocompleteOption) => {
        setFormData('expeditor_localitate_id', option.value as number);
        setFormData('expeditor_localitate', option.text || null);
        setFormData('expeditor_judet', option.judet || null);
        setLocalitateError(null);
    }, [setFormData]);

    const handleLocalitateNoResults = useCallback(() => {
        setFormData('expeditor_localitate_id', null);
        setFormData('expeditor_judet', null);
    }, [setFormData]);

    // Handle expeditor selection (swapped from destinatar)
    const handleExpeditorSelect = useCallback((option: DestinatarAutocompleteOption) => {
        setFormData('expeditor_id', option.value as number ?? null);
        setFormData('expeditor_client_id', option.client_destinatari_id || null);
        setFormData('expeditor_nume', option.text || null);
        setFormData('expeditor_judet', option.judet || null);
        setFormData('expeditor_adresa', option.adresa || null);
        setFormData('expeditor_localitate_id', option.localitate_id || null);
        setFormData('expeditor_localitate', option.localitate || null);
        setFormData('expeditor_contact', option.contact || null);
        setFormData('expeditor_telefon', option.telefon || null);
        setFormData('expeditor_email', option.email || null);
        setLocalitateSearch(option.localitate || null);
        setExpeditorError(null);
    }, [setFormData]);

    const handleExpeditorNoResults = useCallback(() => {
        setFormData('expeditor_id', null);
        setFormData('expeditor_client_id', null);
        setFormData('expeditor_contact', null);
        setFormData('expeditor_telefon', null);
        setFormData('expeditor_email', null);
        setFormData('expeditor_adresa', null);
        setExpeditorError(null);
    }, [setFormData]);

    // Handle destinatar selection (swapped from expeditor - now selects punct de lucru)
    const handleDestinatarSelect = useCallback((value: number) => {
        const selectedPunct = puncteDeLucru.find(punct => punct.id === value);
        if (selectedPunct) {
            setFormData('destinatar_id', selectedPunct.id || null);
            setFormData('destinatar_nume', selectedPunct.nume || null);
            setFormData('destinatar_judet', selectedPunct.judet || null);
            setFormData('destinatar_localitate_id', selectedPunct.localitate_id || null);
            setFormData('destinatar_localitate', selectedPunct.localitate || null);
            setFormData('destinatar_contact', selectedPunct.contact || null);
            setFormData('destinatar_telefon', selectedPunct.telefon || null);
            setFormData('destinatar_adresa', selectedPunct.adresa || null);
        }
    }, [puncteDeLucru, setFormData]);

    // Handle tip_obj change
    const handleTipObjChange = useCallback((value: 1 | 2 | 3) => {
        setFormData('tip_obj', value);

        setPieseError(null);
        setGreutateError(null);
        setVolumError(null);

        if (value === 1) { // Plic
            setFormData('piese', 1);
            setFormData('greutate', 0.5);
            setFormData('volum1', null);
            setFormData('volum2', null);
            setFormData('volum3', null);
        } else if (value === 2) { // Colet
            setFormData('piese', null);
            setFormData('greutate', null);
            setFormData('volum1', null);
            setFormData('volum2', null);
            setFormData('volum3', null);
        } else if (value === 3) { // Palet
            setFormData('piese', 1);
            setFormData('greutate', null);
            setFormData('volum1', null);
            setFormData('volum2', null);
            setFormData('volum3', null);
        }
    }, [setFormData]);

    // Handle cost estimation
    const handleEstimateCost = useCallback(async (event: React.MouseEvent<HTMLButtonElement>) => {
        const buttonElement = event.currentTarget;

        if (estimateOverlayRef.current?.isVisible()) {
            estimateOverlayRef.current.hide();
            setIsEstimateOverlayVisible(false);
            return;
        }
        // Validate required fields first
        setIsEstimating(true);
        if (!validateForm(1)) {
            setIsEstimating(false);
            return; // Don't submit if validation fails
        }
        console.log('Estimating cost with form data:', formData);

        const result = await estimateCost(formData);

        if (result.success) {
            setCostEstimate(result);
            setEstimateMessage(null);
        } else {
            setCostEstimate(null);
            setEstimateMessage(result.message || 'Eroare la estimarea costului');
        }

        estimateOverlayRef.current?.show(event, buttonElement);
        setIsEstimateOverlayVisible(true);
        requestAnimationFrame(() => {
            positionEstimateOverlay(buttonElement);
        });
        setIsEstimating(false);
    }, [formData, positionEstimateOverlay, validateForm]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!validateForm(0)) {
            return;
        }

        if (mode === 'edit' && onUpdate) {
            onUpdate(formData);
        } else if (mode === 'create' && onCreate) {
            onCreate(formData);
        }
    };

    return (
                <form onSubmit={submit} className="space-y-1">
                    {/* Two Column Layout - Expeditor and Destinatar SWAPPED */}
                    <div className="grid gap-1 lg:grid-cols-2">

                        {/* Left Column - Expeditor (now from destinatari autocomplete) */}
                        <Card className='py-0'>
                            <CardHeader className="bg-slate-500 text-white">
                                <CardTitle className="leading-8 font-medium text-white">Expeditor</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6 pt-6 py-0">

                                {/* Expeditor Selection (from destinatari) */}
                                <div className="space-y-2">

                                    <div className="grid gap-3 md:grid-cols-2">
                                            <div className="space-y-2">
                                            <Label htmlFor="expeditor_nume">Nume *</Label>
                                            <Autocomplete
                                                id="expeditor_nume"
                                                text={expeditorSearch || ''}
                                                onChange={setExpeditorSearch}
                                                onSelect={handleExpeditorSelect}
                                                onOpen={handleExpeditorNoResults}
                                                onNoResults={handleExpeditorNoResults}
                                                onError={onSearchError}
                                                placeholder="Search expeditor..."
                                                searchUrl={recipientAutocomplete.url()}
                                                minSearchLength={3}
                                                invalid={!!expeditorError}
                                            />
                                            {expeditorError && (
                                                <p className="text-sm text-red-600 flex items-center gap-1">
                                                    <span className="text-xs">⚠️</span>
                                                    {expeditorError}
                                                </p>
                                            )}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="expeditor_contact">Persoana de contact</Label>
                                            <Input
                                                id="expeditor_contact"
                                                value={formData.expeditor_contact || ''}
                                                onChange={(e) => setFormData('expeditor_contact', e.target.value)}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="expeditor_telefon">Telefon *</Label>
                                            <Input
                                                id="expeditor_telefon"
                                                type="tel"
                                                value={formData.expeditor_telefon || ''}
                                                onChange={(e) => setFormData('expeditor_telefon', e.target.value.trim())}
                                                invalid={formData.sms && !!telefonError}
                                                placeholder="0731234567 or +40731234567"
                                            />
                                            {telefonError && (
                                                <p className="text-sm text-red-600 flex items-center gap-1">
                                                    <span className="text-xs">⚠️</span>
                                                    {telefonError}
                                                </p>
                                            )}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="expeditor_email">Email</Label>
                                            <Input
                                                id="expeditor_email"
                                                keyfilter="email"
                                                value={formData.expeditor_email || ''}
                                                onChange={(e) => setFormData('expeditor_email', e.target.value.trim())}
                                                invalid={!!emailError}
                                                placeholder="example@domain.com"
                                            />
                                            {emailError && (
                                                <p className="text-sm text-red-600 flex items-center gap-1">
                                                    <span className="text-xs">⚠️</span>
                                                    {emailError}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Expeditor Address Details */}
                                    <div className="space-y-4 pb-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="expeditor_localitate">Localitate *</Label>
                                            <Autocomplete
                                                id="expeditor_localitate"
                                                text={localitateSearch || ''}
                                                onChange={setLocalitateSearch}
                                                onSelect={handleLocalitateSelect}
                                                onOpen={handleLocalitateNoResults}
                                                onNoResults={handleLocalitateNoResults}
                                                onError={onSearchError}
                                                searchUrl={localitateAutocomplete.url()}
                                                placeholder="Caută localitate..."
                                                minSearchLength={3}
                                                invalid={!!localitateError}
                                            />
                                            {localitateError && (
                                                <p className="text-sm text-red-600 flex items-center gap-1">
                                                    <span className="text-xs">⚠️</span>
                                                    {localitateError}
                                                </p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="expeditor_adresa">Adresa *</Label>
                                            <Input
                                                id="expeditor_adresa"
                                                value={formData.expeditor_adresa || ''}
                                                onChange={(e) => setFormData('expeditor_adresa', e.target.value)}
                                            invalid={!!adresaError}
                                            />
                                            {adresaError && (
                                                <p className="text-sm text-red-600 flex items-center gap-1">
                                                    <span className="text-xs">⚠️</span>
                                                    {adresaError}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Right Column - Destinatar (now from punct de lucru) */}
                        <Card className='py-0'>
                            <CardHeader className="bg-slate-500 text-white">
                                <CardTitle className="leading-8 font-medium text-white">Destinatar</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6 pt-6 py-0">

                            {/* Destinatar Details (from punct de lucru) */}
                            <div className="space-y-4">

                                <div className="space-y-2">
                                    <Label htmlFor="destinatar">Livreaza expedierea la:</Label>
                                    <Select
                                        id="destinatar"
                                        value={formData.destinatar_id}
                                        onChange={(e) => handleDestinatarSelect(e.value)}
                                        options={puncteDeLucru.map((punct) => ({
                                            label: punct.nume,
                                            value: punct.id
                                        }))}
                                        placeholder="Selecteaza destinatar"
                                        className="w-full"
                                        disabled={puncteDeLucru.length === 1}
                                    />
                                </div>

                                <div className="grid gap-3 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="destinatar_contact">Persoana de contact</Label>
                                        <Input
                                            id="destinatar_contact"
                                            value={formData.destinatar_contact}
                                            onChange={(e) => setFormData('destinatar_contact', e.target.value)}

                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="destinatar_telefon">Telefon</Label>
                                        <Input
                                            id="destinatar_telefon"
                                            value={formData.destinatar_telefon}
                                            onChange={(e) => setFormData('destinatar_telefon', e.target.value)}

                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Destinatar Address */}
                            <div className="space-y-4 pb-4">
                                <h4 className="font-medium text-slate-700">Detalii adresa</h4>
                                <div className="bg-slate-100 p-3 rounded border">
                                    <div className="text-sm text-gray-600">{formData.destinatar_judet && formData.destinatar_judet + ', '}{formData.destinatar_localitate && formData.destinatar_localitate + ', '}{formData.destinatar_adresa && formData.destinatar_adresa}</div>
                                </div>
                            </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Shipment Details (below both columns) */}
                    <Card className='py-0'>
                            <CardHeader className="bg-slate-500 text-white">
                                <CardTitle className="leading-8 font-medium text-white">Detalii expediere</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6 pt-6 py-0">

                        {/* Content Type */}
                        <div className="space-y-4">

                            <div className="grid gap-3 md:grid-cols-5">
                                <div className="space-y-2 flex flex-col gap-3 col-span-1 justify-center">
                                    <label className="flex items-center gap-3 cursor-pointer">
                                        <RadioButton
                                            inputId="tip_obj_plic"
                                            name="tip_obj"
                                            value={1}
                                            checked={formData.tip_obj === 1}
                                            onChange={(e) => handleTipObjChange(e.value)}
                                        />
                                        <span>Plic</span>
                                    </label>
                                    <label className="flex items-center gap-3 cursor-pointer">
                                        <RadioButton
                                            inputId="tip_obj_colet"
                                            name="tip_obj"
                                            value={2}
                                            checked={formData.tip_obj === 2}
                                            onChange={(e) => handleTipObjChange(e.value)}
                                        />
                                        <span>Colet</span>
                                    </label>
                                    <label className="flex items-center gap-3 cursor-pointer">
                                        <RadioButton
                                            inputId="tip_obj_palet"
                                            name="tip_obj"
                                            value={3}
                                            checked={formData.tip_obj === 3}
                                            onChange={(e) => handleTipObjChange(e.value)}
                                        />
                                        <span>Palet</span>
                                    </label>
                                </div>
                                <div className="space-y-2 flex flex-col gap-3 col-span-2">
                                    {/* Package Details column */}
                                    <div className="space-y-2">
                                        <Label htmlFor="piese">Nr. piese</Label>
                                        <InputNumber
                                            inputId="piese"
                                            value={formData.piese}
                                            onChange={((e) => setFormData('piese', e.value || null))}
                                            placeholder="0"
                                            min={0}
                                            mode="decimal"
                                            invalid={!!pieseError}
                                            disabled={formData.tip_obj === 1 || formData.tip_obj === 3}
                                        />
                                        {pieseError && (
                                            <p className="text-sm text-red-600 flex items-center gap-1">
                                                <span className="text-xs">⚠️</span>
                                                {pieseError}
                                            </p>
                                        )}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="greutate">Greutate</Label>
                                        <InputNumber
                                            inputId="greutate"
                                            value={formData.greutate}
                                            onChange={((e) => setFormData('greutate', e.value || null))}
                                            min={0}
                                            mode="decimal"
                                            minFractionDigits={0}
                                            maxFractionDigits={1}
                                            step={0.1}
                                            useGrouping={false}
                                            className="w-full"
                                            inputClassName="w-full"
                                            placeholder="kg"
                                            invalid={!!greutateError}
                                            disabled={formData.tip_obj === 1}
                                        />
                                        {greutateError && (
                                            <p className="text-sm text-red-600 flex items-center gap-1">
                                                <span className="text-xs">⚠️</span>
                                                {greutateError}
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <div className="flex flex-row gap-3">
                                            <div className="space-y-2">
                                                <Label htmlFor="volum1">Lungime</Label>
                                                <InputNumber
                                                    inputId="volum1"
                                                    value={formData.volum1}
                                                    onChange={(e) => setFormData('volum1', e.value || null)}
                                                    min={0}
                                                    mode="decimal"
                                                    useGrouping={false}
                                                    className="w-full"
                                                    inputClassName="w-full"
                                                    placeholder="cm"
                                                    invalid={!!volumError}
                                                    disabled={formData.tip_obj === 1}
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="volum2">Latime</Label>
                                                <InputNumber
                                                    inputId="volum2"
                                                    value={formData.volum2}
                                                    onChange={(e) => setFormData('volum2', e.value || null)}
                                                    min={0}
                                                    mode="decimal"
                                                    useGrouping={false}
                                                    className="w-full"
                                                    inputClassName="w-full"
                                                    placeholder="cm"
                                                    invalid={!!volumError}
                                                    disabled={formData.tip_obj === 1}
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="volum3">Inaltime</Label>
                                                <InputNumber
                                                    inputId="volum3"
                                                    value={formData.volum3}
                                                    onChange={(e) => setFormData('volum3', e.value || null)}
                                                    min={0}
                                                    mode="decimal"
                                                    useGrouping={false}
                                                    className="w-full"
                                                    inputClassName="w-full"
                                                    placeholder="cm"
                                                    invalid={!!volumError}
                                                    disabled={formData.tip_obj === 1}
                                                />
                                            </div>
                                        </div>
                                        {volumError && (
                                            <p className="text-sm text-red-600 flex items-center gap-1 mt-1">
                                                <span className="text-xs">⚠️</span>
                                                {volumError}
                                            </p>
                                        )}
                                    </div>
                                </div>
                                {/* Value and Content */}
                                <div className="space-y-2 flex flex-col gap-3 col-span-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="asigurare">Valoarea declarata</Label>
                                        <InputNumber
                                            inputId="asigurare"
                                            value={formData.asigurare}
                                            onChange={(e) => setFormData('asigurare', e.value || 0.00)}
                                            min={0}
                                            mode="decimal"
                                            minFractionDigits={0}
                                            maxFractionDigits={2}
                                            step={1}
                                            useGrouping={false}
                                            className="w-full"
                                            inputClassName="w-full"
                                        />
                                    </div>
                                    <div className="flex flex-row gap-3">
                                        <div className="space-y-2 basis-2/3">
                                            <Label htmlFor="ramburs">Ramburs</Label>
                                            <InputNumber
                                                inputId="ramburs"
                                                value={formData.ramburs}
                                                onChange={(e) => setFormData('ramburs', e.value || 0.00)}
                                                min={0}
                                                mode="decimal"
                                                minFractionDigits={0}
                                                maxFractionDigits={2}
                                                step={1}
                                                useGrouping={false}
                                                className="w-full"
                                                inputClassName="w-full"
                                            />
                                        </div>
                                        <div className="space-y-2 basis-1/3">
                                            <Label htmlFor="tip_plata">Tip plata</Label>
                                            <Select
                                                value={formData.tip_plata}
                                                onChange={(e) => setFormData('tip_plata', e.value)}
                                                options={cc ? [
                                                    { label: 'cont', value: 3 },
                                                    { label: 'bo', value: 1 },
                                                    { label: 'cec', value: 2 }
                                                ] : [
                                                    { label: 'cash', value: 0 },
                                                    { label: 'bo', value: 1 },
                                                    { label: 'cec', value: 2 }
                                                ]}
                                                placeholder="..."
                                                className="w-full"
                                                id="tip_plata"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {/* Additional Options (Checkboxes)*/}
                        <div className="flex gap-6 flex-wrap">
                            {!def_retur_nc && (
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="ret_nt"
                                    checked={formData.ret_nt}
                                    onChange={(e) => setFormData('ret_nt', e.checked as boolean)}
                                />
                                <Label htmlFor="ret_nt" className="text-sm">Retur NT</Label>
                            </div>
                            )}
                            {def_retur_nc && (
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="ret_nc"
                                        checked={formData.ret_nc || false}
                                        disabled={true} // Always disabled
                                    />
                                    <Label htmlFor="ret_nc" className="text-sm">Retur Nota</Label>
                                </div>
                            )}
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="ret_doc"
                                    checked={formData.ret_doc}
                                    onChange={(e) => setFormData('ret_doc', e.checked as boolean)}
                                />
                                <Label htmlFor="ret_doc" className="text-sm">Retur Doc.</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="ret_amb"
                                    checked={formData.ret_amb}
                                    onChange={(e) => setFormData('ret_amb', e.checked as boolean)}
                                />
                                <Label htmlFor="ret_amb" className="text-sm">Retur ambalaj</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="ret_colet"
                                    checked={formData.ret_colet}
                                    onChange={(e) => setFormData('ret_colet', e.checked as boolean)}
                                />
                                <Label htmlFor="ret_colet" className="text-sm">Retur colet</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="sms"
                                    checked={formData.sms}
                                    onChange={(e) => {
                                        const checked = e.checked as boolean;
                                        setFormData('sms', checked);
                                        validateTelefon(formData.expeditor_telefon, checked);
                                    }}
                                />
                                <Label htmlFor="sms" className="text-sm">SMS livrare</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="copen"
                                    checked={formData.copen}
                                    onChange={(e) => setFormData('copen', e.checked as boolean)}
                                />
                                <Label htmlFor="copen" className="text-sm">Deschidere colet</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="liv_samb"
                                    checked={formData.liv_samb}
                                    onChange={(e) => setFormData('liv_samb', e.checked as boolean)}
                                />
                                <Label htmlFor="liv_samb" className="text-sm">Livrare sambata</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="liv_sed"
                                    checked={formData.liv_sed}
                                    onChange={(e) => setFormData('liv_sed', e.checked as boolean)}
                                />
                                <Label htmlFor="liv_sed" className="text-sm">Livrare sediu</Label>
                            </div>
                        </div>

                        {/* Payment Details */}
                        <div className="space-y-4">
                            <p className="text-sm text-gray-600">Cine suporta cheltuielile de expediere?</p>

                            <div className="flex gap-3">
                                <Button
                                    type="button"
                                    variant={formData.platitor === 1 ? 'default' : 'outline'}
                                    onClick={() => setFormData('platitor', 1)}
                                    className="flex-1"
                                >
                                    Expeditor
                                </Button>
                                <Button
                                    type="button"
                                    variant={formData.platitor === 2 ? 'default' : 'outline'}
                                    onClick={() => setFormData('platitor', 2)}
                                    className="flex-1"
                                >
                                    Destinatar
                                </Button>
                            </div>
                        </div>

                        <div className="flex flex-row gap-3 pb-4">
                            {/* Observations */}
                            <div className="space-y-2 basis-1/2">
                                <Label htmlFor="observatii">Observatii</Label>
                                <Textarea
                                    id="observatii"
                                    rows={3}
                                    value={formData.observatii ?? ''}
                                    onChange={(e) => setFormData('observatii', e.target.value)}
                                    placeholder="Observatii ..."
                                />
                            </div>
                            {/* Detalii documente */}
                            <div className="space-y-2 basis-1/2">
                                <Label htmlFor="detalii_doc">Detalii documente</Label>
                                <Textarea
                                    id="detalii_doc"
                                    rows={3}
                                    value={formData.detalii_doc ?? ''}
                                    onChange={(e) => setFormData('detalii_doc', e.target.value)}
                                    placeholder="Detalii documente ..."
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

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
                        <div className="flex items-center gap-2">
                            {preturi && (
                                <>
                                    <Button
                                        id="estimate_cost"
                                        type="button"
                                        variant="outline"
                                        onClick={handleEstimateCost}
                                        disabled={isEstimating}
                                        className="min-w-36 justify-center"
                                    >
                                        {isEstimating ? 'Calculating...' : (costEstimate?.success && isEstimateOverlayVisible ? formatCurrency(costEstimate?.formattedCost) : 'Estimeaza cost')}
                                    </Button>
                                    <OverlayPanel
                                        ref={estimateOverlayRef}
                                        appendTo={typeof window === 'undefined' ? undefined : document.body}
                                        className="w-[296px] max-w-[calc(100vw-1rem)]"
                                        onShow={() => setIsEstimateOverlayVisible(true)}
                                        onHide={() => setIsEstimateOverlayVisible(false)}
                                    >
                                        <div className="rounded-md border border-slate-300 bg-slate-100 px-4 pb-3 pt-8 text-[15px] leading-7 text-slate-600">
                                            {costEstimate && costEstimate.success ? (
                                                <div className="space-y-0.5">
                                                    <div className="flex items-center justify-between"><span>Baza:</span><span className="tabular-nums font-medium text-slate-700">{formatCurrency(costEstimate.costBreakdown?.tExpeditie)}</span></div>
                                                    <div className="flex items-center justify-between"><span>Km suplimentari:</span><span className="tabular-nums font-medium text-slate-700">{formatCurrency(costEstimate.costBreakdown?.tKm)}</span></div>
                                                    <div className="flex items-center justify-between"><span>Greutate:</span><span className="tabular-nums font-medium text-slate-700">{formatCurrency(costEstimate.costBreakdown?.tGreutate)}</span></div>
                                                    <div className="flex items-center justify-between"><span>Asigurare:</span><span className="tabular-nums font-medium text-slate-700">{formatCurrency(costEstimate.costBreakdown?.tAsigurare)}</span></div>
                                                    <div className="my-1.5 border-t border-slate-400"></div>
                                                    <div className="flex items-center justify-between"><span>Cost (fara TVA):</span><span className="tabular-nums font-medium text-slate-700">{formatCurrency(costEstimate.costBreakdown?.tValoareFaraTva)}</span></div>
                                                    <div className="flex items-center justify-between"><span>TVA:</span><span className="tabular-nums font-medium text-slate-700">{formatCurrency(costEstimate.costBreakdown?.tValoareTva)}</span></div>
                                                    <div className="flex items-center justify-between pt-1 text-[26px] font-semibold leading-8 text-slate-800"><span>Cost total:</span><span className="tabular-nums">{formatCurrency(costEstimate.formattedCost || '???')}</span></div>
                                                </div>
                                            ) : (
                                                <p className="text-sm text-red-600">{estimateMessage || 'Nu s-a putut estima costul.'}</p>
                                            )}
                                        </div>
                                    </OverlayPanel>
                                </>
                            )}
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Processing...' : 'Salveaza'}
                            </Button>
                        </div>
                    </div>
                </form>
    );
}

export default AwbInversForm;
