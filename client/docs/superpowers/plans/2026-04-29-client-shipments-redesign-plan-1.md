# Plan 1 — Backend Extensions + UI Shell + Nepredate Pilot

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Livrează tab-ul **Nepredate** complet în noul design (header compact + filter bar hibrid + bulk bar + tabel generic), cu backend extins (search global `q`, filtru `judet`, endpoint `stats`). Predate / Retururi rămân neschimbate (Plan 2).

**Architecture:** Frontend (Inertia + React 19 + Tailwind 4 + Tabulator) cu componente noi în `resources/js/pages/shipments/`, un hook `useShipmentsFilters` pentru state și `useShipmentsStats` pentru date sumare. Backend (Laravel 13) extinde `ListeController` și `ExpeditiiService` cu noi parametri (`q`, `filter_judet`) și metoda `getStats()`. Migrare incrementală: `index.tsx` randează componente noi pentru tab-ul `nepredate` și păstrează componentele vechi `AwbPredate` / `AwbRetururi` pentru celelalte (în Plan 2 le migrăm).

**Tech Stack:** PHP 8.x, Laravel 13, Inertia.js, React 19, TypeScript, Tailwind 4, shadcn/Radix, PrimeReact 10.9, Tabulator 6.4, lucide-react, axios.

**Note privind verificarea:**
- Backend: `phpunit.xml` există dar `tests/` nu — în loc de TDD scriem **verificare manuală** prin curl + browser după fiecare endpoint.
- Frontend: nu există vitest/RTL configurat — verificăm cu `npm run types` (tsc --noEmit), `npm run lint`, și **rularea manuală în dev server**.
- Acest plan **nu face commit-uri automat**. Comenzile `git commit` din ultimul step al fiecărei task-uri sunt **sugestii** pentru utilizator. Sare-le sau execute-le după preferință.

**Reference spec:** `docs/superpowers/specs/2026-04-29-client-shipments-redesign-design.md`

---

## File Map

### Files to create

```
client/resources/js/pages/shipments/
├── shipments-header.tsx                # NEW (Task 7)
├── shipments-filter-bar.tsx            # NEW (Task 8)
├── shipments-bulk-bar.tsx              # NEW (Task 9)
├── awb-table.tsx                       # NEW (Task 13)
├── tabs/
│   └── nepredate-tab.tsx               # NEW (Task 14)
└── hooks/
    ├── use-shipments-filters.ts        # NEW (Task 10)
    └── use-shipments-stats.ts          # NEW (Task 12)

client/resources/js/types/
└── shipments.d.ts                      # NEW (Task 6)

client/resources/js/lib/
└── judete.ts                           # NEW (Task 11) — lista fixă RO
```

### Files to modify

```
client/app/Services/ExpeditiiService.php           # Tasks 1, 2, 3, 4
client/app/Http/Controllers/Expeditii/ListeController.php  # Tasks 4, 5
client/routes/expeditii.php                        # Task 5
client/resources/js/pages/shipments/index.tsx      # Task 15
```

### Files to delete (sfârșitul Plan 1)

```
client/resources/js/pages/shipments/awb-nepredate.tsx     # Task 18
```

(Predate / Retururi rămân până la Plan 2.)

---

## Phase 1 — Backend Extensions

### Task 1: Adaugă search global (`q`) în `getNepredate()`

**Files:**
- Modify: `client/app/Services/ExpeditiiService.php` (metoda `getNepredate`, linii ~240-262 — bucla `foreach ($request->all())`)

- [ ] **Step 1.1: Citește bucla de filtre din `getNepredate()`**

Verifică structura curentă. Filtrul `q` se aplică **înainte** de bucla `foreach` cu `filter_*`, ca grup OR. Nu intră în bucla existentă.

- [ ] **Step 1.2: Adaugă blocul `q` imediat după `applyDateRangeFilter`**

În `app/Services/ExpeditiiService.php`, în metoda `getNepredate`, după linia care apelează `ToolsService::applyDateRangeFilter($query, $request);` și **înainte** de `$abort = false;`:

```php
// Apply global search (q) — LIKE OR pe câmpurile cheie
$q = trim((string) $request->input('q', ''));
$minChars = config('awb.tabulator.min_chars_filter', 3);
if ($q !== '' && mb_strlen($q) >= $minChars) {
    $isAwb = ToolsService::isAwb($q);
    $query->where(function ($sub) use ($q, $isAwb) {
        if ($isAwb) {
            $sub->orWhere('ep.expeditie', '=', (int) $q);
        }
        $sub->orWhere('cle.nume', 'like', "%{$q}%")
            ->orWhere('cld.nume', 'like', "%{$q}%")
            ->orWhere('lce.nume_lc', 'like', "%{$q}%")
            ->orWhere('lcd.nume_lc', 'like', "%{$q}%");
    });
}
```

- [ ] **Step 1.3: Verifică sintactic**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && php -l app/Services/ExpeditiiService.php
```
Expected: `No syntax errors detected`

- [ ] **Step 1.4: Test manual via browser/curl**

Pornește dev server (dacă nu rulează deja): `cd "/Users/telermarius/dev/DSC APP/client" && php artisan serve` (în terminal separat).

Testează în browser sau curl autenticat. Exemplu URL test:
```
GET /shipments/nepredate?q=Cluj&startDate=2026-03-29&endDate=2026-04-29
```

Așteptat: răspunsul JSON conține doar AWB-uri unde un câmp conține "Cluj". Dacă `q` lipsește sau e sub `min_chars_filter`, comportamentul e același ca acum.

- [ ] **Step 1.5: Commit (sugestie)**

```bash
git add app/Services/ExpeditiiService.php
git commit -m "feat(client): add global search 'q' param to getNepredate"
```

---

### Task 2: Replică `q` în `getPredate()` și `getRetururi()`

**Files:**
- Modify: `client/app/Services/ExpeditiiService.php` (metodele `getPredate`, `getRetururi`)

- [ ] **Step 2.1: Aplică același bloc `q` în `getPredate()`**

În metoda `getPredate`, găsește locul echivalent (după `applyDateRangeFilter`, înainte de bucla `foreach` cu `filter_*`) și inserează același bloc:

```php
$q = trim((string) $request->input('q', ''));
$minChars = config('awb.tabulator.min_chars_filter', 3);
if ($q !== '' && mb_strlen($q) >= $minChars) {
    $isAwb = ToolsService::isAwb($q);
    $query->where(function ($sub) use ($q, $isAwb) {
        if ($isAwb) {
            $sub->orWhere('ep.expeditie', '=', (int) $q);
        }
        $sub->orWhere('cle.nume', 'like', "%{$q}%")
            ->orWhere('cld.nume', 'like', "%{$q}%")
            ->orWhere('lce.nume_lc', 'like', "%{$q}%")
            ->orWhere('lcd.nume_lc', 'like', "%{$q}%");
    });
}
```

- [ ] **Step 2.2: Aplică același bloc `q` în `getRetururi()`**

Identic. Verifică alias-urile JOIN: dacă `cle / cld / lce / lcd` sunt diferite în `getRetururi`, ajustează corespunzător (ar trebui să fie aceleași — același pattern de query).

- [ ] **Step 2.3: Verifică sintactic**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && php -l app/Services/ExpeditiiService.php
```

- [ ] **Step 2.4: Test manual**

```
GET /shipments/predate?q=București&startDate=...&endDate=...
GET /shipments/retururi?q=...&startDate=...&endDate=...
```

- [ ] **Step 2.5: Commit (sugestie)**

```bash
git add app/Services/ExpeditiiService.php
git commit -m "feat(client): add 'q' param to getPredate and getRetururi"
```

---

### Task 3: Adaugă filtru `judet` (cod ISO) pe `destinatar_judet_id`

**Files:**
- Modify: `client/app/Services/ExpeditiiService.php` (toate trei metodele `getNepredate`, `getPredate`, `getRetururi`)

- [ ] **Step 3.1: Adaugă cazul `judet` în match expression din `getNepredate`**

În bucla `foreach ($request->all() as $key => $value)` din `getNepredate`, în `match ($field)`, adaugă un caz nou înainte de `default => null,`:

```php
'judet' => $query->where('jd.cod_jd', '=', strtoupper(trim($value))),
```

(Coloana `jd.cod_jd` e deja join-uită în query — alias-ul e folosit în SELECT ca `destinatar_judet_id`.)

**Important**: caz-ul `'judet'` în match e câmpul după prefix `filter_`, deci query param-ul real va fi `filter_judet=CJ`.

- [ ] **Step 3.2: Adaugă același caz în `getPredate` și `getRetururi`**

