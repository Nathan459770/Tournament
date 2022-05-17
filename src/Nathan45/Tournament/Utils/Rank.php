<?php

namespace Nathan45\Tournament\Utils;

use Nathan45\Tournament\TPlayer;

class Rank
{
    const PLAYER = 0;
    const PREMIUM = 1;
    const BUILDER = 2;
    const DEVELOPER = 3;
    const EXECUTIVE = 4;

    public function __construct(private TPlayer $holder, private int $id)
    {
    }

    public function __toString(): string
    {
        return match ($this->getId()){
            self::PREMIUM => "§7[§ePremium§7] ",
            self::BUILDER => "§7[§2Builder§7] ",
            self::DEVELOPER => "§7[§bDeveloper§7] ",
            self::EXECUTIVE =>"§7[§cExecutive§7] ",
            default => "§7[§aPlayer§7] "
        };
    }

    public function getId(): int{
        return $this->id;
    }

    public function getHolder(): TPlayer{
        return $this->holder;
    }

}