<?php

class Tranzactii implements JsonSerializable
{
	private string $tipTranzactie;
	private string $tipDoc;
	private string $serieDoc;//serie chitanta : carnet de documente
	private string $nrDoc;//numar chitanta
	private string $obiectTranzactie;
	private string $data;//trndate : dd.MM.yyyy
	private string $curs;
	private string $idPartener;//cod_cl
	private string $valoare;//cu TVA
	private string $simbolCentruCost;//$client['destinatar_centru_cod']
	private string $obs;
	private string $anulat;// 'DA' pentru chitanta anulata
	/** @var DistribuireValoare[] */
	private array $distribuireValoare;

	/**
	 * @param DistribuireValoare[] $distribuireValoare
	 */
	public function __construct(
		string $serieDoc,
		string $nrDoc,
		string $data,
		string $idPartener,
		string $valoare,
		string $simbolCentruCost,
		array $distribuireValoare
	) {
		$this->tipTranzactie = 'Incasare';
		$this->tipDoc = 'Chit';
		$this->serieDoc = $serieDoc;
		$this->nrDoc = $nrDoc;
		$this->obiectTranzactie = 'Client';
		$this->data = $data;
		$this->curs = '1';
		$this->idPartener = $idPartener;
		$this->valoare = $valoare;
		$this->simbolCentruCost = $simbolCentruCost;
		$this->obs = '';
		$this->anulat = 'NU';
		$this->distribuireValoare = $distribuireValoare;
	}

	public static function fromJson(\stdClass $data): self
	{
		return new self(
			$data->SerieDoc,
			$data->NrDoc,
			$data->Data,
			$data->IDPartener,
			$data->Valoare,
			$data->SimbolCentruCost,
			array_map(static function($data) {
				return DistribuireValoare::fromJson($data);
			}, $data->DistribuireValoare)
		);
	}

	public function jsonSerialize(): mixed
	{
		return [
			'TipTranzactie' => $this->tipTranzactie,
			'TipDoc' => $this->tipDoc,
			'SerieDoc' => $this->serieDoc,
			'NrDoc' => $this->nrDoc,
			'ObiectTranzactie' => $this->obiectTranzactie,
			'Data' => $this->data,
			'Curs' => $this->curs,
			'IDPartener' => $this->idPartener,
			'Valoare' => $this->valoare,
			'SimbolCentruCost' => $this->simbolCentruCost,
			'Obs' => $this->obs,
			'Anulat' => $this->anulat,
			'DistribuireValoare' => $this->distribuireValoare
		];
	}
}