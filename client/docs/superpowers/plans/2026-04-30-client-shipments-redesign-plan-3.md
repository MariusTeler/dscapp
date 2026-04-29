# Plan 3 — Mobile + URL sync + Status filter values

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development.

**Goal:** Finalizează redesignul `/shipments` cu (1) layout mobil dedicat (rând compact + expand inline), (2) sincronizare filtre cu URL pentru share-uibilitate + back/forward, (3) populare dropdown Status cu valori reale pentru Predate/Retururi.

**Architecture:**
- Mobile: nou component `awb-mobile-list.tsx` care folosește același endpoint + filtre ca `AwbTable`, dar randează listă cu Inertia/axios direct (nu Tabulator). Switch responsive la nivelul tab-urilor (`use-media-query` hook nou). Acțiunile (edit/print/delete) apar în zona expandată.
- URL sync: `useShipmentsFilters` extins cu read/write din `URLSearchParams`. Tab + filtre reflectate în URL la fiecare schimbare. Pe mount, parse din URL. Folosește `history.replaceState` (nu Inertia router — păstrăm SPA-ul fără full reload).
- Status values: endpoint nou `GET /shipments/{tab}/statuses` returnând valori distincte. Hook nou `useStatusOptions`. `ShipmentsFilterBar` populează dropdown-ul când e pe Predate/Retururi.

**Tech Stack:** Identic cu Plan 1+2.

**Reference:**
- Spec: `docs/superpowers/specs/2026-04-29-client-shipments-redesign-design.md`
- Plan 1: `docs/superpowers/plans/2026-04-29-client-shipments-redesign-plan-1.md`
- Plan 2: `docs/superpowers/plans/2026-04-29-client-shipments-redesign-plan-2.md`

**Branch strategy:** Continuare pe `feat/client-shipments-redesign-plan-1` (commits adăugate). Sau branch nou `feat/client-shipments-redesign-plan-3` pornit din ultimul commit Plan 2 dacă vrei PR separat.

---

## File Map

### Files to create

```
client/resources/js/pages/shipments/awb-mobile-list.tsx     # NEW (Task 4)
client/resources/js/pages/shipments/mobile-row-card.tsx     # NEW (Task 3) — sub-component
client/resources/js/hooks/use-media-query.ts                # NEW (Task 2)
client/resources/js/pages/shipments/hooks/use-status-options.ts  # NEW (Task 8)
```

### Files to modify

```
client/app/Http/Controllers/Expeditii/ListeController.php   # Task 7 (statuses endpoint)
client/app/Services/ExpeditiiService.php                    # Task 7 (getStatuses method)
client/routes/expeditii.php                                 # Task 7 (route)
client/resources/js/pages/shipments/hooks/use-shipments-filters.ts  # Task 1 (URL sync)
client/resources/js/pages/shipments/index.tsx               # Task 1 (read URL on mount, sync tab)
client/resources/js/pages/shipments/shipments-filter-bar.tsx  # Task 9 (status options)
client/resources/js/pages/shipments/tabs/nepredate-tab.tsx  # Task 5 (responsive switch)
client/resources/js/pages/shipments/tabs/predate-tab.tsx    # Task 5
client/resources/js/pages/shipments/tabs/retururi-tab.tsx   # Task 5
```

---

## Phase 1 — URL Sync

### Task 1: Extend `useShipmentsFilters` cu read/write URL

**Files:**
- Modify: `client/resources/js/pages/shipments/hooks/use-shipments-filters.ts`
- Modify: `client/resources/js/pages/shipments/index.tsx` (read tab from URL, write on change)

- [ ] **Step 1.1: Adaugă URL sync în hook**

Înlocuiește conținutul `use-shipments-filters.ts` cu:

