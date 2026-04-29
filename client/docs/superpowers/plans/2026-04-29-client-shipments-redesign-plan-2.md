# Plan 2 — Migrare Predate + Retururi pe shell-ul Plan 1

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Migrează tab-urile **Predate** și **Retururi** pe noul shell creat în Plan 1 (header + filter bar + AwbTable generic). După Plan 2, întreg `/shipments` pe desktop folosește același design.

**Architecture:** Reutilizăm `AwbTable`, `ShipmentsFilterBar`, `useShipmentsFilters`, `useShipmentsStats` create în Plan 1. Adăugăm `tabs/predate-tab.tsx` și `tabs/retururi-tab.tsx`. Eliminăm dialogurile interne din componentele vechi (`AwbPredate`, `AwbRetururi`). Stats sunt activate pentru toate 3 tab-urile. CSV export se mută în slot-ul de acțiuni primare al filter bar-ului.

**Tech Stack:** Identic cu Plan 1 — Laravel 13, React 19, TypeScript, Tailwind 4, Tabulator 6.4.

**Reference:**
- Spec: `docs/superpowers/specs/2026-04-29-client-shipments-redesign-design.md`
- Plan 1: `docs/superpowers/plans/2026-04-29-client-shipments-redesign-plan-1.md`

**Branch strategy:** Continuare pe `feat/client-shipments-redesign-plan-1` (Plan 2 commit-uri vor fi pe același branch). Sau creează `feat/client-shipments-redesign-plan-2` pornit din tip-ul Plan 1 dacă vrei PR separat.

---

## File Map

### Files to create

```
client/resources/js/pages/shipments/tabs/
├── predate-tab.tsx                  # NEW (Task 3)
└── retururi-tab.tsx                 # NEW (Task 4)
```

### Files to modify

```
client/resources/js/pages/shipments/index.tsx   # Task 5
```

### Files to delete (Task 7, dacă nu sunt folosite altunde)

```
client/resources/js/pages/shipments/awb-predate.tsx
client/resources/js/pages/shipments/awb-retururi.tsx
```

---

## Phase 1 — Inventariere

### Task 1: Inventariere `predate-service.ts`

**Files:**
- Read-only: `client/resources/js/services/shipments/predate-service.ts`

- [ ] **Step 1.1: Citește fișierul și notează exports**

```bash
cd "/Users/telermarius/dev/DSC APP/.worktrees/shipments-redesign-plan-1/client" && grep -n "^export" resources/js/services/shipments/predate-service.ts
```

Așteptat:
- `export const loadData` (folosit doar internă în awb-predate.tsx)
- `export const baseColumns` (folosit în noul predate-tab.tsx)
- `export const exportPredateCsv` (folosit în noul predate-tab.tsx pentru butonul "Export CSV")

- [ ] **Step 1.2: Citește semnătura `baseColumns()` și `exportPredateCsv()`**

Notează:
- `baseColumns(): ColumnDefinition[]` — fără parametri
- `exportPredateCsv(filters, startDate, endDate)` — verifică tipurile exacte

- [ ] **Step 1.3: Notează coloanele specifice Predate**

Citește array-ul de coloane din `baseColumns()`. Probabil include: AWB, Expeditor, Destinatar, Tip, Greutate, Piese, Ramburs, status (last_ckp / data_status), data_expeditie, etc. Câmpurile care diferă față de Nepredate (probabil status + data ultimei scanări).

---

### Task 2: Inventariere `retururi-service.ts`

**Files:**
- Read-only: `client/resources/js/services/shipments/retururi-service.ts`

- [ ] **Step 2.1: Notează exports**

```bash
cd "/Users/telermarius/dev/DSC APP/.worktrees/shipments-redesign-plan-1/client" && grep -n "^export" resources/js/services/shipments/retururi-service.ts
```

Așteptat: `loadData`, `baseColumns`, `exportRetururiCsv`.

- [ ] **Step 2.2: Notează coloanele specifice Retururi**

Citește array-ul de coloane. Probabil similar cu Predate, plus flag-uri retur (ret_nt, ret_doc, ret_amb, ret_colet) ca pe coloane proprii.

---

## Phase 2 — Crearea componentelor de tab

### Task 3: Creează `tabs/predate-tab.tsx`

**Files:**
- Create: `client/resources/js/pages/shipments/tabs/predate-tab.tsx`

- [ ] **Step 3.1: Scrie componenta**

