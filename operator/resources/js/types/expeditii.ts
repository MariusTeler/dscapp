export interface Expeditie {
    expeditie: number;
    tip_exp: string;
    expeditor: string;
    destinatar: string;
    expeditor_centru: string;
    destinatar_centru: string;
    data_expeditie: string;
    plicuri: number;
    colete: number;
    paleti: number;
    greutate: number;
    ramburs: number;
    tip_plata: string;
    valoare_totala_expeditie: number;
    observatii: string;
    curier_preluare: string | null;
    curier_livrare: string | null;
    km_preluare: number | null;
    km_livrare: number | null;
    valoare_asigurata: number | null;
}

export interface Paginator {
    data: Expeditie[];
    total: number;
    per_page: number;
    current_page: number;
}
