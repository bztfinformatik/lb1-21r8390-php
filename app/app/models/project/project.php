<?php

/**
 * This model represents a project configuration. It is build like a `POPO`.
 *
 * From this model, the project can be downloaded.
 */
class Project
{
    // Attributes
    public int $id;
    public User $user;

    // General
    public string $title;
    public string $description;
    public $createdAt;
    public $fromDate;
    public $toDate;
    public string $docsRepo;
    public string $codeRepo;

    // Project specific
    public bool $wantReadme;
    public bool $wantIgnore;
    public bool $wantCSS;
    public bool $wantJS;
    public bool $wantPages;

    // Appearance
    public Color $color;
    public Font $font;
    public bool $wantDarkMode;
    public bool $wantCopyright;
    public bool $wantSearch;
    public bool $wantTags;
    public array $footerLinks;
    public string $logo;

    // Folder structure
    public bool $wantJournal;
    public bool $wantExamples;
    public StructureNode $structure;

    // Confirmation
    public User $confirmedBy;
    public $confirmedAt;
    public string $comment;
    public Status $status;
    public string $downloadUrl;
}
