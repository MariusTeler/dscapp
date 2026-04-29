# Redesign `/shipments` — aplicația client

**Data**: 2026-04-29
**Modul**: `client/` (Laravel 13 + Inertia + React 19 + TypeScript + Tailwind 4 + Tabulator 6.4)
**Autor**: Marius Teler

## Context

Pagina `/shipments` afișează listele de AWB-uri ale clientului, organizate în 3 tab-uri: **Nepredate**, **Predate**, **Retururi**. Implementarea actuală e desktop-first, fără strategie mobilă, și conține shell duplicat (controale + Tabulator init) între cele 3 fișiere `awb-nepredate.tsx` (500 lin.), `awb-predate.tsx` (375 lin.), `awb-retururi.tsx` (366 lin.).

Clientul intră pe `/shipments` în principal pentru o **vizualizare operațională** a expedițiilor recente — scanare rapidă peste activitatea ultimelor săptămâni, cu posibilitatea de a căuta granular și a executa acțiuni (print, borderou, edit) per rând sau în lot.

## Obiective

1. **Modern și consistent** cu redesignul recent al app2 operator (același stack: shadcn/Radix + Tailwind 4 + lucide).
2. **Densitate informațională** — toate cele 16 coloane existente păstrate.
3. **Responsive + mobile-friendly** — un singur model mental, tabel pe desktop → listă cu rând compact + expand pe mobil.
4. **Filtre vizibile** — bar global cu search + dropdowns, plus header filters per coloană (granular).
5. **Stats sumare** la nivel de tab pentru contextul operațional (count, greutate totală, ramburs total).
6. **Funcționalități păstrate 1:1** — edit/print/delete per rând, bulk print, bulk borderou (Nepredate), download confirmare predare (Predate), bulk export CSV (Predate, Retururi).

## Non-goals (în afara scopului)

- Nu redesenăm `AwbForm`, `EditAwbDialog`, `InfoAwb` (continuă să fie folosite ca acum).
- Nu modificăm rutele Laravel existente.
- Nu adăugăm bulk delete (acțiune distructivă inexistentă).
- Nu adăugăm saved filter presets.
- Nu adăugăm chips de filtre active sub bară (decis: amânat).
- Nu schimbăm structura de 3 tab-uri (decis: păstrată).

## Decizii de design (validate cu utilizatorul)

| Decizie | Aleasă | Note |
|---|---|---|
| Use case principal | Vizualizare operațională expediții recente | — |
| Structură tab-uri | 3 tab-uri (Nepredate / Predate / Retururi) | Păstrate ca acum |
| Paradigmă listă | Tabel modern dens | 16 coloane, sticky header, frozen edges |
| Strategie mobilă | Rând compact + expand inline | Singur model mental cross-device |
| Filtre | Hibrid — bar deasupra + header filters per coloană retained | Best of both |
| Filtre active vizibile (chips) | Nu, deocamdată | — |
| Header layout | Titlu + tabs (stânga), stats (dreapta) | O singură linie |
| Filtru județ | Lista fixă RO + B + IF + sectoare BUC | ~46 valori |
| Filtru județ aplicat pe | `destinatar_judet_id` | Clientul vrea "ce am trimis în X" |
| Stats sursă | Endpoint separat `GET /shipments/{tab}/stats?...` | Cleaner, nu se schimbă la sortare |
| Status filter | Contextual per tab (ascuns pe Nepredate) | Predate / Retururi îl arată |
| URL sync filtre | Da | Share-uibil, back/forward funcționează |
| Stil vizual | Neutral cu accent shadcn/Tailwind existent | Consistent cu app2 redesign |

## Anatomie pagină (desktop)