```tsx
// client/resources/js/pages/shipments/tabs/predate-tab.tsx

import { useMemo, useState, useCallback, useRef } from 'react';
import { Download } from 'lucide-react';
import { predate } from '@/routes/shipments';
import {
    baseColumns as predateBaseColumns,
    exportPredateCsv,
} from '@/services/shipments/predate-service';
import type { AwbData, UserPrefs } from '@/types';
import type { ShipmentsFilters } from '@/types/shipments';
import type { ColumnDefinition } from 'tabulator-tables';
import { AwbTable } from '@/pages/shipments/awb-table';
import { ShipmentsBulkBar } from '@/pages/shipments/shipments-bulk-bar';
import { Dialog } from '@/components/ui/primereact/dialog';
import { InfoAwb } from '@/components/info-awb';
import { formatDate } from '@/lib/formatters';
import Toast, { ToastRef, showSuccess, showError } from '@/components/ui/primereact/toast';

interface PredateTabProps {
    filters: ShipmentsFilters;
    prefs?: UserPrefs;
}

export function PredateTab({ filters, prefs }: PredateTabProps) {
    const [selectedRows, setSelectedRows] = useState<AwbData[]>([]);
    const [viewAwb, setViewAwb] = useState<AwbData | null>(null);
    const toast = useRef<ToastRef>(null);

    const handleView = useCallback((awb: AwbData) => setViewAwb(awb), []);

    const columns: ColumnDefinition[] = useMemo(() => predateBaseColumns(), []);

    const handleClearSelection = () => setSelectedRows([]);

    return (
        <>
            <Toast ref={toast} />

            <ShipmentsBulkBar selectedCount={selectedRows.length} onClear={handleClearSelection}>
                {/* No bulk actions yet for Predate — read-only tab */}
            </ShipmentsBulkBar>

            <AwbTable
                endpoint={predate().url}
                columns={columns}
                filters={filters}
                prefs={prefs}
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
                    <InfoAwb selectedAwb={viewAwb} onClose={() => setViewAwb(null)} />
                </Dialog>
            )}
        </>
    );
}

/**
 * Standalone export action — used as filter-bar action slot from index.tsx.
 * Returns a button that triggers CSV export with current filters.
 */
export function PredateExportButton({ filters }: { filters: ShipmentsFilters }) {
    const toast = useRef<ToastRef>(null);

    const handleExport = async () => {
        try {
            await exportPredateCsv([], filters.dateStart, filters.dateEnd);
            showSuccess(toast, 'Succes', 'Fișier CSV exportat cu succes!');
        } catch (e) {
            showError(toast, 'Eroare', e instanceof Error ? e.message : 'Eroare la exportul CSV');
        }
    };

    return (
        <>
            <Toast ref={toast} />
            <button
                onClick={handleExport}
                className="px-2 py-1 text-xs bg-foreground text-background rounded font-semibold hover:opacity-90 inline-flex items-center gap-1"
            >
                <Download className="h-3.5 w-3.5" />
                Export CSV
            </button>
        </>
    );
}
```

**Notă despre `exportPredateCsv` semnătură**: Plan-ul presupune `(filters, startDate, endDate)`. Verifică în `predate-service.ts` semnătura exactă și ajustează apelul în `handleExport`. Dacă acceptă mai mult (ex: și `q`, `judet`), pasează din `filters`.

- [ ] **Step 3.2: Verifică**

```bash
cd "/Users/telermarius/dev/DSC APP/.worktrees/shipments-redesign-plan-1/client" && npm run types && npm run lint
```

Expected: 1 pre-existing error (app-header.tsx). No new errors.

- [ ] **Step 3.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/tabs/predate-tab.tsx
git commit -m "feat(client): add PredateTab component using new shell"
```

---

### Task 4: Creează `tabs/retururi-tab.tsx`

**Files:**
- Create: `client/resources/js/pages/shipments/tabs/retururi-tab.tsx`

- [ ] **Step 4.1: Scrie componenta (analog cu predate-tab)**

```tsx
// client/resources/js/pages/shipments/tabs/retururi-tab.tsx

import { useMemo, useState, useCallback, useRef } from 'react';
import { Download } from 'lucide-react';
import { retururi } from '@/routes/shipments';
import {
    baseColumns as retururiBaseColumns,
    exportRetururiCsv,
} from '@/services/shipments/retururi-service';
import type { AwbData, UserPrefs } from '@/types';
import type { ShipmentsFilters } from '@/types/shipments';
import type { ColumnDefinition } from 'tabulator-tables';
import { AwbTable } from '@/pages/shipments/awb-table';
import { ShipmentsBulkBar } from '@/pages/shipments/shipments-bulk-bar';
import { Dialog } from '@/components/ui/primereact/dialog';
import { InfoAwb } from '@/components/info-awb';
import { formatDate } from '@/lib/formatters';
import Toast, { ToastRef, showSuccess, showError } from '@/components/ui/primereact/toast';