Identic, în match-urile lor de filter. Verifică numele alias-ului JOIN: dacă în `getRetururi` join-ul cu `judete` are alt alias, ajustează (de ex. dacă e `j2` în loc de `jd`, folosește acel alias).

- [ ] **Step 3.3: Verifică sintactic**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && php -l app/Services/ExpeditiiService.php
```

- [ ] **Step 3.4: Test manual**

```
GET /shipments/nepredate?filter_judet=CJ&startDate=2026-03-29&endDate=2026-04-29
```

Așteptat: doar AWB-uri unde destinatarul e în Cluj (`CJ`).

- [ ] **Step 3.5: Commit (sugestie)**

```bash
git add app/Services/ExpeditiiService.php
git commit -m "feat(client): add 'filter_judet' on destinatar_judet_id"
```

---

### Task 4: Creează metoda `getStats()` în `ExpeditiiService`

**Files:**
- Modify: `client/app/Services/ExpeditiiService.php` (adaugă metoda nouă la final, înainte de `}` de clasă)

- [ ] **Step 4.1: Adaugă metoda `getStats()`**

În `app/Services/ExpeditiiService.php`, adaugă **după** `getRetururi()` (sau oriunde la finalul clasei):

```php
/**
 * Get aggregated stats (count, total weight, total ramburs) for a tab + filters.
 *
 * @param string $tab 'nepredate'|'predate'|'retururi'
 */
public function getStats(
    Request $request,
    User $user,
    string $tab,
    bool $swapped = false,
): array {
    $cond = $this->getExpeditorScopeCondition($user);

    $query = DB::table('exp_prelucrate as ep')
        ->join('clienti as cle', 'ep.expeditor_id', '=', 'cle.cod_cl')
        ->join('localitati as lce', 'cle.cod_lc', '=', 'lce.cod_lc')
        ->leftJoin('judete as je', 'lce.cod_jd', '=', 'je.cod_jd')
        ->join('clienti as cld', 'ep.destinatar_id', '=', 'cld.cod_cl')
        ->join('localitati as lcd', 'cld.cod_lc', '=', 'lcd.cod_lc')
        ->leftJoin('judete as jd', 'lcd.cod_jd', '=', 'jd.cod_jd')
        ->where('ep.tip_exp', 0)
        ->where('ep.anulata', 0);

    // Tab-specific WHERE
    if ($tab === 'nepredate') {
        if (! $swapped) {
            $query->where('ep.swapped', 0);
        }
        $query->where('ep.borderou_id', 0)
              ->where('ep.idfact', 0)
              ->whereNotExists(function ($subQuery) {
                  $subQuery->select(DB::raw(1))
                      ->from('decont_expeditii as dee')
                      ->join('decont_facturi as dfa', function ($join) {
                          $join->on('dee.factura_id', '=', 'dfa.id')
                               ->where('dfa.anulata', 0);
                      })
                      ->whereRaw('dee.expeditie = ep.expeditie')
                      ->where('dee.anulata', 0);
              })
              ->whereNotExists(function ($subQuery) {
                  $subQuery->select(DB::raw(1))
                      ->from('scanari_coduri as sc')
                      ->whereRaw('sc.expeditie = ep.expeditie');
              })
              ->whereRaw($cond);
    } elseif ($tab === 'predate') {
        // Replică WHERE-urile din getPredate — minim: scope + există scanare/predare
        $query->whereExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('scanari_coduri as sc')
                ->whereRaw('sc.expeditie = ep.expeditie');
        })->whereRaw($cond);
    } elseif ($tab === 'retururi') {
        // Replică WHERE-urile din getRetururi
        $query->where(function ($sub) {
            $sub->where('ep.ret_nt', 1)
                ->orWhere('ep.ret_doc', 1)
                ->orWhere('ep.ret_colet', 1)
                ->orWhere('ep.ret_amb', 1);
        })->whereRaw($cond);
    }

    // Date range
    $query = ToolsService::applyDateRangeFilter($query, $request);

    // Global search q
    $q = trim((string) $request->input('q', ''));
    $minChars = config('awb.tabulator.min_chars_filter', 3);
    if ($q !== '' && mb_strlen($q) >= $minChars) {
        $isAwb = ToolsService::isAwb($q);
        $query->where(function ($sub) use ($q, $isAwb) {
            if ($isAwb) {
                $sub->orWhere('ep.expeditie', '=', (int) $q);
            }
            $sub->orWhere('cle.nume', 'like', "%{$q}%")
                ->orWhere('cld.nume', 'like', "%{$q}%")
                ->orWhere('lce.nume_lc', 'like', "%{$q}%")
                ->orWhere('lcd.nume_lc', 'like', "%{$q}%");
        });
    }

    // filter_* (judet, tip_obj)
    foreach ($request->all() as $key => $value) {
        if (str_starts_with($key, 'filter_') && ! empty($value)) {
            $field = substr($key, 7);
            match ($field) {
                'judet' => $query->where('jd.cod_jd', '=', strtoupper(trim($value))),
                'tip_obj' => $query->where('ep.tip_obj', '=', (int) $value),
                'destinatar_nume' => $query->where('cld.nume', 'like', "{$value}%"),
                'destinatar_localitate' => $query->where('lcd.nume_lc', 'like', "{$value}%"),
                default => null,
            };
        }
    }

    $row = $query->selectRaw('
        COUNT(DISTINCT ep.cod_expeditie) as count,
        COALESCE(SUM(ep.greutate), 0) as total_weight,
        COALESCE(SUM(ep.ramburs), 0) as total_ramburs
    ')->first();

    return [
        'count' => (int) ($row->count ?? 0),
        'total_weight' => round((float) ($row->total_weight ?? 0), 2),
        'total_ramburs' => round((float) ($row->total_ramburs ?? 0), 2),
    ];
}
```

**Notă**: filtrul `judet` și `tip_obj` se aplică similar cu cel din list endpoints. Restul `filter_*` (awb, expeditor_nume etc.) nu sunt esențiale pentru stats — clientul de obicei filtrează pe câmpuri largi când vrea sumar (q, judet, tip).

- [ ] **Step 4.2: Verifică sintactic**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && php -l app/Services/ExpeditiiService.php
```

- [ ] **Step 4.3: Test manual via Tinker (rapid)**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && php artisan tinker
```
```php
$svc = app(\App\Services\ExpeditiiService::class);
$user = \App\Models\User::find(/* ID user de test */);
$req = new \Illuminate\Http\Request(['startDate' => '2026-03-29', 'endDate' => '2026-04-29']);
dd($svc->getStats($req, $user, 'nepredate'));
```
Așteptat: array cu `count`, `total_weight`, `total_ramburs`.

- [ ] **Step 4.4: Commit (sugestie)**

```bash
git add app/Services/ExpeditiiService.php
git commit -m "feat(client): add ExpeditiiService::getStats() for shipments stats"
```

---

### Task 5: Adaugă endpoint-uri `stats` în controller + route

**Files:**
- Modify: `client/app/Http/Controllers/Expeditii/ListeController.php`
- Modify: `client/routes/expeditii.php`

- [ ] **Step 5.1: Adaugă metoda `stats()` în `ListeController`**

În `app/Http/Controllers/Expeditii/ListeController.php`, adaugă la finalul clasei (înainte de `}`):

```php
/**
 * Get aggregated stats for a tab.
 */
