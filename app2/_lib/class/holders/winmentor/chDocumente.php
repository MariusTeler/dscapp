<?php

class ChDocumente implements JsonSerializable
{
	private string $sursa;
	private string $numeCasa;//$client['destinatar_centru_cod']
	private string $data;//dd.MM.yyyy
	private string $moneda;//'EUR'|'RON'|'USD'
	/** @var Tranzactii[] */
	private array $tranzactii;

	/**
	 * @param Tranzactii[] $tranzactii
	 */
	public function __construct(
		string $numeCasa,
		string $data,
		string $moneda,
		array $tranzactii
	) {
		$this->sursa = 'CASA';
		$this->numeCasa = $numeCasa;
		$this->data = $data;
		$this->moneda = $moneda;
		$this->tranzactii = $tranzactii;
	}

	public static function fromJson(\stdClass $data): self
	{
		return new self(
			$data->NumeCasa,
			$data->Data,
			$data->Moneda,
			array_map(static function($data) {
				return Tranzactii::fromJson($data);
			}, $data->Tranzactii)
		);
	}

	public function jsonSerialize(): mixed
	{
		return [
			'Sursa' => $this->sursa,
			'NumeCasa' => $this->numeCasa,
			'Data' => $this->data,
			'Moneda' => $this->moneda,
			'Tranzactii' => $this->tranzactii
		];
	}
}