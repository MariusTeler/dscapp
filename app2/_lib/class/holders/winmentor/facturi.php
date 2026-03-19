<?php
class Facturi implements JsonSerializable {
    private string $tipDocument;
    private string $anLucru;
    private string $lunaLucru;
    /** @var FaDocumente[] */
    private array $documente;

    /**
     * @param FaDocumente[] $documente
     */
    public function __construct(
        string $anLucru,
        string $lunaLucru,
        array $documente
    ) {
        $this->tipDocument = 'FACTURA IESIRE';
        $this->anLucru = $anLucru;
        $this->lunaLucru = $lunaLucru;
        $this->documente = $documente;
    }

    public static function fromJson(\stdClass $data): self
    {
        return new self(
            $data->AnLucru,
            $data->LunaLucru,
            array_map(static function($data) {
                return FaDocumente::fromJson($data);
            }, $data->Documente)
        );
    }

    public function jsonSerialize(): mixed
	{
		return [
			'TipDocument' => $this->tipDocument,
			'AnLucru' => $this->anLucru,
			'LunaLucru' => $this->lunaLucru,
			'Documente' => $this->documente
		];
	}
}    