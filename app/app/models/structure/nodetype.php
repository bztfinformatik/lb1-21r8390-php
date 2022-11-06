<?php

/**
 * This is the enum of all possible types of a node.
 *
 * It determines the type of a node and therefore the type of the content. 
 */
enum NodeType: int
{
    case FOLDER = 0;
    case FILE = 1;
    case PUML = 2;
}