```ts
import { useState, useCallback, useEffect } from 'react';
import type { ShipmentsFilters, TipObj } from '@/types/shipments';

function ymd(d: Date): string {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

function defaultFilters(): ShipmentsFilters {
    const today = new Date();
    const monthAgo = new Date();
    monthAgo.setMonth(monthAgo.getMonth() - 1);
    return {
        q: '',
        dateStart: ymd(monthAgo),
        dateEnd: ymd(today),
        status: null,
        tipObj: null,
        judet: null,
    };
}

function readFromUrl(): Partial<ShipmentsFilters> {
    if (typeof window === 'undefined') return {};
    const p = new URLSearchParams(window.location.search);
    const out: Partial<ShipmentsFilters> = {};
    if (p.has('q')) out.q = p.get('q') ?? '';
    if (p.has('start')) out.dateStart = p.get('start') ?? '';
    if (p.has('end')) out.dateEnd = p.get('end') ?? '';
    if (p.has('status')) out.status = p.get('status') || null;
    if (p.has('tip')) {
        const v = Number(p.get('tip'));
        if (v === 1 || v === 2 || v === 3) out.tipObj = v as TipObj;
    }
    if (p.has('judet')) out.judet = p.get('judet') || null;
    return out;
}

function writeToUrl(filters: ShipmentsFilters, tab: string): void {
    if (typeof window === 'undefined') return;
    const p = new URLSearchParams(window.location.search);
    p.set('tab', tab);
    if (filters.q) p.set('q', filters.q); else p.delete('q');
    if (filters.dateStart) p.set('start', filters.dateStart); else p.delete('start');
    if (filters.dateEnd) p.set('end', filters.dateEnd); else p.delete('end');
    if (filters.status) p.set('status', filters.status); else p.delete('status');
    if (filters.tipObj) p.set('tip', String(filters.tipObj)); else p.delete('tip');
    if (filters.judet) p.set('judet', filters.judet); else p.delete('judet');
    const newUrl = `${window.location.pathname}?${p.toString()}`;
    window.history.replaceState({}, '', newUrl);
}

export function useShipmentsFilters(initialTab: string) {
    const [filters, setFiltersState] = useState<ShipmentsFilters>(() => ({
        ...defaultFilters(),
        ...readFromUrl(),
    }));

    // Write filters to URL whenever they change (or tab changes)
    useEffect(() => {
        writeToUrl(filters, initialTab);
    }, [filters, initialTab]);

    const setFilters = useCallback((next: ShipmentsFilters) => {
        setFiltersState(next);
    }, []);

    const resetFilters = useCallback(() => {
        setFiltersState(defaultFilters());
    }, []);

    return { filters, setFilters, resetFilters };
}
```

- [ ] **Step 1.2: Update `index.tsx` să citească tab din URL**

Schimbă inițializarea `activeTab` în `index.tsx`:

```tsx
const [activeTab, setActiveTab] = useState<ShipmentsTab>(() => {
    if (typeof window === 'undefined') return 'nepredate';
    const tab = new URLSearchParams(window.location.search).get('tab');
    return (tab === 'predate' || tab === 'retururi') ? tab : 'nepredate';
});
const { filters, setFilters } = useShipmentsFilters(activeTab);
```

(Hook-ul primește acum `activeTab` ca să-l includă în URL.)

- [ ] **Step 1.3: Sync URL la schimbare tab**

Înlocuiește `onTabChange={setActiveTab}` în `<ShipmentsHeader>` cu:

```tsx
onTabChange={(tab) => {
    setActiveTab(tab);
    // Hook-ul useEffect din useShipmentsFilters va sincroniza URL-ul automat
}}
```

(De fapt, nu trebuie schimbat — hook-ul `useShipmentsFilters` are `useEffect` ce monitorizează `initialTab`, deci când `activeTab` se schimbă, URL-ul se actualizează la următorul render.)

- [ ] **Step 1.4: Verifică**

`npm run types && npm run lint`. Expected: 1 pre-existing error.

- [ ] **Step 1.5: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/hooks/use-shipments-filters.ts resources/js/pages/shipments/index.tsx
git commit -m "feat(client): URL sync for shipments filters and active tab"
```

---

## Phase 2 — Mobile responsive

### Task 2: Creează hook `use-media-query.ts`

**Files:**
- Create: `client/resources/js/hooks/use-media-query.ts`

- [ ] **Step 2.1: Scrie hook-ul**

```ts
import { useState, useEffect } from 'react';