public function stats(Request $request, string $tab): JsonResponse
{
    if (! in_array($tab, ['nepredate', 'predate', 'retururi'], true)) {
        return response()->json(['success' => false, 'message' => 'Invalid tab'], 400);
    }

    try {
        $user = $request->user();
        if ($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $swapped = $request->input('swapped', '0') === '1';
        $stats = $this->expeditiiService->getStats($request, $user, $tab, $swapped);

        return response()->json(['success' => true, 'data' => $stats]);
    } catch (\Exception $e) {
        Log::error('Error loading stats', ['tab' => $tab, 'error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Error loading stats'], 500);
    }
}
```

- [ ] **Step 5.2: Adaugă ruta**

În `routes/expeditii.php`, în interiorul grupului `Route::middleware(['web', 'auth'])`, după rutele existente `shipments/*`:

```php
Route::get('shipments/{tab}/stats', [ListeController::class, 'stats'])
    ->where('tab', 'nepredate|predate|retururi')
    ->name('shipments.stats');
```

- [ ] **Step 5.3: Verifică sintactic**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && php -l app/Http/Controllers/Expeditii/ListeController.php && php -l routes/expeditii.php
```

- [ ] **Step 5.4: Verifică ruta înregistrată**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && php artisan route:list --name=shipments.stats
```
Expected: 1 ruta `GET shipments/{tab}/stats`.

- [ ] **Step 5.5: Test manual**

În browser autenticat:
```
GET /shipments/nepredate/stats?startDate=2026-03-29&endDate=2026-04-29
```
Așteptat: `{ "success": true, "data": { "count": N, "total_weight": ..., "total_ramburs": ... } }`.

- [ ] **Step 5.6: Commit (sugestie)**

```bash
git add app/Http/Controllers/Expeditii/ListeController.php routes/expeditii.php
git commit -m "feat(client): add /shipments/{tab}/stats endpoint"
```

---

## Phase 2 — Frontend Types & Hooks

### Task 6: Adaugă tipuri TypeScript pentru filtre + stats

**Files:**
- Create: `client/resources/js/types/shipments.d.ts`

- [ ] **Step 6.1: Creează fișierul de tipuri**

```ts
// client/resources/js/types/shipments.d.ts

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
```

- [ ] **Step 6.2: Type-check**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && npm run types
```
Expected: `0 errors`.

- [ ] **Step 6.3: Commit (sugestie)**

```bash
git add resources/js/types/shipments.d.ts
git commit -m "feat(client): add Shipments TypeScript types"
```

---

### Task 7: Creează `ShipmentsHeader` component

**Files:**
- Create: `client/resources/js/pages/shipments/shipments-header.tsx`

- [ ] **Step 7.1: Scrie componenta**

```tsx
// client/resources/js/pages/shipments/shipments-header.tsx

import type { ShipmentsTab, ShipmentsStats } from '@/types/shipments';
import { cn } from '@/lib/utils';

interface ShipmentsHeaderProps {
    activeTab: ShipmentsTab;
    onTabChange: (tab: ShipmentsTab) => void;
    tabCounts: Record<ShipmentsTab, number | null>;
    stats: ShipmentsStats | null; // null → ascunde stats
}

const TABS: { value: ShipmentsTab; label: string }[] = [
    { value: 'nepredate', label: 'Nepredate' },
    { value: 'predate', label: 'Predate' },
    { value: 'retururi', label: 'Retururi' },
];

function formatNumber(n: number, opts?: Intl.NumberFormatOptions): string {
    return new Intl.NumberFormat('ro-RO', opts).format(n);
}

export function ShipmentsHeader({ activeTab, onTabChange, tabCounts, stats }: ShipmentsHeaderProps) {
    return (
        <div className="flex items-center gap-4 px-4 py-2 border-b border-border">
            <h2 className="text-base font-bold text-foreground shrink-0">Expediții</h2>

            <div className="inline-flex gap-0.5 p-0.5 bg-muted rounded-md">
                {TABS.map((tab) => {
                    const isActive = tab.value === activeTab;
                    const count = tabCounts[tab.value];
                    return (
                        <button
                            key={tab.value}
                            onClick={() => onTabChange(tab.value)}
                            className={cn(
                                'inline-flex items-center gap-1.5 px-2.5 py-1 text-xs rounded font-medium transition-colors',
                                isActive
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            )}
                        >
                            <span>{tab.label}</span>
                            {count != null && (
                                <span
                                    className={cn(
                                        'px-1.5 rounded-full text-[10px]',
                                        isActive ? 'bg-foreground text-background' : 'bg-border text-muted-foreground'
                                    )}
                                >
                                    {count}
                                </span>
                            )}
                        </button>
                    );
                })}
            </div>

            <div className="flex-1" />

            {stats && (
                <div className="flex gap-2">
                    <Stat label="AWB" value={formatNumber(stats.count)} />
                    <Stat
                        label="Greut."
                        value={`${formatNumber(stats.total_weight, { maximumFractionDigits: 1 })} kg`}
                    />
                    <Stat
                        label="Ramburs"
                        value={`${formatNumber(stats.total_ramburs, { maximumFractionDigits: 0 })} RON`}
                        accent="green"
                    />
                </div>
            )}
        </div>
    );
}

function Stat({ label, value, accent }: { label: string; value: string; accent?: 'green' }) {
    return (
        <div className="flex items-center gap-1.5 px-2.5 py-1 border border-border rounded-md bg-muted/30">
            <span className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
                {label}
            </span>
            <span
                className={cn(
                    'text-xs font-bold font-mono',
                    accent === 'green' ? 'text-emerald-600' : 'text-foreground'
                )}
            >
                {value}
            </span>
        </div>
    );
}
```

- [ ] **Step 7.2: Type-check + lint**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && npm run types && npm run lint
```
Expected: 0 errors. Dacă `cn` nu e exportat din `@/lib/utils`, verifică ce util are utility merging (probabil `tailwind-merge` + `clsx`).

- [ ] **Step 7.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/shipments-header.tsx
git commit -m "feat(client): add ShipmentsHeader component"
```

---

### Task 8: Creează `ShipmentsFilterBar` component

**Files:**
- Create: `client/resources/js/pages/shipments/shipments-filter-bar.tsx`

- [ ] **Step 8.1: Scrie componenta (search + butoane dropdown)**

Aceasta e o componentă "shell" — pentru moment, butoanele Date / Status / Tip / Județ sunt placeholder-e care **dispatch onChange cu valori statice/date din popoverul intern** (Calendar pentru date, simple `<select>` pentru restul). Polish-ul UI-ului per dropdown poate fi în Plan 3.

```tsx
// client/resources/js/pages/shipments/shipments-filter-bar.tsx

import { Search } from 'lucide-react';
import { Calendar } from '@/components/ui/primereact/calendar';
import type { ShipmentsTab, ShipmentsFilters, TipObj } from '@/types/shipments';
import { JUDETE } from '@/lib/judete';
import { useState, useEffect, useRef, type ReactNode } from 'react';

interface ShipmentsFilterBarProps {
    tab: ShipmentsTab;
    filters: ShipmentsFilters;
    onChange: (next: ShipmentsFilters) => void;
    /** Slot pentru acțiuni primare (dreapta), ex: <Button>+ AWB nou</Button> */
    actions?: ReactNode;
}

export function ShipmentsFilterBar({ tab, filters, onChange, actions }: ShipmentsFilterBarProps) {
    // Search debounce (400ms)
    const [searchValue, setSearchValue] = useState(filters.q);
    const searchTimer = useRef<number | null>(null);

    useEffect(() => {
        setSearchValue(filters.q);
    }, [filters.q]);

    const handleSearchChange = (value: string) => {
        setSearchValue(value);
        if (searchTimer.current) window.clearTimeout(searchTimer.current);
        searchTimer.current = window.setTimeout(() => {
            onChange({ ...filters, q: value });
        }, 400);
    };

    const handleDateChange = (which: 'start' | 'end', date: Date | null) => {
        if (!date) return;
        const ymd = formatLocalYmd(date);
        if (which === 'start') {
            onChange({ ...filters, dateStart: ymd });
        } else {
            onChange({ ...filters, dateEnd: ymd });
        }
    };

    return (
        <div className="flex flex-wrap items-center gap-1.5 px-4 py-2 bg-muted/30 border-b border-border">
            <div className="relative flex-1 min-w-[180px]">
                <Search className="absolute left-2 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
                <input
                    value={searchValue}
                    onChange={(e) => handleSearchChange(e.target.value)}
                    placeholder="Caută AWB, destinatar, expeditor, localitate..."
                    className="w-full pl-7 pr-2 py-1 text-xs border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                />
            </div>

            <Calendar
                value={parseYmd(filters.dateStart)}
                onChange={(e: { value: Date | null }) => handleDateChange('start', e.value)}
                readOnlyInput
                showIcon
                dateFormat="dd.mm.yy"
                placeholder="De la"
                maxDate={parseYmd(filters.dateEnd) ?? undefined}
                className="w-auto text-xs"
                showButtonBar
            />
            <Calendar
                value={parseYmd(filters.dateEnd)}
                onChange={(e: { value: Date | null }) => handleDateChange('end', e.value)}
                readOnlyInput
                showIcon
                dateFormat="dd.mm.yy"
                placeholder="Până la"
                minDate={parseYmd(filters.dateStart) ?? undefined}
                maxDate={new Date()}
                className="w-auto text-xs"
                showButtonBar
            />

            {tab !== 'nepredate' && (
                <select
                    value={filters.status ?? ''}
                    onChange={(e) => onChange({ ...filters, status: e.target.value || null })}
                    className="px-2 py-1 text-xs border border-border rounded bg-background"
                >
                    <option value="">Toate statusurile</option>
                    {/* Valorile reale se vor inventaria — placeholder pentru Plan 1 */}
                </select>
            )}

            <select
                value={filters.tipObj ?? ''}
                onChange={(e) => {
                    const v = e.target.value;
                    onChange({ ...filters, tipObj: v ? (Number(v) as TipObj) : null });
                }}
                className="px-2 py-1 text-xs border border-border rounded bg-background"
            >
                <option value="">Toate tipurile</option>
                <option value="1">Plic</option>
                <option value="2">Colet</option>
                <option value="3">Palet</option>
            </select>

            <select
                value={filters.judet ?? ''}
                onChange={(e) => onChange({ ...filters, judet: e.target.value || null })}
                className="px-2 py-1 text-xs border border-border rounded bg-background"
            >
                <option value="">Toate județele</option>
                {JUDETE.map((j) => (
                    <option key={j.code} value={j.code}>
                        {j.name}
                    </option>
                ))}
            </select>

            <div className="flex-1" />

            {actions}
        </div>
    );
}

