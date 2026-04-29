export type ShipmentsTab = 'nepredate' | 'predate' | 'retururi';

export type TipObj = 1 | 2 | 3; // 1=Plic, 2=Colet, 3=Palet

export interface ShipmentsFilters {
    q: string;
    dateStart: string; // YYYY-MM-DD
    dateEnd: string;   // YYYY-MM-DD
    status: string | null;
    tipObj: TipObj | null;
    judet: string | null; // ISO 3166-2:RO sub-code (e.g. 'CJ', 'B', 'IF')
}

export interface ShipmentsStats {
    count: number;
    total_weight: number;  // kg
    total_ramburs: number; // RON
}

export interface ShipmentsStatsResponse {
    success: boolean;
    data?: ShipmentsStats;
    message?: string;
}

export interface JudetOption {
    code: string; // 'CJ'
    name: string; // 'Cluj'
}