interface RetururiTabProps {
    filters: ShipmentsFilters;
    prefs?: UserPrefs;
}

export function RetururiTab({ filters, prefs }: RetururiTabProps) {
    const [selectedRows, setSelectedRows] = useState<AwbData[]>([]);
    const [viewAwb, setViewAwb] = useState<AwbData | null>(null);
    const toast = useRef<ToastRef>(null);

    const handleView = useCallback((awb: AwbData) => setViewAwb(awb), []);

    const columns: ColumnDefinition[] = useMemo(() => retururiBaseColumns(), []);

    const handleClearSelection = () => setSelectedRows([]);

    return (
        <>
            <Toast ref={toast} />

            <ShipmentsBulkBar selectedCount={selectedRows.length} onClear={handleClearSelection}>
                {/* No bulk actions yet for Retururi */}
            </ShipmentsBulkBar>

            <AwbTable
                endpoint={retururi().url}
                columns={columns}
                filters={filters}
                prefs={prefs}
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
                    <InfoAwb selectedAwb={viewAwb} onClose={() => setViewAwb(null)} />
                </Dialog>
            )}
        </>
    );
}

export function RetururiExportButton({ filters }: { filters: ShipmentsFilters }) {
    const toast = useRef<ToastRef>(null);

    const handleExport = async () => {
        try {
            await exportRetururiCsv([], filters.dateStart, filters.dateEnd);
            showSuccess(toast, 'Succes', 'Fișier CSV exportat cu succes!');
        } catch (e) {
            showError(toast, 'Eroare', e instanceof Error ? e.message : 'Eroare la exportul CSV');
        }
    };

    return (
        <>
            <Toast ref={toast} />
            <button
                onClick={handleExport}
                className="px-2 py-1 text-xs bg-foreground text-background rounded font-semibold hover:opacity-90 inline-flex items-center gap-1"
            >
                <Download className="h-3.5 w-3.5" />
                Export CSV
            </button>
        </>
    );
}
```

**Notă semnătură**: la fel ca la Predate — verifică `exportRetururiCsv` și ajustează parametrii.

- [ ] **Step 4.2: Verifică**

`npm run types && npm run lint`. Expected: 1 pre-existing error.

- [ ] **Step 4.3: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/tabs/retururi-tab.tsx
git commit -m "feat(client): add RetururiTab component using new shell"
```

---

## Phase 3 — Integrare în `index.tsx`

### Task 5: Update `index.tsx` să folosească noile tab-uri

**Files:**
- Modify: `client/resources/js/pages/shipments/index.tsx`

- [ ] **Step 5.1: Citește fișierul curent**

`Read` resources/js/pages/shipments/index.tsx (Plan 1 versiune).

- [ ] **Step 5.2: Modifică imports**

Schimbă:
```tsx
// Componente vechi păstrate temporar pentru tabs nemigrate
import { AwbPredate } from '@/pages/shipments/awb-predate';
import { AwbRetururi } from '@/pages/shipments/awb-retururi';
```

În:
```tsx
import { PredateTab, PredateExportButton } from '@/pages/shipments/tabs/predate-tab';
import { RetururiTab, RetururiExportButton } from '@/pages/shipments/tabs/retururi-tab';
```

- [ ] **Step 5.3: Activează stats pentru toate tab-urile**

Schimbă:
```tsx
const { stats } = useShipmentsStats(activeTab, filters, { enabled: activeTab === 'nepredate' });
```

În:
```tsx
const { stats } = useShipmentsStats(activeTab, filters);
```

(Default `enabled: true` în hook.) Header primește stats pentru toate 3 tab-urile.

- [ ] **Step 5.4: Înlocuiește branch-urile Predate / Retururi**

Schimbă:
```tsx
{activeTab === 'predate' && (
    <div className="p-4">
        <AwbPredate />
    </div>
)}

{activeTab === 'retururi' && (
    <div className="p-4">
        <AwbRetururi />
    </div>
)}
```

În:
```tsx
{activeTab === 'predate' && (
    <>
        <ShipmentsFilterBar
            tab="predate"
            filters={filters}
            onChange={setFilters}
            actions={<PredateExportButton filters={filters} />}
        />
        <PredateTab filters={filters} prefs={prefs as UserPrefs} />
    </>
)}

{activeTab === 'retururi' && (
    <>
        <ShipmentsFilterBar
            tab="retururi"
            filters={filters}
            onChange={setFilters}
            actions={<RetururiExportButton filters={filters} />}
        />
        <RetururiTab filters={filters} prefs={prefs as UserPrefs} />
    </>
)}
```

