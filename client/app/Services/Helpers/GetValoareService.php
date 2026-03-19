<?php
namespace App\Services\Helpers;

use Illuminate\Support\Facades\DB;
use App\Data\AwbData;
use App\Services\Tarife\Tarif;
use App\Services\Tarife\TarifDet;
use App\Services\Tarife\TarifG;
use App\Services\Helpers\ClientService;
use Illuminate\Support\Facades\Log;
use \StdClass;

class GetValoareService
{
    public static function valoareInitialaClient(AwbData $awb_data): StdClass
    {
		$result = new StdClass();
        $result->tExpeditie = $result->tBaza = $result->tRetururi = $result->tAmb = $result->tOpt = $result->tKm = $result->tGreutate = 0;
        $result->tAsigurare = $result->tRamburs = $result->procAsigurare = $result->procRamburs = 0.00;
        $result->moneda = config('awb.moneda')[$awb_data->moneda] ?? 'RON'; //RON
        $swapped = ($awb_data->swapped == 1) ? true : false;

        Log::debug("swapped: " . intval($swapped));
        $client_infos = ClientService::infos(!$swapped ? $awb_data->expeditor_id : $awb_data->destinatar_id);
        //Log::debug("GetValoareService::valoareInitialaClient client : " . (!$swapped ? $awb_data->expeditor_id : $awb_data->destinatar_id) . "  ");
        //Log::debug("GetValoareService::valoareInitialaClient client : " . json_encode($client_infos));
        $mod_plata = ($awb_data->platitor == 1 && !$swapped) || ($awb_data->platitor == 2 && $swapped) ? $client_infos->mod_plata : 0;

        //incarcam tariful
		$tarif = $tarifLista = Tarif::select(0);

        if((!$swapped && ($awb_data->platitor == 1 && $client_infos->contract == 1 || $awb_data->platitor == 2 && $client_infos->taxa_destinatie == 1))
            || ($swapped && ($awb_data->platitor == 2 && $client_infos->contract == 1 || $awb_data->platitor == 1 && $client_infos->taxa_destinatie == 1))) {
                $tarif = Tarif::select($client_infos->cod_cl);
                Log::debug("GetValoareService::valoareInitialaClient tarif : {$client_infos->cod_cl} : " . json_encode($tarif));
        }

		//tip tarif	det
		$tip_tarif = TarifDet::NATIONAL;
		if($awb_data->expeditor_localitate_id == $awb_data->destinatar_localitate_id) $tip_tarif = TarifDet::LOCO;

		$tarif_det = TarifDet::select($tarif->id, $tip_tarif);
        Log::debug("GetValoareService::valoareInitialaClient tarif_det : tarif_id = {$tarif->id} , tip_tarif = {$tip_tarif} : " . json_encode($tarif_det));
        $tarif_g = TarifG::select($tarif_det->id, $tip_tarif);
        Log::debug("GetValoareService::valoareInitialaClient tarif_g : tarif_det_id = {$tarif_det->id} , tip_tarif = {$tip_tarif} : " . json_encode($tarif_g));
        if($tarif_g === null || $tarif_g->isEmpty()) {
            throw new \Exception('Nu exista tarife G pentru clientul ' . (!$swapped ? $awb_data->expeditor_id : $awb_data->destinatar_id) . ' !');
        }
		//tip tarif	g
		$result->tBaza = $tarif_det->colet;
		//facturare pe km si greutate la paleti
		if($awb_data->tip_obj == 3)
		{
            $result->tBaza = $tarif_det->palet;
            $km_dist = max(1, self::calculateKMDist($awb_data->expeditor_localitate_id, $awb_data->destinatar_localitate_id));
            $tarif_g = TarifG::select($tarif_det->id, $tip_tarif, TarifG::PALET, $km_dist);
            if($tarif_g === null || $tarif_g->isEmpty()) {
                throw new \Exception('Nu exista tarife G pentru paleti la clientul ' . (!$swapped ? $awb_data->expeditor_id : $awb_data->destinatar_id) . ' !');
            }
            //daca palet si tarif colet
		    if($tarif_g->first() != null && $tarif_g->first()->tipObj == TarifG::COLET)
                $result->tBaza = $tarif_det->colet;
		}
        else if($awb_data->tip_obj == 1) {
            $result->tBaza = $tarif_det->plic;
        }

        if($tarif->tarif_proc_indexc > 0)
            $result->tBaza += ($result->tBaza  * $tarif->tarif_proc_indexc) / 100;

		//greutate
		if($awb_data->tip_obj == 2 || $awb_data->tip_obj == 3) $result->tGreutate = TarifG::getValoareGreutate($tarif_g, $awb_data->greutate, ($awb_data->greutate_vol ?? 0), $tarif->tarif_proc_indexc);

		//valoare asigurare
		if($awb_data->asigurare > 0) {
            $result->tAsigurare = ($awb_data->asigurare  * $tarif_det->proc_asig)/100;
            $result->procAsigurare = $tarif_det->proc_asig;
        }

		//livrare sediu
		if(!empty($awb_data->liv_sed))
            $result->tOpt += $tarif_det->liv_sed;

		//livrare sambata
		if(!empty($awb_data->liv_samb))
            $result->tOpt += $tarif_det->liv_samb;

		//sms la livrare
        //mod_plata == 0 se plateste un singur sms si se pot trimite maximum 3
        //mod_plata == 1 se plateste per sms la scanare iesire curier
        if($mod_plata == 0)
            $result->tOpt += !empty($awb_data->sms) ? ($tarif->tarif_sms > 0 ? $tarif->tarif_sms : $tarifLista->tarif_sms) : 0.00;

		//deschidere colet
		if(!empty($awb_data->copen))
            $result->tOpt += $tarif->tarif_open > 0 ? $tarif->tarif_open : $tarifLista->tarif_open;
        //km exteriori
		$result->tKm = Tarif::getValoareKM($tarif, $awb_data->km_preluare, $awb_data->km_livrare);

        //valoare retururi
		//OPTIUNE taxa unica la retururi obligatorie pentru toata lumea
        $plataRetururiLaInitiala = !$swapped ? ($mod_plata == 0 || $awb_data->platitor == 2 || $client_infos->contract == 2 || ($client_infos->contract == 1 && !empty($tarif->taxa_expediere))) : true;
		//OPTIUNE taxa retururi la expediere : daca are aceasta optiune, retururile se platesc la initiala
		if($plataRetururiLaInitiala)
		{
			if((!empty($awb_data->ret_nt) || !empty($awb_data->extrainfo)) && empty($awb_data->ret_doc))
				$result->tRetururi += $tarif_det->retur_nt;
			else if(empty($awb_data->ret_nt) && empty($awb_data->extrainfo) && !empty($awb_data->ret_doc))
				$result->tRetururi += $tarif_det->retur_doc;
			else if((!empty($awb_data->ret_nt) || !empty($awb_data->extrainfo)) && !empty($awb_data->ret_doc))
				$result->tRetururi += max($tarif_det->retur_nt, $tarif_det->retur_doc);

            //index combustibil
            if($tarif->tarif_proc_indexc > 0)
                $result->tRetururi += ($result->tRetururi * $tarif->tarif_proc_indexc) / 100;

            $tRetururiRamburs = 0.00;
            //ramburs
            if($awb_data->ramburs > 0)
            {
                //tip plata ramburs
                if(isset($awb_data->tip_plata) && ($awb_data->tip_plata == 1 || $awb_data->tip_plata == 2)) {
                    $tarif_det->taxa_ramb = max($tarif_det->retur_nt, $tarif_det->retur_doc);
                    $tarif_det->asig_ramb = 0;
                }
                //OPTIUNE taxa de ramburs include retururile : plateste la expeditie maximum din trei taxe
                if(!empty($tarif->taxa_ramburs))
                {
                    $result->tRetururi = 0;
 
                    if((!empty($awb_data->ret_nt) || !empty($awb_data->extrainfo)) && empty($awb_data->ret_doc))
                        $tRetururiRamburs += max($tarif_det->retur_nt, $tarif_det->taxa_ramb);
                    else if(empty($awb_data->ret_nt) && empty($awb_data->extrainfo) && !empty($awb_data->ret_doc))
                        $tRetururiRamburs += max($tarif_det->retur_doc,$tarif_det->taxa_ramb);
                    else if((!empty($awb_data->ret_nt) || !empty($awb_data->extrainfo)) && !empty($awb_data->ret_doc))
                        $tRetururiRamburs += max($tarif_det->retur_nt, $tarif_det->retur_doc, $tarif_det->taxa_ramb);
                    else
                        $tRetururiRamburs += $tarif_det->taxa_ramb;
                }
                else
                    $tRetururiRamburs += $tarif_det->taxa_ramb;

                $result->tRamburs += ($awb_data->ramburs * $tarif_det->asig_ramb) / 100;
                $result->procRamburs = $tarif_det->asig_ramb;

                //index combustibil
                if($tarif->tarif_proc_indexc > 0)
                    $tRetururiRamburs += ($tRetururiRamburs * $tarif->tarif_proc_indexc) / 100;
            }

            $result->tRetururi += $tRetururiRamburs;

            if(!empty($awb_data->ret_amb))
            {
                $result->tAmb += $tarif_det->colet;
                $kgRetAmb = $client_infos->kg_ret_amb > 0 ? $client_infos->kg_ret_amb : $tarifLista->kg_ret_amb;
                $result->tAmb += TarifG::getValoareGreutate($tarif_g, $kgRetAmb, 0, $tarif->tarif_proc_indexc);
                //index combustibil
                if($tarif->tarif_proc_indexc > 0)
                    $result->tAmb += ($result->tAmb * $tarif->tarif_proc_indexc) / 100;
            }

            //daca are retur nt, retur nota, retur doc, ramburs, retur amb
            if(!empty($awb_data->ret_nt) || !empty($awb_data->extrainfo) || !empty($awb_data->ret_doc) || $awb_data->ramburs  > 0 || !empty($awb_data->ret_amb))
                $result->tKm  += Tarif::getValoareKM($tarif, 0, $awb_data->km_preluare);
        }

		$result->tBaza = round($result->tBaza, 2);
        $result->tRetururi = round($result->tRetururi, 2);
        $result->tAmb = round($result->tAmb, 2);
        $result->tOpt = round($result->tOpt, 2);
        $result->tExpeditie = round($result->tBaza + $result->tRetururi + $result->tAmb + $result->tOpt, 2);
		$result->tKm = round($result->tKm, 2);
		$result->tGreutate = round($result->tGreutate, 2);
        $result->tAsigurare = round($result->tAsigurare, 2);
        $result->procAsigurare = round($result->procAsigurare, 2);
        $result->tRamburs = round($result->tRamburs, 2);
        $result->procRamburs = round($result->procRamburs, 2);
		$result->moneda = $tarif->moneda;
		$result->mod_plata = $mod_plata;
        $result->tHt = round($result->tExpeditie + $result->tKm + $result->tGreutate + $result->tAsigurare + $result->tRamburs, 2);

		return $result;
	}

