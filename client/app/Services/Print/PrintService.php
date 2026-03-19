<?php
/*
print_awb: 0, 1, 2, 5, 6, 7 : print master and puisori
print_awb: 3, 4, 10 : print master or puisori only
//format IL100x150 (4, 5), IL70x100 (3), A4 (0, 1, 2, 6, 7, 10)
print_awb: 0, 1, 2, 10 - master A4 and puisori A4 : cate 4 puisori per pagina
print_awb: 3 - master A4 and puisori IL70x100
print_awb: 4 - master A4 and puisori IL100x150
print_awb: 7 - master A4 and puisori A4 : cate 2 puisori per pagina

print_awb: 5 - master and puisori IL100x150
print_awb: 6 - master and puisori A4 : cate 4 AWB per pagina
print_awb: 8 - master and puisori IL70x100
*/

namespace App\Services\Print;

use App\Services\Print\AwbPdfService;
use App\Constants\Pdf as ConstantsPdf;
use App\Services\Helpers\ToolsService;

class PrintService
{
    public function __construct(
        private readonly AwbPdfService $awbPdfService,
    ) {
    }
    
    public function awbsPdf(string $filename, array $awbs, int $print_awb = 1): mixed
    {
        //print master and puisori
        if($print_awb == 3) {
            $print_awb = 8;
        } else if($print_awb == 4) {
            $print_awb = 5;
        } else if($print_awb == 10) {
            $print_awb = 1;
        }
        
        match($print_awb){
            4, 5 => $this->awbPdfService->setPdfPageFormat(array(ConstantsPdf::IL100, ConstantsPdf::IL150)),
            3, 8 => $this->awbPdfService->setPdfPageFormat(array(ConstantsPdf::IL70, ConstantsPdf::IL100), 'L'),
            default => null, //A4
        };

        if(count($awbs) < 1) {
            $this->awbPdfService->AddPage();
        }
        //dd($print_awb); //--- IGNORE ---
        foreach($awbs as $row)
        {
            $this->generateAwbPdf($row, $print_awb);
        }

        // move pointer to last page
        $this->awbPdfService->lastPage();
        
        // Output PDF as string
        return $this->awbPdfService->Output($filename, 'S');
    }

    public function awbsMasterPdf(string $filename, array $awbs, int $print_awb = 1): mixed
    {
        //print master only A4
        if(in_array($print_awb, [5,6,8])) {
            $print_awb = 1;
        }
        if(count($awbs) < 1) {
            $this->awbPdfService->AddPage();
        }
        foreach($awbs as $key=>$row)
        {
            $this->generateAwbPdf($row, $print_awb, 1);
        }

        // move pointer to last page
        $this->awbPdfService->lastPage();
        
        // Output PDF as string
        return $this->awbPdfService->Output($filename, 'S');
    }

    public function awbsPuisoriPdf(string $filename, array $awbs, int $print_awb = 4): mixed
    {   
        if(count($awbs) < 1) {
            $this->awbPdfService->AddPage();
        }
        //print puisori or puisori and master (5,6,8)
        match($print_awb){
            4, 5 => $this->awbPdfService->setPdfPageFormat(array(ConstantsPdf::IL100, ConstantsPdf::IL150)),
            3, 8 => $this->awbPdfService->setPdfPageFormat(array(ConstantsPdf::IL70, ConstantsPdf::IL100), 'L'),
            default => null, //A4
        };

        foreach($awbs as $row)
        {
            $this->generateAwbPdf($row, $print_awb, 2);
        }

        // move pointer to last page
        $this->awbPdfService->lastPage();
        
        // Output PDF as string
        return $this->awbPdfService->Output($filename, 'S');
    }

