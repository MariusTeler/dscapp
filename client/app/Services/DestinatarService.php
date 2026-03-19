<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DestinatarService
{
    /**
     * Get autocomplete suggestions for destinatari
     */
    public function getAutocomplete(int $expeditor_id, int $master_id = -1, string $search = '', int $limit = 100): array
    {
        if($limit > 100) $limit = 100;
        if(empty($search)) {
            return [];
        }

        $query = DB::table('client_destinatari as cld')
                    ->select('cld.id', 'cld.cod_cl', 'cld.nume', 'cld.adresa', 'cld.id_loc', 'lcd.nume_lc', 'lcd.cod_jd', 'lcd.dist_km', 
                             'cld.contact', 'cld.telefon', 'cld.email')
                    ->leftJoin('localitati as lcd', function ($join) {
                        $join->on('cld.id_loc', '=', 'lcd.cod_lc')
                             ->where('lcd.deleted', 0);
                    })
                    ->where('cld.nume', 'like', "{$search}%")
                    ->where('cld.activ', 1);
        if($expeditor_id == $master_id || in_array($master_id, [-1, 0])) {
            $query->where('cld.id_exp', '=', $expeditor_id);
        } else {
            $query->whereIn('cld.id_exp', [$expeditor_id, $master_id]);
        }
        $query->orderBy('cld.nume', 'asc')->limit($limit);
        $items = $query->get()
            ->map(function ($item) {
                //truncate address to 30 chars for better display in autocomplete
                $smallAddress = $item->adresa ?? '';
                if($item->adresa && strlen($item->adresa) > 30) {
                    $smallAddress= substr($item->adresa, 0, 27) . '...';
                }
                return [
                    'value' => $item->cod_cl,
                    'text' => $item->nume,
                    'option' => ($item->nume ?? '') . ' (' . ($item->nume_lc . ', ' ?? '') . $smallAddress . ')',
                    'client_destinatari_id' => $item->id,
                    'contact' => $item->contact ?? null,
                    'telefon' => $item->telefon ?? null,
                    'email' => $item->email ?? null,
                    'adresa' => $item->adresa ?? null,
                    'localitate' => $item->nume_lc ?? null,
                    'localitate_id' => $item->id_loc ?? 0,
                    'judet' => $item->cod_jd ?? null,
                    'km' => $item->dist_km ?? null,
                ];
            })
            ->toArray();
        return $items;
    }

    /**
     * Create a new destinatar
     */
    public function create(User $user, array $data): int
    {
        //'created_at', 'updated_at'
        try {
            //DB::enableQueryLog();
            $id =  DB::table('client_destinatari')
                ->insertGetId([
                    'id_loc' => $data['localitate_id'],
                    'nume' => $data['nume'],
                    'adresa' => $data['adresa'],
                    'contact' => $data['contact'] ?? '',
                    'telefon' => $data['telefon'] ?? '',
                    'email' => $data['email'] ?? null,
                    'activ' => 1,
                    'id_exp' => $user->expeditor_id,
                    'created_at' => now(),
                    'created_by' => $user->id,
                ]);
            //dd(DB::getRawQueryLog());
            //DB::disableQueryLog();
            //return array_merge($data, ['id' => $id]);
            return $id;
        } catch (\Exception $e) {
            throw new \Exception('Failed to update destinatar: ' . $e->getMessage());
        }
        return 0;
    }

    /**
     * Get a single destinatar by ID
     */
    public function getById(int $expeditor_id, int $master_id = -1, int $id): array
    {
        if($id == 0) return [];
        //'cld.email', 'created_at', 'updated_at'
        $item = DB::table('client_destinatari as cld')
            ->select('cld.id', 'cld.created_at', 'cld.updated_at', 'cld.id_loc as localitate_id', 'cld.nume', 'cld.adresa', 'cld.contact', 'cld.telefon', 'cld.email', 'lcd.nume_lc as localitate')
            ->join('localitati as lcd', function ($join) {
                $join->on('cld.id_loc', '=', 'lcd.cod_lc')
                     ->where('lcd.deleted', 0);
            })
            ->where('cld.id', $id)
            ->where('cld.activ', 1);
        if($expeditor_id == $master_id || in_array($master_id, [-1, 0])) {
            $item->where('cld.id_exp', '=', $expeditor_id);
        } else {
            $item->whereIn('cld.id_exp', [$expeditor_id, $master_id]);
        }
        $item = $item->first();

        if (!$item) {
            return [];
        }

        return [
            'id' => $item->id,
            'localitate_id' => $item->localitate_id,
            'localitate' => $item->localitate,
            'nume' => $item->nume,
            'adresa' => $item->adresa,
            'contact' => $item->contact,
            'telefon' => $item->telefon,
            'email' => $item->email ?? null,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    /**
     * Get destinatar for editing
     */
    public function getForEdit(int $expeditor_id, int $master_id = -1, int $id): array
    {
        if($id == 0) return [];
        //'cld.email', 'created_at', 'updated_at'
        $item = DB::table('client_destinatari as cld')
            ->select('cld.id', 'cld.created_at', 'cld.updated_at', 'cld.id_loc as localitate_id', 'cld.nume', 'cld.adresa', 'cld.contact', 'cld.telefon', 'cld.email', 'lcd.nume_lc as localitate')
            ->join('localitati as lcd', function ($join) {
                $join->on('cld.id_loc', '=', 'lcd.cod_lc')
                     ->where('lcd.deleted', 0);
            })
            ->where('cld.id', $id)
            ->where('cld.activ', 1);
        if($expeditor_id == $master_id || in_array($master_id, [-1, 0])) {
            $item->where('cld.id_exp', '=', $expeditor_id);
        } else {
            $item->whereIn('cld.id_exp', [$expeditor_id, $master_id]);
        }
        $item = $item->first();

        if (!$item) {
            return [];
        }

        return [
            'id' => $item->id,
            'localitate_id' => $item->localitate_id,
            'localitate' => $item->localitate,
            'nume' => $item->nume,
            'adresa' => $item->adresa,
            'contact' => $item->contact,
            'telefon' => $item->telefon,
            'email' => $item->email ?? null,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    /**
     * Update a destinatar
     */
    public function update(User $user, int $id, array $data): bool
    {
        if($id == 0) throw new \Exception('Failed to update destinatar: destinatar not found or access denied.');
        try {
            //DB::enableQueryLog();
            $item = DB::table('client_destinatari')
                ->where('id', $id);
            if($user->expeditor_id == $user->master_id || in_array($user->master_id, [-1, 0])) {
                $item->where('id_exp', '=', $user->expeditor_id);
            } else {
                $item->whereIn('id_exp', [$user->expeditor_id, $user->master_id]);
            }
            $item = $item->first();
            if (!$item) {
                throw new \Exception('Failed to update destinatar: destinatar not found or access denied.');
            }

            $affected = DB::table('client_destinatari')
                ->where('id', $id)
                ->update(
                    [
                        'cod_cl' => 0, //reset cod client to force re-generation
                        'id_loc' => $data['localitate_id'],
                        'nume' => $data['nume'],
                        'adresa' => $data['adresa'],
                        'contact' => $data['contact'] ?? '',
                        'telefon' => $data['telefon'] ?? '',
                        'activ' => 1,
                        'email' => $data['email'] ?? null,
                        'updated_at' => now(),
                        'updated_by' => $user->id,
                    ]
                );
            //dd(DB::getRawQueryLog());
            //DB::disableQueryLog();
            return $affected > 0;
        } catch (\Exception $e) {
            throw new \Exception('Failed to update destinatar: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Delete a destinatar
     */
    public function delete(int $expeditor_id, int $master_id = -1, int $id): bool
    {
        if($id == 0) return false;
        try {
            //DB::enableQueryLog();
            $query = DB::table('client_destinatari')
                ->where('id', $id);
            if($expeditor_id == $master_id || in_array($master_id, [-1, 0])) {
                $query->where('id_exp', '=', $expeditor_id);
            } else {
                $query->whereIn('id_exp', [$expeditor_id, $master_id]);
            }
            //dd(DB::getRawQueryLog());
            //DB::disableQueryLog();
            return $query->delete();
        } catch (\Exception $e) {
            throw new \Exception('Failed to delete destinatar: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Get paginated destinatari with filters and sorting
     */
    public function getPaginated(
        int $expeditor_id, 
        int $master_id = -1,
        int $page = 1,
        int $rows = 100,
        string $sortField = 'nume',
        string $sortOrder = 'asc',
        Request $request
    ): array {
        /*
        id: number;
        nume: string;
        localitate_id?: number;
        localitate: string;
        adresa: string;
        contact: string;
        telefon: string;
        email: string;
        created_at: string;
        updated_at: string;
        */
        //'cld.email', 'cld.created_at', 'cld.updated_at', 'lcd.cod_jd as judet'
        $query = DB::table('client_destinatari as cld')
                    ->select('cld.id', 'cld.nume', 'lcd.cod_lc as localitate_id', 'lcd.nume_lc as localitate', 'jd.nume_jd as judet',
                    'cld.adresa', 'cld.contact', 'cld.telefon', 'cld.email', 'cld.created_at', 'cld.updated_at', 
                    'cu.nume as created_by', 'uu.nume as updated_by')
                    ->join('localitati as lcd', function ($join) {
                        $join->on('cld.id_loc', '=', 'lcd.cod_lc')
                             ->where('lcd.deleted', 0);
                    })
                    ->leftJoin('judete as jd', 'lcd.cod_jd', '=', 'jd.cod_jd')
                    ->leftJoin('users as cu', 'cld.created_by', '=', 'cu.id')
                    ->leftJoin('users as uu', 'cld.updated_by', '=', 'uu.id')
                    ->where('cld.activ', 1);
        if($expeditor_id == $master_id || in_array($master_id, [-1, 0])) {
            $query->where('cld.id_exp', '=', $expeditor_id);
        } else {
            $query->whereIn('cld.id_exp', [$expeditor_id, $master_id]);
        }

        // Apply filters
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && !empty($value)) {
                $field = substr($key, 7);
                match ($field) {
                    'nume', 'adresa', 'contact', 'telefon', 'email' => 
                        $query->where('cld.' . $field, 'like', "{$value}%"),
                    'localitate' => 
                        $query->where('lcd.nume_lc', 'like', "{$value}%"),
                    'judet' => 
                        $query->where('jd.nume_jd', 'like', "{$value}%"),
                    default => $query->where($field, 'like', "{$value}%"),
                };
            }
        }
        // Apply sorting
        match ($sortField) {
            'nume', 'adresa', 'contact', 'telefon', 'email', 'created_at', 'updated_at' => 
                $query->orderBy('cld.' . $sortField, $sortOrder),
            'localitate' => 
                $query->orderBy('lcd.nume_lc', $sortOrder),
            'judet' => 
                $query->orderBy('jd.nume_jd', $sortOrder),
            default => 
                $query->orderBy('cld.nume', 'asc'),
        };

        //dd($query->toSql(), $query->getBindings());

        // Paginate
        $result = $query->paginate($rows, ['*'], 'page', $page);

        return [
            'data' => $result->items(),
            'total' => $result->total(),
            'current_page' => $result->currentPage(),
            'per_page' => $result->perPage(),
            'last_page' => $result->lastPage(),
            'from' => $result->firstItem(),
            'to' => $result->lastItem(),
        ];
    }

    /**
     * Validate CSV import file
     */
    public function validateImportFile(string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \Exception('Nu s-a putut deschide fisierul CSV.');
        }

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($handle);
        }

        // Read header row
        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);
            throw new \Exception('Fisierul CSV este gol sau invalid.');
        }

        // Expected headers
        $expectedHeaders = ['Nume', 'Judet', 'Localitate', 'Adresa', 'Contact', 'Telefon', 'Email'];

        // Validate headers
        if (count($headers) !== count($expectedHeaders)) {
            fclose($handle);
            throw new \Exception(
                'Numarul de coloane nu corespunde cu template-ul. Asteptat: ' .
                count($expectedHeaders) . ', Gasit: ' . count($headers)
            );
        }

        // Read and validate data rows
        $validRows = [];
        $errors = [];
        $rowNumber = 2; // Start from 2 (1 is header)
        $maxRows = 1000; // Limit preview to first 1000 rows

        while (($row = fgetcsv($handle)) !== false && $rowNumber <= $maxRows + 1) {
            // Skip empty rows
            if (count(array_filter($row)) === 0) {
                $rowNumber++;
                continue;
            }

            $rowErrors = [];

            // Validate required fields
            if (empty($row[0])) $rowErrors[] = "Nume lipsa";
            if (empty($row[1])) $rowErrors[] = "Judet lipsa";
            if (empty($row[2])) $rowErrors[] = "Localitate lipsa";
            if (empty($row[3])) $rowErrors[] = "Adresa lipsa";
            if (empty($row[4])) $rowErrors[] = "Contact lipsa";
            if (empty($row[5])) $rowErrors[] = "Telefon lipsa";

            // Validate email format if provided
            if (!empty($row[5]) && !filter_var($row[5], FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = "Email invalid";
            }

            // Validate phone format (Romanian phone number)
            if (!empty($row[4]) && !preg_match('/^(\+40|0)?[0-9]{9}$/', str_replace([' ', '-', '.'], '', $row[4]))) {
                $rowErrors[] = "Telefon invalid";
            }

            if (count($rowErrors) > 0) {
                $errors[] = [
                    'row' => $rowNumber,
                    'errors' => $rowErrors,
                    'data' => $row
                ];
            } else {
                $validRows[] = [
                    'row' => $rowNumber,
                    'nume' => $row[0] ?? '',
                    'judet' => $row[1] ?? '',
                    'localitate' => $row[2] ?? '',
                    'adresa' => $row[3] ?? '',
                    'contact' => $row[4] ?? '',
                    'telefon' => $row[5] ?? '',
                    'email' => $row[6] ?? '',
                ];
            }

            $rowNumber++;
        }

        fclose($handle);

        return [
            'valid_rows' => array_slice($validRows, 0, 10), // Return first 10 for preview
            'total_valid' => count($validRows),
            'total_errors' => count($errors),
            'errors' => array_slice($errors, 0, 10), // Return first 10 errors for preview
            'total_rows' => $rowNumber - 2,
        ];
    }
}