export function useMediaQuery(query: string): boolean {
    const [matches, setMatches] = useState<boolean>(() => {
        if (typeof window === 'undefined') return false;
        return window.matchMedia(query).matches;
    });

    useEffect(() => {
        if (typeof window === 'undefined') return;
        const mql = window.matchMedia(query);
        const listener = (e: MediaQueryListEvent) => setMatches(e.matches);
        mql.addEventListener('change', listener);
        return () => mql.removeEventListener('change', listener);
    }, [query]);

    return matches;
}
```

- [ ] **Step 2.2: Verifică**

`npm run types`. Expected: 1 pre-existing error.

- [ ] **Step 2.3: Commit (sugestie)**

```bash
git add resources/js/hooks/use-media-query.ts
git commit -m "feat(client): add useMediaQuery hook for responsive switches"
```

---

### Task 3: Creează `mobile-row-card.tsx` (sub-component)

**Files:**
- Create: `client/resources/js/pages/shipments/mobile-row-card.tsx`

- [ ] **Step 3.1: Scrie componenta**

Card pentru un AWB pe mobil — colapsat afișează AWB / status / destinatar / ramburs. Click → expand → detalii grid 2 coloane + butoane acțiune.

```tsx
import { useState, type ReactNode } from 'react';
import { ChevronRight, ChevronDown } from 'lucide-react';
import type { AwbData, UserPrefs } from '@/types';
import { formatDate } from '@/lib/formatters';
import { cn } from '@/lib/utils';

interface MobileRowCardProps {
    row: AwbData;
    selected: boolean;
    onSelect: (id: number) => void;
    /** Slot for action buttons in expanded zone */
    actions?: (row: AwbData) => ReactNode;
}

function statusPillClass(tipObj: number | undefined): string {
    if (tipObj === 1) return 'bg-indigo-100 text-indigo-700';
    if (tipObj === 2) return 'bg-emerald-100 text-emerald-700';
    if (tipObj === 3) return 'bg-amber-100 text-amber-700';
    return 'bg-slate-100 text-slate-700';
}

function tipObjLabel(tipObj: number | undefined): string {
    if (tipObj === 1) return 'Plic';
    if (tipObj === 2) return 'Colet';
    if (tipObj === 3) return 'Palet';
    return '—';
}

