<?php
namespace App\Services\Validators;

use App\Http\Requests\Expeditii\AwbPriceRequest;
use Illuminate\Support\Facades\Validator;
use App\Services\Helpers\ToolsService;

use Illuminate\Support\Facades\Log;

class ValidateJsonPriceService
{
    /**
     * Validate JSON price request
     */
    public static function validate(string $json, array $pcIds, bool $withPc = false): array
    {
        $ret = [
            'success' => false,
            'message' => '',
            'data' => null,
        ];
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $ret['message'] = 'Invalid JSON format: ' . json_last_error_msg();
            return $ret;
        }
        if(count($data) == 0) {
            $ret['message'] = 'Empty json';
            return $ret;
        }

        if (!empty($data['tipExpeditie'])) {
            $data['tipExpeditie'] = strtolower($data['tipExpeditie']);
        }
        if (!empty($data['tipPlata'])) {
            $data['tipPlata'] = strtolower($data['tipPlata']);
        }

        if($withPc) {
            $pcId = intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['pcId'] ?? null));
            if(empty($pcId)) {
                $ret['message'] = "pcId : required";
                return $ret;
            }
            if(!in_array($pcId, $pcIds)) {
                $ret['message'] = "pcId invalid : ".$pcId;
                return $ret;
            }
        }

        $validator = Validator::make($data, AwbPriceRequest::rules($data), AwbPriceRequest::messages());

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => implode(', ', $validator->errors()->all()),
                'data' => null,
            ];
        }

        return [
            'success' => true,
            'message' => 'Validation successful',
            'data' => [
                'pcId' => $withPc ? $pcId : null,
                'judet' => ToolsService::sSanitizeCleanEdgesIconvTranslate($data['judet'] ?? null),
                'localitate' => ToolsService::sSanitizeCleanEdgesIconvTranslate($data['localitate'] ?? null),
                'tip_obj' => match(strtolower(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['tipExpeditie'] ?? 'plic'))) {
                    'plic' => 1,
                    'colet' => 2,
                    'palet' => 3,
                    default => 1,
                },
                'piese' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['nrColete'] ?? 1)),
                'greutate' => floatval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['greutate'] ?? 1)),
                'volum1' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['lungime'] ?? 0)),
                'volum2' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['latime'] ?? 0)),
                'volum3' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['inaltime'] ?? 0)),
                'asigurare' => floatval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['valoareAsigurare'] ?? 0)),
                'ramburs' => floatval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['valoareRamburs'] ?? 0)),
                'tip_plata' => match(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['tipPlata'] ?? 'cash')) {
                    'cash' => 0,
                    'bo' => 1,
                    'card' => 2,
                    'cont' => 3,
                    default => 0,
                },
                'sms' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['sms'] ?? '0')) == 1 ? 1 : 0,
                'ret_nt' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['ret_nt'] ?? '0')) == '1' ? 1 : 0,
                'ret_doc' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['ret_doc'] ?? '0')) == '1' ? 1 : 0,
                'ret_amb' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['ret_amb'] ?? '0')) == '1' ? 1 : 0,
                'ret_colet' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['ret_colet'] ?? '0')) == '1' ? 1 : 0,
                'copen' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['deschidereColet'] ?? '0')) == '1' ? 1 : 0,
                'liv_samb' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['tarifSambata'] ?? '0')) == '1' ? 1 : 0,
                'liv_sed' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['livrareSediu'] ?? '0')) == '1' ? 1 : 0,
                'platitor' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['platitorEsteDestinatarul'] ?? 0)) == 1 ? 2 : 1, // 1: expeditor, 2: destinatar
            ]
        ];
    }
}