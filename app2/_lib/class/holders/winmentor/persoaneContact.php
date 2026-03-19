<?php

class PersoaneContact implements JsonSerializable
{
	private string $nume;
	private string $prenume;
	private string $telefon;
	private string $email;
	private string $functie;

	public function __construct(
		string $nume,
		string $prenume,
		string $telefon,
		string $email,
		string $functie
	) {
		$this->nume = $nume;
		$this->prenume = $prenume;
		$this->telefon = $telefon;
		$this->email = $email;
		$this->functie = $functie;
	}

	public static function fromJson(\stdClass $data): self
	{
		return new self(
			$data->Nume,
			$data->Prenume,
			$data->Telefon,
			$data->Email,
			$data->Functie
		);
	}

	public function jsonSerialize(): mixed
	{
		return [
			'Nume' => $this->nume,
			'Prenume' => $this->prenume,
			'Telefon' => $this->telefon,
			'Email' => $this->email,
			'Functie' => $this->functie
		];
	}
}