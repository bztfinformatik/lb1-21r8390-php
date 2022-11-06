<?php

/**
 * This is the enum of all possible statuses of a project.
 *
 * The status is used to determine if a project is ready to be downloaded.
 */
enum Status: int
{
    case IN_PROGRESS = 0;
    case ACCEPTED = 1;
    case REJECTED = 2;
}
