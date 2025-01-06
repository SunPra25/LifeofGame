<?php declare(strict_types = 1);

namespace Life;

class Game
{
    private int $iterationsCount;

    private int $size;

    private int $species;

    /**
     * @var int[][]|null[][]
     * Array of available cells in the game with size x size dimensions
     * Indexed by y coordinate and than x coordinate
     */
    private array $cells;

    public function run(string $inputFile, string $outputFile): void
    {
        $input = new XmlFileReader($inputFile);
        $output = new XmlFileWriter($outputFile);

        [$size, $species, $cells, $iterationsCount] = $input->loadFile();

        $this->size = $size;
        $this->species = $species;
        $this->cells = $cells;
        $this->iterationsCount = $iterationsCount;

        for ($i = 0; $i < $this->iterationsCount; $i++) {
            $newCells = [];
            for ($y = 0; $y < $this->size; $y++) {
                $newCells[] = [];
                for ($x = 0; $x < $this->size; $x++) {
                    $newCells[$y][$x] = $this->evolveCell($x, $y);
                }
            }
            $this->cells = $newCells;
        }

        $output->saveWorld($this->size, $this->species, $this->cells);
    }

    private function getNeighbors(int $x, int $y): array
    {
        $neighbors = [];
        $directions = [
            [-1, -1], [-1, 0], [-1, 1],
            [0, -1],         [0, 1],
            [1, -1], [1, 0], [1, 1]
        ];

        foreach ($directions as [$dy, $dx]) {
            $nx = $x + $dx;
            $ny = $y + $dy;

            if ($nx >= 0 && $nx < $this->size && $ny >= 0 && $ny < $this->size) {
                $neighbors[] = $this->cells[$ny][$nx];
            }
        }

        return $neighbors;
    }

    private function evolveCell(int $x, int $y): ?int
    {
        $cell = $this->cells[$y][$x]; // Current state of the cell
        $neighbours = $this->getNeighbors($x, $y);

        // Count live neighbors
        $liveNeighboursCount = count(array_filter($neighbours, fn($n) => $n !== null));

        // Rule 1: Underpopulation (fewer than 2 live neighbors)
        if ($cell !== null && $liveNeighboursCount < 2) {
            return null; // Cell dies
        }

        // Rule 2: Survival (2 or 3 live neighbors)
        if ($cell !== null && ($liveNeighboursCount === 2 || $liveNeighboursCount === 3)) {
            return $cell; // Cell lives
        }

        // Rule 3: Overpopulation (more than 3 live neighbors)
        if ($cell !== null && $liveNeighboursCount > 3) {
            return null; // Cell dies
        }

        // Rule 4: Reproduction (dead cell with exactly 3 live neighbors)
        if ($cell === null && $liveNeighboursCount === 3) {
            // Determine species for reproduction
            $speciesCounts = array_count_values(array_filter($neighbours, fn($n) => $n !== null));
            arsort($speciesCounts); // Sort by count, descending
            return (int) array_key_first($speciesCounts); // Return the most common species
        }

        return null; // Default case: Cell stays dead
    }
}
