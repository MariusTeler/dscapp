<?php

class DistribuireValoare implements JsonSerializable
{
	private string $reprezinta;
	private string $numarFactura;
	private string $serieFactura;
	private string $valoare;// cu TVA

	public function __construct(
		string $serieFactura,
		string $numarFactura,
		string $valoare
	) {
		$this->reprezinta = 'Factura';
		$this->serieFactura = $serieFactura;
		$this->numarFactura = $numarFactura;
		$this->valoare = $valoare;
	}

	public static function fromJson(\stdClass $data): self
	{
		return new self(
			$data->SerieFactura,
			$data->NumarFactura,
			$data->Valoare
		);
	}

	public function jsonSerialize(): mixed
	{
		return [
			'Reprezinta' => $this->reprezinta,
			'SerieFactura' => $this->serieFactura,
			'NumarFactura' => $this->numarFactura,
			'Valoare' => $this->valoare
		];
	}
}