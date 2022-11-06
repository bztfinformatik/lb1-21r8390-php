<?php

/**
 * This model represents a tree file structure. It is build like a `POPO`.
 */
class StructureNode extends BaseModel
{
    // Attributes
    private string $name;
    private array $children;
    private NodeType $type;
}