```
┌─────────────────────────────────────────────────────────────────────────┐
│ Expediții   [Nepredate 142][Predate 528][Retururi 14]   AWB·Greut·Ramb │ ← header (1 linie)
├─────────────────────────────────────────────────────────────────────────┤
│ [🔍 Caută AWB, destinatar, expeditor, localitate...] [📅] [Status][Tip][Județ]   [+ AWB nou] [⋯] │ ← filter bar
├─────────────────────────────────────────────────────────────────────────┤
│ ▣ 3 selectate    [🖨 Print]  [📦 Borderou]              [Deselectează] │ ← bulk bar (condiționat)
├─────────────────────────────────────────────────────────────────────────┤
│ [☑][#][AWB][Expeditor][Loc.Exp][Destinatar][Loc.Dest][Tip]...[Acțiuni]  │ ← header tabel (sticky)
│ ─── header filters per coloană (input/list) ───                         │
│ ☑ 1  100245678  SC Logistic Plus  Cluj  SC Mobila Vest  Cluj  Colet ... │
│ ☐ 2  100245679  ...                                                     │
│ ───────────────────────────────────────────────────────────────────     │
│ 5 din 142 afișate · sortare: Data ↓     ← scroll orizontal pentru rest │ ← footer
└─────────────────────────────────────────────────────────────────────────┘
```

### Coloane tabel (toate cele 16 existente păstrate)

| # | Câmp | Comportament |
|---|---|---|
| 1 | Checkbox | Frozen-left, selecție rând |
| 2 | `rownum` | Frozen-left, click → InfoAwb dialog |
| 3 | `awb` | Frozen-left, header filter (input), monospace |
| 4 | `expeditor_nume` | Header filter (input) |
| 5 | `expeditor_localitate` | Header filter (input) |
| 6 | `destinatar_nume` | Header filter (input) |
| 7 | `destinatar_localitate` | Header filter (input) |
| 8 | `tip_obj` | Header filter (list: Plic / Colet / Palet), pill colorat |
| 9 | `piese` | Numeric, sumat în footer |
| 10 | `greutate` | Numeric (kg), sumat în footer |
| 11 | `ramburs` | Currency, accent verde |
| 12 | `km_exteriori` | Numeric |
| 13 | `valoare_fara_tva` | Currency |
| 14 | `valoare_tva` | Currency |
| 15 | `data_expeditie` | Format `dd.mm.yy` |
| 16 | Acțiuni | Frozen-right; ✎ Edit (condiționat `can_update`) · 🖨 Print · 🗑 Delete |

Frozen pe stânga: `Checkbox`, `#`, `AWB`. Frozen pe dreapta: `Acțiuni`. Restul scroll orizontal natural în Tabulator (`fitColumns` pe ecrane mari, scroll pe ecrane mici).

### Header

- **Stânga**: titlu `Expediții` (font-bold, 16-18px) + tabs pills compacte cu count per tab (ex: `Nepredate 142`).
- **Dreapta**: 3 carduri stat compacte (border subtil, font monospace pentru valori): `AWB · 142`, `Greut. · 387,5 kg`, `Ramburs · 18.450 RON` (verde).
- Stats reflectă filtrele active (perioadă + restul).

### Filter bar

- **Search global** (input cu lupă, debounce 400ms): caută în `awb`, `expeditor_nume`, `destinatar_nume`, `expeditor_localitate`, `destinatar_localitate` (LIKE OR pe backend).
- **Date range button** (popover Calendar PrimeReact): default ultima lună, max 90 zile (păstrat).
- **Status dropdown** (contextual: doar pe Predate/Retururi). Valorile exacte se inventariază la implementare prin interogarea valorilor distincte din coloana `status` pentru tab-ul respectiv.
- **Tip dropdown**: Plic / Colet / Palet.
- **Județ dropdown**: lista fixă bazată pe ISO 3166-2:RO — cele 41 județe + `B` (Municipiul București) + 6 sectoare (`B1`–`B6`) dacă datele existente diferențiază sectoarele. Se aplică pe `destinatar_judet_id`.
- **Acțiuni primare slot** (dreapta): `+ AWB nou` (Nepredate), `Export CSV` (Predate/Retururi), buton `⋯` pentru acțiuni secundare.

### Bulk bar (condiționat)

Apare deasupra tabelului doar când `selectedRows.length > 0`. Înlocuiește filter bar (sau apare suprapus). Conținut per tab:
- **Nepredate**: `🖨 Print AWB-uri` · `📦 Borderou` · `Deselectează`.
- **Predate**: `📥 Download confirmări` · `Deselectează`.
- **Retururi**: `📥 Export selecție` · `Deselectează`.

## Anatomie mobil (≤ `lg` breakpoint, ~1024px)

