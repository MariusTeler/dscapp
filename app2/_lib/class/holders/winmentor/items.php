<?php

class Items implements JsonSerializable
{
	private string $idArticol;//SC{$factura['procTva']}
	private string $cant;//una factura
	private string $pret; //$factura['sumamnt'] : daca se face storno, pretul trebuie sa fie negativ
	private string $observatii;// $denumire_serviciu = WinMentorNewApi::SERVICIU_CURIERAT; $denumire_serviciu .= " Expeditii facturate:".$factura['expeditii'];
	private string $simbolCentruCost;

	public function __construct(
		string $idArticol,
		string $cant,
		string $pret,
		string $simbolCentruCost,
		string $observatii
	) {
		$this->idArticol = $idArticol;
		$this->cant = $cant;
		$this->pret = $pret;
		$this->simbolCentruCost = $simbolCentruCost;
		$this->observatii = $observatii;
	}

	public static function fromJson(\stdClass $data): self
	{
		return new self(
			$data->IDArticol,
			$data->Cant,
			$data->Pret,
			$data->SimbolCentruCost,
			$data->Observatii
		);
	}

	public function jsonSerialize(): mixed
	{
		return [
			'IDArticol' => $this->idArticol,
			'Cant' => $this->cant,
			'Pret' => $this->pret,
			'Observatii' => $this->observatii,
			'SimbolCentruCost' => $this->simbolCentruCost
		];
	}
	
}