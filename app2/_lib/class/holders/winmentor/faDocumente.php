<?php

class FaDocumente implements JsonSerializable
{
	private string $simbolCarnet;//serie factura
	private string $nrDoc;//nr factura
	private string $codClient;//cod_client
	private string $operat;
	private string $operatie;
	private string $data;//trndate dd.MM.yyyy
	private string $anulat;// 'D' daca trimit factura anulata
	private string $tipTva;// $client['tip_tva'] > 0 ? $client['tip_tva'] : 1
	private string $tipTranzactie;//$client['facturare_tip_tranzactie'] > 0 ? $client['facturare_tip_tranzactie'] : 1
	private string $tvaLaIncasare;//$client['tva_incasare'] == 1 ? 'DA' : 'NU' :
	private string $moneda;// 'EUR'|'RON'|'USD'
	private string $curs;
	private string $tipSaft;//0 (initiala), 1 (storno)
	private string $observatii;
	private string $pretAmanunt;// 'DA'|'NU' : cand se pune pe DA se trimite valoare cu TVA : NU la facturile periodice, DA la facturile de android
	private string $scadenta;//$termen = intval($client['termen_plata']); $data_factura = date("d.m.Y", strtotime($factura['trndate'])); $data_scadenta = date("d.m.Y", strtotime($data_factura. " + {$termen} days"));
	/** @var Items[] */
	private array $items;

	/**
	 * @param Items[] $items
	 */
	public function __construct(
		string $simbolCarnet,
		string $nrDoc,
		string $codClient,
		string $data,
		string $tipTva,
		string $tipTranzactie,
		string $tvaLaIncasare,
		string $moneda,
		string $tipSaft,
		string $observatii,
		string $pretAmanunt,
		string $scadenta,
		array $items
	) {
		$this->simbolCarnet = $simbolCarnet;
		$this->nrDoc = $nrDoc;
		$this->codClient = $codClient;
		$this->operat = 'D';
		$this->operatie = 'A';
		$this->data = $data;
		$this->anulat = 'N';
		$this->tipTva = $tipTva == 0 ? '1' : ''.$tipTva;
		$this->tipTranzactie = $tipTranzactie == 0 ? 1 : $tipTranzactie;
		$this->tvaLaIncasare = $tvaLaIncasare == 1 ? 'DA' : 'NU';
		$this->moneda = $moneda;
		$this->curs = '1';
		$this->tipSaft = $tipSaft;
		$this->observatii = $observatii;
		$this->pretAmanunt = $pretAmanunt ? 'DA' : 'NU';
		$this->scadenta = $scadenta;
		$this->items = $items;
	}

	public static function fromJson(\stdClass $data): self
	{
		return new self(
			$data->SimbolCarnet,
			$data->NrDoc,
			$data->CodClient,
			$data->Operatie,
			$data->Data,
			$data->TipTVA,
			$data->TipTranzactie,
			$data->TVALaIncasare,
			$data->Moneda,
			$data->TipSAFT,
			$data->Observatii,
			$data->PretAmanunt,
			$data->Scadenta,
			array_map(static function($data) {
				return Items::fromJson($data);
			}, $data->Items)
		);
	}

	public function jsonSerialize(): mixed
	{
		return [
			'SimbolCarnet' => $this->simbolCarnet,
			'NrDoc' => $this->nrDoc,
			'CodClient' => $this->codClient,
			'Operat' => $this->operat,
			'Operatie' => $this->operatie,
			'Data' => $this->data,
			'Anulat' => $this->anulat,
			'TipTVA' => $this->tipTva,
			'TipTranzactie' => $this->tipTranzactie,
			'TVALaIncasare' => $this->tvaLaIncasare,
			'Moneda' => $this->moneda,
			'Curs' => $this->curs,
			'TipSAFT' => $this->tipSaft,
			'Observatii' => $this->observatii,
			'PretAmanunt' => $this->pretAmanunt,
			'Scadenta' => $this->scadenta,
			'Items' => $this->items
		];
	}

}