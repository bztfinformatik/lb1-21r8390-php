<?php

/**
 * This is a enum of all possible roles.
 * They are in a flag format, so that they can be combined.
 * @link https://timdeschryver.dev/blog/flagged-enum-what-why-and-how
 */
enum Role: int
{
    case USER = 0;
    case ADMIN = 1;
}