- [ ] **Step 5.5: Șterge handler-ul `handleDownloadConfirmation`**

Dacă încă există în fișier (poate a rămas mort din Plan 1), șterge-l. Nu mai e folosit.

- [ ] **Step 5.6: Verifică**

```bash
cd "/Users/telermarius/dev/DSC APP/.worktrees/shipments-redesign-plan-1/client" && npm run types && npm run lint
```

Expected: 1 pre-existing error. No new errors.

- [ ] **Step 5.7: Commit (sugestie)**

```bash
git add resources/js/pages/shipments/index.tsx
git commit -m "feat(client): wire PredateTab + RetururiTab into shipments index"
```

---

## Phase 4 — Verificare + cleanup

### Task 6: Verificare manuală în browser (când ai schema)

**Files:** none

Reprodu pașii din Plan 1 Task 16, dar pentru toate 3 tab-urile:

- [ ] **Step 6.1: `/shipments` → tab Nepredate** — header + filter bar + tabel + bulk bar (la fel ca Plan 1).
- [ ] **Step 6.2: Tab Predate** — header rămâne, filter bar arată dropdown-ul Status (gol pentru moment), butonul "Export CSV" în dreapta. Tabel cu coloane Predate. Stats actualizate.
- [ ] **Step 6.3: Tab Retururi** — analog cu Predate, cu butonul "Export CSV" Retururi.
- [ ] **Step 6.4: Tranziție tabs** — schimbă rapid între tab-uri; stats + filter se actualizează corect.
- [ ] **Step 6.5: Export CSV** — pe Predate / Retururi, click "Export CSV" → fișier descărcat.
- [ ] **Step 6.6: Filtre comune** — search + date range + Tip + Județ funcționează pe toate 3 tab-urile.

Notă: dacă schema DB nu e încă disponibilă, sare acest task. Verificare se face când ai datele.

---

### Task 7: Cleanup — șterge `awb-predate.tsx` și `awb-retururi.tsx`

**Files:**
- Delete (condițional): `client/resources/js/pages/shipments/awb-predate.tsx`
- Delete (condițional): `client/resources/js/pages/shipments/awb-retururi.tsx`

- [ ] **Step 7.1: Verifică folosire alte locuri**

```bash
cd "/Users/telermarius/dev/DSC APP/.worktrees/shipments-redesign-plan-1/client" && grep -rn "awb-predate\|AwbPredate\|awb-retururi\|AwbRetururi" resources/js --include="*.tsx" --include="*.ts" 2>/dev/null
```

Așteptat: doar self-references în fișierele respective (ele mențin export-urile lor interne, dar nimeni nu le mai importă).

Dacă apar usages externe (ex: în `pages/borderouri/...`), **NU șterge** — raportează `DONE_WITH_CONCERNS`.

- [ ] **Step 7.2: Dacă nu e folosit altunde, șterge**

```bash
cd "/Users/telermarius/dev/DSC APP/.worktrees/shipments-redesign-plan-1/client" && rm resources/js/pages/shipments/awb-predate.tsx resources/js/pages/shipments/awb-retururi.tsx
```

- [ ] **Step 7.3: Verifică finală**

`npm run types && npm run lint` — expected 1 pre-existing error, no regression.

- [ ] **Step 7.4: Commit (sugestie)**

```bash
git add -A resources/js/pages/shipments/
git commit -m "chore(client): remove old awb-predate.tsx + awb-retururi.tsx (migrated)"
```

---

## Self-Review Checklist (după execuție)

Verifică spec-ul și bifează:

- [ ] PredateTab folosește AwbTable + ShipmentsFilterBar + ShipmentsBulkBar
- [ ] RetururiTab folosește AwbTable + ShipmentsFilterBar + ShipmentsBulkBar
- [ ] Stats afișate pentru toate 3 tab-urile (header)
- [ ] Filter bar contextual: Status dropdown apare pe Predate/Retururi (chiar dacă opțiunile sunt goale)
- [ ] Export CSV pe Predate + Retururi păstrat (mutat din toolbar internă în filter bar slot)
- [ ] Date range + search + Tip + Județ funcționează pe toate tab-urile (prin `ShipmentsFilters` partajate)
- [ ] InfoAwb dialog (rowDblClick) păstrat pe Predate + Retururi
- [ ] `awb-predate.tsx` și `awb-retururi.tsx` șterse (sau păstrate cu motivare)
- [ ] Type-check + lint la nivelul Plan 1 (1 pre-existing eroare, 0 noi)

Plan 3 (mobile + URL sync + status filter values) urmează după Plan 2.
