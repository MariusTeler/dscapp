import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    appName: string;
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    user: string;
    email: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    nume?: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface AutocompleteOption {
    value: string | number;
    text: string;
    option: string;
}

export interface DestinatarAutocompleteOption extends AutocompleteOption {
    client_destinatari_id?: number;
    localitate_id?: number;
    localitate?: string;
    judet?: string;
    adresa?: string;
    contact?: string;
    telefon?: string;
    email?: string;
}

export interface LocalitateAutocompleteOption extends AutocompleteOption {
    judet?: string;
    km?: number;
}

export interface PunctDeLucru {
    id: number;
    nume: string;
    judet: string;
    localitate: string;
    localitate_id: number;
    contact?: string;
    telefon?: string;
    email?: string;
    adresa: string;
}

export interface UserPrefs {
    print?: number;
    cc?: boolean;
    importcsv?: boolean;
    importxls?: boolean;
    def_sms?: boolean;
    preturi?: boolean;
    def_obsv?: string;
    def_retur_nc?: boolean;
    show_master_clienti?: boolean;
    /** Minimum characters required before a header filter triggers a server request */
    min_chars_filter?: number;
    /** Regex pattern used to validate AWB field values before sending to server */
    awb_regexp?: string;
}

// Service response interface for paginated data
export interface TabulatorDataResponse<T> {
    success: boolean;
    message?: string;
    data?: {
        data: T[];
        total: number;
        current_page: number;
        per_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
    };
}

export interface UpdatedAwbResponse {
    success: boolean;
    message?: string;
    data?: UpdatedAwbResponseData;
}

export interface UpdatedAwbResponseData {
    id: number;
    awb: number;
    tValoareFaraTva?: number | string | null;
    tValoareTva?: number | string | null;
}

export interface AwbData {
    id?: number;
    awb?: number;
    referire?: string | null;
    created_at?: string;
    created_by?: number;
    updated_at?: string;
    updated_by?: number;
    printed_at?: string;
    printed_by?: number;
    data_expeditie?: string;

    // Sender/Expeditor Information
    expeditor_id: number | null;
    expeditor_client_id?: number | null;
    expeditor_nume: string | null;
    expeditor_localitate?: string | null;
    expeditor_judet?: string | null;
    expeditor_judet_id?: string | null;
    expeditor_localitate_id: number | null;
    expeditor_contact: string | null;
    expeditor_telefon: string | null;
    expeditor_email: string | null;
    expeditor_adresa: string | null;
    expeditor_cui?: string | null;
    expeditor_j?: string | null;

    // Recipient/Destinatar Information
    destinatar_id: number | null;
    destinatar_client_id?: number | null;
    destinatar_nume: string | null;
    destinatar_localitate?: string | null;
    destinatar_judet?: string | null;
    destinatar_judet_id?: string | null;
    destinatar_localitate_id: number | null;
    destinatar_contact: string | null;
    destinatar_telefon: string | null;
    destinatar_email: string | null;
    destinatar_adresa: string | null;
    destinatar_centru?: string | null;
    destinatar_centru_cod?: string | null;
    destinatar_centru_zona?: string | null;

    platitor: number; // 1 - Sender, 2 - Recipient

    // Package content details
    tip_exp?: number | null; // 0 - Initiala, 1 - Retur NT, 2 - Retur Doc, 3 - Ramburs, 5 - Returnare, 6 - Retur Amb., 7 - Retur Colet, 33 - Borderou ramburs
    tip_obj: 1 | 2 | 3; // 1 - Plic, 2 - Colet, 3 - Palet
    greutate: number | null;
    greutate_vol?: number | null;
    piese: number | null;
    asigurare?: number | null;
    ramburs?: number | null;
    tip_plata: number; // 0 - Cash, 1 - BO, 2 - CEC, 3 - Cont

    volum1?: number | null;
    volum2?: number | null;
    volum3?: number | null;

    km_preluare?: number | null;
    km_livrare?: number | null;
    km_exteriori?: number | null;

    valoare_fara_tva?: number | string | null;
    valoare_tva?: number | string | null;

    destinatar_rut_bvh?: string | null;
    destinatar_rut_buh?: string | null;
    destinatar_rut_buc?: string | null;

    //booleans
    ret_nt: boolean;
    ret_doc: boolean;
    ret_amb: boolean;
    ret_colet: boolean;
    liv_samb: boolean;
    liv_sed: boolean;
    sms: boolean;
    copen: boolean;
    ret_nc?: boolean;

    extra_info?: string | null;
    large_info?: string | null;

    detalii_doc: string | null;
    observatii: string | null;

    swapped?: boolean;
    can_update?: boolean;
    print_awb?: number; // For conditional print logic

    status?: string | null;
    data_status?: string | null;
    ckp?: string | null;
    data_ckp?: string | null;
    centru_ckp?: string | null;
    primitor?: string | null;
    confirmare?: string | null;
    folder?: string | null;
}

// Order data interface
export interface OrderData {
    id?: number | null;
    created_at?: string;
    created_by?: number;
    updated_at?: string;
    updated_by?: number;
    deleted_at?: string;
    deleted_by?: number;
    collected_at?: string;
    expeditor_id: number;
    expeditor_nume: string;
    expeditor_pc?: string;
    data_colectare?: Date;
    data_colectare_string?: string;
    interval_colectare_start: number;
    interval_colectare_end: number;
    ridica_de_la?: string | null;
    expeditor_judet?: string | null;
    expeditor_localitate_id: number | null;
    expeditor_localitate: string;
    expeditor_contact?: string | null;
    expeditor_telefon: string;
    expeditor_email?: string | null;
    expeditor_adresa: string;
    colete: number | null;
    paleti: number | null;
    greutate: number | null;
    volum: number | null;
    observatii?: string | null;
    status?: number | null;
    data_status?: string | null;
    motiv?: string | null;
    curier?: string | null;
}

//borderou data interface
export interface BorderouData {
    id: number;
    borderou_id: number;
    expeditii?: number;
    created_at?: string;
    created_by?: string;
    print_awb?: number;
    status?: string;
}

//destinatar data interface
export interface DestinatarData {
    id?: number;
    created_at?: string;
    created_by?: number;
    updated_at?: string;
    updated_by?: number;
    localitate_id: number;
    localitate?: string;
    judet?: string;
    nume: string;
    adresa: string;
    contact: string | null;
    telefon: string | null;
    email: string | null;
}

export interface CostEstimateResponse {
    success: boolean;
    message?: string;
    costBreakdown?: CostBreakdown;
    details?: CostDetails;
    formattedCost?: string;
    
}

export interface CostBreakdown {
    
    tExpeditie: number;
    tGreutate: number;
    tKm: number;
    tAsigurare: number;
    tValoareFaraTva: number;
    tValoareTva: number;
    
}

export interface CostDetails {
    tipObj: string;
    modPlata: string;
    procTva: number;
    moneda: string;
}


/**
 * Import validation response interface
 */
export interface ImportValidationResponse {
    success: boolean;
    message: string;
    data?: {
        import_id?: string;
        columns?: string[];
        valid_rows?: Array<string[]>;
        total_valid?: number;
        total_errors: number;
        errors: Array<{
            row: number;
            errors: string[];
        }>;
        total_rows?: number;
    };
}
