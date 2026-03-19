<?php
namespace App\Services\Print;

class AwbZplService
{
	private $exp;

	public function setItem(array $item): void
	{
		$this->exp = $item;
	}

	public function getZpl($code, $nb){
		setlocale(LC_CTYPE, 'en_GB');
		
		if($this->exp['mod_plata'] == 0){
			$this->exp['mod_plata_text'] = 'CASH';
		} else if($this->exp['mod_plata'] == 1){
			$this->exp['mod_plata_text'] = 'CONTRACT';
		}

		$this->exp['valoare_totala_cu_tva'] = "";
		$this->exp['transport_text'] = "";
        if($this->exp['mod_plata'] == 0){
			$this->exp['transport_text'] .= "La " . ($this->exp['platitor'] == 1 ? "PRELUARE" : "LIVRARE");
			$this->exp['valoare_totala_cu_tva'] = number_format(round(floatval($this->exp['valoare_fara_tva']) + floatval($this->exp['valoare_tva']),2), 2, '.', '');
        }

		if(intval($this->exp['valoare_totala_cu_tva']) == 0){
			$this->exp['valoare_totala_cu_tva'] = "--- ".strtoupper($this->exp['moneda']);
		} else {
			$this->exp['valoare_totala_cu_tva'] .= ' '.strtoupper($this->exp['moneda']);
		}

		$ramburs = "";
		if(!empty($this->exp['ramburs']) && $this->exp['ramburs'] > 0 && !(isset($this->exp['tip_exp']) && $this->exp['tip_exp'] == 3))
		{
			if(isset($this->exp['tip_plata'])){
				if($this->exp['tip_plata'] == 0) $ramburs .= "CASH ";
				else if($this->exp['tip_plata'] == 2) $ramburs .= "CEC ";
				else if($this->exp['tip_plata'] == 1) $ramburs .= "BO ";
				else if($this->exp['tip_plata'] == 3) $ramburs .= "CONT ";
			}
			$ramburs .= round($this->exp['ramburs'],2).' '.strtoupper($this->exp['moneda']);
		}
		else{
			$ramburs .= "--- ".strtoupper($this->exp['moneda']);
		}

		$this->exp['data_expeditie'] = date("d.m.Y",strtotime($this->exp['data_expeditie']));

		if(isset($this->exp['asigurare']) && $this->exp['asigurare'] > 0){
			$this->exp['asigurare_text'] = round($this->exp['asigurare'],2).' '.strtoupper($this->exp['moneda']);
		} else {
			$this->exp['asigurare_text'] = '--- '.strtoupper($this->exp['moneda']);
		}

        $this->exp['greutate'] = number_format(round($this->exp['greutate'],1), 1, '.', '');

		$this->exp['detalii_nt'] = "";
		$detalii_nt = [];
		if(!empty($this->exp['liv_samb'])) $detalii_nt[] = 'Livrare Sambata';
		if(!empty($this->exp['liv_sed']) && $this->exp['liv_sediu'] == 1) $detalii_nt[] = 'Livrare sediu';
		if(!empty($this->exp['ret_doc'])) $detalii_nt[] = 'Retur Doc';
		if(!empty($this->exp['ret_nt'])) $detalii_nt[] = 'Retur NT';
		if(!empty($this->exp['ret_colet']) && $this->exp['ret_colet'] == 1)  $detalii_nt[] =  'Retur colet';
		if(!empty($this->exp['ret_amb']) && $this->exp['ret_amb'] == 1) $detalii_nt[] = 'Retur ambalaj';
		if(!empty($this->exp['copen'])) $detalii_nt[] = 'Deschidere colet';
		if(!empty($this->exp['sms']) && $this->exp['sms'] == -1) $detalii_nt[] = 'SMS livrare';
		if(count($detalii_nt) > 0){
			$this->exp['detalii_nt'] = implode("\&", $detalii_nt);
		}

		if(!strlen(trim($this->exp['destinatar_telefon']))){
			$this->exp['destinatar_telefon'] = "______________";
		}

		if(!strlen(trim($this->exp['destinatar_contact']))){
			$this->exp['destinatar_contact'] = "______________";
		}

		$this->exp['footer_contact'] = "comenzi@dscexpres.ro - www.dscexpres.ro";

		$this->exp['expeditor_cui'] = $this->exp['expeditor_cui'] ?? "";
		$this->exp['expeditor_j'] = $this->exp['expeditor_j'] ?? "";

		$ret = "^XA";
		$ret .= "^CF0,100,";
		//logo
		$ret .= "^FO50,50^GFA,3264,3264,32,,:::::::::::::::::gP06,gQ08,gQ02,Y019M02I08,gG02L08I02,X01N02K08,gM04,gM03,gM038,gM07E,gG01K07F,gM03FC,gI0LFE,X02N07FF,Y041L03FF8,S06J0CN03FF8,R02L03M07FF8,Q01K01F01L01FF8008,W03F801L0FF8,Q08K07FI01K07F804,P01L03F8I02J03F808,P02001IF7CP0F82,R039JF4I04L0784,Q0403JFI02M039,P0C007IF8001N01A,S0IFE001,R01FFAI08,R01FC2004,,:T03F,S01FFE,S07IF8,R03JFC,Q01KFEI03FC003C003FI01FF1C0707F01FE03FE078,Q07KFEI03FF80FF01FFE001FF1E0F07FC1FF83FE1FE,P03MFI03FFC1FF83IF001FF0E0E07FC1FF83FE3FF,M0601NF800383C1C707C1F801C0071C071E1C3C38038E,M07PF800381E1C20F007001C007BC070E1C1C380384,M03PFC00380E1E00EK01C003B8070E1C1C3803C,M03PFE0038070F01EK01C001F0071E1C3C3801E,M01PFE0038070F81CK01FF01F007FC1FF83FE1F,N0JF003JF00380707C1CK01FF00E007FC1FF83FE0F8,N01FFJ0JF00380703E1CK01FF01F007F01FE03FE07C,U07IF80380700F1CK01C003F807001CE038001E,U03IF8038070079EK01C003B807001CF038I0F,U03IFC0380F0038EK01C007BC07001C7038I07,U03IFC0380E0838F007001C00F1E07001C78380107,U03IFC0383E3C787C1F801C00E0E07001C3838078F,U03IFC03FFC1FF03IF001FF1E0F07001C383FE3FE,U03IFC03FF81FF01FFE001FF3C0787001C1C3FE3FE,U03IFC03FE007C007FI01FF380387001C1C3FE0F8,U03IFC,:U03IF8,U03IF,U07FFE,U07FFC,U07FF8,U07FEN01C003EI07E01C1E1FF1C03F,U07F8N01C00FF801FF81C3F9FF1C0FFC,U038O01C03FFE07FFC1C7FDFF1C3FFE,gL01C03C1E0783C1C718381C3C1F,gL01C0780F0F0081C710381C7806,gL01C070070EI01C780381CF,gL01C0E0039CI01C3C0381CE,gL01C0E0039CI01C1E0381CE,gL01C0E0039C0FF1C0F0381CE,gL01C0E0039C0FF1C078381CE,gL01C0E0039C0FF1C03C381CE,gL01C070078E00F1C01C381CF,gL01C0780F0F00E1C21C381C7806,gL01C03C1E0783E1CF1C381C7C1F,gL01FC3FFE07FFC1C7FC381C3FFE,gL01FC0FF801FF81C7F8381C0FFC,gL01FC03FI07E01C1F0381C03F,,:::::::^FS";

		$ret .= "^FO200,30^GB100,1,80,,^FS";
		$ret .= "^FO200,50^AUN,,^FB100,1,,C,^FR^FD".(isset($this->exp['destinatar_centru_rut_bvh']) ? strtoupper($this->exp['destinatar_centru_rut_bvh']) : "")."^FS";

		$ret .= "^FO310,30^GB160,1,80,,^FS";
		$ret .= "^FO310,50^AUN,,^FB160,1,,C,^FR^FD".strtoupper($this->exp['destinatar_centru_cod'])."^FS";

		$ret .= "^FO480,30^GB240,1,80,,^FS";
		$ret .= "^FO480,50^AUN,,^FB240,1,,C,^FR^FD".$nb." din ".$this->exp['piese']."^FS";

		$ret .= "^FO730,30^GB100,1,80,,^FS";
		$ret .= "^FO730,50^AUN,,^FB100,1,,C,^FR^FD".(isset($this->exp['destinatar_centru_rut_buh']) ? strtoupper($this->exp['destinatar_centru_rut_buh']) : "")."^FS";

		/*
		$ret .= "^FO470,50^GB250,1,150,,^FS";
		$ret .= "^FO470,75^ATN,160,120^FB250,1,,C,^FR^FD".strtoupper($this->exp['destinatar_centru_cod)."^FS";
		$ret .= "^FO730,50^GB420,1,150,,^FS";
		$ret .= "^CF0,100,";
		$ret .= "^FO730,85^ATN,100,80^FB420,1,,C,^FR^FD".$nb." din ".$this->exp['piese."^FS";
		*/

		//$ret .= "^FXCENTRU^FS";
		$ret .= "^CF0,80,";
		if($nb > $this->exp['piese'])
			return $ret . "^FO50,215^AUN,80,60^FB1100,1,,R,^FDERROR : nb greater then quantity^FS^XZ";
		$ret .= "^FO50,215^AUN,80,60^FB1100,1,,R,^FD".strtoupper($this->stripChars($this->exp['destinatar_centru'])).$this->exp['destinatar_centru_zona']."   NT: ".$this->exp['awb']."^FS";
	
		//$ret .= "^FXCOD BARE^FS";
		$ret .= "^FO300,280^BY4^BCN,155,N,N,N^FD".$code."^FS^FS";
		$ret .= "^CF0,80,";
		$ret .= "^FO50,450^AUN,80,60^FB1100,1,,C,^FD".$code."^FS";

		//$ret .= "^FXExpeditor^FS";
		$ret .= "^CF0,40,";
		$ret .= "^FO70,475^TBN,1100,40^FDExpeditor:^FS";
		$ret .= "^CF0,60,";
		$ret .= "^FO70,520^AUN,80,60^TBN,1100,80^FD".strtoupper($this->stripChars($this->exp['expeditor_nume']))."^FS";
		//$ret .= "^FO20,400^AQN,,^TBN,760,30^FD".strtoupper($this->stripChars($this->exp['expeditor_cui'])." " . $this->exp['expeditor_j'])."^FS";
		$ret .= "^CF0,40,";
		$ret .= "^FO70,600^TBN,1080,80^FD".$this->stripChars($this->exp['expeditor_adresa'])." / Contact: ".$this->stripChars($this->exp['expeditor_contact'])." / Tel: ".$this->stripChars($this->exp['expeditor_telefon'])." / Loc: ".strtoupper($this->stripChars($this->exp['expeditor_localitate']))." (".strtoupper($this->stripChars($this->exp['expeditor_judet']))."), C.O.: ".strtoupper($this->exp['expeditor_centru_cod'])."^FS";

		//$ret .= "^FXSquares design^FS";
		$ret .= "^FO50,680^GB1100,980,3^FS";
		$ret .= "^FO600,750^GB550,1,3^FS";
		$ret .= "^FO600,830^GB550,1,3^FS";
		$ret .= "^FO800,750^GB1,80,3^FS";
		$ret .= "^FO940,750^GB1,80,3^FS";
		$ret .= "^FO600,920^GB550,1,3^FS";
		$ret .= "^FO600,1190^GB550,1,3^FS";
		$ret .= "^FO50,1220^GB550,1,1^FS";
		$ret .= "^FO600,680^GB1,740,3^FS";
		$ret .= "^FO50,1420^GB1100,1,3^FS";

		//$ret .= "^FXDestinatar^FS";
		$ret .= "^CF0,40,";
		$ret .= "^FO70,710^TBN,530,40^FDDestinatar:^FS";
		$ret .= "^CF0,60,";
		$ret .= "^FO70,760^AUN,80,60^TBN,550,60^FD".strtoupper($this->stripChars($this->exp['destinatar_nume']))."^FS";
		$ret .= "^CF0,40,";
		$ret .= "^FO70,840^TBN,530,120^FD".$this->stripChars($this->exp['destinatar_adresa'])."^FS";
		$ret .= "^CF0,60,";
		$ret .= "^FO70,960^AUN,80,60^TBN,530,60^FD".strtoupper($this->stripChars($this->exp['destinatar_localitate']))." (".strtoupper($this->stripChars($this->exp['destinatar_judet'])).")^FS";
		$ret .= "^CF0,50,";
		$ret .= "^FO70,1030^TBN,530,100^FDContact: ".strtoupper($this->stripChars($this->exp['destinatar_contact']))."^FS";
		$ret .= "^FO70,1150^TBN,530,50^FDTel.: ".$this->stripChars($this->exp['destinatar_telefon'])."^FS";

		//$ret .= "^FXDescriere continut^FS";
		$ret .= "^CF0,40,";
		$ret .= "^FO70,1240^TBN,530,150^FDDesc.: ".$this->stripChars($this->exp['detalii_doc'])."^FS";

		//$ret .= "^FXColectat^FS";
		$ret .= "^CF0,40,";
		$ret .= "^FO650,690^FDColectat:^FS";
		$ret .= "^CF0,50,";
		$ret .= "^FO850,690^AUN,80,60^FD".$this->exp['data_expeditie']."^FS";

		//$ret .= "^FXTip expeditie, piese, kg^FS";
		$tip = match($this->exp['tip_obj']) {
			1 => 'PLIC',
			2 => 'COLET',
			3 => 'PALET',
			default => 'N/A'
		};
		$ret .= "^CF0,40,";
		$ret .= "^FO610,775^AUN,80,60^FB200,1,,C,^FD".$tip."^FS";
		$ret .= "^FO800,775^AUN,80,60^FB140,1,,C,^FD".$this->exp['piese']."^FS";
		$ret .= "^FO950,775^AUN,80,60^FB200,1,,C,^FD".$this->exp['greutate']." Kg^FS";

		//$ret .= "^FXAsigurare^FS";
		$ret .= "^CF0,50,";
		$ret .= "^FO620,860^FB510,1,,C,^FDAsigurare: ".$this->exp['asigurare_text']."^FS";

		//$ret .= "^FXServicii^FS";
		$ret .= "^CF0,40,";
		$ret .= "^FO620,940^FB530,6,,L^FD".$this->exp['detalii_nt']."^FS";

		//$ret .= "^FXDe incasat^FS";
		$ret .= "^CF0,40,";
		$ret .= "^FO600,1210^FB550,1,,C^FDDE INCASAT (cu TVA):^FS";
		$ret .= "^FO600,1270^AUN,80,60^FB550,1,,C^FD" . $this->exp['transport_text']. ": ".$this->exp['valoare_totala_cu_tva']."^FS";
		$ret .= "^FO600,1340^AUN,80,60^FB550,1,,C^FDRBS: ".$ramburs."^FS";


		//$ret .= "^FXObservatii^FS";
		$ret .= "^CF0,40,";
		$ret .= "^FO70,1440^TBN,1000,200^FDObs.: ".$this->stripChars($this->exp['observatii'])."^FS";

		if(!empty($this->exp['destinatar_centru_rut_buc'])){
			$ret .= "^FO1020,1455^GB118,2,118,,^FS";
			$ret .= "^FO1027,2200^AUN,,^FB118,1,,C,^FR^FD".strtoupper($this->exp['destinatar_centru_rut_buc'])."^FS";
		}

		//$ret .= "^FXFooter^FS";
		$ret .= "^FO70,1680^BY3^BCN,50,N,N,N^FD".$code."^FS^FS";
		$ret .= "^FO800,1680^AUN,80,60^FDTel. 021-9501^FS";
		$ret .= "^CF0,40,";
		$ret .= "^FO50,1750^FB1100,1,,C,^FD".$this->exp['footer_contact']."^FS";
		$ret .= "^XZ";

		return $ret;
	}

