<?php

class Sedii implements JsonSerializable
{
	private string $denumire; //strtoupper($client['nume_societate']) | strtoupper($punct_de_lucru['nume'])
	private string $localitate; //$client['nume_lc'] | $punct_de_lucru['nume_lc']
	private string $tipSediu;//SFL if master, FL if pc
	private string $codFiscal;//strtoupper($client['cod_fiscal'])
	private string $strada;//$client['adresa_sediu_social'] | $punct_de_lucru['adresa_sediu_social']
	private string $numar;
	private string $bloc;
	private string $idSediu;//$client['cod_cl'] | $punct_de_lucru['cod_cl'] : TODO CUI
	private string $etaj;
	private string $apartament;
	private string $judet;//$client['cod_jd'] | $punct_de_lucru['cod_jd']
	private string $codPostal;
	private string $tara;//RO
	private string $agent;//$client['ag_vanzari_id'] | $punct_de_lucru['ag_vanzari_id']

	public function __construct(
		string $idSediu,
		string $codFiscal,
		string $denumire,
		string $judet,
		string $localitate,
		string $strada,
		string $tipSediu,
		string $agent,
		string $tara
	) {
		$this->idSediu = $idSediu;
		$this->codFiscal = $codFiscal;
		$this->denumire = $denumire;
		$this->judet = $judet;
		$this->localitate = $localitate;
		$this->strada = $strada;
		$this->tipSediu = $tipSediu;
		$this->agent = $agent;
		$this->tara = $tara;
		$this->numar = '';
		$this->bloc = '';
		$this->etaj = '';
		$this->apartament = '';
		$this->codPostal = '';
	}

	public function setTipSediu($tipSediu) {
		$this->tipSediu = $tipSediu;
	}

	public static function fromJson(\stdClass $data): self
	{
		return new self(
			$data->IDSediu,
			$data->CodFiscal,
			$data->Denumire,
			$data->Judet,
			$data->Localitate,
			$data->Strada,
			$data->TipSediu,
			$data->Agent,
			$data->Tara
		);
	}

	public function jsonSerialize(): mixed
	{
		return [
			'Denumire' => $this->denumire,
			'Localitate' => $this->localitate,
			'TipSediu' => $this->tipSediu,
			'CodFiscal' => $this->codFiscal,
			'Strada' => $this->strada,
			'Numar' => $this->numar,
			'Bloc' => $this->bloc,
			'IDSediu' => $this->idSediu,
			'Etaj' => $this->etaj,
			'Apartament' => $this->apartament,
			'Judet' => $this->judet,
			'CodPostal' => $this->codPostal,
			'Tara' => $this->tara,
			'Agent' => $this->agent
		];
	}
}