export function MobileRowCard({ row, selected, onSelect, actions }: MobileRowCardProps) {
    const [expanded, setExpanded] = useState(false);
    const Chevron = expanded ? ChevronDown : ChevronRight;

    return (
        <div
            className={cn(
                'border rounded-lg bg-background mb-2',
                selected ? 'border-primary ring-1 ring-primary/30' : 'border-border'
            )}
        >
            <div
                onClick={() => setExpanded((e) => !e)}
                className="flex items-center gap-2 p-3 cursor-pointer"
            >
                <input
                    type="checkbox"
                    checked={selected}
                    onChange={(e) => {
                        e.stopPropagation();
                        if (typeof row.id === 'number') onSelect(row.id);
                    }}
                    onClick={(e) => e.stopPropagation()}
                />
                <Chevron className="h-4 w-4 text-muted-foreground shrink-0" />
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                        <span className="font-mono font-bold text-sm">{row.awb ?? '—'}</span>
                        <span className={cn('px-2 py-0.5 rounded-full text-[10px] font-semibold', statusPillClass(row.tip_obj))}>
                            {tipObjLabel(row.tip_obj)}
                        </span>
                    </div>
                    <div className="text-xs font-medium text-foreground mt-0.5 truncate">
                        {row.destinatar_nume ?? '—'}
                    </div>
                    <div className="text-[11px] text-muted-foreground truncate">
                        {row.destinatar_localitate ?? '—'}
                    </div>
                </div>
                <div className="text-right shrink-0">
                    {row.ramburs && Number(row.ramburs) > 0 ? (
                        <span className="font-mono font-bold text-sm text-emerald-600">
                            {Number(row.ramburs).toLocaleString('ro-RO')} RON
                        </span>
                    ) : (
                        <span className="text-muted-foreground">—</span>
                    )}
                </div>
            </div>

            {expanded && (
                <div className="px-3 pb-3 pt-1 border-t border-border bg-muted/20">
                    <div className="grid grid-cols-2 gap-x-3 gap-y-1 text-[11px] mt-2">
                        <div><span className="text-muted-foreground">Data:</span> {row.data_expeditie ? formatDate(row.data_expeditie) : '—'}</div>
                        <div><span className="text-muted-foreground">Greutate:</span> {row.greutate ?? 0} kg</div>
                        <div><span className="text-muted-foreground">Piese:</span> {row.piese ?? '—'}</div>
                        <div><span className="text-muted-foreground">Km:</span> {row.km_exteriori ?? '—'}</div>
                        <div className="col-span-2"><span className="text-muted-foreground">Expeditor:</span> {row.expeditor_nume ?? '—'}</div>
                        <div className="col-span-2"><span className="text-muted-foreground">Adresă:</span> {row.destinatar_adresa ?? '—'}</div>
                    </div>
                    {actions && (
                        <div className="flex flex-wrap gap-2 mt-3">
                            {actions(row)}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

// Suppress unused import warning if UserPrefs not needed
export type { UserPrefs };
```

- [ ] **Step 3.2: Verifică**

`npm run types && npm run lint`. Expected: 1 pre-existing error.

- [ ] **Step 3.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/mobile-row-card.tsx
git commit -m "feat(client): add MobileRowCard for shipments mobile list"
```

---

### Task 4: Creează `awb-mobile-list.tsx`

**Files:**
- Create: `client/resources/js/pages/shipments/awb-mobile-list.tsx`

- [ ] **Step 4.1: Scrie componenta**

Listă paginată cu progressive load on scroll. Folosește același endpoint + filtre + extraParams ca `AwbTable`.

```tsx
import { useEffect, useState, useRef, useCallback, type ReactNode } from 'react';
import axios from '@/lib/axios';
import type { AwbData } from '@/types';
import type { ShipmentsFilters } from '@/types/shipments';
import { MobileRowCard } from '@/pages/shipments/mobile-row-card';

interface AwbMobileListProps {
    endpoint: string;
    filters: ShipmentsFilters;
    extraParams?: Record<string, string | number | boolean>;
    /** Slot for action buttons in expanded zone */
    rowActions?: (row: AwbData) => ReactNode;
    onSelectionChange?: (rows: AwbData[]) => void;
    /** Trigger refresh — bump key to refetch list */
    refreshKey?: number;
}

const PAGE_SIZE = 50;

export function AwbMobileList({
    endpoint,
    filters,
    extraParams = {},
    rowActions,
    onSelectionChange,
    refreshKey = 0,
}: AwbMobileListProps) {
    const [rows, setRows] = useState<AwbData[]>([]);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [loading, setLoading] = useState(false);
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const sentinelRef = useRef<HTMLDivElement>(null);
    const abortRef = useRef<AbortController | null>(null);

    const buildUrl = useCallback((p: number) => {
        const u = new URLSearchParams();
        Object.entries(extraParams).forEach(([k, v]) => u.append(k, String(v)));
        if (filters.dateStart) u.append('startDate', filters.dateStart);
        if (filters.dateEnd) u.append('endDate', filters.dateEnd);
        if (filters.q) u.append('q', filters.q);
        if (filters.judet) u.append('filter_judet', filters.judet);
        if (filters.tipObj) u.append('filter_tip_obj', String(filters.tipObj));
        if (filters.status) u.append('filter_status', filters.status);
        u.append('page', String(p));
        u.append('rows', String(PAGE_SIZE));
        return `${endpoint}?${u.toString()}`;
    }, [endpoint, filters, extraParams]);

    const loadPage = useCallback(async (p: number, replace: boolean) => {
        if (abortRef.current) abortRef.current.abort();
        const ctrl = new AbortController();
        abortRef.current = ctrl;
        setLoading(true);
        try {
            const res = await axios.get(buildUrl(p), { signal: ctrl.signal });
            const data: AwbData[] = res.data?.data?.data ?? [];
            const lastPage: number = res.data?.data?.last_page ?? 1;
            setRows((prev) => (replace ? data : [...prev, ...data]));
            setHasMore(p < lastPage);
            setPage(p);
        } catch (e) {
            if (axios.isCancel(e)) return;
            console.error('Mobile list load error', e);
        } finally {
            setLoading(false);
        }
    }, [buildUrl]);

    // Reload on filter / refreshKey change
    useEffect(() => {
        setSelectedIds(new Set());
        loadPage(1, true);
    }, [filters.q, filters.dateStart, filters.dateEnd, filters.status, filters.tipObj, filters.judet, refreshKey, loadPage]);

    // IntersectionObserver for infinite scroll
    useEffect(() => {
        const el = sentinelRef.current;
        if (!el || !hasMore || loading) return;
        const obs = new IntersectionObserver((entries) => {
            if (entries[0]?.isIntersecting) {
                loadPage(page + 1, false);
            }
        });
        obs.observe(el);
        return () => obs.disconnect();
    }, [hasMore, loading, page, loadPage]);

    // Notify parent of selection changes
    useEffect(() => {
        if (!onSelectionChange) return;
        const sel = rows.filter((r) => typeof r.id === 'number' && selectedIds.has(r.id));
        onSelectionChange(sel);
    }, [selectedIds, rows, onSelectionChange]);

    const handleSelect = useCallback((id: number) => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id); else next.add(id);
            return next;
        });
    }, []);

    return (
        <div className="px-3 py-2">
            {rows.length === 0 && !loading && (
                <p className="text-center text-sm text-muted-foreground py-8">
                    Nu au fost găsite AWB-uri.
                </p>
            )}
            {rows.map((r) => (
                <MobileRowCard
                    key={r.id}
                    row={r}
                    selected={typeof r.id === 'number' && selectedIds.has(r.id)}
                    onSelect={handleSelect}
                    actions={rowActions}
                />
            ))}
            <div ref={sentinelRef} className="h-8" />
            {loading && (
                <p className="text-center text-xs text-muted-foreground py-2">
                    Se încarcă...
                </p>
            )}
        </div>
    );
}
```

- [ ] **Step 4.2: Verifică**

`npm run types && npm run lint`. Expected: 1 pre-existing error.

- [ ] **Step 4.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/awb-mobile-list.tsx
git commit -m "feat(client): add AwbMobileList with progressive scroll + selection"
```

---

### Task 5: Adaugă switch responsive în tab-uri

**Files:**
- Modify: `client/resources/js/pages/shipments/tabs/nepredate-tab.tsx`
- Modify: `client/resources/js/pages/shipments/tabs/predate-tab.tsx`
- Modify: `client/resources/js/pages/shipments/tabs/retururi-tab.tsx`

- [ ] **Step 5.1: NepredateTab — switch desktop/mobile**

În `nepredate-tab.tsx`:

1. Adaugă imports:
```tsx
import { useMediaQuery } from '@/hooks/use-media-query';
import { AwbMobileList } from '@/pages/shipments/awb-mobile-list';
import { Edit, Printer, Trash2 } from 'lucide-react';
```

2. În corpul componentei, adaugă:
```tsx
const isMobile = useMediaQuery('(max-width: 1023px)');
```

3. Înlocuiește randarea `<AwbTable ...>` cu un block conditional:

```tsx
{isMobile ? (
    <AwbMobileList
        endpoint={nepredate().url}
        filters={filters}
        extraParams={{ swapped: '1' }}
        onSelectionChange={setSelectedRows}
        rowActions={(row) => (
            <>
                {(row.can_update ?? true) && (
                    <button
                        onClick={(e) => { e.stopPropagation(); onEditAwb(row); }}
                        className="px-2 py-1 text-xs bg-primary text-primary-foreground rounded inline-flex items-center gap-1"
                    >
                        <Edit className="h-3 w-3" />
                        Editează
                    </button>
                )}
                <button
                    onClick={(e) => { e.stopPropagation(); if (typeof row.id === 'number') onPrintRows([row.id]); }}
                    className="px-2 py-1 text-xs bg-foreground text-background rounded inline-flex items-center gap-1"
                >
                    <Printer className="h-3 w-3" />
                    Print
                </button>
                <button
                    onClick={(e) => { e.stopPropagation(); onDeleteAwb(row); }}
                    className="px-2 py-1 text-xs bg-red-600 text-white rounded inline-flex items-center gap-1"
                >
                    <Trash2 className="h-3 w-3" />
                    Șterge
                </button>
            </>
        )}
    />
) : (
    <AwbTable
        endpoint={nepredate().url}
        columns={columns}
        filters={filters}
        extraParams={{ swapped: '1' }}
        prefs={prefs}
        createUpdateDeleteRow={createUpdateDeleteRow}
        printRows={printRows}
        onSelectionChange={setSelectedRows}
        onRowDblClick={handleView}
    />
)}
```

- [ ] **Step 5.2: PredateTab — switch desktop/mobile**

În `predate-tab.tsx`, similar:

1. Imports `useMediaQuery`, `AwbMobileList`.
2. `const isMobile = useMediaQuery('(max-width: 1023px)');`
3. Conditional render — mobile fără `rowActions` (Predate e read-only):

```tsx
{isMobile ? (
    <AwbMobileList
        endpoint={predate().url}
        filters={filters}
        onSelectionChange={setSelectedRows}
    />
) : (
    <AwbTable
        endpoint={predate().url}
        columns={columns}
        filters={filters}
        prefs={prefs}
        onSelectionChange={setSelectedRows}
        onRowDblClick={handleView}
    />
)}
```

- [ ] **Step 5.3: RetururiTab — analog cu PredateTab**

Aplică același pattern.

- [ ] **Step 5.4: Verifică**

`npm run types && npm run lint`. Expected: 1 pre-existing error.

- [ ] **Step 5.5: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/tabs/
git commit -m "feat(client): responsive switch — mobile list under 1024px for all 3 tabs"
```

---

### Task 6: Verificare manuală mobile

**Files:** none

- [ ] **Step 6.1: În browser**

Deschide `/shipments`, micșorează fereastra la sub 1024px. Verifică:
- Tabel devine listă de carduri
- Card colapsat: AWB / Tip pill / Destinatar / Localitate / Ramburs
- Click pe card → expand → date suplimentare + butoane acțiune (pe Nepredate)
- Pe Nepredate: butoanele Editează (dacă `can_update`) / Print / Șterge funcționează
- Pe Predate / Retururi: doar zone de info expandate
- Selecție checkbox funcționează — bulk bar apare
- Infinite scroll: scroll-ul în jos încarcă mai multe carduri (când există mai mult de 50 rezultate)

Notă: necesită schema DB completă pentru date.

---

## Phase 3 — Status filter values

### Task 7: Backend — endpoint `statuses`

**Files:**
- Modify: `client/app/Services/ExpeditiiService.php`
- Modify: `client/app/Http/Controllers/Expeditii/ListeController.php`
- Modify: `client/routes/expeditii.php`

- [ ] **Step 7.1: Adaugă `getStatuses()` în `ExpeditiiService`**

Inserează la finalul clasei (după `getStats`):

```php
/**
 * Get distinct status values (operatiune) for a tab.
 *
 * @param string $tab 'predate'|'retururi'
 */
public function getStatuses(User $user, string $tab): array
{
    $cond = $this->getExpeditorScopeCondition($user);

    if ($tab === 'predate') {
        $query = DB::table('exp_prelucrate as ep')
            ->select(DB::raw('DISTINCT ep.operatiune as status'))
            ->whereNotNull('ep.operatiune')
            ->where('ep.operatiune', '!=', '')
            ->where('ep.tip_exp', 0)
            ->where('ep.anulata', 0)
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('scanari_coduri as sc')
                    ->whereRaw('sc.expeditie = ep.expeditie');
            })
            ->whereRaw($cond);
    } elseif ($tab === 'retururi') {
        $query = DB::table('exp_prelucrate as epr')
            ->select(DB::raw('DISTINCT epr.operatiune as status'))
            ->whereNotNull('epr.operatiune')
            ->where('epr.operatiune', '!=', '')
            ->where(function ($sub) {
                $sub->where('epr.ret_nt', 1)
                    ->orWhere('epr.ret_doc', 1)
                    ->orWhere('epr.ret_colet', 1)
                    ->orWhere('epr.ret_amb', 1);
            })
            ->whereRaw($cond);
    } else {
        return [];
    }

    return $query->orderBy('status')->pluck('status')->filter()->values()->toArray();
}
```

- [ ] **Step 7.2: Adaugă `statuses()` în `ListeController`**

```php
public function statuses(Request $request, string $tab): JsonResponse
{
    if (! in_array($tab, ['predate', 'retururi'], true)) {
        return response()->json(['success' => false, 'message' => 'Invalid tab'], 400);
    }
    try {
        $user = $request->user();
        if ($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $statuses = $this->expeditiiService->getStatuses($user, $tab);
        return response()->json(['success' => true, 'data' => $statuses]);
    } catch (\Exception $e) {
        Log::error('Error loading statuses', ['tab' => $tab, 'error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Error loading statuses'], 500);
    }
}
```

- [ ] **Step 7.3: Adaugă ruta**

În `routes/expeditii.php`, în interiorul grupului auth, după `shipments/{tab}/stats`:

```php
Route::get('shipments/{tab}/statuses', [ListeController::class, 'statuses'])
    ->where('tab', 'predate|retururi')
    ->name('shipments.statuses');
```

- [ ] **Step 7.4: Verifică**

```bash
cd "/Users/telermarius/dev/DSC APP/.worktrees/shipments-redesign-plan-1/client" && php -l app/Services/ExpeditiiService.php && php -l app/Http/Controllers/Expeditii/ListeController.php && php artisan route:list --name=shipments.statuses
```

- [ ] **Step 7.5: Commit (sugestie)**

```bash
git add app/Services/ExpeditiiService.php app/Http/Controllers/Expeditii/ListeController.php routes/expeditii.php
git commit -m "feat(client): backend - distinct statuses endpoint per tab"
```

---

### Task 8: Hook `useStatusOptions`

**Files:**
- Create: `client/resources/js/pages/shipments/hooks/use-status-options.ts`

- [ ] **Step 8.1: Scrie hook-ul**

```ts
import { useEffect, useState } from 'react';
import axios from '@/lib/axios';
import type { ShipmentsTab } from '@/types/shipments';

export function useStatusOptions(tab: ShipmentsTab): string[] {
    const [options, setOptions] = useState<string[]>([]);

    useEffect(() => {
        if (tab === 'nepredate') {
            setOptions([]);
            return;
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
```

- [ ] **Step 8.2: Verifică**

`npm run types`. Expected: 1 pre-existing error.

- [ ] **Step 8.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/hooks/use-status-options.ts
git commit -m "feat(client): add useStatusOptions hook"
```

---

### Task 9: Folosește status options în `ShipmentsFilterBar`

**Files:**
- Modify: `client/resources/js/pages/shipments/shipments-filter-bar.tsx`

- [ ] **Step 9.1: Importă hook + populează dropdown**

În `shipments-filter-bar.tsx`:

1. Adaugă import:
```tsx
import { useStatusOptions } from '@/pages/shipments/hooks/use-status-options';
```

2. În corpul componentei:
```tsx
const statusOptions = useStatusOptions(tab);
```

3. Înlocuiește dropdown-ul Status (cel cu placeholder de inventar):

```tsx
{tab !== 'nepredate' && (
    <select
        value={filters.status ?? ''}
        onChange={(e) => onChange({ ...filters, status: e.target.value || null })}
        className="px-2 py-1 text-xs border border-border rounded bg-background"
    >
        <option value="">Toate statusurile</option>
        {statusOptions.map((s) => (
            <option key={s} value={s}>{s}</option>
        ))}
    </select>
)}
```

- [ ] **Step 9.2: Verifică**

`npm run types && npm run lint`. Expected: 1 pre-existing error.

- [ ] **Step 9.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/shipments-filter-bar.tsx
git commit -m "feat(client): populate Status dropdown from /shipments/{tab}/statuses"
```

---

## Self-Review Checklist (după execuție)

- [ ] URL sync: filtre + tab reflectate în URL la schimbare; reîncărcare pagină păstrează starea
- [ ] Mobile (sub 1024px): tabel devine listă carduri, expand inline funcționează, acțiuni Edit/Print/Delete pe Nepredate
- [ ] Selecție multiplă pe mobile cu checkbox + bulk bar
- [ ] Infinite scroll pe mobile (50 elemente / page)
- [ ] Status dropdown populat dinamic pe Predate / Retururi
- [ ] Routa nouă `/shipments/{tab}/statuses` înregistrată
- [ ] Type-check + lint la nivelul Plan 2 (1 pre-existing error)

## Done

După Plan 3, redesignul `/shipments` e complet conform spec-ului:
- Modern, dens, responsive
- Toate cele 16 coloane vizibile pe desktop
- Mobile cu rând compact + expand
- Filtre hibrid + URL share-uibil
- Stats live + status dropdown contextual
- Comportament 1:1 păstrat (edit/print/delete/CRUD)

Ce rămâne **explicit în afara scope-ului spec-ului**:
- Chips filtre active (decizie: amânat)
- Bulk delete (decizie: nu adăugăm)
- Saved filter presets (out of scope)
- Mobile bulk actions (Print bulk pe mobil — adăugat doar dacă cazul de utilizare e clar)