```
┌───────────────────────────────────┐
│ Expediții           [≡]            │ ← header restrâns
│ [Nepredate][Predate][Retururi]    │ ← tabs scroll orizontal sau dropdown
│ AWB·142  Greut·387,5  Rb·18.4k   │ ← stats compacte 1 linie sau wrap
├───────────────────────────────────┤
│ [🔍 Search...]    [📅] [Filtre ▾] │ ← filter bar comprimat
├───────────────────────────────────┤
│ ▸ 100245678  [Nou]                │
│   SC Mobila Vest · Cluj   350 RON │ ← rând colapsat
│ ▾ 100245679  [Tranzit]            │
│   Popescu Ion · București      —  │
│   ┌───────────────────────────┐   │
│   │ Data: 12.04 · Tip: Plic  │   │
│   │ Greutate: 0,3 kg · 1 piesă│   │ ← rând expandat
│   │ KM: 498 · Valoare: 12 RON│   │
│   │ [✎ Editează][🖨][🗑]     │   │
│   └───────────────────────────┘   │
│ ▸ 100245680  [Nou]                │
└───────────────────────────────────┘
```

- Tabs pills cu scroll orizontal dacă nu încap.
- Stats wrap în 1-2 linii.
- Search + buton "Filtre" → deschide bottom sheet cu toate filtrele.
- Rând colapsat: AWB + Status pill, Destinatar · Localitate, Ramburs.
- Rând expandat: 2 zone — metadata în grid 2 coloane + butoane acțiune (Edit / Print / Delete).
- `can_update === false` ascunde "Editează" la fel ca pe desktop.
- Modal-urile (`EditAwbDialog`, `ConfirmDialog`) păstrate, redimensionate la 100vw / 100vh pe mobil.

## Componente

### Structură fișiere

```
client/resources/js/pages/shipments/
├── index.tsx                       # orchestrare — tabs + dialogs (≤150 lin.)
├── shipments-header.tsx            # NEW — titlu + tabs + stats
├── shipments-filter-bar.tsx        # NEW — search + dropdowns + actions slot
├── shipments-bulk-bar.tsx          # NEW — apare condiționat la selecție
├── awb-table.tsx                   # NEW — Tabulator generic, replace 3 fișiere
├── awb-mobile-list.tsx             # NEW — listă cu rând compact + expand
├── tabs/
│   ├── nepredate-tab.tsx           # NEW — config columns + bulk actions
│   ├── predate-tab.tsx             # NEW
│   └── retururi-tab.tsx            # NEW
├── edit-awb-dialog.tsx             # neschimbat
└── hooks/
    ├── use-shipments-filters.ts    # NEW — state filtre + URL sync
    └── use-shipments-stats.ts      # NEW — fetch stats per tab
```

Cele 3 fișiere `awb-nepredate.tsx / -predate.tsx / -retururi.tsx` se șterg și logica unică (column defs + acțiuni specifice) se mută în `tabs/*-tab.tsx` (≤100 lin. fiecare).

### Responsabilități

- **`index.tsx`** — orchestrează tabs, păstrează `EditAwbDialog`, `ConfirmDialog`, `AwbPrintDialog`, dialogul create AWB, toast-ul. Pasează callback-uri către tab-ul activ.
- **`shipments-header.tsx`** — primește `activeTab`, `tabCounts`, `stats`. Emite `onTabChange`.
- **`shipments-filter-bar.tsx`** — primește `tab` (pentru a decide dacă afișează Status), `filters` curente, `prefs` (min_chars_filter etc.). Emite `onFiltersChange(filters)`. Slot pentru acțiuni primare.
- **`shipments-bulk-bar.tsx`** — primește `selectedCount`, `actions[]` (slot). Apare doar când `selectedCount > 0`.
- **`awb-table.tsx`** — generic. Primește `endpoint`, `columns` (ColumnDefinition[]), `filters`, `dateRange`, `onSelectionChange`, `onRowAction(action, rowData)`. Inițializează Tabulator, gestionează AJAX, sincronizează cu filtrele externe (refresh `setData()` la `filters` change).
- **`awb-mobile-list.tsx`** — primește același `endpoint` + `filters`, dar randează listă cu Inertia/axios direct. Gestionează expand state local.
- **`tabs/{tab}-tab.tsx`** — config specific: column definitions, slot acțiuni primare (`+ AWB nou` vs `Export CSV`), slot bulk actions, callbacks pentru row actions.
- **`use-shipments-filters.ts`** — state global pentru filtre (`q`, `dateStart`, `dateEnd`, `status`, `tipObj`, `judet`). Sync cu URL prin `URLSearchParams` și `history.replaceState` / Inertia partial reload.
- **`use-shipments-stats.ts`** — `useEffect` ce face fetch la `/shipments/{tab}/stats?...` la fiecare schimbare de filtre, cu debounce.

