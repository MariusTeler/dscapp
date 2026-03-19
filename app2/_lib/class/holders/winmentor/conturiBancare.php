<?php

class ConturiBancare implements JsonSerializable
{
	private string $simbolBanca;
	private string $numarCont;//$client['cont_fa']
	private string $sucursala;
	private string $moneda;//'EUR'|'RON'|'USD'
	private string $contTva;
	private string $localitate;
	private string $judet;
	private string $tara;
	private string $implicit;

	public function __construct(
		string $numarCont
	) {
		$this->simbolBanca = '';
		$this->numarCont = $numarCont;
		$this->sucursala = '';
		$this->moneda = '';
		$this->contTva = '';
		$this->localitate = '';
		$this->judet = '';
		$this->tara = '';
		$this->implicit = 'N';
	}

	public static function fromJson(\stdClass $data): self
	{
		return new self(
			$data->NumarCont
		);
	}

	public function jsonSerialize(): mixed
	{
		return [
			'SimbolBanca' => $this->simbolBanca,
			'NumarCont' => $this->numarCont,
			'Sucursala' => $this->sucursala,
			'Moneda' => $this->moneda,
			'ContTVA' => $this->contTva,
			'Localitate' => $this->localitate,
			'Judet' => $this->judet,
			'Tara' => $this->tara,
			'Implicit' => $this->implicit
		];
	}
}