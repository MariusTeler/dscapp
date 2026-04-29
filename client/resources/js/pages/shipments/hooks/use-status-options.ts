import { useEffect, useState } from 'react';
import axios from '@/lib/axios';
import type { ShipmentsTab } from '@/types/shipments';

export function useStatusOptions(tab: ShipmentsTab): string[] {
    const [options, setOptions] = useState<string[]>([]);

    useEffect(() => {
        if (tab === 'nepredate') {
            const t = window.setTimeout(() => setOptions([]), 0);
            return () => window.clearTimeout(t);
        }
        let cancelled = false;
        axios
            .get<{ success: boolean; data: string[] }>(`/shipments/${tab}/statuses`)
            .then((res) => {
                if (cancelled) return;
                if (res.data?.success) setOptions(res.data.data ?? []);
            })
            .catch((e) => {
                console.error('Failed to load statuses', e);
            });
        return () => { cancelled = true; };
    }, [tab]);

    return options;
}
