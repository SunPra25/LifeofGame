<?php declare(strict_types = 1);

namespace Life;

use SimpleXMLElement;

class XmlFileReader
{
    public function __construct(private readonly string $filePath)
    {
    }

    public function loadFile(): array
    {
        $life = $this->loadXmlFile();
        $this->validateXmlFile($life);

        $iterationsCount = (int) $life->world->iterations;
        $worldSize = (int) $life->world->cells;
        $speciesCount = (int) $life->world->species;

        if ($iterationsCount < 0) {
            throw new InvalidInputException("Value of element 'iterations' must be zero or positive number");
        }

        if ($worldSize <= 0) {
            throw new InvalidInputException("Value of element 'cells' must be positive number");
        }

        if ($speciesCount <= 0) {
            throw new InvalidInputException("Value of element 'species' must be positive number");
        }

        $cells = $this->readCells($life, $worldSize, $speciesCount);

        return [$worldSize, $speciesCount, $cells, $iterationsCount];
    }

    private function loadXmlFile(): SimpleXMLElement
    {
        if (!file_exists($this->filePath)) {
            throw new InvalidInputException("The file '{$this->filePath}' does not exist.");
        }
        try {
            libxml_use_internal_errors(true);
            $xmlContent = file_get_contents($this->filePath);
            $life = simplexml_load_string($xmlContent);
            $errors = libxml_get_errors();
            libxml_clear_errors();

            if ($life === false || count($errors) > 0) {
                throw new InvalidInputException("Failed to parse the XML file.");
            }
        }
        catch (\Exception $e) {
            throw new InvalidInputException("An error occurred while reading the XML file: " . $e->getMessage());
        }
        return $life;
    }

    private function validateXmlFile(SimpleXMLElement $life): void
    {
        if (!isset($life->world)) {
            throw new InvalidInputException("Missing element 'world'");
        }
        if (!isset($life->world->iterations)) {
            throw new InvalidInputException("Missing element 'iterations'");
        }
        if (!isset($life->world->cells)) {
            throw new InvalidInputException("Missing element 'cells'");
        }
        if (!isset($life->world->species)) {
            throw new InvalidInputException("Missing element 'species'");
        }
        if (!isset($life->organisms)) {
            throw new InvalidInputException("Missing element 'organisms'");
        }
        foreach ($life->organisms->organism as $organism) {
            if (!isset($organism->x_pos)) {
                throw new InvalidInputException("Missing element 'x_pos' in some of the element 'organism'");
            }
            if (!isset($organism->y_pos)) {
                throw new InvalidInputException("Missing element 'y_pos' in some of the element 'organism'");
            }
            if (!isset($organism->species)) {
                throw new InvalidInputException("Missing element 'species' in some of the element 'organism'");
            }
        }
    }

    private function readCells(SimpleXMLElement $life, int $worldSize, int $speciesCount): array
    {
        $cells = [];
        foreach ($life->organisms->organism as $organism) {
            $x = (int) $organism->x_pos;
            if ($x < 0 || $x >= $worldSize) {
                throw new InvalidInputException("Value of element 'x_pos' of element 'organism' must be between 0 and number of cells");
            }
            $y = (int) $organism->y_pos;
            if ($y < 0 || $y >= $worldSize) {
                throw new InvalidInputException("Value of element 'y_pos' of element 'organism' must be between 0 and number of cells");
            }
            $species = (int) $organism->species;
            if ($species < 0 || $species >= $speciesCount) {
                throw new InvalidInputException("Value of element 'species' of element 'organism' must be between 0 and maximal number of species");
            }
            $cells[$y] ??= [];
            $finalSpecies = $species;
            if (isset($cells[$y][$x])) {
                $existingCell = $cells[$y][$x]; /** @var int $existingCell */
                $availableSpecies = [$existingCell, $species];
                $finalSpecies = $availableSpecies[array_rand($availableSpecies)];
            }
            $cells[$y][$x] = $finalSpecies;
        }
        for ($y = 0; $y < $worldSize; $y++) {
            $cells[$y] ??= [];
            for ($x = 0; $x < $worldSize; $x++) {
                if (!isset($cells[$y][$x])) {
                    $cells[$y][$x] = null;
                }
            }
        }
        return $cells;
    }

}
