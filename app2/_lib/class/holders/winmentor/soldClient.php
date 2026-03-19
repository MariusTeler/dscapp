<?php

class SoldClient implements JsonSerializable
{
	private string $IDPartener;
    private string $CodFiscal;
    private string $Denumire;
    private string $TipDocument;
	private string $Subunitatea;
	private string $CodSubunitate;
	private string $CodSIUI;
	private string $CodDocument;
    private string $Serie;
	private string $Numar;
	private string $Data;
	private string $Valoare;
	private string $Rest;
	private string $Termen;
	private string $Moneda;
	private string $Curs;
	private string $Sediu;
	private string $IDSediu;
	private string $Observatii;
	private string $CodObligatie;
	private string $MarcaAgent;
	// Example data structure:
	/*
			"IDPartener": "121012",
            "CodFiscal": "RO16484179",
            "Denumire": "CURIER TRANS SERVICE S.R.L.",
            "TipDocument": "Factura",
            "Subunitatea": "Sediu Central",
            "CodSubunitate": "2",
            "CodSIUI": "",
            "CodDocument": "898082",
            "Serie": "DSCL",
            "Numar": "1013490",
            "Data": "28.02.2025",
            "Valoare": "139815,54",
            "Rest": "44323,17",
            "Termen": "14.04.2025",
            "Moneda": "RON",
            "Curs": "1",
            "Sediu": "CURIER TRANS SERVICE S.R.L.",
            "IDSediu": "",
            "Observatii": "",
            "CodObligatie": "4409896",
            "MarcaAgent": ""
	*/
	public function __construct(
		string $IDPartener,
		string $CodFiscal,
		string $Denumire,
		string $TipDocument,
		string $Subunitatea,
		string $CodSubunitate,
		string $CodSIUI,
		string $CodDocument,
		string $Serie,
		string $Numar,
		string $Data,
		string $Valoare,
		string $Rest,
		string $Termen,
		string $Moneda,
		string $Curs,
		string $Sediu,
		string $IDSediu,
		string $Observatii,
		string $CodObligatie,
		string $MarcaAgent
	) {
		$this->IDPartener = $IDPartener;
		$this->CodFiscal = $CodFiscal;
		$this->Denumire = $Denumire;
		$this->TipDocument = $TipDocument;
		$this->Subunitatea = $Subunitatea;
		$this->CodSubunitate = $CodSubunitate;
		$this->CodSIUI = $CodSIUI;
		$this->CodDocument = $CodDocument;
		$this->Serie = $Serie;
		$this->Numar = $Numar;
		$this->Data = $Data;
		$this->Valoare = $Valoare;
		$this->Rest = $Rest;
		$this->Termen = $Termen;
		$this->Moneda = $Moneda;
		$this->Curs = $Curs;
		$this->Sediu = $Sediu;
		$this->IDSediu = $IDSediu;
		$this->Observatii = $Observatii;
		$this->CodObligatie = $CodObligatie;
		$this->MarcaAgent = $MarcaAgent;
	}

	public static function fromJson(\stdClass $data): self
	{
		return new self(
			$data->IDPartener,
			$data->CodFiscal,
			$data->Denumire,
			$data->TipDocument,
			$data->Subunitatea,
			$data->CodSubunitate,
			$data->CodSIUI,
			$data->CodDocument,
			$data->Serie,
			$data->Numar,
			$data->Data,
			$data->Valoare,
			$data->Rest,
			$data->Termen,
			$data->Moneda,
			$data->Curs,
			$data->Sediu,
			$data->IDSediu,
			$data->Observatii,
			$data->CodObligatie,
			$data->MarcaAgent
		);
	}

	public function jsonSerialize(): mixed
	{
		return [
			'IDPartener' => $this->IDPartener,
			'CodFiscal' => $this->CodFiscal,
			'Denumire' => $this->Denumire,
			'TipDocument' => $this->TipDocument,
			'Subunitatea' => $this->Subunitatea,
			'CodSubunitate' => $this->CodSubunitate,
			'CodSIUI' => $this->CodSIUI,
			'CodDocument' => $this->CodDocument,
			'Serie' => $this->Serie,
			'Numar' => $this->Numar,
			'Data' => $this->Data,
			'Valoare' => $this->Valoare,
			'Rest' => $this->Rest,
			'Termen' => $this->Termen,
			'Moneda' => $this->Moneda,
			'Curs' => $this->Curs,
			'Sediu' => $this->Sediu,
			'IDSediu' => $this->IDSediu,
			'Observatii' => $this->Observatii,
			'CodObligatie' => $this->CodObligatie,
			'MarcaAgent' => $this->MarcaAgent
		];
	}
	
}