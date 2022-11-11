<?php

/**
 * This model represents a tree file structure. It is build like a `POPO`.
 */
class StructureNode
{
    // Attributes
    public int $id;
    public string $name;
    public array $children;
    public NodeType $type;
}
