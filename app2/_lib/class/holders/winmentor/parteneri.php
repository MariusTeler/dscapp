<?php

class Parteneri implements JsonSerializable
{
	private string $tipOperatie;
	private string $cui; //strtoupper($client['cod_fiscal'])
	private string $codExtern;//cod_cl
	private string $regCom; //$client['reg_com']
	private string $nume; //strtoupper($client['nume_societate'] ?? $client['nume'])
	private string $persoanaFizica; //self::isValidCui($cui) ? "NU" : "DA"
	private string $tvaLaIncasare;
	private string $blocat;
	private string $tipContabil;
	private string $scadentaLaCumparare;
	private string $scadentaLaVanzare;//$client['termen_plata']
	private string $moneda;//'EUR'|'RON'|'USD'
	private string $observatii;//cod_cl
	private string $splitTva;
	/** @var PersoaneContact[] */
	private array $persoaneContact;
	/** @var Sedii[] */
	private array $sedii;
	/** @var ConturiBancare[] */
	private array $conturiBancare;
	/** @var string[] */
	private array $modPlata;

	/**
	 * @param PersoaneContact[] $persoaneContact
	 * @param Sedii[] $sedii
	 * @param ConturiBancare[] $conturiBancare
	 * @param string[] $modPlata
	 */
	public function __construct(
		string $cui,
		string $codExtern,
		string $regCom,
		string $nume,
		string $scadentaLaVanzare,
		string $observatii,
		array $sedii = [],
		array $conturiBancare = [],
		array $persoaneContact = []
	) {
		$this->tipOperatie = 'A';
		$this->cui = $cui;
		$this->codExtern = $codExtern;
		$this->regCom = $regCom;
		$this->nume = $nume;
		$this->persoanaFizica = self::isValidCui($cui) ? 'NU' : 'DA';
		$this->tvaLaIncasare = 'NU';
		$this->blocat = 'NU';
		$this->tipContabil = '';
		$this->scadentaLaCumparare = '1';
		$this->scadentaLaVanzare = $scadentaLaVanzare;
		$this->moneda = '';
		$this->observatii = $observatii;
		$this->splitTva = 'NU';
		$this->sedii = $sedii;
		$this->conturiBancare = $conturiBancare;
		$this->modPlata = ['Numerar', 'OP'];
		$this->persoaneContact = $persoaneContact;
	}

	public static function fromJson(\stdClass $data): self
	{
		return new self(
			$data->CUI,
			$data->CodExtern,
			$data->RegCom,
			$data->Nume,
			$data->ScadentaLaVanzare,
			$data->Observatii,
			array_map(static function($data) {
				return Sedii::fromJson($data);
			}, $data->Sedii),
			array_map(static function($data) {
				return ConturiBancare::fromJson($data);
			}, $data->ConturiBancare),
			array_map(static function($data) {
				return PersoaneContact::fromJson($data);
			}, $data->PersoaneContact)
		);
	}

	public function jsonSerialize(): mixed
	{
		$ret = [
			'TipOperatie' => $this->tipOperatie,
			'CUI' => $this->cui,
			'CodExtern' => $this->codExtern,
			'RegCom' => $this->regCom,
			'Nume' => $this->nume,
			'PersoanaFizica' => $this->persoanaFizica,
			'TVALaIncasare' => $this->tvaLaIncasare,
			'Blocat' => $this->blocat,
			'TipContabil' => $this->tipContabil,
			'ScadentaLaCumparare' => $this->scadentaLaCumparare,
			'ScadentaLaVanzare' => $this->scadentaLaVanzare,
			'Moneda' => $this->moneda,
			'Observatii' => $this->observatii,
			'SplitTVA' => $this->splitTva,
			'Sedii' => $this->sedii,
			'ModPlata' => $this->modPlata
		];

		if(is_array($this->persoaneContact) && count($this->persoaneContact) > 0)
			$ret['PersoaneContact'] = $this->persoaneContact;
		if(is_array($this->conturiBancare) && count($this->conturiBancare) > 0)
			$ret['ConturiBancare'] = $this->conturiBancare;

		return $ret;
	}

	public static function isValidCui($cui) {
        $cui = strtoupper(trim($cui));
        if(str_starts_with($cui, "RO")) $cui = intval(substr($cui, 2));
        if(!preg_match('/^\d{2,10}$/',$cui)) return false;

        $v = 753217532;
        $c1 = $cui % 10;
        $cui = intdiv($cui, 10);

        $t = 0;
        while($cui > 0){
            $t += ($cui % 10) * ($v % 10);
            $cui = intdiv($cui, 10);
            $v = intdiv($v, 10);
        }
        
        // aplica inmultirea cu 10 si afla modulo 11
        $c2 = ($t * 10) % 11;
        
        // daca modulo 11 este 10, atunci cifra de control este 0
        if($c2 == 10){
            $c2 = 0;
        }

        return $c1 == $c2;

    }
}