	public function getZpl203dpi($code, $nb){

		setlocale(LC_CTYPE, 'en_GB');
		
		if($this->exp['mod_plata'] == 0){
			$this->exp['mod_plata_text'] = 'CASH';
		} else if($this->exp['mod_plata'] == 1){
			$this->exp['mod_plata_text'] = 'CONTRACT';
		}

		$this->exp['valoare_totala_cu_tva'] = "";
		$this->exp['transport_text'] = "";
        if($this->exp['mod_plata'] == 0){
			$this->exp['transport_text'] .= "La " . ($this->exp['platitor'] == 1 ? "PRELUARE" : "LIVRARE");
			$this->exp['valoare_totala_cu_tva'] = number_format(round(floatval($this->exp['valoare_fara_tva']) + floatval($this->exp['valoare_tva']),2), 2, '.', '');
        }

		if(intval($this->exp['valoare_totala_cu_tva']) == 0){
			$this->exp['valoare_totala_cu_tva'] = "--- ".strtoupper($this->exp['moneda']);
		} else {
			$this->exp['valoare_totala_cu_tva'] .= ' '.strtoupper($this->exp['moneda']);
		}

		$ramburs = "";
		if(!empty($this->exp['ramburs']) && $this->exp['ramburs'] > 0 && !(isset($this->exp['tip_exp']) && $this->exp['tip_exp'] == 3))
		{
			if(isset($this->exp['tip_plata'])){
				if($this->exp['tip_plata'] == 0) $ramburs .= "CASH ";
				else if($this->exp['tip_plata'] == 2) $ramburs .= "CEC ";
				else if($this->exp['tip_plata'] == 1) $ramburs .= "BO ";
				else if($this->exp['tip_plata'] == 3) $ramburs .= "CONT ";
			}
			$ramburs .= round($this->exp['ramburs'],2).' '.strtoupper($this->exp['moneda']);
		}
		else{
			$ramburs .= "--- ".strtoupper($this->exp['moneda']);
		}

		$this->exp['data_expeditie'] = date("d.m.Y",strtotime($this->exp['data_expeditie']));

		if(isset($this->exp['asigurare']) && $this->exp['asigurare'] > 0){
			$this->exp['asigurare_text'] = round($this->exp['asigurare'],2).' '.strtoupper($this->exp['moneda']);
		} else {
			$this->exp['asigurare_text'] = '--- '.strtoupper($this->exp['moneda']);
		}

        $this->exp['greutate'] = number_format(round($this->exp['greutate'],1), 1, '.', '');
		
		$this->exp['detalii_nt'] = "";
		$detalii_nt = [];
		if(!empty($this->exp['liv_samb'])) $detalii_nt[] = 'Livrare Sambata';
		if(!empty($this->exp['liv_sed']) && $this->exp['liv_sed'] == 1) $detalii_nt[] = 'Livrare sediu';
		if(!empty($this->exp['ret_doc'])) $detalii_nt[] = 'Retur Doc';
		if(!empty($this->exp['ret_nt'])) $detalii_nt[] = 'Retur NT';
		if(!empty($this->exp['ret_colet']) && $this->exp['ret_colet'] == 1)  $detalii_nt[] =  'Retur colet';
		if(!empty($this->exp['ret_amb']) && $this->exp['ret_amb'] == 1) $detalii_nt[] = 'Retur ambalaj';
		if(!empty($this->exp['copen'])) $detalii_nt[] = 'Deschidere colet';
		if(!empty($this->exp['sms']) && $this->exp['sms'] == -1) $detalii_nt[] = 'SMS livrare';
		if(count($detalii_nt) > 0){
			$this->exp['detalii_nt'] = implode("\&", $detalii_nt);
		}

		if(!strlen(trim($this->exp['destinatar_telefon']))){
			$this->exp['destinatar_telefon'] = "______________";
		}

		if(!strlen(trim($this->exp['destinatar_contact']))){
			$this->exp['destinatar_contact'] = "______________";
		}


		$this->exp['footer_contact'] = "comenzi@dscexpres.ro - www.dscexpres.ro";

		$this->exp['expeditor_cui'] = $this->exp['expeditor_cui'] ?? "";
		$this->exp['expeditor_j'] = $this->exp['expeditor_j'] ?? "";

		$ret = "^XA";
		//logo
		$ret .= "^FO20,50^GFA,1240,1240,20,7IF007PF,3IF8007NFE,1IF8I0NFC,0IFCI07MF8,07FFCI01FCKF,03FFCI01F8JFE,01FFCI01F9JFC,00FFCJ0F3007F8001IFL07F8J03FF8,007F8J07200FEI01JFJ01IFJ0IFE,203F8J02003FC0201JFEI03IF8003JF8301FEM03F80601KFI0JFC007JFC1807F8L07F00C01KFC00JFE00KFE0C03FEL07E01801KFE01JFE01KFE0E01FEL07C03801KFE01JFC03KFC0700FF1CI07F80F001LF03JF807KF807C07F3FI0FF01F001FE0IF83FE07007FF00F803E03F3F819FE03E001FE03FF83FC0200FFE007,03F01IFC1DFC07E001FE00FFC3FEJ0FF8,01F80IF89FF80FC001FE007FC3FF8001FF8,01FC07FF93FE01F8001FE007FC1FFE001FF,00FE03FFB7FC03F8001FE003FC1IF801FF,007F01FF2FF807FI01FE003FC0IFE01FE,007F807F3FF01FFI01FE003FC07IF01FE,003FE03F7FE03FEI01FE003FC03IFC1FE,003FF01F7FC07FEI01FE003FC00IFC1FE,001FF80F7F80FFCI01FE003FC003FFE1FE,001FFC06FF01FF8I01FE007FCI0IF1FF,I0FFE02FE03FF8I01FE007FCI03FF1FF,I07FF01FC07FFJ01FE00FFCI01FF0FF8,I07FF80F80IFJ01FE01FF80600FF0FFC002,I03FFC0703FFEJ01FE07FF80F00FF0FFE00F,I03IFI07FFEJ01LF01FC3FF07FFC3F8I01IF800IFCJ01LF03KF03KFCI01IFC01IF8J01KFE07JFE03KFCJ0IFE03IF8J01KFC07JFE01KFEJ07IF07IFK01KF803JFC00KFEJ07IF07IFK01JFE001JF8003JFCJ03IF07FFEK01JF8I07IFI01JF,J03IF07FFEK01IFCJ01FFCJ07FFC,J01IF07FFCgI07C,J01IF07FF8,K0IF07FF8,K07FF07FF,:K03FF07FE,:K01FF07FC,K01FF07F8,L0FF07F8,L07F07FM01EF19E3C90EF6387B6E5DEL07F07FM013D19866D18462C437B59AL03F07EM013F3D3E2F064F38436E5DEL03F07EM01EF3DB66B0E4F386B4E5DEL01F07CM01CDE6E3C90E49AC39CB5DBL01F078,M0F078,M0707,:M0306,:M0104,,^FS";

		$ret .= "^FO200,30^GB80,1,80,,^FS";
		$ret .= "^FO200,50^AUN,,^FB80,1,,C,^FR^FD".(isset($this->exp['destinatar_centru_rut_bvh']) ? strtoupper($this->exp['destinatar_centru_rut_bvh']) : "")."^FS";

		$ret .= "^FO285,30^GB145,1,80,,^FS";
		$ret .= "^FO285,50^AUN,,^FB145,1,,C,^FR^FD".strtoupper($this->exp['destinatar_centru_cod'])."^FS";

		$ret .= "^FO435,30^GB240,1,80,,^FS";
		$ret .= "^FO435,50^AUN,,^FB240,1,,C,^FR^FD".$nb." din ".$this->exp['piese']."^FS";

		$ret .= "^FO680,30^GB80,1,80,,^FS";
		$ret .= "^FO680,50^AUN,,^FB80,1,,C,^FR^FD".(isset($this->exp['destinatar_centru_rut_buh']) ? strtoupper($this->exp['destinatar_centru_rut_buh']) : "")."^FS";

		//$ret .= "^FXCENTRU^FS";
		$ret .= "^CF0,30,";
		if($nb > $this->exp['piese'])
			return $ret . "^FO20,130^ASN,,^FB750,1,,R,^FDERROR : nb greater then quantity^FS^XZ";
		$ret .= "^FO20,130^ASN,,^FB750,1,,R,^FD".strtoupper($this->stripChars($this->exp['destinatar_centru'])).$this->exp['destinatar_centru_zona']."   NT: ".$this->exp['awb']."^FS";

		//$ret .= "^FXCOD BARE^FS";
		$ret .= "^FO70,180^BY4^BCN,155,N,N,N^FD".$code."^FS^FS";
		$ret .= "^CF0,30,";
		$ret .= "^FO70,355^ASN,,^FB760,1,,C,^FD".$code."^FS";

		//$ret .= "^FXExpeditor^FS";
		$ret .= "^CF0,30,";
		$ret .= "^FO20,370^TBN,760,30^FDExpeditor:^FS";
		$ret .= "^FO20,400^AQN,,^TBN,760,30^FD".strtoupper($this->stripChars($this->exp['expeditor_nume']))."^FS";
		$ret .= "^CF0,24,";
		$ret .= "^FO20,430^AQN,,^TBN,760,30^FDCIF : ".strtoupper($this->stripChars($this->exp['expeditor_cui']))." " . $this->exp['expeditor_judet']."^FS";
		$ret .= "^FO20,460^TBN,760,90^FD".$this->stripChars($this->exp['expeditor_adresa'])." / Contact: ".$this->stripChars($this->exp['expeditor_contact'])." / Tel: ".$this->stripChars($this->exp['expeditor_telefon'])." / Loc: ".strtoupper($this->stripChars($this->exp['expeditor_localitate']))." (".strtoupper($this->stripChars($this->exp['expeditor_judet']))."), C.O.: ".strtoupper($this->exp['expeditor_centru_cod'])."^FS";

		//$ret .= "^FXSquares design^FS";
		$ret .= "^FO20,520^GB760,560,3^FS";
		$ret .= "^FO410,570^GB370,1,3^FS";
		$ret .= "^FO410,620^GB370,1,3^FS";
		$ret .= "^FO540,570^GB1,50,3^FS";
		$ret .= "^FO650,570^GB1,50,3^FS";
		$ret .= "^FO410,670^GB370,1,3^FS";
		$ret .= "^FO410,845^GB370,1,3^FS";
		$ret .= "^FO20,860^GB390,1,1^FS";
		$ret .= "^FO410,520^GB1,450,3^FS";
		$ret .= "^FO20,970^GB760,1,3^FS";

		//$ret .= "^FXDestinatar^FS";
		$ret .= "^CF0,30,";
		$ret .= "^FO30,535^TBN,370,30^FDDestinatar:^FS";
		$ret .= "^FO30,565^ARN,,^TBN,375,70^FD".strtoupper($this->stripChars($this->exp['destinatar_nume']))."^FS";
		$ret .= "^FO30,645^TBN,370,90^FD".$this->stripChars($this->exp['destinatar_adresa'])."^FS";
		$ret .= "^FO30,720^ARN,,^TBN,370,35^FD".strtoupper($this->stripChars($this->exp['destinatar_localitate']))." (".strtoupper($this->stripChars($this->exp['destinatar_judet'])).")^FS";
		$ret .= "^CF0,26,";
		$ret .= "^FO30,760^TBN,370,70^FDContact: ".strtoupper($this->stripChars($this->exp['destinatar_contact']))."^FS";
		$ret .= "^FO30,825^TBN,370,35^FDTel.: ".$this->stripChars($this->exp['destinatar_telefon'])."^FS";

		//$ret .= "^FXDescriere continut^FS";
		$ret .= "^CF0,26,";
		$ret .= "^FO30,870^TBN,370,120^FDDesc.: ".$this->stripChars($this->exp['detalii_doc'])."^FS";

		//$ret .= "^FXColectat^FS";
		$ret .= "^CF0,26,";
		$ret .= "^FO450,540^FDColectat:^FS";
		$ret .= "^FO570,540^FD".$this->exp['data_expeditie']."^FS";

		//$ret .= "^FXTip expeditie, piese, kg^FS";
		$tip = match($this->exp['tip_obj'] ?? 0) {
			1 => 'PLIC',
			2 => 'COLET',
			3 => 'PALET',
			default => 'N/A'
		};
		$ret .= "^CF0,26,";
		$ret .= "^FO416,585^FB120,1,,C,^FD".$tip."^FS";
		$ret .= "^FO536,585^FB120,1,,C,^FD".$this->exp['piese']."^FS";
		$ret .= "^FO656,585^FB120,1,,C,^FD".$this->exp['greutate']." Kg^FS";

		//$ret .= "^FXAsigurare^FS";
		$ret .= "^CF0,30,";
		$ret .= "^FO416,635^FB360,1,,C,^FDAsigurare: ".$this->exp['asigurare_text']."^FS";

		//$ret .= "^FXServicii^FS";
		$ret .= "^CF0,24,";
		$ret .= "^FO416,680^FB360,6,,L^FD".$this->exp['detalii_nt']."^FS";

		//$ret .= "^FXDe incasat^FS";
		$ret .= "^CF0,30,";
		$ret .= "^FO416,855^AQN,,^FB360,1,,C^FDDE INCASAT (cu TVA):^FS";
		$ret .= "^CF0,35,";
		$ret .= "^FO416,890^ARN,,^FB360,1,,C^FD" . $this->exp['transport_text']. ": ".$this->exp['valoare_totala_cu_tva']."^FS";
		$ret .= "^FO416,930^ARN,,^FB360,1,,C^FDRBS: ".$ramburs."^FS";


		//$ret .= "^FXObservatii^FS";
		$ret .= "^CF0,26,";
		$ret .= "^FO30,980^TBN,650,120^FDObs.: ".$this->stripChars($this->exp['observatii'])."^FS";

		if(!empty($this->exp['destinatar_centru_rut_buc'])){
			$ret .= "^FO690,985^GB80,1,80,,^FS";
			$ret .= "^FO695,1005^AUN,,^FB80,1,,C,^FR^FD".strtoupper($this->exp['destinatar_centru_rut_buc'])."^FS";
		}

		//$ret .= "^FXFooter^FS";
		$ret .= "^FO20,1090^BY3^BCN,40,N,N,N^FD".$code."^FS^FS";
		$ret .= "^FO580,1090^ASN,,^FDTel. 021-9501^FS";
		$ret .= "^CF0,20,";
		$ret .= "^FO20,1150^ADN,,^FB760,1,,C,^FD".$this->exp['footer_contact']."^FS";
		$ret .= "^XZ";

		return $ret;
	}

	function stripChars($s){
		$s = str_replace(['^','~','\\'],['','','/'],$s);
		$s = iconv('UTF-8','ASCII//TRANSLIT',$s);
		return $s;
	}
}