    private function generateAwbPdf(array $row, int $print_awb = 1, int $tip = 0): void
    {
        if(empty($row) || !is_array($row)) {
            $this->awbPdfService->AddPage();
            return;
        }

        $row['expeditor_nume'] = strtoupper(htmlspecialchars_decode(strtolower($row['expeditor_nume']), ENT_QUOTES));
		$row['expeditor_contact'] = strtoupper(htmlspecialchars_decode(strtolower($row['expeditor_contact']), ENT_QUOTES));
        $row['destinatar_nume'] = strtoupper(htmlspecialchars_decode(strtolower($row['destinatar_nume']), ENT_QUOTES));
		$row['destinatar_contact'] = strtoupper(htmlspecialchars_decode(strtolower($row['destinatar_contact']), ENT_QUOTES));

        $row['greutate'] = round($row['greutate'], 1);
        $row['greutate_vol'] = round($row['greutate_vol'], 1);

 		if($row['asigurare'] > 0){
			$row['asigurare'] = round($row['asigurare'], 2);
		}

        if($row['ramburs'] > 0){
        	$row['ramburs'] = round($row['ramburs'], 2);
        }

        $row['valoare_fara_tva'] = round($row['valoare_fara_tva'], 2);
        $row['valoare_tva'] = round($row['valoare_tva'], 2);

        if(empty($row['moneda'])) $row['moneda'] = 1;
        match($row['moneda']) {
            1 => $row['moneda'] = 'LEI',
            2 => $row['moneda'] = 'EUR',
            3 => $row['moneda'] = 'USD',
            default => $row['moneda'] = 'LEI',
        };

        match($row['tip_obj']) {
            1 => $row['tip_obj'] = 'PLIC',
            2 => $row['tip_obj'] = 'COLET',
            3 => $row['tip_obj'] = 'PALET',
            default => $row['tip_obj'] = 'COLET',
        };

        /*
            if(!self::isValidCui($row['expeditor_cui'])) {
                $row['expeditor_cui'] = $row['expeditor_j'] = "";
            }
        */
        $this->awbPdfService->setItem($row);
        if(($tip == 0 || $tip == 1) && in_array($print_awb, [0,1,2,3,4,7,10])) {
            //master
            $this->generateAwbPdfMaster($row);
            if($tip == 1) return;
        }
        if(($tip == 0 || $tip == 2) && in_array($print_awb, [0,1,2,3,4,7,10])) {
            //master
            if(false === $this->generateAwbPdfPuisori($row, $print_awb) && $tip == 2) {
                $this->awbPdfService->AddPage();
            }
            return;
        }
        if($tip == 0 && in_array($print_awb, [5,6,8])) {
            //puisori
            if(false === $this->generateAwbPdfMasterPuisori($row, $print_awb)) {
                $this->awbPdfService->AddPage();
            }
            return;
        }
        $this->generateAwbPdfMaster($row);
    }

    private function generateAwbPdfMaster(array $row): void
    {
        $this->awbPdfService->AddPage();
        if(empty($row) || !is_array($row)) {
            $this->awbPdfService->AddPage();
            return;
        }
        
        //nota de comanda
		$nc = false;
		if(!empty($row['extrainfo'])) $nc = true;

		$this->awbPdfService->makeHalfFirstPage(0, 0);
		$this->awbPdfService->makeDashedLine('H');
        if($nc)
            $this->awbPdfService->makeNotaComanda(0, 148);
        else
            $this->awbPdfService->makeHalfFirstPage(0, 148);
    }

    private function generateAwbPdfPuisori(array $row, int $print_awb = 1): bool
    {
        if(empty($row) || !is_array($row)) {
            $this->awbPdfService->AddPage();
            return false;
        }

        if(in_array($print_awb, [0,1,2,10]) ) {
            //puisori A4 - cate 4 puisori per pagina
            return $this->generatePuisori4A4($row, $print_awb);
        }
        if($print_awb == 7) {
            //puisori A4 - cate 2 puisori per pagina
            return $this->generatePuisori2A4($row, $print_awb);
        }
        if($print_awb == 3 || $print_awb == 4) {
            //puisori IL70x100
            //cate 1 puisor per pagina
            return $this->generatePuisori1PerPageIL($row, $print_awb);
        }       
        return false;
    }

