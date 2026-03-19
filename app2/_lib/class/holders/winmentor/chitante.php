<?php
class Chitante implements JsonSerializable {
    private string $anLucru;
    private string $lunaLucru;
    /** @var ChDocumente[] */
    private array $documente;

    /**
     * @param ChDocumente[] $documente
     */
    public function __construct(
        string $anLucru,
        string $lunaLucru,
        array $documente
    ) {
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
                return ChDocumente::fromJson($data);
            }, $data->Documente)
        );
    }

    public function jsonSerialize(): mixed
	{
		return [
			'AnLucru' => $this->anLucru,
			'LunaLucru' => $this->lunaLucru,
			'Documente' => $this->documente
		];
	}
}
    