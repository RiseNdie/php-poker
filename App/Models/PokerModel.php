<?php

namespace App\Models;
use Yee\Managers\CacheManager;
use App\Libraries\PTHE;

class PokerModel
{
    public static function Game(&$dealer,&$players,&$poker,&$usersInGame,&$round,&$gameStarted,&$isGameInProgress,&$usersInQueue,&$totalPot,&$endTurn,&$turn,&$newRound,&$startTurn,&$prevDealer,&$callback)
    {
        $poker = new PTHE(6);
        $isGameInProgress = true;
        $usersInQueue = array();
        $round = 1;
        $gameStarted = array();
        $totalPot = 0;

        if(in_array('dealer',$usersInGame))
        {
            $kill = array_search('dealer',$usersInGame);
            unset($usersInGame[$kill]);

            sort($usersInGame);
            $usersInGame = array_values($usersInGame);

            $key = array_search($prevDealer,$usersInGame);

            if($key !== false)
            {
                $key++;
            }
            else
            {
                $usersInGame[] = $prevDealer;
                sort($usersInGame);
                $usersInGame = array_values($usersInGame);
                $key = array_search($prevDealer,$usersInGame);
                unset($usersInGame[$key]);
                $key++;
            }
        }
        else
        {
            sort($usersInGame);
            $usersInGame = array_values($usersInGame);
            $key = array_search($dealer,$usersInGame);
            $key++;
        }

        if(isset($usersInGame[$key]))
        {
            $dealer = $usersInGame[$key];
        }
        else
        {
            $dealer = $usersInGame[0];
        }

        sort($usersInGame);
        $usersInGame = array_values($usersInGame);

        $helper = array_merge($usersInGame,$usersInGame);

        $key = array_search($dealer,$helper);

        if(count($usersInGame) == 2)
        {
            $small = $dealer;
            $big = $helper[$key+1];
            $startTurn = $dealer;
            $endTurn = $big;
        }
        else
        {
            $small = $helper[$key+1];
            $big = $helper[$key+2];
            $startTurn = $helper[$key+3];
            $endTurn = $big;
        }

        if($players[$small]['chips'] >= 5)
        {
            $players[$small]['bet'] += 5;
            $players[$small]['chips'] -= 5;
            $totalPot += 5;
        }
        else
        {
            $players[$small] = 'free';
            $kick = array_search($small,$usersInGame);
            unset($usersInGame[$kick]);
            $isGameInProgress = false;
            $callback['restart'] = 'another';
        }

        if($players[$big]['chips'] >= 10)
        {
            $players[$big]['bet'] += 10;
            $players[$big]['chips'] -= 10;
            $totalPot += 10;
        }
        else
        {
            $players[$big] = 'free';
            $kick = array_search($big,$usersInGame);
            unset($usersInGame[$kick]);
            $isGameInProgress = false;
            $callback['restart'] = 'another';
        }

        $turn = array_search($startTurn,$usersInGame);
        $newRound = false;
    }

    public static function Turn(&$turn,&$usersInGame)
    {
        $turn++;
        if(!isset($usersInGame[$turn]))
        {
            $turn = 0;
        }
    }

    public static function QueueToGame(&$usersInQueue,&$players,&$usersInGame)
    {
        if(!empty($usersInQueue))
        {
            foreach($usersInQueue as $user)
            {
                $userInfo = array(
                    "email" => 'default' . random_int(100000,99999999) . '@gmail.com',
                    "chips" => 2000,
                    "bet" => 0,
                    "status" => 'play',
                    "points" => 0,
                    "description" => '',
                );

                $players[$user] = $userInfo;
                $usersInGame[] = $user;
            }
        }
        $usersInQueue = array();
    }

    public static function array_comb($keys, $values){
        $result = array();

        foreach ($keys as $i => $k) {
            $result[$k][] = $values[$i];
        }

        array_walk($result, function(&$v){
            $v = (count($v) == 1) ? array_pop($v): $v;
        });

        return $result;
    }
}