    private function generateAwbPdfMasterPuisori(array $row, int $print_awb = 6): bool
    {
        if(empty($row) || !is_array($row)) {
            $this->awbPdfService->AddPage();
            return true;
        }
        if($print_awb == 5) {
            //master
            $this->awbPdfService->AddPage();
            $this->awbPdfService->makePuisorMultiCell((string)$row['awb'], 1, -1); //master
            $this->generatePuisori1PerPageIL($row, $print_awb);
            return true;
        }
        if($print_awb == 6) {
            return $this->generatePuisori4A4($row, $print_awb);   
        }
        if($print_awb == 8) {
            //master
            $this->awbPdfService->AddPage();
            $this->awbPdfService->makePuisorMultiCellPrintAwb3((string)$row['awb'], 1, 0); //master
            $this->generatePuisori1PerPageIL($row, $print_awb);
            return true;
        }
        return false;
    }

    private function generatePuisori4A4(array $row, int $print_awb = 1): bool
    {
        if(($row['piese'] ?? 0) <= 1) {
            return false;
        }
        $nr_pag = ceil((float)($row['piese'] - 1) / 4);
        if($print_awb == 6) {
            $nr_pag = ceil((float)$row['piese'] / 4);
        }
        $ret = false;
        for($ip = 0; $ip < $nr_pag; $ip++) {
            $this->awbPdfService->AddPage();
            $this->awbPdfService->makeDashedLine('H');
            $this->awbPdfService->makeDashedLine('V');
            $ret = true;
            for($pos = 1; $pos <= 4; $pos++) {
                $nr = $ip * 4 + $pos + 1;
                if($print_awb == 6) $nr--; //pentru print_awb 6, primul puisor este masterul
                if($nr > $row['piese']) break;
                $cod_bare = $nr == 1 ? (string)$row['awb'] : $row['awb'].'-'.ToolsService::getPuisorNr($nr);
                $this->awbPdfService->makePuisorMultiCell($cod_bare, $nr, $pos);
            }
        }
        return $ret;
    }

    private function generatePuisori2A4(array $row, int $print_awb = 1): bool
    {
        if(($row['piese'] ?? 0) <= 1) {
            return false;
        }

        $nr_pag = ceil((float)($row['piese'] - 1) / 2);
        $ret = false;
        for($ip = 0; $ip < $nr_pag; $ip++) {
            $this->awbPdfService->AddPage();
            $this->awbPdfService->makeDashedLine('V');
            $ret = true;
            for($pos = 1; $pos <= 2; $pos++) {
                $nr = $ip * 2 + $pos + 1;
                if($nr > $row['piese']) break;
                $cod_bare = $row['awb'].'-'.ToolsService::getPuisorNr($nr);
                $this->awbPdfService->makePuisorMultiCell($cod_bare, $nr, $pos);
            }
        }
        return $ret;
    }

    private function generatePuisori1PerPageIL(array $row, int $print_awb = 1): bool
    {
        if(($row['piese'] ?? 0) <= 1) {
            return false;
        }

        $nr = 2;
        $nr_pag = ceil((float)($row['piese'] - 1));
        $ret = false;
        for($i=0; $i < $nr_pag && $nr <= $row['piese']; $i++) {
            $this->awbPdfService->AddPage();
            $cod_bare = $row['awb'] .'-'. ToolsService::getPuisorNr($nr);
            $ret = true;
            if($print_awb == 3 || $print_awb == 8) {
                $this->awbPdfService->makePuisorMultiCellPrintAwb3($cod_bare, $nr++);
                continue;
            }
            $this->awbPdfService->makePuisorMultiCell($cod_bare, $nr++, -1);
        }
        return $ret;
    }
}