<?php

/*
 * This file is a part of the DF13 project.
 *
 * Copyright (c) 2022-present Valithor Obsidion <valithor@valzargaming.com>
 */

use \DF13\DF13;
use \Discord\Discord;
use \React\EventLoop\Loop;
use \WyriHaximus\React\Cache\Redis as RedisCache;
use \Clue\React\Redis\Factory as Redis;
use \React\Filesystem\Factory as Filesystem;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Discord\WebSockets\Intents;
use \React\Http\Browser;

set_time_limit(0);
ignore_user_abort(1);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1'); //Unlimited memory usage
define('MAIN_INCLUDED', 1); //Token and SQL credential files may be protected locally and require this to be defined to access
require getcwd() . '/token.php'; //$token
include getcwd() . '/vendor/autoload.php';

$loop = Loop::get();
$redis = new RedisCache((new Redis($loop))->createLazyClient('127.0.0.1:6379'), 'dphp:cache:'); // prefix is "dphp:cache"
$logger = new Logger('New logger');
$logger->pushHandler(new StreamHandler('php://stdout'));
$discord = new Discord([
    'loop' => $loop,
    'logger' => $logger,
    'cacheInterface' => $redis,
    'cacheSweep' => false, //Don't periodically wipe the in-memory cache in case something happens to Redis
    /*'socket_options' => [
        'dns' => '8.8.8.8', // can change dns
    ],*/
    'token' => $token,
    'loadAllMembers' => true,
    'storeMessages' => true, //Because why not?
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS | Intents::MESSAGE_CONTENT,
]);
include 'stats_object.php'; 
$stats = new Stats();
$stats->init($discord);
$browser = new Browser($loop);
$filesystem = Filesystem::create($loop);
include 'functions.php'; //execInBackground(), portIsAvailable()
include 'variable_functions.php';
include 'verifier_functions.php';
include 'DF13.php';

$options = array(
    'loop' => $loop,
    'discord' => $discord,
    'browser' => $browser,
    'filesystem' => $filesystem,
    'logger' => $logger,
    'stats' => $stats,
    
    //Configurations
    'github' => 'https://github.com/VZGCoders/DF13-bot',
    'command_symbol' => '!s',
    'owner_id' => '116927250145869826', //Valithor
    'DF13_guild_id' => '1043390003285344306', //DF13
    'verifier_feed_channel_id' => '1043390006150053947', //Channel VZG Verifier webhooks verification messages to
    'files' => array(
        //Fun
        'insults_path' => 'insults.txt',
        'ranking_path' => 'ranking.txt',
        'status_path' => 'status.txt',
        
        //Defines
        'map_defines_path' => 'C:/GitHub/Dwarf-Fortress-13/DF13-git/code/__defines/maps.dm',
        
        //DF13
        'log_basedir' => 'C:/GitHub/Dwarf-Fortress-13/data/logs',
        'ooc_path' => 'C:/GitHub/Dwarf-Fortress-13/ooc.log',
        'admin_path' => 'C:/GitHub/Dwarf-Fortress-13/admin.log',
        'discord2ooc' => 'C:/GitHub/Dwarf-Fortress-13/SQL/discord2ooc.txt',
        'discord2admin' => 'C:/GitHub/Dwarf-Fortress-13/SQL/discord2admin.txt',
        'discord2dm' => 'C:/GitHub/Dwarf-Fortress-13/SQL/discord2dm.txt',
        'discord2ban' => 'C:/GitHub/Dwarf-Fortress-13/SQL/discord2ban.txt',
        'discord2unban' => 'C:/GitHub/Dwarf-Fortress-13/SQL/discord2unban.txt',
        'whitelist' => 'C:/GitHub/Dwarf-Fortress-13/SQL/whitelist.txt',
        'bans' => 'C:/GitHub/Dwarf-Fortress-13/SQL/bans.txt',
        
        //Script paths
        'updateserverabspaths' => 'C:/GitHub/Dwarf-Fortress-13/scripts/updateserverabspaths.py',
        'serverdata' => 'C:/GitHub/Dwarf-Fortress-13/serverdata.txt',
        'dmb' => 'C:/GitHub/Dwarf-Fortress-13/DF13.dmb',
        'killsudos' => 'C:/GitHub/Dwarf-Fortress-13/scripts/killsudos.py',
        'killDF13' => 'C:/GitHub/Dwarf-Fortress-13/scripts/killDF13.py',
        'mapswap' => 'C:/GitHub/Dwarf-Fortress-13/scripts/mapswap.py',
        
         //Unused
        'playerlogs' => 'C:/GitHub/Dwarf-Fortress-13/SQL/playerlogs.txt',
    ),
    'channel_ids' => array(
        'ooc_channel' => '', //#ooc
        'admin_channel' => '', //#ahelp
    ),
    'role_ids' => array(
        'thane' => '1043390003381817362', //Host
        'rune king' => '1043390003381817359', //Head admin
        'longbeard' => '1043390003381817354', //Admin
        'bearded' => '1043390003327291395', //Promoted
        'unbearded' => '1043390003327291394', //Verified
        'banished' => '1043390003327291397', //Banned in-game (unused)
        'paroled' => '1043390003360837729', //On parole (unused)
    ),
    'functions' => array(
        'ready' => [
            'on_ready' => $on_ready,
            'status_changer_timer' => $status_changer_timer,
            'status_changer_random' => $status_changer_random,
            'set_ips' => $set_ips,
            'df13_listeners' => $df13_listeners,
            'serverinfo_timer' => $serverinfo_timer,
        ],
        'ready_slash' => [
            'slash_init' => $slash_init,
        ],
        'message' => [
            'on_message' => $on_message,
        ],
        'GUILD_MEMBER_ADD' => [
            'join_roles' => $join_roles,
        ],
        'misc' => [ //Custom functions
            'ooc_relay' => $ooc_relay,
            'timer_function' => $timer_function,
            'status_changer' => $status_changer,
            'ban' => $ban,
            'browser_call' => $browser_call,
            'bancheck' => $bancheck,
            'verify_new' => $verify_new,
            'promotable_check' => $promotable_check,
            'mass_promotion_loop' => $mass_promotion_loop,
            'mass_promotion_check' => $mass_promotion_check,
            'serverinfo_parse' => $serverinfo_parse,
        ],
    ),
);
if (include 'df13_token.php') $options['df13_token'] = $df13_token;
$DF13 = new DF13($options);
include 'webapi.php'; //$socket, $webapi, webapiFail(), webapiSnow();
$DF13->run();