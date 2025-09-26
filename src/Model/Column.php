<?php

namespace Adamski\Bundle\FetchTableBundle\Model;

class Column {
    private ?string $className = null;

    public function __construct(
        private readonly string           $name,
        private readonly string|float|int $value,
    ) {}

    public function getName(): string {
        return $this->name;
    }

    public function getValue(): float|int|string {
        return $this->value;
    }

    public function getClassName(): ?string {
        return $this->className;
    }

    public function setClassName(?string $className): Column {
        $this->className = $className;
        return $this;
    }

    /**
     * Parse column information to array.
     *
     * @return array
     */
    public function toArray(): array {
        $data = [
            "column" => $this->getName(),
            "value"  => $this->getValue(),
        ];

        // Add className if set
        if (null !== ($className = $this->getClassName())) {
            $data["className"] = $className;
        }

        return $data;
    }
}
