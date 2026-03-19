<?php
namespace App\Services\Validators;

use App\Http\Requests\Comenzi\ComandaCreateRequest;
use Illuminate\Support\Facades\Validator;
use App\Services\Helpers\ToolsService;

use Illuminate\Support\Facades\Log;

class ValidateJsonOrderService
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

        if($withPc) {
            $pcId = intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['pcId'] ?? null));
            if(empty($pcId)) {
                $ret['message'] = "pcId : required";
                return $ret;
            }
            Log::debug("Validating pcId : ".$pcId." against allowed pcIds: ".implode(", ", $pcIds));
            if(!in_array($pcId, $pcIds)) {
                $ret['message'] = "pcId invalid : ".$pcId;
                return $ret;
            }
        }

        $validator = Validator::make($data, ComandaCreateRequest::rules($data), ComandaCreateRequest::messages());

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => implode(', ', $validator->errors()->all()),
                'data' => null,
            ];
        }

        try {
			$collect_at = new \DateTimeImmutable(ToolsService::sSanitizeCleanEdgesIconvTranslate($data["dataCollect"]));
		} catch (\Exception $e) {
			$collect_at = new \DateTimeImmutable('now');
		}
        //if collect_at < today, set collect_at to today
        if($collect_at < (new \DateTimeImmutable())->setTime(0, 0, 0)) {
            $collect_at = new \DateTimeImmutable();
        }

		$collect_at = $collect_at->setTime(0, 0, 0);

        $h_start = intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data["hStartCollect"]));
        $h_end = intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data["hEndCollect"]));

        if($h_start == 0 || $h_end == 0) {
			$h_start = 9;
			$h_end = 17;
		}

        if($collect_at->format('Y-m-d') === (new \DateTime())->format('Y-m-d') && $h_start < (new \DateTime())->format('H')) {
            $h_start = (new \DateTime())->format('H');
        }

        if($h_start >= $h_end) 
            $h_end = $h_end + 1;

        if($h_end >= 17) {
			$collect_at = (new \DateTime())->add(new \DateInterval('P1D'));
			$h_start = 9;
			$h_end = 17;
		}

		$collect_at = $collect_at->format('Y-m-d');

        return [
            'success' => true,
            'message' => 'Validation successful',
            'data' => [
                'pcId' => $withPc ? $pcId : null,
                'collect_at' => ToolsService::sSanitizeCleanEdgesIconvTranslate($collect_at),
                'h_start' => $h_start,
                'h_end' => $h_end,
                'ridica_de_la' => ToolsService::sSanitizeCleanEdgesIconvTranslate($data['ridicaDeLa'] ?? null),
                'telefon' => ToolsService::sSanitizeCleanEdgesIconvTranslate($data['telefon'] ?? null),
                'contact' => ToolsService::sSanitizeCleanEdgesIconvTranslate($data['contact'] ?? null),
                'email' => ToolsService::sSanitizeCleanEdgesIconvTranslate($data['email'] ?? null),
                'adresa' => ToolsService::sSanitizeCleanEdgesIconvTranslate($data['adresa'] ?? null),
                'judet' => ToolsService::sSanitizeCleanEdgesIconvTranslate($data['judet'] ?? null),
                'localitate' => ToolsService::sSanitizeCleanEdgesIconvTranslate($data['localitate'] ?? null),
                'colete' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['colete'] ?? 1)),
                'paleti' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['paleti'] ?? 0)),
                'greutate' => floatval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['greutate'] ?? 1)),
                'volum' => intval(ToolsService::sSanitizeCleanEdgesIconvTranslate($data['lungime'] ?? 0)),
                'observatii' => ToolsService::sSanitizeCleanEdgesIconvTranslate($data['observatii'] ?? null),
            ]
        ];
    }
}