### Comportament responsive

În `index.tsx` (sau în `awb-table.tsx`):
```tsx
const isMobile = useMediaQuery('(max-width: 1023px)');
return isMobile
  ? <AwbMobileList endpoint={...} filters={...} {...} />
  : <AwbTable endpoint={...} columns={...} filters={...} {...} />;
```

Aceleași date (același endpoint), prezentări diferite. Acțiunile (edit/print/delete) declanșează aceleași callback-uri în `index.tsx`.

## Data flow

### Endpoint-uri existente (păstrate)

- `GET /shipments` — Inertia render `shipments/index`.
- `GET /shipments/nepredate?startDate&endDate&page&rows&sortField&sortOrder&swapped&filter_*` — listă paginată.
- `GET /shipments/predate?...` — similar.
- `GET /shipments/retururi?...` — similar.
- `GET /shipments/predate/export`, `GET /shipments/retururi/export` — CSV export.
- `POST /awb`, `PUT /awb/{id}`, `DELETE /awb/{id}`, `GET /awb/{id}` — CRUD AWB.

### Endpoint-uri noi sau extinse

**1. `GET /shipments/{tab}/stats?startDate&endDate&q&filter_judet&filter_tip_obj&filter_status`**
- Returnează `{ count, total_weight, total_ramburs }` pentru filtrele aplicate.
- Implementare în `ListeController::stats()` + `ExpeditiiService::getStats()`.
- Folosit de `use-shipments-stats.ts`.

**2. List endpoints extinse cu**:
- `q` — string. LIKE OR pe `awb`, `expeditor_nume`, `destinatar_nume`, `expeditor_localitate`, `destinatar_localitate`. Min 3 chars (la fel ca `min_chars_filter`).
- `filter_judet` — string (cod județ, ex: `CJ`). Aplicat doar pe `destinatar_judet_id`.
- `filter_status` — string. Aplicat doar pe Predate / Retururi (ignorat pe Nepredate).

Validare la backend: ignorare valori sub `minCharsFilter` pentru `q` (același pattern ca header filters actuale).

### State filter sync

`use-shipments-filters.ts` ține un obiect:
```ts
type ShipmentsFilters = {
  tab: 'nepredate' | 'predate' | 'retururi';
  q: string;
  dateStart: string; // YYYY-MM-DD
  dateEnd: string;
  status: string | null;
  tipObj: 1 | 2 | 3 | null;
  judet: string | null; // cod (CJ, B, IF, ...)
};
```

URL exemplu: `/shipments?tab=nepredate&start=2026-04-12&end=2026-04-19&q=cluj&judet=CJ&tip=2`.

La mount: parse din URL, fallback la default-uri (ultima lună, fără filtre). La schimbare: `history.replaceState` sau Inertia `router.reload({ only: [], preserveScroll: true })` cu URL update.

## Funcționalități păstrate (must-not-break)

- ✓ Edit per rând în Nepredate cu `can_update` (ascunde butonul când `false`)
- ✓ Print per rând cu `useAwbPrint` și marcare `printed_at` în UI
- ✓ Delete per rând cu `ConfirmDialog`
- ✓ Bulk print pe selecție Nepredate
- ✓ Bulk borderou pe selecție Nepredate (mode='borderouri' în awb-nepredate actual)
- ✓ Download confirmare predare per rând Predate
- ✓ Bulk export CSV Predate (`exportPredate`)
- ✓ Bulk export CSV Retururi (`exportRetururi`)
- ✓ AWB nou (creare) prin `AwbForm` cu `mode='create-from-shipment'`
- ✓ Refresh granular tabel la create/update/delete (folosind `createUpdateDeleteRow` state)
- ✓ Validare interval dată max 90 zile + toast eroare
- ✓ `min_chars_filter` și `awb_regexp` din `prefs`
- ✓ Header filters per coloană (input pentru text, list pentru tip_obj)

