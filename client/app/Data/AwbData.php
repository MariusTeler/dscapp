<?php

namespace App\Data;

use App\Services\Helpers\ToolsService;
use \StdClass;
/**
 * AwbData DTO
 * 
 * Data Transfer Object for AWB data, encapsulating shipment details,
 * sender and recipient information, package specifics.
 */
class AwbData
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $awb,
        public readonly ?int $referire,
        public readonly ?string $data_expeditie,
        public readonly ?int $expeditor_id,
        public readonly ?int $expeditor_client_id = null,
        public readonly ?string $expeditor_localitate,
        public readonly ?int $expeditor_localitate_id,
        public readonly ?string $expeditor_judet,
        public readonly ?string $expeditor_judet_id,
        public readonly ?string $expeditor_centru,
        public readonly ?string $expeditor_centru_cod,
        public readonly ?string $expeditor_nume,
        public readonly ?string $expeditor_contact,
        public readonly ?string $expeditor_telefon,
        public readonly ?string $expeditor_email,
        public readonly ?string $expeditor_adresa,
        public readonly ?string $expeditor_cui,
        public readonly ?string $expeditor_j,
        public readonly ?int $destinatar_id,
        public readonly ?int $destinatar_client_id = null,
        public readonly ?string $destinatar_localitate,
        public readonly ?int $destinatar_localitate_id,
        public readonly ?string $destinatar_judet,
        public readonly ?string $destinatar_judet_id,
        public readonly ?string $destinatar_centru,
        public readonly ?string $destinatar_centru_cod,
        public readonly ?string $destinatar_nume,
        public readonly ?string $destinatar_contact,
        public readonly ?string $destinatar_telefon,
        public readonly ?string $destinatar_email,
        public readonly ?string $destinatar_adresa,
        public readonly ?string $destinatar_centru_zona,
        public readonly ?int $platitor,
        public readonly ?int $platitor_id,
        public readonly ?int $tip_obj,
        public readonly ?int $piese,
        public readonly ?float $greutate,
        public readonly ?int $volum1,
        public readonly ?int $volum2,
        public readonly ?int $volum3,
        public readonly ?float $asigurare,
        public readonly ?float $ramburs,
        public readonly ?int $tip_plata,
        public readonly ?bool $ret_nt,
        public readonly ?bool $ret_nc,
        public readonly ?bool $ret_doc,
        public readonly ?bool $ret_amb,
        public readonly ?bool $ret_colet,
        public readonly ?bool $liv_samb,
        public readonly ?bool $liv_sed,
        public readonly ?bool $sms,
        public readonly ?bool $copen,
        public readonly ?bool $swapped,
        public readonly ?string $observatii,
        public readonly ?string $detalii_doc,
        public readonly ?int $tip_tarif,
        public readonly ?int $mod_plata,
        public readonly ?string $moneda,
        public readonly ?string $volum,
        public readonly ?float $greutate_vol,
        public readonly ?int $km_preluare,
        public readonly ?float $expeditor_localitate_km,
        public readonly ?int $km_livrare,
        public readonly ?float $destinatar_localitate_km,
        public readonly ?string $destinatar_rut_bvh,
        public readonly ?string $destinatar_rut_buh,
        public readonly ?string $destinatar_rut_buc,
        public readonly ?float $valoare_exp,
        public readonly ?float $valoare_asig,
        public readonly ?float $valoare_km,
        public readonly ?float $valoare_kg,
        public readonly ?float $valoare_fara_tva,
        public readonly ?float $valoare_tva,
        public readonly ?int $procTva,
        public readonly ?int $anulata,
        public readonly ?int $src,
        public readonly ?int $borderou_id,
        public readonly ?string $created_at,
        public readonly ?int $created_by,
        public readonly ?string $created_by_user,
        public readonly ?string $updated_at,
        public readonly ?int $updated_by,
        public readonly ?string $updated_by_user,
        public readonly ?string $printed_at,
        public readonly ?int $printed_by,
        public readonly ?string $printed_by_user,
        public readonly ?string $deleted_at,
        public readonly ?int $deleted_by,
        public readonly ?string $extrainfo,
        public readonly ?string $largeinfo,
        public readonly ?string $folder,
        public readonly ?int $idfact = null,
        public readonly ?int $last_ckp = null,
        public readonly ?int $km_exteriori = null,
    ) {}

    // Create DTO from array
    public static function fromUiArray(array $data, int $expeditor_cc = 0): self
    {
        return new self(
            intval($data['id'] ?? null),
            intval($data['awb'] ?? null),
            0,
            date('Y-m-d'),
            intval($data['expeditor_id'] ?? null),
            intval($data['expeditor_client_id'] ?? null),
            null,
            intval($data['expeditor_localitate_id'] ?? null),
            null,
            null,
            null,
            null,
            mb_strtoupper(ToolsService::sSanitizeCleanEdges($data['expeditor_nume'] ?? null)),
            mb_strtoupper(ToolsService::sSanitizeCleanEdges($data['expeditor_contact'] ?? null)),
            ToolsService::sSanitizeCleanEdges($data['expeditor_telefon'] ?? null),
            ToolsService::sSanitizeCleanEdges($data['expeditor_email'] ?? null),
            mb_strtoupper(ToolsService::sSanitizeCleanEdges($data['expeditor_adresa'] ?? null)),
            null,
            null,
            intval($data['destinatar_id'] ?? null),
            intval($data['destinatar_client_id'] ?? null),
            null,
            intval($data['destinatar_localitate_id'] ?? null),
            null,
            null,
            null,
            null,
            mb_strtoupper(ToolsService::sSanitizeCleanEdges($data['destinatar_nume'] ?? null)),
            mb_strtoupper(ToolsService::sSanitizeCleanEdges($data['destinatar_contact'] ?? null)),
            ToolsService::sSanitizeCleanEdges($data['destinatar_telefon'] ?? null),
            ToolsService::sSanitizeCleanEdges($data['destinatar_email'] ?? null),
            mb_strtoupper(ToolsService::sSanitizeCleanEdges($data['destinatar_adresa'] ?? null)),
            null,
            intval($data['platitor'] ?? null),
            intval($data['platitor'] ?? null) == 2 ? intval($data['destinatar_id'] ?? 0) : intval($data['expeditor_id'] ?? 0),
            intval($data['tip_obj'] ?? null),
            intval($data['piese'] ?? null),
            round(floatval(ToolsService::sSanitize($data['greutate'] ?? 0)), 2),
            intval($data['volum1'] ?? null),
            intval($data['volum2'] ?? null),
            intval($data['volum3'] ?? null),
            round(floatval(ToolsService::sSanitize($data['asigurare'] ?? 0)), 2),
            round(floatval(ToolsService::sSanitize($data['ramburs'] ?? 0)), 2),
            intval($data['tip_plata'] ?? null) == 0 && $expeditor_cc > 0 ? 3 : intval($data['tip_plata'] ?? null),
            intval($data['ret_nt'] ?? 0) == 1,
            intval($data['ret_nc'] ?? null) == 1,
            intval($data['ret_doc'] ?? null) == 1,
            intval($data['ret_amb'] ?? null) == 1,
            intval($data['ret_colet'] ?? null) == 1,
            intval($data['liv_samb'] ?? null) == 1,
            intval($data['liv_sed'] ?? null) == 1,
            intval($data['sms'] ?? null) == 1,
            intval($data['copen'] ?? null) == 1,
            intval($data['swapped'] ?? null) == 1,
            ToolsService::sSanitizeCleanEdges($data['observatii'] ?? null),
            ToolsService::sSanitizeCleanEdges($data['detalii_doc'] ?? null),
            intval($data['expeditor_localitate_id'] ?? null) == intval($data['expeditor_destinatar_id'] ?? null) ? 0 : 1,
            intval($data['mod_plata'] ?? 0),
            1,
            ToolsService::uiToVolum($data['volum1'] ?? 0, $data['volum2'] ?? 0, $data['volum3'] ?? 0),
            ToolsService::getGreutateVolumetrica($data['volum1'] ?? 0, $data['volum2'] ?? 0, $data['volum3'] ?? 0),
            intval($data['km_preluare'] ?? null),
            null,
            intval($data['km_livrare'] ?? null),
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            0,
            8,
            $data['borderou_id'] ?? 0,
            date('Y-m-d H:i:s'),
            $data['created_by'] ?? 0,
            null,
            null,
            $data['updated_by'] ?? 0,
            null,
            null,
            null,
            null,
            null,
            null,
            ToolsService::sSanitizeCleanEdges($data['extrainfo'] ?? null),
            ToolsService::sSanitizeCleanEdges($data['largeinfo'] ?? null),
            null,
            null,
            null,
            null,
        );
    }

    // Create DTO from array
    public static function fromSqlArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['awb'] ?? null,
            $data['referire'] ?? null,
            $data['data_expeditie'] ?? null,
            $data['expeditor_id'] ?? null,
            $data['expeditor_client_id'] ?? null,
            $data['expeditor_localitate'] ?? null,
            $data['expeditor_localitate_id'] ?? null,
            $data['expeditor_judet'] ?? null,
            $data['expeditor_judet_id'] ?? null,
            $data['expeditor_centru'] ?? null,
            $data['expeditor_centru_cod'] ?? null,
            $data['expeditor_nume'] ?? null,
            $data['expeditor_contact'] ?? null,
            $data['expeditor_telefon'] ?? null,
            $data['expeditor_email'] ?? null,
            $data['expeditor_adresa'] ?? null,
            $data['expeditor_cui'] ?? null,
            $data['expeditor_j'] ?? null,
            $data['destinatar_id'] ?? null,
            $data['destinatar_client_id'] ?? null,
            $data['destinatar_localitate'] ?? null,
            $data['destinatar_localitate_id'] ?? null,
            $data['destinatar_judet'] ?? null,
            $data['destinatar_judet_id'] ?? null,
            $data['destinatar_centru'] ?? null,
            $data['destinatar_centru_cod'] ?? null,
            $data['destinatar_nume'] ?? null,
            $data['destinatar_contact'] ?? null,
            $data['destinatar_telefon'] ?? null,
            $data['destinatar_email'] ?? null,
            $data['destinatar_adresa'] ?? null,
            $data['destinatar_centru_zona'] ?? null,
            ($data['platitor_id'] ?? -1) == ($data['destinatar_id'] ?? -2) ? 2 : 1,
            $data['platitor_id'] ?? null,
            $data['tip_obj'] ?? null,
            $data['piese'] ?? null,
            $data['greutate'] ?? null,
            $data['volum1'] ?? ToolsService::sqlToVolum1($data['volum'] ?? 0),
            $data['volum2'] ?? ToolsService::sqlToVolum2($data['volum'] ?? 0),
            $data['volum3'] ?? ToolsService::sqlToVolum3($data['volum'] ?? 0),
            $data['asigurare'] ?? null,
            $data['ramburs'] ?? null,
            $data['tip_plata'] ?? null,
            ($data['ret_nt'] ?? 0) == 1,
            !empty($data['extrainfo'] ?? null),
            ($data['ret_doc'] ?? 0) == 1,
            ($data['ret_amb'] ?? 0) == 1,
            ($data['ret_colet'] ?? 0) == 1,
            ($data['liv_samb'] ?? 0) == 1,
            ($data['liv_sed'] ?? 0) == 1,
            ($data['sms'] ?? 0) != 0,
            ($data['copen'] ?? 0) == 1,
            ($data['swapped'] ?? 0) == 1,
            $data['observatii'] ?? null,
            $data['detalii_doc'] ?? null,
            $data['tip_tarif'] ?? null,
            $data['mod_plata'] ?? null,
            $data['moneda'] ?? null,
            $data['volum'] ?? null,
            $data['greutate_vol'] ?? null,
            $data['km_preluare'] ?? null,
            $data['expeditor_localitate_km'] ?? null,
            $data['km_livrare'] ?? null,
            $data['destinatar_localitate_km'] ?? null,
            $data['destinatar_rut_bvh'] ?? null,
            $data['destinatar_rut_buh'] ?? null,
            $data['destinatar_rut_buc'] ?? null,
            $data['valoare_exp'] ?? null,
            $data['valoare_asig'] ?? null,
            $data['valoare_km'] ?? null,
            $data['valoare_kg'] ?? null,
            $data['valoare_fara_tva'] ?? null,
            $data['valoare_tva'] ?? null,
            $data['procTva'] ?? null,
            $data['anulata'] ?? null,
            $data['src'] ?? null,
            $data['borderou_id'] ?? null,
            $data['created_at'] ?? null,
            $data['created_by'] ?? null,
            $data['created_by_user'] ?? null,
            $data['updated_at'] ?? null,
            $data['updated_by'] ?? null,
            $data['updated_by_user'] ?? null,
            $data['printed_at'] ?? null,
            $data['printed_by'] ?? null,
            $data['printed_by_user'] ?? null,
            $data['deleted_at'] ?? null,
            $data['deleted_by'] ?? null,
            $data['extrainfo'] ?? null,
            $data['largeinfo'] ?? null,
            $data['folder'] ?? null,
            $data['idfact'] ?? null,
            $data['last_ckp'] ?? null,
            $data['km_exteriori'] ?? null,
        );
    }

    // Convert DTO to array
    public function toUiArray(): array
    {
        $awb_data_array = get_object_vars($this);
        unset($awb_data_array['expeditor_centru']);
        unset($awb_data_array['expeditor_centru_cod']);
        unset($awb_data_array['expeditor_localitate_km']);
        unset($awb_data_array['destinatar_localitate_km']);
        unset($awb_data_array['platitor_id']);
        unset($awb_data_array['tip_tarif']);
        unset($awb_data_array['mod_plata']);
        unset($awb_data_array['valoare_exp']);
        unset($awb_data_array['valoare_asig']);
        unset($awb_data_array['valoare_km']);
        unset($awb_data_array['valoare_kg']);
        unset($awb_data_array['procTva']);
        unset($awb_data_array['anulata']);
        unset($awb_data_array['src']);
        unset($awb_data_array['borderou_id']);
        unset($awb_data_array['created_by_user']);
        unset($awb_data_array['updated_by_user']);
        unset($awb_data_array['printed_by_user']);
        unset($awb_data_array['extrainfo']);
        unset($awb_data_array['largeinfo']);
        unset($awb_data_array['idfact']);
        return $awb_data_array;
    }

    public function toStdClass(): StdClass
    {
        return (object) get_object_vars($this);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    // Convert DTO to JSON
    public function toJson(): string
    {
        return json_encode(get_object_vars($this));
    }
}