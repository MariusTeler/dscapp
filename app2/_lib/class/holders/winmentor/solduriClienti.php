<?php
class SolduriClienti implements JsonSerializable {
    private string $rezult;
    private array $ErrorList;
    /** @var SoldClient[] */
    private array $InfoSolduri;

    /**
     * @param SoldClient[] $InfoSolduri
     */

    // Example usage of fromJson:
    // Assuming $jsonString contains a JSON representation of SolduriClienti
    // $data = json_decode($jsonString);
    // $solduriClienti = SolduriClienti::fromJson($data);
    public function __construct(
        string $rezult,
        array $ErrorList,
        array $InfoSolduri
    ) {
        $this->rezult = $rezult;
        $this->ErrorList = $ErrorList;
        $this->InfoSolduri = $InfoSolduri;
    }

    public static function fromJson(\stdClass $data): self
    {
        return new self(
            $data->rezult,
            $data->errorList ?? [],
            array_map(static function($data) {
                return SoldClient::fromJson($data);
            }, $data->InfoSolduri ?? [])
        );
    }

    public function jsonSerialize(): mixed
	{
		return [
			'rezult' => $this->rezult,
            'ErrorList' => $this->ErrorList,
            'InfoSolduri' => array_map(static function(SoldClient $item) {
                return $item->jsonSerialize();
            }, $this->InfoSolduri)
		];
	}
}
    