<?php

namespace Nathan45\Tournament\Utils;

interface IConfig
{
    const SPAWN_X = 0;
    const SPAWN_Y = 80;
    const SPAWN_Z = 0;

    const EVENT_MAP_NAME = "Event";
    const SPAWN1_EVENT_X = 324;
    const SPAWN1_EVENT_Y = 80;
    const SPAWN1_EVENT_Z = 308;

    const SPAWN2_EVENT_X = 314;
    const SPAWN2_EVENT_Y = 80;
    const SPAWN2_EVENT_Z = 308;

    const SPAWN_SPECTATOR_X = 348;
    const SPAWN_SPECTATOR_Y = 85;
    const SPAWN_SPECTATOR_Z = 308;

    #--- Scoreboard and Items ---
    const ITEM_EVENTS = "§cEvents";
    const ITEM_SPECTATE = "§cSpectate";
    const ITEM_SETTINGS = "§cSettings";

    const PREFIX = "§7[§cRose§7] ";

    const SCOREBOARD_TITLE = "§l§cROSE";
    const LINE_0 = "";
    const LINE_1 = " §cRound §7(§f{round}§7)";
    const LINE_2 = "§r§r";
    const LINE_3 = " §c{player} §fvs §c{player2}";
    const LINE_4 = "§r";
    const LINE_WAITING = "§cStarting in {seconds} seconds";

    # --- PERMS ---
    const RANK_PERM = "rank.use";

}