    public static function valoareInitiala(AwbData $initialaRow): StdClass
    {
        $result = new StdClass();
        $result->tExpeditie = $result->tGreutate = $result->tKm = $result->tBaza = $result->tRetururi = $result->tAmb = $result->tOpt = $result->tAsigurare = 0.00;
        $result->tRamburs = $result->asigurare = $result->procAsigurare = $result->ramburs = $result->procRamburs = $result->tKmPreluare = $result->tKmLivrare =  0.00;
		$result->moneda = config('awb.moneda')[$initialaRow->moneda] ?? 'RON'; //RON
		$result->mod_plata = 0;

        //greutate
        //$greutate = ceil($initialaRow->greutate ?? 0);
		$greutate = round($initialaRow->greutate ?? 0, 2);
        //asigurare, ramburs
        $result->asigurare = round($initialaRow->asigurare ?? 0, 2);
		$result->ramburs = round($initialaRow->ramburs ?? 0, 2);
        //km
		$kmPreluare = $initialaRow->km_preluare ?? 0;
		$kmLivrare = $initialaRow->km_livrare ?? 0;

        $expeditor_id = intval($initialaRow->expeditor_id ?? 0);
        $destinatar_id = intval($initialaRow->destinatar_id ?? 0);
        $platitor_id = intval($initialaRow->platitor_id ?? 0);

		if(empty($expeditor_id) || empty($destinatar_id) || empty($platitor_id)) {
            error_log("debug valoareInitiala : {$expeditor_id}  : {$destinatar_id} : {$platitor_id}");
        }

        // cod_cl, activ, tarif, mod_plata, cc, icc, taxa_destinatie, ret_amb, kg_ret_amb, tarif_sms
        $platitor_infos = ClientService::infos($platitor_id);
        $tip_tarif = $initialaRow->tip_tarif ?? (($initialaRow->expeditor_localitate_id ?? 0) != ($initialaRow->destinatar_localitate_id ?? 1) ? TarifDet::NATIONAL : TarifDet::LOCO);
        $tip_obj = $initialaRow->tip_obj ?? (($greutate >= 1) ? 2 : 1);
        $mod_plata = $platitor_infos->mod_plata ?? 0;

        //incarc tarife
        $tarif = $tarifLista = Tarif::select(0);
        //daca platitorul are tarif de lista sau mod plata per NT : plata retururi se face la initiala
        $plataRetururiLaInitiala = ($mod_plata == 0 || $platitor_infos->contract == 2 || $platitor_id == $destinatar_id);
        $kgRetAmb = ($platitor_infos->contract == 1 && $platitor_infos->kg_ret_amb > 0) ? $platitor_infos->kg_ret_amb :  $tarifLista->kg_ret_amb;
        if($platitor_id == $destinatar_id) {
            $expeditor_infos = ClientService::infos($expeditor_id);
            $expeditor_taxa_destinatie = $expeditor_infos->contract == 1 ? $expeditor_infos->taxa_destinatie ?? 0 : 0;
            if($expeditor_taxa_destinatie > 0){
                $tarif = Tarif::select($expeditor_infos->cod_cl);
            }
            else {
                $tarif = Tarif::select($platitor_infos->cod_cl);
            }
            //kg retur ambalaj : se iau tot timpul din contractul expeditorului
            if(!empty($initialaRow->ret_amb))
                $kgRetAmb = $expeditor_infos->kg_ret_amb > 0 ? $expeditor_infos->kg_ret_amb : $kgRetAmb;
        }
        else{
            if($platitor_infos->contract == 1){
                //error_log("platitor : " . $platitor_infos->cod_cl);
                $tarif = Tarif::select($platitor_infos->cod_cl);
                //Taxa retururi la initiala
                if($mod_plata > 0)
                    $plataRetururiLaInitiala = !empty($tarif->taxa_expediere);
            }
            //plata la tertz : kg retur ambalaj : se iau tot timpul din contractul expeditorului
            if(!empty($initialaRow->ret_amb) && $platitor_id != $expeditor_id) {
                $expeditor_infos = ClientService::infos($expeditor_id);
                //daca expeditorul are contract negociat si nu este puisor al tertzului
                if($expeditor_infos->contract == 1 && $expeditor_infos->cod_cl != $platitor_infos->cod_cl)
                    $kgRetAmb = $expeditor_infos->kg_ret_amb > 0 ? $expeditor_infos->kg_ret_amb : $kgRetAmb;
            }
        }

        $tarif_det = TarifDet::select($tarif->id, $tip_tarif);
        $tarif_g = TarifG::select($tarif_det->id, $tip_tarif);

        //error_log("{$tarif->id} : {$platitor_infos->cod_cl} : {$tarif_det->id}");

        //error_log("debug valoareInitiala : tarif : " . ($platitor_id == $destinatar_id && $expeditor_taxa_destinatie > 0) ? $expeditor_id : $platitor_id);

        $result->tBaza = $tarif_det->colet;
        $greutate_vol = !empty($initialaRow->greutate_vol) ? $initialaRow->greutate_vol : 0;

        //error_log("{$tip_tarif} : {$result->tBaza} : {$tarif_det->plic} : {$tarif_det->colet} : {$tarif_det->palet} : ");

        switch($tip_obj) {
            case 1 :
                $result->tBaza = $tarif_det->plic;
                break;
            case 3 :
                $result->tBaza = $tarif_det->palet;
                $km_dist = max(1, self::calculateKMDist($initialaRow->expeditor_localitate_id, $initialaRow->destinatar_localitate_id));
                $tarif_g = TarifG::select($tarif_det->id, $tip_tarif, TarifG::PALET, $km_dist);

                $result->tGreutate = TarifG::getValoareGreutate($tarif_g, $greutate, $greutate_vol, $tarif->tarif_proc_indexc);
                if($tarif_g->first() != null && $tarif_g->first()->tipObj == TarifG::COLET)
                    $result->tBaza = $tarif_det->colet;
                break;
            default :
                $result->tBaza = $tarif_det->colet;
                $result->tGreutate = TarifG::getValoareGreutate($tarif_g, $greutate, $greutate_vol, $tarif->tarif_proc_indexc);
        }

        if($tarif->tarif_proc_indexc > 0)
            $result->tBaza += ($result->tBaza * $tarif->tarif_proc_indexc) / 100;

        //valoare asigurare : se ia la cea mai mare dintre ramburs si asigurare
        if($result->asigurare > 0)
            $result->tAsigurare= ($result->asigurare * $tarif_det->proc_asig)/100;

        //livrare sediu : poate fi negativ sau procent din (tbaza + tkg)
        if(!empty($initialaRow->liv_sed)){
            $result->tOpt += $tarif_det->liv_sed;
            //la livrare sediu : kmLivrare se anuleaza, interdictie la livrare din android
            //$kmLivrare = 0;
        }

        //livrare sambata
        if(!empty($initialaRow->liv_samb))
            $result->tOpt += $tarif_det->liv_samb;

        //sms la livrare
        //mod_plata == 0 se plateste un singur sms si se pot trimite maximum 3
        //mod_plata == 1 se plateste per sms
        if($mod_plata == 0)
            $result->tOpt += !empty($initialaRow->sms) ? ($tarif->tarif_sms > 0 ? $tarif->tarif_sms : $tarifLista->tarif_sms) : 0.00;
        else if(isset($initialaRow->sms) && intval($initialaRow->sms) > 0)
            $result->tOpt += intval($initialaRow->sms) * ($tarif->tarif_sms > 0 ? $tarif->tarif_sms : $tarifLista->tarif_sms);

        //deschidere colet
        if(!empty($initialaRow->copen))
            $result->tOpt += $tarif->tarif_open > 0 ? $tarif->tarif_open : $tarifLista->tarif_open;
        //km
        //if($this->user_id == self::MARIAN)
        //    error_log(print_r($tarif, true));
        $result->tKmPreluare = Tarif::getValoareKM($tarif, $kmPreluare, 0);
        $result->tKmLivrare = Tarif::getValoareKM($tarif, 0, $kmLivrare);
        $result->tKm = $result->tKmPreluare + $result->tKmLivrare;

        //OPTIUNE taxa unica la retururi obligatorie pentru toata lumea
        //OPTIUNE taxa retururi la expediere : daca are aceasta optiune, retururile se platesc la initiala
        if($plataRetururiLaInitiala)
        {
            //maravet
            $ret_nt = $initialaRow->ret_nt ?? false;
            if(!empty($initialaRow->extrainfo)) $ret_nt = true;
            //return nt, retur doc
            if(!empty($ret_nt) || !empty($initialaRow->ret_doc)){
                if(!empty($ret_nt) && empty($initialaRow->ret_doc))
                    $result->tRetururi += $tarif_det->retur_nt;
				else if(empty($ret_nt) && !empty($initialaRow->ret_doc))
                    $result->tRetururi += $tarif_det->retur_doc;
				else if(!empty($ret_nt) && !empty($initialaRow->ret_doc))
                    $result->tRetururi += max($tarif_det->retur_nt,$tarif_det->retur_doc);
                //index combustibil
                if($tarif->tarif_proc_indexc > 0)
                    $result->tRetururi += ($result->tRetururi * $tarif->tarif_proc_indexc) / 100;
            }

			//ramburs
            $tRetururiRamburs = 0.00;
			if($result->ramburs > 0)
			{
				//tip plata ramburs : de anulat si modificat raport Scanare->Istoric scanari : coloana Asig/Ramb
				$tip_plata_rbs = $initialaRow->tip_plata ?? 0;
                if($tip_plata_rbs == 1 || $tip_plata_rbs == 2){
                    $tarif_det->taxa_ramb = max($tarif_det->retur_nt,$tarif_det->retur_doc);
                    $tarif_det->asig_ramb = 0;
                }

				//OPTIUNE taxa de ramburs include retururile : plateste la expeditie maximum din trei taxe
				if(!empty($tarif->taxa_ramburs))
				{
                    $result->tRetururi = 0;
                    $ret_nt = $initialaRow->ret_nt ?? false;
                    //maravet
                    if(!empty($initialaRow->extrainfo)) $ret_nt = true;

                    if(!empty($ret_nt) && empty($initialaRow->ret_doc))
						$tRetururiRamburs += max($tarif_det->retur_nt,$tarif_det->taxa_ramb);
					else if(empty($ret_nt) && !empty($initialaRow->ret_doc))
						$tRetururiRamburs += max($tarif_det->retur_doc,$tarif_det->taxa_ramb);
					else if(!empty($ret_nt) && !empty($initialaRow->ret_doc))
						$tRetururiRamburs += max($tarif_det->retur_nt,$tarif_det->retur_doc,$tarif_det->taxa_ramb);
					else
						$tRetururiRamburs += $tarif_det->taxa_ramb;
				}
				else
                    $tRetururiRamburs += $tarif_det->taxa_ramb;
				$result->tRamburs += ($result->ramburs * $tarif_det->asig_ramb)/100;

                //index combustibil
                if($tarif->tarif_proc_indexc > 0 && $tip_plata_rbs != 3)
                    $tRetururiRamburs += ($tRetururiRamburs * $tarif->tarif_proc_indexc) / 100;
			}

            $result->tRetururi += $tRetururiRamburs;

            if(!empty($initialaRow->ret_amb))
            {
                $result->tAmb += $tarif_det->colet;
                $result->tAmb += TarifG::getValoareGreutate($tarif_g, $kgRetAmb, 0, $tarif->tarif_proc_indexc);
                //index combustibil
                if($tarif->tarif_proc_indexc > 0)
                    $result->tAmb += ($result->tAmb * $tarif->tarif_proc_indexc) / 100;
            }

            //daca are retur nt, retur doc, retur amb, ramburs : km la livrare
            if(!empty($initialaRow->ret_nt) || !empty($initialaRow->extrainfo) || !empty($initialaRow->ret_doc) || $result->ramburs > 0 || !empty($initialaRow->ret_amb))
                $result->tKm += Tarif::getValoareKM($tarif, 0, $kmPreluare);
        }

        /////////////////////////////////
        //if($this->user_id == self::MARIAN)
        //error_log("{$tip_tarif} : {$tip_obj} : {$result->tBaza} : {$result->tRetururi} : {$result->tAmb} : {$result->tOpt} : {$result->tKm} : {$result->tAsigurare} : {$result->tRamburs} ");
		//rezultate
		$result->tBaza = round($result->tBaza, 2);
        $result->tRetururi = round($result->tRetururi, 2);
        $result->tAmb = round($result->tAmb, 2);
        $result->tOpt = round($result->tOpt, 2);
        $result->tExpeditie = round($result->tBaza + $result->tRetururi + $result->tAmb + $result->tOpt, 2);
		$result->tKm = round($result->tKm, 2);
		$result->tGreutate = round($result->tGreutate, 2);
        $result->tAsigurare = round($result->tAsigurare, 2);
        $result->tRamburs = round($result->tRamburs, 2);
		$result->asigurare = round($result->asigurare, 2);
        if($result->asigurare >= 1)
		    $result->procAsigurare = round($tarif_det->proc_asig, 2);
		$result->ramburs = round($result->ramburs, 2);
        if($result->ramburs >= 1)
		    $result->procRamburs = round($tarif_det->asig_ramb, 2);
        //error_log("{$result->ramburs} : {$tarif_det->asig_ramb}");
		$result->moneda = $tarif->moneda;
		$result->mod_plata = $mod_plata;
        $result->greutate = round($greutate, 3);
        //error_log("debug valoareInitiala : mod_plata2 " . $mod_plata);

		if(!empty($initialaRow->awb))  $result->awb = $initialaRow->awb;
		return $result;
	}

