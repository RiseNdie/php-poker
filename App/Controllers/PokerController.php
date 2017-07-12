<?php

use Yee\Managers\Controller\Controller;
use Yee\Managers\CacheManager;
use App\Libraries\PTHE;
use App\Models\PokerModel;

class PokerController extends Controller
{
    /**
     * @Route('/http')
     * @Name('http.index')
     */
    public function Http()
    {
        $players = array(
            'player_1' => 'free',
            'player_2' => 'free',
            'player_3' => 'free',
            'player_4' => 'free',
            'player_5' => 'free',
            'player_6' => 'free',
        );

        $http = new swoole_http_server("0.0.0.0", 9509);
        $usersInGame = array();
        $usersInQueue = array();
        $poker = new PTHE(6);
        $round = 1;
        $gameStarted = array();
        $isGameInProgress = false;
        $dealer = '';
        $totalPot = 0;
        $endTurn = '';
        $startTurn = '';
        $spin = false;
        $maxBet = 10;
        $turn = 0;
        $newRound = false;
        $restartCounter = 0;
        $prevDealer = '';
        $connections = array();

        $http->on('request', function (swoole_http_request $request, swoole_http_response $response)
            use(&$dealer,&$players,&$poker,&$usersInGame,&$round,&$gameStarted,&$isGameInProgress,
                &$usersInQueue,&$totalPot,&$endTurn,&$spin,&$maxBet,&$turn,&$newRound,&$startTurn,
                &$restartCounter,&$prevDealer,&$http,&$connections)
        {
            $response->header('Access-Control-Allow-Origin','http://games.igaming.loc:9510');

            $nextRound = false;
            $canGoToNext = true;

            $callback = array();

            $array = (array) $request;

            $action = $array["post"]["action"];

            $id = $array["post"]["id"];

            if($action == 'free_spots')
            {
                for($i = 1; $i<7; $i++)
                {
                    if($players['player_'.$i] == 'free')
                    {
                        $callback['free_spots'][] = 'player_'.$i;
                    }
                }
            }

            if(isset($array["post"]["spot"]))
            {
                $spot = $array["post"]["spot"];
            }

            if($action == 'user_leave' && isset($connections[$id]))
            {
                $spot = $connections[$id];
                unset($connections[$id]);

                if(is_array($players[$spot]))
                {
                    $players[$spot]['status'] = 'left';

                    $k = array_search($spot,$usersInGame);

                    if($k !== false)
                    {
                        if($usersInGame[$k] != $dealer)
                        {
                            unset($usersInGame[$k]);
                        }
                        else
                        {
                            $prevDealer = $dealer;
                            $usersInGame[$k] = 'dealer';
                            $dealer = 'dealer';
                            $endTurn = 'dealer';
                        }
                    }

                    $usersInGame = array_values($usersInGame);

                    $check = 0;

                    for($c = 0; $c < count($usersInGame); $c++)
                    {
                        if($usersInGame[$c] != 'dealer')
                        {
                            $check++;
                        }
                    }

                    if($check < 2)
                    {
                        if($check == 0)
                        {
                            $players = array(
                                'player_1' => 'free',
                                'player_2' => 'free',
                                'player_3' => 'free',
                                'player_4' => 'free',
                                'player_5' => 'free',
                                'player_6' => 'free',
                            );
                            $round = 1;
                            $usersInGame = array();
                            $isGameInProgress = false;
                            $gameStarted = array();
                            $dealer = '';
                        }
                        else
                        {
                            $round = 5;
                        }
                    }
                }
                else
                {
                    $k = array_search($spot,$usersInQueue);
                    if($k !== false)
                    {
                        unset($usersInQueue[$k]);
                    }
                    $players[$spot] = 'free';
                }
            }

            if($action == 'user_join' && !isset($connections[$id]) && isset($spot) && !in_array($spot,$connections) && isset($players[$spot]) && $players[$spot] == 'free')
            {
                $connections[$id] = $spot;

                if($isGameInProgress)
                {
                    $usersInQueue[] = $spot;
                    $players[$spot] = 'taken';
                }
                else
                {
                    $userInfo = array(
                        "email" => 'default' . random_int(100000,99999999) . '@gmail.com',
                        "chips" => random_int(1000,2000),
                        "bet" => 0,
                        "status" => 'play',
                        "points" => 0,
                        "description" => '',
                    );

                    $players[$spot] = $userInfo;
                    $usersInGame[] = $spot;
                    $restartCounter = 0;
                }
            }

            if($action == 'clicked' && $round < 5 && $round > 0 && isset($spot) && $spot == $usersInGame[$turn])
            {
                $move = $array["post"]["move"];
                $maxBet = max(array_column($players,'bet'));

                if(isset($array["post"]["amount"]))
                {
                    $amount = $array["post"]["amount"];
                }

                if($move == 'allin')
                {
                    $players[$spot]['bet'] += $players[$spot]['chips'];
                    $totalPot += $players[$spot]['chips'];
                    $players[$spot]['chips'] = 0;
                    $players[$spot]['status'] = 'allin';

                    if($players[$spot]['bet'] > $maxBet)
                    {
                        $endKey = array_search($spot,$usersInGame);
                        $endKey--;
                        if($endKey > 0)
                        {
                            $endTurn = $usersInGame[$endKey];
                        }
                        else
                        {
                            $endTurn = end($usersInGame);
                            reset($usersInGame);
                        }
                    }
                }
                elseif($move == 'fold')
                {
                    $players[$spot]['status'] = 'fold';
                    $remove = array_search($spot,$usersInGame);

                    if($usersInGame[$remove] != $dealer)
                    {
                        if($usersInGame[$remove] == $endTurn)
                        {
                            $nextRound = true;
                        }

                        unset($usersInGame[$remove]);
                        $usersInGame = array_values($usersInGame);

                        if($turn > 0)
                        {
                            $turn--;
                        }
                        else
                        {
                            $turn = array_search(end($usersInGame),$usersInGame);
                            reset($usersInGame);
                        }
                    }
                    else
                    {
                        $prevDealer = $dealer;
                        $usersInGame[$remove] = 'dealer';
                        $dealer = 'dealer';
                        $endTurn = 'dealer';
                    }

                    $count = 0;

                    for($c = 0; $c < count($usersInGame); $c++)
                    {
                        if($usersInGame[$c] != 'dealer')
                        {
                            $count++;
                        }
                    }

                    if($count < 2)
                    {
                        $round = 5;
                    }
                }
                else
                {
                    $needed = $maxBet - $players[$spot]['bet'];

                    if($move == 'raise')
                    {
                        if(isset($amount))
                        {
                            if((int)$amount)
                            {
                                $needed += $amount;
                            }
                        }
                    }

                    if($players[$spot]['chips'] >= $needed)
                    {
                        $players[$spot]['bet'] += $needed;
                        $totalPot += $needed;
                        $players[$spot]['chips'] -= $needed;

                        if($players[$spot]['chips'] == 0)
                        {
                            $players[$spot]['status'] = 'allin';
                        }

                        if($move == 'raise')
                        {
                            $endTurn = $spot;
                            $spin = false;
                        }
                    }
                    else
                    {
                        $players[$spot] = 'free';
                        $kick = array_search($spot,$usersInGame);
                        unset($usersInGame[$kick]);
                        $usersInGame = array_values($usersInGame);
                        $callback['messages'][] = "You don't have enough chips.";
                        $callback['messages'][] = "You have been removed from the game.";
                    }
                }

                $useArray = PokerModel::array_comb( array_column($players, 'status') , array_column($players, 'bet') );

                if(isset($useArray['play']))
                {
                    if(is_array($useArray['play']))
                    {
                        $check = array_unique($useArray['play']);
                        $biggestPlay = max($useArray['play']);
                    }
                    else
                    {
                        $check = $useArray['play'];
                        $biggestPlay = $useArray['play'];
                    }
                }

                if(isset($useArray['allin']))
                {
                    if(is_array($useArray['allin']))
                    {
                        $biggestAllin = max($useArray['allin']);
                    }
                    else
                    {
                        $biggestAllin = $useArray['allin'];
                    }
                }

                if(isset($biggestPlay) && isset($biggestAllin))
                {
                    if($biggestPlay < $biggestAllin)
                    {
                        $canGoToNext = false;
                    }
                }

                if(isset($usersInGame[$turn + 1]))
                {
                    $next = $usersInGame[$turn + 1];
                }
                else
                {
                    $next = $usersInGame[0];
                }

                $status = array_count_values(array_column($players, 'status'));

                if(!isset($status['play']))
                {
                    $status['play'] = 0;
                }

                if($status['play'] == 1)
                {
                    $nextRound = true;
                }

                if( ( (isset($check) && count($check) == 1 && $canGoToNext) || !isset($check) ) && $spin && ($endTurn == $usersInGame[$turn] || $next == 'dealer' || $nextRound) )
                {

                    $status = array_count_values(array_column($players, 'status'));

                    if(!isset($status['play']))
                    {
                        $status['play'] = 0;
                    }

                    if($status['play'] <= 1)
                    {
                        $round = 5;
                    }
                    else
                    {
                        $round++;
                    }

                    $spin = false;
                    $newRound = true;
                    $k = array_search($dealer,$usersInGame);

                    if(isset($usersInGame[$k + 1]))
                    {
                        $startTurn = $usersInGame[$k + 1];
                    }
                    else
                    {
                        $startTurn = $usersInGame[0];
                    }

                    if(isset($players[$dealer]) && $players[$dealer]['status'] == 'allin')
                    {
                        $endKey = array_search($dealer, $usersInGame);
                        $endKey--;

                        if ($endKey >= 0)
                        {
                            $endTurn = $usersInGame[$endKey];
                        }
                        else
                        {
                            $endTurn = end($usersInGame);
                            reset($usersInGame);
                        }
                    }
                    else
                    {
                        $endTurn = $dealer;
                    }
                }
                else
                {
                    PokerModel::Turn($turn,$usersInGame);
                    $spin = true;
                }
            }

            if($action == 'next' && isset($spot))
            {
                $callback['ingame'] = $usersInGame;

                if(isset($players[$spot]['bet']))
                {
                    $callback['bet'] = $players[$spot]['bet'];
                    $callback['maxbet'] = max(array_column($players,'bet'));
                }

                if(isset($players[$spot]['chips']))
                {
                    $callback['chips'] = $players[$spot]['chips'];
                }

                $callback['pot'] = $totalPot;

                if(in_array($spot,$gameStarted))
                {
                    if($round < 5 && $round > 0)
                    {
                        if($round == 2)
                        {
                            $cards = $poker-> show_flop();
                            foreach($cards as $card)
                            {
                                $callback['cards']['flop'][] = $card;
                            }
                        }
                        elseif($round == 3)
                        {
                            $callback['cards']['turn'] = $poker->show_turn()[0];
                        }
                        elseif($round == 4)
                        {
                            $callback['cards']['river'] = $poker->show_river()[0];
                        }

                        if($newRound)
                        {
                            $turn = array_search($startTurn,$usersInGame);
                            $newRound = false;
                        }

                        if(isset($usersInGame[$turn]) && $usersInGame[$turn] != 'dealer' && $players[$usersInGame[$turn]]['status'] != 'allin')
                        {
                            $callback['player_turn'] = $usersInGame[$turn];
                        }
                        else
                        {
                            PokerModel::Turn($turn,$usersInGame);
                        }
                    }
                    else
                    {
                        $cards = $poker-> show_flop();
                        foreach($cards as $card)
                        {
                            $callback['cards']['flop'][] = $card;
                        }
                        $callback['cards']['turn'] = $poker->show_turn()[0];
                        $callback['cards']['river'] = $poker->show_river()[0];

                        if($turn < 7)
                        {
                            $turn = 7;

                            //distribute winnings

                            $pots = array();
                            $bets = array();
                            $x=0;

                            foreach($players as $key => $player)
                            {
                                if($player != 'free' && $player != 'taken')
                                {
                                    $bets[$x]['player'] = $key;
                                    $bets[$x]['bet'] = $player['bet'];
                                }
                                $x++;
                            }

                            array_multisort(array_column($bets,'bet'),SORT_ASC,$bets);

                            for($i = 0; $i < count($bets); $i++)
                            {
                                $currentPot = 0;
                                $contestants = array();

                                for($j = count($bets) - 1; $j >= $i; $j--)
                                {
                                    $currentPot += $bets[$i]['bet'];
                                    $bets[$j]['bet'] -= $bets[$i]['bet'];
                                    if($players[$bets[$j]['player']]['status'] != 'fold' && $players[$bets[$j]['player']]['status'] != 'left')
                                    {
                                        $contestants[$bets[$j]['player']] = $players[$bets[$j]['player']]['points'];
                                    }
                                }

                                if($currentPot > 0)
                                {
                                    arsort($contestants);
                                    $pots['pot_'.($i + 1)]['value'] = $currentPot;
                                    $pots['pot_'.($i + 1)]['players'] = $contestants;
                                }
                            }

                            krsort($pots);

                            foreach($pots as $pot)
                            {
                                if(!empty($pot['players']))
                                {
                                    $winners = array_keys($pot['players'], max($pot['players']));
                                    $chipsToWin = $pot['value'] / count($winners);

                                    foreach($winners as $winner)
                                    {
                                        $players[$winner]['chips'] += $chipsToWin;
                                    }
                                }
                                else
                                {
                                    // here we have a pot with no players playing for it
                                    // no one knows how this should be handled
                                    // simply because it can't happen in real life
                                    // THE ONLY option left is for us to take it
                                }
                            }

                            // end distribute winnings

                            $totalPot = 0;
                            $restartCounter = 0;
                            $isGameInProgress = false;
                            $round = 0;

                            for($x=1; $x<=count($players); $x++)
                            {
                                if($players['player_'.$x] != 'free' && $players['player_'.$x] != 'taken')
                                {
                                    if($players['player_'.$x]['status'] == 'fold')
                                    {
                                        $usersInGame[] = 'player_'.$x;
                                    }

                                    if($players['player_'.$x]['status'] == 'left')
                                    {
                                        $players['player_'.$x] = 'free';
                                    }
                                    else
                                    {
                                        $players['player_'.$x]['bet'] = 0;
                                        $players['player_'.$x]['description'] = '';
                                        $players['player_'.$x]['status'] = 'play';
                                        $players['player_'.$x]['points'] = 0;

                                        if($players['player_'.$x]['chips'] == 0)
                                        {
                                            $players['player_'.$x] = 'free';
                                            $kick = array_search('player_'.$x,$usersInGame);
                                            unset($usersInGame[$kick]);
                                        }
                                    }
                                }
                            }

                           PokerModel::QueueToGame($usersInQueue,$players,$usersInGame);
                        }

                        $callback['winner'][] = 'data';
                    }
                }
                elseif(in_array($spot,$usersInQueue))
                {
                    if($round < 5 && $round > 0)
                    {
                        $callback['messages'][] = "Waiting for current game to end.";
                    }
                    else
                    {
                        $callback['messages'][] = "New game starts in 5 seconds.";
                    }
                }
                else
                {
                    if(count($usersInGame) >= 2 && in_array($spot,$usersInGame) && $round == 1)
                    {
                        $cards = $poker->show_players_hands();
                        $info = $poker->show_players_points();

                        if(isset($cards[$spot]))
                        {
                            $players[$spot]['points'] = $info[$spot]['value'];
                            $players[$spot]['description'] = $info[$spot]['description'];
                            $gameStarted[] = $spot;
                            $callback['cards']['hand'][] = $cards[$spot][0];
                            $callback['cards']['hand'][] = $cards[$spot][1];
                        }
                    }
                }
            }
            elseif($action == 'start')
            {
                if(!$isGameInProgress && count($usersInGame) >= 2 && $round != 0)
                {
                    PokerModel::Game($dealer,$players,$poker,$usersInGame,$round,$gameStarted,$isGameInProgress,$usersInQueue,$totalPot,$endTurn,$turn,$newRound,$startTurn,$prevDealer,$callback);
                }
            }
            elseif($action == 'restart')
            {
                $restartCounter++;

                if( ( (count($usersInGame) >= 2 && !in_array('dealer',$usersInGame)) || (count($usersInGame) >= 3 && in_array('dealer',$usersInGame)) ) && count($usersInGame) == $restartCounter && !$isGameInProgress)
                {
                    PokerModel::Game($dealer,$players,$poker,$usersInGame,$round,$gameStarted,$isGameInProgress,$usersInQueue,$totalPot,$endTurn,$turn,$newRound,$startTurn,$prevDealer,$callback);
                    $prevDealer = '';
                }
            }

            $callback['dealer'] = $dealer;
            $callback = json_encode($callback);

            $response->end($callback);
        });

        $http->start();
    }
}