## Stil vizual

- **Bază**: shadcn/Radix + Tailwind 4 (deja în proiect). Neutral palette — `bg-background`, `text-foreground`, `border-border`.
- **Status pills**: `Plic` (indigo-100/700), `Colet` (green-100/700), `Palet` (amber-100/700). Pe Predate/Retururi, alte statusuri primesc culori similare.
- **Monospace** pentru AWB și valori numerice (`font-mono`).
- **Accent verde** pentru ramburs (`text-emerald-600` / `text-emerald-700`).
- **Iconițe**: lucide-react (deja în proiect) — `Search`, `Calendar`, `Filter`, `Printer`, `Edit`, `Trash2`, `Package`, `Mail`, `Boxes`, `MoreHorizontal`.
- **Spacing**: dens pe desktop (`p-2` pe celule), mai larg pe mobil (`p-3`).
- **Tabulator theme**: customizat în `resources/css/` să se potrivească cu Tailwind tokens (probabil deja făcut pentru app2 redesign — verificat la implementare).

## Acceptance criteria

1. Toate cele 16 coloane existente sunt vizibile pe desktop, cu același comportament de header filter și sortare.
2. Edit / Print / Delete funcționează identic cu acum, inclusiv `can_update`.
3. Bulk Print + Borderou pe Nepredate, Export CSV pe Predate/Retururi, Download confirmare pe Predate — toate funcționale.
4. Pe mobil (≤1023px), lista se afișează cu rânduri compacte + expand. Aceleași acțiuni sunt accesibile în zona expandată.
5. Search global găsește AWB-uri după AWB, expeditor, destinatar sau localități.
6. Filtrele se reflectă în URL și pot fi share-uite. Reîncărcare pagină păstrează starea.
7. Stats (AWB / Greut. / Ramburs) se actualizează la schimbarea filtrelor sau a tab-ului.
8. Header pe o singură linie, tabs lângă titlu, stats în dreapta.
9. Cele 3 fișiere `awb-{nepredate,predate,retururi}.tsx` sunt șterse și înlocuite cu structura nouă.
10. `index.tsx` ≤ 150 linii (orchestrare + dialogs).

## Considerații / risc

- **Tabulator pe mobil**: nu folosim Tabulator sub `lg` — randăm `awb-mobile-list.tsx` separat. Asta înseamnă două surse de adevăr pentru "ce coloane se afișează". Mitigare: column definitions in `tabs/*-tab.tsx` sunt sursă unică, mobile-list le citește pentru a decide ce câmpuri să afișeze în zona expandată.
- **Refresh granular pe mobil**: pattern-ul `createUpdateDeleteRow` din Tabulator nu se traduce 1:1. Pe mobil, vom folosi React state local cu refresh după edit/delete (refetch listă) — mai simplu, mai puțin optimizat, dar volumul e mai mic pe mobil.
- **Stats endpoint cost**: o cerere în plus per schimbare filtru. Mitigare: debounce 400ms identic cu search; cache pe client cu key `tab+filters`.
- **URL sync conflict cu Tabulator state**: Tabulator are propriul state intern de paginare/sortare. Mitigare: UI filters → URL. Pagination/sortare nu sunt în URL (rămân Tabulator-internal). Dacă vrem și sortare în URL, decidem la implementare.

## Implementation phasing (sugestie pentru plan-ul de implementare)

1. **Faza 1 — Backend extensions**: `q` și `filter_judet` pe list endpoints + stats endpoint nou.
2. **Faza 2 — Componente noi (UI shell)**: `shipments-header`, `shipments-filter-bar`, `shipments-bulk-bar`, hook-uri.
3. **Faza 3 — Tabel generic**: `awb-table.tsx` extras din `awb-nepredate.tsx`. Migrare tab-ul Nepredate ca pilot.
4. **Faza 4 — Migrare Predate + Retururi** la noua arhitectură.
5. **Faza 5 — Mobile**: `awb-mobile-list.tsx` + responsive switch.
6. **Faza 6 — URL sync filtre + stats live**.
7. **Faza 7 — Cleanup**: șterge fișierele vechi, polish vizual.