    private static function calculateKMDist(int $expeditor_localitate_id, int $destinatar_localitate_id): float
    {
        //imi trebuie id centru expeditie si id centrul dest ca sa aflu km in tabla centre_km
        $id_centru_exp = DB::table('localitati')->where('cod_lc', $expeditor_localitate_id)->first('cod_centru');
        if($id_centru_exp === null) {
            return 0.0;
        }
        $id_centru_exp = $id_centru_exp->cod_centru;
        $id_centru_dest = DB::table('localitati')->where('cod_lc', $destinatar_localitate_id)->first('cod_centru');
        if($id_centru_dest === null) {
            return 0.0;
        }
        $id_centru_dest = $id_centru_dest->cod_centru;
        $km_dist = DB::table('centre_km')
            ->where(function ($query) use ($id_centru_exp, $id_centru_dest) {
                $query->where('id_centru_exp', $id_centru_exp)
                      ->where('id_centru_dest', $id_centru_dest);
            })
            ->orWhere(function ($query) use ($id_centru_exp, $id_centru_dest) {
                $query->where('id_centru_exp', $id_centru_dest)
                      ->where('id_centru_dest', $id_centru_exp);
            })
            ->first('km');
        if($km_dist === null) {
            return 0.0;
        }
        return $km_dist->km;
    }
}