function formatLocalYmd(d: Date): string {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

function parseYmd(s: string | null | undefined): Date | null {
    if (!s) return null;
    const [y, m, d] = s.split('-').map(Number);
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
}
```

- [ ] **Step 8.2: Type-check + lint**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && npm run types && npm run lint
```
Va fi roșu pe `@/lib/judete` (creat în Task 11) — vezi mai jos.

- [ ] **Step 8.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/shipments-filter-bar.tsx
git commit -m "feat(client): add ShipmentsFilterBar component"
```

---

### Task 9: Creează `ShipmentsBulkBar` component

**Files:**
- Create: `client/resources/js/pages/shipments/shipments-bulk-bar.tsx`

- [ ] **Step 9.1: Scrie componenta**

```tsx
// client/resources/js/pages/shipments/shipments-bulk-bar.tsx

import type { ReactNode } from 'react';

interface ShipmentsBulkBarProps {
    selectedCount: number;
    onClear: () => void;
    /** Slot pentru butoane bulk (Print, Borderou, Export, etc.) */
    children?: ReactNode;
}

export function ShipmentsBulkBar({ selectedCount, onClear, children }: ShipmentsBulkBarProps) {
    if (selectedCount === 0) return null;

    return (
        <div className="flex items-center gap-2.5 px-4 py-1.5 bg-foreground text-background">
            <span className="font-semibold text-xs">{selectedCount} selectate</span>
            <div className="flex gap-1.5">{children}</div>
            <div className="flex-1" />
            <button
                onClick={onClear}
                className="px-2.5 py-0.5 text-xs border border-background/30 rounded hover:bg-background/10"
            >
                Deselectează
            </button>
        </div>
    );
}
```

- [ ] **Step 9.2: Type-check + lint**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && npm run types && npm run lint
```

- [ ] **Step 9.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/shipments-bulk-bar.tsx
git commit -m "feat(client): add ShipmentsBulkBar component"
```

---

### Task 10: Creează hook `useShipmentsFilters`

**Files:**
- Create: `client/resources/js/pages/shipments/hooks/use-shipments-filters.ts`

- [ ] **Step 10.1: Scrie hook-ul**

Pentru Plan 1 — **state local doar**, fără URL sync (URL sync vine în Plan 3).

```ts
// client/resources/js/pages/shipments/hooks/use-shipments-filters.ts

import { useState, useCallback } from 'react';
import type { ShipmentsFilters } from '@/types/shipments';

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

function ymd(d: Date): string {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

export function useShipmentsFilters() {
    const [filters, setFilters] = useState<ShipmentsFilters>(defaultFilters);

    const updateFilters = useCallback((next: ShipmentsFilters) => {
        setFilters(next);
    }, []);

    const resetFilters = useCallback(() => {
        setFilters(defaultFilters());
    }, []);

    return { filters, setFilters: updateFilters, resetFilters };
}
```

- [ ] **Step 10.2: Type-check**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && npm run types
```

- [ ] **Step 10.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/hooks/use-shipments-filters.ts
git commit -m "feat(client): add useShipmentsFilters hook"
```

---

### Task 11: Creează lista fixă județe RO (`@/lib/judete`)

**Files:**
- Create: `client/resources/js/lib/judete.ts`

- [ ] **Step 11.1: Scrie lista**

Lista ISO 3166-2:RO standard (41 județe + B, IF). Sectoarele BUC pot fi adăugate dacă datele clientului le diferențiază — pentru Plan 1 nu le includem (suficient `B` general).

```ts
// client/resources/js/lib/judete.ts

import type { JudetOption } from '@/types/shipments';

export const JUDETE: JudetOption[] = [
    { code: 'AB', name: 'Alba' },
    { code: 'AR', name: 'Arad' },
    { code: 'AG', name: 'Argeș' },
    { code: 'BC', name: 'Bacău' },
    { code: 'BH', name: 'Bihor' },
    { code: 'BN', name: 'Bistrița-Năsăud' },
    { code: 'BT', name: 'Botoșani' },
    { code: 'BV', name: 'Brașov' },
    { code: 'BR', name: 'Brăila' },
    { code: 'B',  name: 'București' },
    { code: 'BZ', name: 'Buzău' },
    { code: 'CS', name: 'Caraș-Severin' },
    { code: 'CL', name: 'Călărași' },
    { code: 'CJ', name: 'Cluj' },
    { code: 'CT', name: 'Constanța' },
    { code: 'CV', name: 'Covasna' },
    { code: 'DB', name: 'Dâmbovița' },
    { code: 'DJ', name: 'Dolj' },
    { code: 'GL', name: 'Galați' },
    { code: 'GR', name: 'Giurgiu' },
    { code: 'GJ', name: 'Gorj' },
    { code: 'HR', name: 'Harghita' },
    { code: 'HD', name: 'Hunedoara' },
    { code: 'IL', name: 'Ialomița' },
    { code: 'IS', name: 'Iași' },
    { code: 'IF', name: 'Ilfov' },
    { code: 'MM', name: 'Maramureș' },
    { code: 'MH', name: 'Mehedinți' },
    { code: 'MS', name: 'Mureș' },
    { code: 'NT', name: 'Neamț' },
    { code: 'OT', name: 'Olt' },
    { code: 'PH', name: 'Prahova' },
    { code: 'SM', name: 'Satu Mare' },
    { code: 'SJ', name: 'Sălaj' },
    { code: 'SB', name: 'Sibiu' },
    { code: 'SV', name: 'Suceava' },
    { code: 'TR', name: 'Teleorman' },
    { code: 'TM', name: 'Timiș' },
    { code: 'TL', name: 'Tulcea' },
    { code: 'VS', name: 'Vaslui' },
    { code: 'VL', name: 'Vâlcea' },
    { code: 'VN', name: 'Vrancea' },
];
```

- [ ] **Step 11.2: Type-check**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && npm run types
```

- [ ] **Step 11.3: Commit (sugestie)**

```bash
git add resources/js/lib/judete.ts
git commit -m "feat(client): add fixed RO județe list"
```

---

### Task 12: Creează hook `useShipmentsStats`

**Files:**
- Create: `client/resources/js/pages/shipments/hooks/use-shipments-stats.ts`

- [ ] **Step 12.1: Scrie hook-ul**

```ts
// client/resources/js/pages/shipments/hooks/use-shipments-stats.ts

import { useEffect, useState, useRef } from 'react';
import axios from '@/lib/axios';
import type { ShipmentsTab, ShipmentsFilters, ShipmentsStats, ShipmentsStatsResponse } from '@/types/shipments';

interface UseShipmentsStatsOptions {
    enabled?: boolean; // dacă false, hook-ul nu fetch-uiește
    debounceMs?: number;
}

export function useShipmentsStats(
    tab: ShipmentsTab,
    filters: ShipmentsFilters,
    options: UseShipmentsStatsOptions = {}
) {
    const { enabled = true, debounceMs = 400 } = options;
    const [stats, setStats] = useState<ShipmentsStats | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const debounceTimer = useRef<number | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        if (!enabled) return;

        if (debounceTimer.current) window.clearTimeout(debounceTimer.current);
        debounceTimer.current = window.setTimeout(async () => {
            // Cancel previous in-flight
            if (abortRef.current) abortRef.current.abort();
            const ctrl = new AbortController();
            abortRef.current = ctrl;

            const params = new URLSearchParams();
            if (filters.q) params.append('q', filters.q);
            if (filters.dateStart) params.append('startDate', filters.dateStart);
            if (filters.dateEnd) params.append('endDate', filters.dateEnd);
            if (filters.judet) params.append('filter_judet', filters.judet);
            if (filters.tipObj) params.append('filter_tip_obj', String(filters.tipObj));
            if (filters.status) params.append('filter_status', filters.status);

            setLoading(true);
            setError(null);
            try {
                const res = await axios.get<ShipmentsStatsResponse>(
                    `/shipments/${tab}/stats?${params.toString()}`,
                    { signal: ctrl.signal }
                );
                if (res.data.success && res.data.data) {
                    setStats(res.data.data);
                } else {
                    setError(res.data.message ?? 'Stats fetch failed');
                }
            } catch (e) {
                if (axios.isCancel(e)) return;
                setError(e instanceof Error ? e.message : 'Stats fetch error');
            } finally {
                setLoading(false);
            }
        }, debounceMs);

        return () => {
            if (debounceTimer.current) window.clearTimeout(debounceTimer.current);
        };
    }, [tab, filters, enabled, debounceMs]);

    return { stats, loading, error };
}
```

- [ ] **Step 12.2: Type-check**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && npm run types
```

- [ ] **Step 12.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/hooks/use-shipments-stats.ts
git commit -m "feat(client): add useShipmentsStats hook"
```

---

## Phase 3 — Generic Table + Nepredate Migration

### Task 13: Creează `awb-table.tsx` generic

**Files:**
- Create: `client/resources/js/pages/shipments/awb-table.tsx`

- [ ] **Step 13.1: Scrie componenta generică**

Extrage din `awb-nepredate.tsx` logica Tabulator: init, AJAX request func, response handler, refresh granular pe `createUpdateDeleteRow` și `printRows`. Ține-o **generică**: primește columns + endpoint + filters + callbacks.

```tsx
// client/resources/js/pages/shipments/awb-table.tsx

import { useEffect, useMemo, useRef, useState } from 'react';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import type { ColumnDefinition } from 'tabulator-tables';
import type { AwbData, UserPrefs } from '@/types';
import type { ShipmentsFilters } from '@/types/shipments';
import { buildAnchoredRegex } from '@/lib/utils';

interface AwbTableProps {
    /** Endpoint URL pentru ajax (ex: shipments.nepredate URL) */
    endpoint: string;
    columns: ColumnDefinition[];
    filters: ShipmentsFilters;
    /** Param suplimentar (ex: swapped pentru nepredate) */
    extraParams?: Record<string, string | number | boolean>;
    prefs?: UserPrefs;
    /** Trigger pentru refresh granular: create/update/delete pe rând */
    createUpdateDeleteRow?: { rowId: number | undefined; newAwbData: AwbData | null; action: 'create' | 'update' | 'delete' | 'bo' | null };
    /** Trigger pentru actualizare „printed_at" pe rânduri */
    printRows?: { rowIds: number[]; printed_by_user: string | null } | null;
    onSelectionChange?: (rows: AwbData[]) => void;
    onRowDblClick?: (row: AwbData) => void;
    /** Callback la dataLoaded — pentru a actualiza counter-e externe */
    onDataLoaded?: (info: { displayed: number; total: number }) => void;
}

const createEmptyResponse = (rows = 100) => ({
    success: true,
    data: { data: [], total: 0, current_page: 1, per_page: rows, last_page: 1, from: null, to: null },
});

export function AwbTable({
    endpoint,
    columns,
    filters,
    extraParams = {},
    prefs,
    createUpdateDeleteRow,
    printRows,
    onSelectionChange,
    onRowDblClick,
    onDataLoaded,
}: AwbTableProps) {
    const tableRef = useRef<HTMLDivElement>(null);
    const tabulatorRef = useRef<Tabulator | null>(null);
    const filtersRef = useRef(filters);
    const extraParamsRef = useRef(extraParams);

    const minCharsFilter = prefs?.min_chars_filter ?? 3;
    const awbRegexp = prefs?.awb_regexp ?? '.*';

    // Keep refs in sync (Tabulator ajaxRequestFunc reads them)
    useEffect(() => {
        filtersRef.current = filters;
        extraParamsRef.current = extraParams;
    }, [filters, extraParams]);

    // Re-fetch when filters change (after init)
    useEffect(() => {
        if (tabulatorRef.current) {
            tabulatorRef.current.setData();
        }
    }, [filters.q, filters.dateStart, filters.dateEnd, filters.status, filters.tipObj, filters.judet]);

    // Memoize columns to prevent unnecessary re-init
    const memoColumns = useMemo(() => columns, [columns]);

    // Init Tabulator
    useEffect(() => {
        if (tableRef.current && !tabulatorRef.current) {
            let isMounted = true;

            tabulatorRef.current = new Tabulator(tableRef.current, {
                height: 720,
                progressiveLoad: 'scroll',
                progressiveLoadScrollMargin: 50,
                paginationSize: 100,
                ajaxURL: endpoint,
                ajaxParams: {},
                selectableRowsPersistence: false,
                ajaxRequestFunc: async (_url, _config, params) => {
                    const empty = createEmptyResponse(params.size || 100);
                    if (!isMounted) return Promise.resolve(empty);

                    const f = filtersRef.current;
                    const ep = extraParamsRef.current;
                    const u = new URLSearchParams();

                    // Extra params (ex: swapped)
                    Object.entries(ep).forEach(([k, v]) => u.append(k, String(v)));

                    // Date range
                    if (f.dateStart) u.append('startDate', f.dateStart);
                    if (f.dateEnd) u.append('endDate', f.dateEnd);

                    // Global search q
                    if (f.q && f.q.length >= minCharsFilter) u.append('q', f.q);

                    // Filter bar dropdowns → filter_*
                    if (f.judet) u.append('filter_judet', f.judet);
                    if (f.tipObj) u.append('filter_tip_obj', String(f.tipObj));
                    if (f.status) u.append('filter_status', f.status);

                    // Pagination
                    if (params.page) u.append('page', String(params.page));
                    if (params.size) u.append('rows', String(params.size));

                    // Sort
                    if (params.sort && params.sort.length > 0) {
                        u.append('sortField', params.sort[0].field);
                        u.append('sortOrder', params.sort[0].dir);
                    }

                    // Header column filters (păstrate din Tabulator)
                    let abort = false;
                    if (params.filter && Array.isArray(params.filter)) {
                        for (const ft of params.filter) {
                            const value = ft.value;
                            if (value == null || value === '') continue;
                            if (ft.field === 'tip_obj') {
                                u.append('filter_tip_obj', String(value));
                                continue;
                            }
                            if (ft.field === 'awb' && awbRegexp) {
                                const rx = buildAnchoredRegex(awbRegexp);
                                if (!rx.test(String(value).trim())) {
                                    abort = true;
                                    continue;
                                }
                                u.append('filter_awb', String(value).trim());
                                continue;
                            }
                            if (typeof value === 'string') {
                                const v = value.trim();
                                if (v.length < minCharsFilter) { abort = true; continue; }
                                u.append(`filter_${ft.field}`, v);
                            } else if (typeof value === 'number') {
                                u.append(`filter_${ft.field}`, String(value));
                            }
                        }
                    }

                    if (abort) return Promise.resolve(empty);

                    try {
                        const axios = (await import('@/lib/axios')).default;
                        const res = await axios.get(`${endpoint}?${u.toString()}`, {
                            headers: { 'Content-Type': 'application/json' },
                        });
                        if (res.data?.success) return res.data;
                        return empty;
                    } catch (e) {
                        console.error('AwbTable ajax error', e);
                        return Promise.reject(e);
                    }
                },
                ajaxResponse: (_url, _params, response) => {
                    if (!response?.data) return { data: [], last_page: 1 };
                    return { data: response.data.data, last_page: response.data.last_page };
                },
                dataLoaderError: 'Eroare la încărcarea datelor',
                columns: memoColumns,
                headerFilterLiveFilterDelay: 600,
                filterMode: 'remote',
                sortMode: 'remote',
                layout: 'fitColumns',
                resizableColumnFit: true,
                placeholder: 'Nu au fost gasite AWB-uri.',
            });

            tabulatorRef.current.on('rowSelectionChanged', (data) => {
                if (onSelectionChange) onSelectionChange(data as AwbData[]);
            });

            if (onRowDblClick) {
                tabulatorRef.current.on('rowDblClick', (_e, row) => {
                    onRowDblClick(row.getData() as AwbData);
                });
            }

            tabulatorRef.current.on('dataLoaded', () => {
                const data = (tabulatorRef.current?.getData() as AwbData[]) || [];
                if (onDataLoaded) onDataLoaded({ displayed: data.length, total: 0 }); // total preluat din response separat dacă e nevoie
            });

            return () => {
                isMounted = false;
                if (tabulatorRef.current) {
                    tabulatorRef.current.destroy();
                    tabulatorRef.current = null;
                }
            };
        }
    }, [endpoint, memoColumns, awbRegexp, minCharsFilter, onSelectionChange, onRowDblClick, onDataLoaded]);

    // Granular refresh pe create/update/delete row
    useEffect(() => {
        if (!tabulatorRef.current || !createUpdateDeleteRow) return;
        const { rowId, newAwbData, action } = createUpdateDeleteRow;
        if (action === 'create' && newAwbData?.id !== undefined) {
            tabulatorRef.current.addRow(newAwbData, true).then((row) => row.reformat());
        } else if (action === 'update' && rowId !== undefined && newAwbData) {
            const row = tabulatorRef.current.getRow(rowId);
            if (row) row.update(newAwbData).then(() => row.reformat());
        } else if (action === 'delete' && rowId !== undefined) {
            const row = tabulatorRef.current.getRow(rowId);
            if (row) row.delete();
        } else if (action === 'bo') {
            tabulatorRef.current.setData();
        }
    }, [createUpdateDeleteRow]);

    // Print rows update
    useEffect(() => {
        if (!tabulatorRef.current || !printRows) return;
        const { rowIds, printed_by_user } = printRows;
        if (!rowIds || !printed_by_user) return;
        const printedAt = new Date().toISOString();
        rowIds.forEach((id) => {
            const row = tabulatorRef.current?.getRow(id);
            const data = row?.getData() as AwbData | undefined;
            if (row && data) {
                row.update({ ...data, can_update: false, printed_at: printedAt, printed_by_user });
                row.reformat();
                row.deselect();
            }
        });
        tabulatorRef.current?.deselectRow();
    }, [printRows]);

    return (
        <div
            ref={tableRef}
            className="w-full tabulator-container rounded-lg border border-border overflow-hidden bg-background"
        />
    );
}
```

- [ ] **Step 13.2: Type-check + lint**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && npm run types && npm run lint
```

Posibilă problemă: `params.filter` în Tabulator types. Dacă lint/tsc reclamă, cast la `any` sau folosește tipul corect din `tabulator-tables`. Existing code în `awb-nepredate.tsx` linia 278 folosește `params.filter || []` — preia același pattern.

- [ ] **Step 13.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/awb-table.tsx
git commit -m "feat(client): add generic AwbTable component"
```

---

### Task 14: Creează `tabs/nepredate-tab.tsx`

**Files:**
- Create: `client/resources/js/pages/shipments/tabs/nepredate-tab.tsx`

- [ ] **Step 14.1: Scrie componenta de tab**

```tsx
// client/resources/js/pages/shipments/tabs/nepredate-tab.tsx

import { useMemo, useState, useCallback } from 'react';
import { CheckSquare, Printer } from 'lucide-react';
import { nepredate } from '@/routes/shipments';
import {
    baseColumns as nepredateBaseColumns,
    actionsColumns as nepredateActionsColumns,
} from '@/services/shipments/nepredate-service';
import type { AwbData, UserPrefs } from '@/types';
import type { ShipmentsFilters } from '@/types/shipments';
import type { ColumnDefinition } from 'tabulator-tables';
import { Button } from '@/components/ui/button';
import { AwbTable } from '@/pages/shipments/awb-table';
import { ShipmentsBulkBar } from '@/pages/shipments/shipments-bulk-bar';
import { Dialog } from '@/components/ui/primereact/dialog';
import { InfoAwb } from '@/components/info-awb';
import { formatDate } from '@/lib/formatters';

interface NepredateTabProps {
    filters: ShipmentsFilters;
    prefs?: UserPrefs;
    isPrinting: boolean;
    createUpdateDeleteRow?: Parameters<typeof AwbTable>[0]['createUpdateDeleteRow'];
    printRows?: Parameters<typeof AwbTable>[0]['printRows'];
    onCreateAwb: () => void;
    onEditAwb: (awb: AwbData) => void;
    onPrintRows: (rowIds: number[]) => void;
    onDeleteAwb: (awb: AwbData) => void;
}

export function NepredateTab({
    filters,
    prefs,
    isPrinting,
    createUpdateDeleteRow,
    printRows,
    onCreateAwb,
    onEditAwb,
    onPrintRows,
    onDeleteAwb,
}: NepredateTabProps) {
    const [selectedRows, setSelectedRows] = useState<AwbData[]>([]);
    const [viewAwb, setViewAwb] = useState<AwbData | null>(null);

    const handleView = useCallback((awb: AwbData) => setViewAwb(awb), []);

    const columns: ColumnDefinition[] = useMemo(() => {
        const base = nepredateBaseColumns({ onView: handleView }).map((col) => {
            if (col.field === 'piese') {
                return {
                    ...col,
                    bottomCalc: 'sum' as const,
                    bottomCalcFormatter: (cell: { getValue: () => number }) => `<strong>${cell.getValue()}</strong>`,
                };
            }
            if (col.field === 'greutate') {
                return {
                    ...col,
                    bottomCalc: 'sum' as const,
                    bottomCalcFormatter: (cell: { getValue: () => number }) => `<strong>${cell.getValue().toFixed(2)} kg</strong>`,
                };
            }
            return col;
        });
        const actions = nepredateActionsColumns({
            onEdit: onEditAwb,
            onPrint: (ids) => onPrintRows(ids),
            onDelete: onDeleteAwb,
        });
        return [...base, actions];
    }, [handleView, onEditAwb, onPrintRows, onDeleteAwb]);

    const handlePrintSelected = () => {
        const ids = selectedRows.map((r) => r.id).filter((id): id is number => typeof id === 'number');
        if (ids.length > 0) onPrintRows(ids);
    };

    const handleClearSelection = () => setSelectedRows([]);

    return (
        <>
            {/* Filter bar primary action: + AWB nou */}
            {/* Notă: butonul "+ AWB nou" e injectat via slot din index.tsx în ShipmentsFilterBar — vezi Task 15. */}

            <ShipmentsBulkBar selectedCount={selectedRows.length} onClear={handleClearSelection}>
                <button
                    onClick={handlePrintSelected}
                    disabled={isPrinting}
                    className="px-2.5 py-0.5 text-xs bg-background text-foreground rounded font-semibold hover:bg-background/90 disabled:opacity-50"
                >
                    <Printer className="inline h-3 w-3 mr-1" />
                    {isPrinting ? 'Se printează...' : 'Print'}
                </button>
                {/* Borderou: în Plan 1 doar Nepredate; pentru moment, folosim flux existent dacă e disponibil */}
            </ShipmentsBulkBar>

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

            {viewAwb && (
                <Dialog
                    visible={!!viewAwb}
                    position="top"
                    draggable={false}
                    onHide={() => setViewAwb(null)}
                    header={
                        <div className="flex flex-wrap items-center gap-3">
                            <span>{viewAwb.awb}</span>
                            {viewAwb.status && viewAwb.data_status && (
                                <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-800">
                                    {viewAwb.status} la {formatDate(viewAwb.data_status)}
                                </span>
                            )}
                        </div>
                    }
                    modal
                    style={{ width: '50vw' }}
                    breakpoints={{ '960px': '75vw', '641px': '100vw' }}
                >
                    <InfoAwb
                        selectedAwb={viewAwb}
                        onEdit={(awb) => { setViewAwb(null); onEditAwb(awb); }}
                        onPrint={(rowIds) => { setViewAwb(null); onPrintRows(rowIds); }}
                        onDelete={(awb) => { setViewAwb(null); onDeleteAwb(awb); }}
                        onClose={() => setViewAwb(null)}
                    />
                </Dialog>
            )}
        </>
    );
}

// Slot suplimentar — renunțat la Borderou bulk în Plan 1 pentru simplitate (Plan 2 reintroduce dacă e folosit)
// Dacă mode='borderouri' din awb-nepredate.tsx vechi e necesar și aici, se va trata în Plan 2 când reintegrăm /borderouri.
```

**Notă explicită despre Borderou bulk**: în `awb-nepredate.tsx` vechi, butonul "Borderou" apare doar când `mode === 'borderouri'` (când componenta e folosită în pagina /borderouri, nu în /shipments). Pe `/shipments` el nu apare. Deci pentru tab-ul Nepredate al `/shipments`, **nu avem buton Borderou** — doar Print bulk. Confirmat din codul existent.

- [ ] **Step 14.2: Type-check + lint**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && npm run types && npm run lint
```

- [ ] **Step 14.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/tabs/nepredate-tab.tsx
git commit -m "feat(client): add NepredateTab component"
```

---

### Task 15: Refactor `index.tsx` — folosește noul shell pentru Nepredate

**Files:**
- Modify: `client/resources/js/pages/shipments/index.tsx`

- [ ] **Step 15.1: Rescrie `index.tsx` cu shell-ul nou**

Ține orchestrarea (dialog-uri, toast, stats hook). Pentru `nepredate` randează componente noi; pentru `predate` / `retururi` păstrează componentele vechi (vor fi migrate în Plan 2).

```tsx
// client/resources/js/pages/shipments/index.tsx

import { useState, useCallback, useEffect, useRef } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { SharedData, BreadcrumbItem, AwbData, UserPrefs } from '@/types';
import type { ShipmentsTab } from '@/types/shipments';
import { index as shipmentsIndex, nepredate as nepredateRoute, predate as predateRoute, retururi as retururiRoute } from '@/routes/shipments';
import Toast, { ToastRef, showError, showSuccess, type ToastMessageState } from '@/components/ui/primereact/toast';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Dialog } from '@/components/ui/primereact/dialog';
import { Button } from '@/components/ui/button';
import { ArrowRightFromLine } from 'lucide-react';
import AwbForm from '@/pages/awb/awb-form';
import { EditAwbDialog } from '@/pages/shipments/edit-awb-dialog';
import { createAwb, deleteAwb, getById, updateAwb, downloadPredateConfirmation } from '@/services/awbs';
import { useAwbPrint } from '@/hooks/use-awb-print';

import { ShipmentsHeader } from '@/pages/shipments/shipments-header';
import { ShipmentsFilterBar } from '@/pages/shipments/shipments-filter-bar';
import { useShipmentsFilters } from '@/pages/shipments/hooks/use-shipments-filters';
import { useShipmentsStats } from '@/pages/shipments/hooks/use-shipments-stats';
import { NepredateTab } from '@/pages/shipments/tabs/nepredate-tab';

// Componente vechi păstrate temporar pentru tabs nemigrate
import { AwbPredate } from '@/pages/shipments/awb-predate';
import { AwbRetururi } from '@/pages/shipments/awb-retururi';

export default function ShipmentsIndex() {
    const { auth, pcs, prefs } = usePage<SharedData & { prefs: UserPrefs }>().props;
    const toast = useRef<ToastRef>(null);

    const [activeTab, setActiveTab] = useState<ShipmentsTab>('nepredate');
    const { filters, setFilters } = useShipmentsFilters();

    // Stats — doar pentru Nepredate în Plan 1 (alte tabs primesc null → header ascunde)
    const { stats } = useShipmentsStats(activeTab, filters, { enabled: activeTab === 'nepredate' });

    // Refresh triggers (din vechiul flow)
    const [createUpdateDeleteRow, setCreateUpdateDeleteRow] = useState({ rowId: undefined, newAwbData: null, action: null } as { rowId: number | undefined, newAwbData: AwbData | null, action: 'create' | 'update' | 'delete' | 'bo' | null });
    const [printRows, setPrintRows] = useState({ rowIds: [], printed_by_user: null } as { rowIds: number[]; printed_by_user: string | null });

    // Dialogs state
    const [showEditDialog, setShowEditDialog] = useState(false);
    const [editingAwb, setEditingAwb] = useState<AwbData | null>(null);
    const [loadingAwb, setLoadingAwb] = useState(false);
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [awbToDelete, setAwbToDelete] = useState<AwbData | null>(null);
    const [showToast, setShowToast] = useState<ToastMessageState | null>(null);

    const { printing, handlePrintSelected, AwbPrintDialog } = useAwbPrint({
        prefs,
        userName: auth.user.user,
        onToast: setShowToast,
        onPrinted: (rowIds, printedByUser) => setPrintRows({ rowIds, printed_by_user: printedByUser }),
    });

    useEffect(() => {
        if (showToast) {
            if (showToast.severity === 'success') showSuccess(toast, showToast.summary, showToast.detail || 'Operațiune reușită.');
            else if (showToast.severity === 'error') showError(toast, showToast.summary, showToast.detail || 'A apărut o eroare.');
        }
    }, [showToast]);

    // Edit flow
    const handleEditAwb = useCallback(async (awb: AwbData) => {
        setLoadingAwb(true);
        setShowEditDialog(true);
        const result = await getById(awb.id || 0);
        setLoadingAwb(false);
        if (result.success && result.data) {
            setEditingAwb(result.data);
        } else {
            setShowToast({ severity: 'error', summary: 'Eroare', detail: result.message || 'Eroare la încărcarea AWB.' });
            setShowEditDialog(false);
        }
    }, []);

    const handleCreateAwb = useCallback(async (formData: AwbData) => {
        const result = await createAwb(formData);
        if (result.success) {
            setShowToast({ severity: 'success', summary: 'Succes', detail: result.message || `AWB-ul ${result.data?.id || ''} creat!` });
            setShowCreateDialog(false);
            setCreateUpdateDeleteRow({ rowId: undefined, newAwbData: result.data as AwbData || null, action: 'create' });
            return;
        }
        setShowToast({ severity: 'error', summary: 'Eroare', detail: result.message || 'Eroare la crearea AWB.' });
    }, []);

    const handleUpdateAwb = useCallback(async (formData: AwbData) => {
        if (!editingAwb) return;
        const result = await updateAwb(editingAwb.id, formData);
        if (result.success) {
            setShowToast({ severity: 'success', summary: 'Succes', detail: result.message || 'AWB actualizat!' });
            setShowEditDialog(false);
            setEditingAwb(null);
            setCreateUpdateDeleteRow({ rowId: editingAwb.id, newAwbData: result.data as AwbData || null, action: 'update' });
        } else {
            setShowToast({ severity: 'error', summary: 'Eroare', detail: result.message || 'Eroare actualizare AWB.' });
        }
    }, [editingAwb]);

    const handleDeleteAwb = useCallback((awb: AwbData) => {
        setAwbToDelete(awb);
        setShowDeleteConfirm(true);
    }, []);

    const handleConfirmDelete = useCallback(async () => {
        if (!awbToDelete) return;
        const result = await deleteAwb(awbToDelete.id || 0);
        if (result.success) {
            setShowToast({ severity: 'success', summary: 'Succes', detail: result.message || 'AWB șters!' });
            setCreateUpdateDeleteRow({ rowId: awbToDelete.id, newAwbData: null, action: 'delete' });
        } else {
            setShowToast({ severity: 'error', summary: 'Eroare', detail: result.message || 'Eroare la ștergere.' });
        }
        setAwbToDelete(null);
    }, [awbToDelete]);

    const handleDownloadConfirmation = async (awb: AwbData) => {
        if (!awb.id || !awb.awb) return;
        try {
            await downloadPredateConfirmation(awb.id, awb.awb);
        } catch (e) {
            showError(toast, 'Eroare', e instanceof Error ? e.message : 'Eroare descărcare confirmare');
        }
    };

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Liste expeditii', href: shipmentsIndex().url }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Liste expeditii" />

            <ShipmentsHeader
                activeTab={activeTab}
                onTabChange={setActiveTab}
                tabCounts={{ nepredate: null, predate: null, retururi: null }}
                stats={activeTab === 'nepredate' ? stats : null}
            />

            {activeTab === 'nepredate' && (
                <>
                    <ShipmentsFilterBar
                        tab="nepredate"
                        filters={filters}
                        onChange={setFilters}
                        actions={
                            <Button size="sm" onClick={() => setShowCreateDialog(true)} className="text-xs">
                                <ArrowRightFromLine className="h-3.5 w-3.5 mr-1" />
                                AWB nou
                            </Button>
                        }
                    />
                    <NepredateTab
                        filters={filters}
                        prefs={prefs}
                        isPrinting={printing}
                        createUpdateDeleteRow={createUpdateDeleteRow}
                        printRows={printRows}
                        onCreateAwb={() => setShowCreateDialog(true)}
                        onEditAwb={handleEditAwb}
                        onPrintRows={handlePrintSelected}
                        onDeleteAwb={handleDeleteAwb}
                    />
                </>
            )}

            {activeTab === 'predate' && (
                <div className="p-4">
                    <AwbPredate
                        onDownloadConfirmare={handleDownloadConfirmation}
                        prefs={prefs}
                    />
                </div>
            )}

            {activeTab === 'retururi' && (
                <div className="p-4">
                    <AwbRetururi
                        onDownloadConfirmare={handleDownloadConfirmation}
                        prefs={prefs}
                    />
                </div>
            )}

            {/* Dialogs (păstrate ca acum) */}
            {loadingAwb && showEditDialog && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white p-6 rounded-lg">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto" />
                        <p className="mt-4">Se încarcă...</p>
                    </div>
                </div>
            )}

            {!loadingAwb && showEditDialog && editingAwb && (
                <EditAwbDialog
                    visible={showEditDialog && !loadingAwb}
                    onVisibleChange={setShowEditDialog}
                    awb={editingAwb}
                    auth={auth}
                    pcs={pcs}
                    prefs={prefs}
                    onUpdate={handleUpdateAwb}
                />
            )}

            {showCreateDialog && (
                <Dialog
                    visible={showCreateDialog}
                    onHide={() => setShowCreateDialog(false)}
                    header="AWB nou"
                    modal
                    maximized
                >
                    <AwbForm
                        auth={auth}
                        pcs={pcs}
                        prefs={prefs}
                        mode="create-from-shipment"
                        onCreate={handleCreateAwb}
                        onCancel={() => setShowCreateDialog(false)}
                    />
                </Dialog>
            )}

            <Toast ref={toast} />
            {AwbPrintDialog}

            <ConfirmDialog
                open={showDeleteConfirm}
                onOpenChange={setShowDeleteConfirm}
                title="Confirmare ștergere"
                description={`Sunteți sigur că doriți să ștergeți AWB ${awbToDelete?.awb}?`}
                confirmLabel="Șterge"
                cancelLabel="Anulează"
                variant="destructive"
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
```

**Notă**: am șters `Tabs / TabsList / TabsTrigger` din shadcn — tab-urile sunt acum în `ShipmentsHeader`.

- [ ] **Step 15.2: Type-check + lint**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && npm run types && npm run lint
```

Posibile probleme:
- Importurile `nepredateRoute / predateRoute / retururiRoute` din `@/routes/shipments` — verifică numele exact (în vechiul `index.tsx` sunt `nepredate, predate, retururi`).
- `extraParams` typing — dacă `nepredate-tab.tsx` cere `swapped: '1'` ca string, ajustează tipul în `awb-table.tsx` la `Record<string, string | number | boolean>` (deja așa).

- [ ] **Step 15.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/index.tsx
git commit -m "feat(client): refactor shipments/index to use new shell + NepredateTab"
```

---

### Task 16: Verificare manuală în browser (golden path)

**Files:** none

- [ ] **Step 16.1: Pornește dev server**

În terminal separat:
```bash
cd "/Users/telermarius/dev/DSC APP/client" && npm run dev
```
Și (în alt terminal):
```bash
cd "/Users/telermarius/dev/DSC APP/client" && php artisan serve
```

- [ ] **Step 16.2: Verificare scenariile golden path**

Deschide `http://localhost:8000/shipments` autenticat. Verifică:

1. ✓ **Header**: titlu "Expediții" stânga, tabs (Nepredate / Predate / Retururi) lângă, stats (AWB · Greut. · Ramburs) dreapta.
2. ✓ **Tab Nepredate (default)**:
   - Filter bar cu search + două Calendar (date range) + dropdowns Tip / Județ + buton "AWB nou".
   - Tabelul cu cele 16 coloane existente, pagination scroll, header filters per coloană funcționale.
   - Stats se actualizează după filtrare.
3. ✓ **Search global**: tastează "Cluj" în search → tabelul filtrează după 400ms; același cu "Bucur" / număr AWB.
4. ✓ **Filtru județ**: alege "Cluj" din dropdown → doar AWB-uri cu destinatar în CJ.
5. ✓ **Filtru Tip**: alege "Colet" → doar colete.
6. ✓ **Selecție rânduri**: bifează 2-3 → bulk bar apare cu "X selectate" + "Print" + "Deselectează".
7. ✓ **Edit AWB**: click ✎ pe un rând care are `can_update=true` → dialog edit; salvează → row update în Tabulator.
8. ✓ **Edit ascuns când `can_update=false`**: găsește un AWB deja printat, ✎ nu apare.
9. ✓ **Delete AWB**: click 🗑 → ConfirmDialog → confirmă → row dispare.
10. ✓ **Print individual**: click 🖨 pe un rând → AwbPrintDialog → print → row update `printed_at`.
11. ✓ **Print bulk**: selectează 2+ rânduri → buton "Print" în bulk bar → AwbPrintDialog → print → rândurile se actualizează.
12. ✓ **AWB nou**: click "AWB nou" → dialog Maximized cu AwbForm → submit → toast succes → row nou în Tabulator.
13. ✓ **Tab Predate**: schimbă tab → componenta veche `AwbPredate` se randează cu propria toolbar internă (filter bar nou e ascuns intenționat — Plan 2).
14. ✓ **Tab Retururi**: la fel ca Predate.

- [ ] **Step 16.3: Verifică networking**

În DevTools Network:
- `GET /shipments/nepredate/stats?...` — răspunde 200, body conține count/total_weight/total_ramburs.
- `GET /shipments/nepredate?q=...&filter_judet=CJ&filter_tip_obj=2` — răspunde 200, body filtrat corespunzător.
- Schimbarea filtrelor declanșează 1 request `/stats` (debounce 400ms) + 1 request listă.

- [ ] **Step 16.4: Notează regresii**

Dacă găsești probleme, rezolvă-le iterativ. Comune:
- Tabulator theme pe Tailwind 4 — dacă tabelul arată ne-stilat, verifică `resources/css/` pentru import Tabulator CSS (probabil deja făcut).
- Filter bar wraps urât pe ecran mediu — adjust `flex-wrap` și ordinele în `ShipmentsFilterBar`.

- [ ] **Step 16.5: Commit (sugestie) — orice fix din verificare**

```bash
git add -p
git commit -m "fix(client): regressions found during shipments redesign verification"
```

---

### Task 17: Șterge `awb-nepredate.tsx`

**Files:**
- Delete: `client/resources/js/pages/shipments/awb-nepredate.tsx`

- [ ] **Step 17.1: Verifică cine îl mai folosește**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && grep -rn "awb-nepredate\|AwbNepredate" resources/js --include="*.tsx" --include="*.ts" 2>/dev/null
```

Dacă apare folosit în alte locuri (ex: `pages/borderouri/index.tsx`), **NU șterge** încă. Acel caz folosește `mode='borderouri'` și e separat de `/shipments`. Migrarea acelor consumeri intră în Plan 2 sau ulterior.

- [ ] **Step 17.2: Dacă e folosit doar din `pages/shipments/index.tsx` (acum nemai-importat), șterge**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && rm resources/js/pages/shipments/awb-nepredate.tsx
```

**Dacă e folosit și de `/borderouri`**: lasă-l. Plan 2 / 3 decide cum.

- [ ] **Step 17.3: Final type-check + lint**

```bash
cd "/Users/telermarius/dev/DSC APP/client" && npm run types && npm run lint
```

- [ ] **Step 17.4: Re-verifică `/shipments` în browser**

Asigură-te că nu s-a stricat nimic.

- [ ] **Step 17.5: Commit (sugestie)**

```bash
git add -A resources/js/pages/shipments/awb-nepredate.tsx
git commit -m "chore(client): remove old awb-nepredate.tsx (migrated to NepredateTab)"
```

(Dacă lasi fișierul, sare acest commit.)

---

## Self-Review Checklist (engineer-side, după ce execuți planul)

Înainte de a închide Plan 1, verifică spec-ul (`docs/superpowers/specs/2026-04-29-client-shipments-redesign-design.md`) și bifează ce-a fost livrat:

- [ ] Backend: `q`, `filter_judet`, endpoint `stats` per tab (Phase 1) — Plan 1 ✓
- [ ] Frontend types pentru filtre + stats — Plan 1 ✓
- [ ] `ShipmentsHeader`, `ShipmentsFilterBar`, `ShipmentsBulkBar` — Plan 1 ✓
- [ ] `useShipmentsFilters`, `useShipmentsStats` — Plan 1 ✓
- [ ] `awb-table.tsx` generic + `tabs/nepredate-tab.tsx` — Plan 1 ✓
- [ ] `index.tsx` refactor (tab Nepredate folosește shell-ul nou) — Plan 1 ✓
- [ ] Predate / Retururi rămân pe componentele vechi — Plan 1 ✓ (migrarea e Plan 2)
- [ ] Mobile (`awb-mobile-list.tsx`) — **NU în Plan 1** (Plan 3)
- [ ] URL sync filtre — **NU în Plan 1** (Plan 3)
- [ ] Stats pentru Predate/Retururi — **NU în Plan 1** (Plan 2)
- [ ] Edit/Print/Delete păstrate pe Nepredate cu `can_update` — Plan 1 ✓ (verificat la Task 16)
- [ ] AWB nou (creare) — Plan 1 ✓ (verificat la Task 16)
- [ ] Print bulk — Plan 1 ✓ (verificat la Task 16)
- [ ] 16 coloane vizibile — Plan 1 ✓ (folosite din `nepredate-service.ts` neatinse)

Dacă ceva din Plan 1 e parțial sau lipsește, extinde Plan 1 înainte să treci la Plan 2.

---

## Următorii pași

După ce Plan 1 e merged și verificat în staging/dev:
- **Plan 2**: migrarea Predate + Retururi pe noul shell, plus stats pentru ele.
- **Plan 3**: mobile (`awb-mobile-list.tsx`), URL sync filtre, status filter values, polish vizual final.
