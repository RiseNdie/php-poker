<?php
/*****************************************************************************
 * Usage examples:
 *****************************************************************************
 
 
	#> Instantiate the PTHE object with the number of players.
	$PTHE = new PTHE(2);
	
	#> Getting flop, turn and river's card.
	print_r($PTHE->show_flop());
	print_r($PTHE->show_turn());
	print_r($PTHE->show_river());

	#> Retrieves the hands generated for players.
	print_r($PTHE->show_players_hands());
	
	#> Retrieve the points awarded to players.
	print_r($PTHE->show_players_points());

	#> Clean all.
	$PTHE->free_resources();


 *****************************************************************************/

namespace App\Libraries;

class PokerDeck
{
	/**	* 52 card poker deck 
	*/
	private $deck = array(	'AC', '2C', '3C', '4C', '5C', '6C', '7C', '8C', '9C', '10C', 'JC', 'QC', 'KC',
							'AQ', '2Q', '3Q', '4Q', '5Q', '6Q', '7Q', '8Q', '9Q', '10Q', 'JQ', 'QQ', 'KQ',
							'AF', '2F', '3F', '4F', '5F', '6F', '7F', '8F', '9F', '10F', 'JF', 'QF', 'KF',
							'AP', '2P', '3P', '4P', '5P', '6P', '7P', '8P', '9P', '10P', 'JP', 'QP', 'KP',
						);

	private $deck_index 	= 0;
	private $deck_shuffle	= array();
	private $players_hands 	= array();



	/** * Shuffle deck order
	*/
	public function make_deck_shuffle()
	{
		$this->deck_index = 0;
		$this->deck_shuffle = $this->deck;
		shuffle($this->deck_shuffle);

		return $this->deck_shuffle;
	}


	/** * Get players hands
	*/
	public function get_players_hands($players, $cards)
	{
		if ((int)$players && (int)$cards)
		{
			$player = 0;
			$this->players_hands = array();

			for ($i = 1; $i <= $players; ++$i)
			{
				++$player;
				$this->players_hands['player_' . $player][] = $this->deck_shuffle[$i];

				for ($j = 1; $j < $cards; ++$j)
						$this->players_hands['player_' . $player][] = $this->deck_shuffle[($players*$j)+$player];
			}

			$this->deck_index = $players*$cards;

			return $this->players_hands;
		}
	}


	/** * Draw $number card(s)
	*/
	public function draw_card($number)
	{
		++$this->deck_index;

		$draw_cards = array();

		for ($i = 0; $i < $number; ++$i)
				$draw_cards[] = $this->deck_shuffle[++$this->deck_index];


		return $draw_cards;
	}


	/** * return the value of card, without consider seed
	*/
	public function card_value($card)
	{
		return substr($card, 0, -1);
	}

	/** * return the seed of the card
	*/
	public function card_seed($card)
	{
		return substr($card, -1);
	}


	public function reset_pokerdeck()
	{
		unset($this->deck);
		unset($this->deck_index);
		unset($this->deck_shuffle);
		unset($this->players_hands);
	}
}


/** * Defining Texas Holdem Rules and specific method
	* @author Salvatore Rotondo <s.rotondo90@gmail.com>
*/
class PTHE extends PokerDeck
{
	private $TH_cards				= 2;
	private $TH_players_hands 	= array();
	private $TH_players_points 	= array();
	private $TH_game_state		= array();

	private $TH_desc_cards		= array(
											14	=> 'Ace',
											13 	=> 'King',
											12	=> 'Queen',
											11	=> 'Jack',
											10	=> 'Ten',
											9	=> 'Nine',
											8	=> 'Eight',
											7	=> 'Seven',
											6	=> 'Six',
											5	=> 'Five',
											4	=> 'Four',
											3	=> 'Three',
											2	=> 'Two',
											1	=> 'Ace',

											200	=> 'High',
											201 => 'Four of',
											202 => 'Full Over',
											203 => 'Kicker',
											204 => 'Pair of',
											205 => 'Three of',
											);

	private $TH_desc_points		= array(
											1000 	=> 'Royal Flush',
											999		=> 'Flush Straight',
											900		=> 'Four of a Kind',
											800 	=> 'Full House',
											700 	=> 'Flush',
											600 	=> 'Straight',
											500 	=> 'Three of a Kind',
											400		=> 'Two Pair',
											300		=> 'One Pair',
											200		=> 'High Card'
											);


	public function __construct($players = 1)
	{
		$this->make_deck_shuffle();
		$this->TH_players_hands = $this->get_players_hands($players, $this->TH_cards);
		$this->TH_draw_state();

		for ($P = 1; $P <= $players; ++$P)
				$this->evaluate_points($P);
	}


	private function TH_draw_state()
	{
		$this->TH_game_state['flop'] = $this->draw_card(3);
		$this->TH_game_state['turn'] = $this->draw_card(1);
		$this->TH_game_state['river'] = $this->draw_card(1);


		foreach ($this->TH_game_state as $game_cards)
				foreach ($game_cards as $card)
						$this->TH_game_state['all'][] = $card;
	}


	public function show_flop()
	{
		return $this->TH_game_state['flop'];
	}


	public function show_turn()
	{
		return $this->TH_game_state['turn'];
	}


	public function show_river()
	{
		return $this->TH_game_state['river'];
	}


	public function show_game_state()
	{
		return $this->TH_game_state['all'];
	}


	public function show_players_hands()
	{
		return $this->TH_players_hands;
	}


	public function show_players_points()
	{
		return $this->TH_players_points;
	}


	private function evaluate_points($player)
	{
		$player_cards = $this->get_all_player_card($player);


		/* *** Checking for Royal Straight *** */

		# Order cards by seed
		$higher = $this->card_order($player_cards['higher']);
		$lower = $this->card_order($player_cards['lower']);

		for ($i = 2; $i >= 0; --$i)
		{
			$rs = $this->straight_match($i, $higher);

			if ($this->straight_value($higher[$i]) == 60 && $rs['is_straight'])
			{
				if ($this->seed_match($i, $higher))
					$this->TH_players_points['player_' . $player] = array(	'value' 		=> 1000,
																			'description' 	=> $this->TH_desc_points[1000],
																			'cards'			=> $rs['cards']
																			);

				# Exit For
				$i = -1;
			}
		}


		/* *** Checking for others Flush Straight *** */

		for ($i = 2; $i >= 0; --$i)
		{
			$fs = $this->straight_match($i, $higher);

			# only if player doesn't have a better point yet
			if (!isset($this->TH_players_points['player_' . $player]['value']) && $fs['is_straight'])
			{
				if ($this->seed_match($i, $lower))
				{
					switch ($this->straight_value($lower[$i]))
					{
						case 55: $point = 999;	break; // 9 - K
						case 50: $point = 998;	break; // 8 - Q
						case 45: $point = 997;	break; // 7 - J
						case 40: $point = 996;	break; // 6 - 10
						case 35: $point = 995;	break; // 5 - 9
						case 30: $point = 994;	break; // 4 - 8
						case 25: $point = 993;	break; // 3 - 7
						case 20: $point = 992;	break; // 2 - 6
						case 15: $point = 991;	break; // 1 - 5
					}

					$this->TH_players_points['player_' . $player] = array(	'value' => $point,
																			'description' 	=> 	$this->TH_desc_points[999]
																								.' '.
																								$this->TH_desc_cards[$this->card_value($lower[$i+4])]
																							 	.' '.
																								$this->TH_desc_cards[200],

																			'cards'			=> 	$fs['cards']
																		);
					# Exit For
					$i = -1;
				}

			}
		}


		/* *** Checking for Four of a Kind *** */

		# Order cards
		$higher = $this->card_order($player_cards['higher'], 'natural');

		for ($i = 3; $i >= 0; --$i)
		{
			$poker = $this->poker_match($i, $higher);

			if (!isset($this->TH_players_points['player_' . $player]['value']) && $poker['is_poker'])
			{
				switch ($i)
				{
					case 3: $kicker = $higher[2]; 	break;
					case 2:
					case 1:
					case 0:
							$kicker = $higher[6];
					break;
				}

				switch ($this->card_value($kicker))
				{
					case 14:	$point = 900;	break;
					case 13: 	$point = 899;	break;
					case 12: 	$point = 898;	break;
					case 11: 	$point = 897;	break;
					case 10: 	$point = 896;	break;
					case 9: 	$point = 895;	break;
					case 8: 	$point = 894;	break;
					case 7: 	$point = 893;	break;
					case 6: 	$point = 892;	break;
					case 5: 	$point = 891;	break;
					case 4: 	$point = 890;	break;
					case 3: 	$point = 889;	break;
					case 2: 	$point = 888;	break;
				}


				$poker['cards'][] = $kicker;

				$this->TH_players_points['player_' . $player] = array(
																	'value' 		=> 	$point,
																	'description' 	=> 	$this->TH_desc_points[900]
																						.', '.
																						$this->TH_desc_cards[201]
																						.' '.
																						$this->TH_desc_cards[$this->card_value($higher[$i+3])]
																						.', '.
																						$this->TH_desc_cards[$this->card_value($kicker)]
																						.' '.
																						$this->TH_desc_cards[203],

																	'cards'			=> $poker['cards']
																	);
			}
		}


		/* *** Checking for Full House *** */

		for ($i = 4; $i >= 0; --$i)
		{
			$full = $this->full_match($i, $higher);


			if (!isset($this->TH_players_points['player_' . $player]['value']) && $full['is_full'])
			{
				$point = 	$this->card_value($full['cards'][0])	+
							$this->card_value($full['cards'][1])	+
							$this->card_value($full['cards'][2])	+
							$this->card_value($full['cards'][3])	+
							$this->card_value($full['cards'][4])	+
							732;

				$this->TH_players_points['player_' . $player] = array(
																	'value' 		=> 	$point,
																	'description' 	=>	$this->TH_desc_cards[$this->card_value($full['cards'][2])]
																						.' '.
																						$this->TH_desc_cards[202]
																						.' '.
																						$this->TH_desc_cards[$this->card_value($full['cards'][0])],

																	'cards'			=> $full['cards']
																	);
			}
		}


		/* *** Checking for flush *** */

		# Order cards by seeds
		$higher = $this->card_order($player_cards['higher']);

		for ($i = 2; $i >= 0; --$i)
		{
			$flush = $this->flush_match($i, $higher);

			# only if player doesn't have a better point yet
			if (!isset($this->TH_players_points['player_' . $player]['value']) && $flush['is_flush'])
			{

				switch ($this->card_value($flush['cards'][4]))
				{
					case 14: $point = 700; break;
					case 13: $point = 699; break;
					case 12: $point = 698; break;
					case 11: $point = 697; break;
					case 10: $point = 696; break;
					case 9: $point = 695; break;
					case 8: $point = 694; break;
					case 7: $point = 693; break;
				}

				$this->TH_players_points['player_' . $player] = array(	'value' 	=> $point,
																		'description' => 	$this->TH_desc_points[700]
																							.', '.
																							$this->TH_desc_cards[$this->card_value($flush['cards'][4])]
																							.' '.
																							$this->TH_desc_cards[200],

																		'cards'			=> $flush['cards']
																	);

				# Exit For
				$i = -1;
			}
		}


		/* *** Checking for normal high straight *** */

		# Order cards by their values, without consider seeds
		$higher = $this->card_order($higher, false);
		$lower = $this->card_order($lower, false);

		for ($i = 2; $i >= 0; --$i)
		{
			$straight = $this->straight_match($i, $higher);

			# only if player doesn't have a better point yet
			if (	!isset($this->TH_players_points['player_' . $player]['value']) &&
					$this->straight_value($higher[$i]) == 60 &&
					$straight['is_straight']
				)
			{

				$this->TH_players_points['player_' . $player] = array(	'value' 	  => 600,
																		'description' =>$this->TH_desc_points[600]	.' '.
																						$this->TH_desc_cards[$this->card_value($straight['cards'][4])]
																						.' '.
																						$this->TH_desc_cards[200],

																		'cards' 	  => $straight['cards']
																	);

				# Exit For
				$i = -1;
			}
		}


		/* *** Checking for any normal straight *** */

		for ($i = 2; $i >= 0; --$i)
		{
			$straight = $this->straight_match($i, $lower);

			# only if player doesn't have a better point yet
			if (!isset($this->TH_players_points['player_' . $player]['value']) && $straight['is_straight'])
			{
				switch ($this->straight_value($lower[$i]))
				{
					case 55: $point = 699;	break; // 9 - K
					case 50: $point = 698;	break; // 8 - Q
					case 45: $point = 697;	break; // 7 - J
					case 40: $point = 696;	break; // 6 - 10
					case 35: $point = 695;	break; // 5 - 9
					case 30: $point = 694;	break; // 4 - 8
					case 25: $point = 693;	break; // 3 - 7
					case 20: $point = 692;	break; // 2 - 6
					case 15: $point = 691;	break; // 1 - 5
				}

				$this->TH_players_points['player_' . $player] = array(	'value' 	  => $point,
																		'description' =>$this->TH_desc_points[600]	.' '.
																						$this->TH_desc_cards[$this->card_value($straight['cards'][4])]
																						.' '.
																						$this->TH_desc_cards[200],

																		'cards'		  => $straight['cards']
																	);
				# Exit for
				$i = -1;

			}
		}


		/* *** Checking for Three of a Kind *** */

		# Order cards
		$higher = $this->card_order($player_cards['higher'], 'natural');

		for ($i = 4; $i >= 0; --$i)
		{
			$tris = $this->tris_match($i, $higher);

			# only if player doesn't have a better point yet
			if (!isset($this->TH_players_points['player_' . $player]['value']) && $tris['is_tris'])
			{
				switch ($this->card_value($tris['cards'][4]))
				{
					case 14: $point = 500; break;
					case 13: $point = 499; break;
					case 12: $point = 498; break;
					case 11: $point = 497; break;
					case 10: $point = 496; break;
					case 9: $point = 495; break;
					case 8: $point = 494; break;
					case 7: $point = 493; break;
					case 6: $point = 492; break;
					case 5: $point = 491; break;
					case 4: $point = 490; break;
					case 3: $point = 489; break;
					case 2: $point = 488; break;
				}

				$this->TH_players_points['player_' . $player] = array(	'value' 		=> $point,
																		'description' 	=> 	$this->TH_desc_points[500]
																							.', '.
																							$this->TH_desc_cards[205]
																							.' '.
																							$this->TH_desc_cards[$this->card_value($tris['cards'][0])]
																							.', '.
																							$this->TH_desc_cards[$this->card_value($tris['cards'][4])]
																							.' '.
																							$this->TH_desc_cards[203],

																		'cards'			=>	$tris['cards']
																		);

				$i = -1;
			}
		}


		/* *** Checking for Two Pairs *** */

		for ($i = 3; $i >= 0; --$i)
		{
			$twop = $this->twopair_match($i, $higher);

			# only if player doesn't have a better point yet
			if (!isset($this->TH_players_points['player_' . $player]['value']) && $twop['is_twopair'])
			{
				$point = 	$this->card_value($twop['cards'][0])	+
							$this->card_value($twop['cards'][1])	+
							$this->card_value($twop['cards'][2])	+
							$this->card_value($twop['cards'][3])	+
							$this->card_value($twop['cards'][4])	+
							334;

				$this->TH_players_points['player_' . $player] = array(
																	'value' 		=> 	$point,
																	'description' 	=>	$this->TH_desc_points[400]
																						.', '.
																						$this->TH_desc_cards[204]
																						.' '.
																						$this->TH_desc_cards[$this->card_value($twop['cards'][1])]
																						.' and '.
																						$this->TH_desc_cards[$this->card_value($twop['cards'][3])]
																						.', '.
																						$this->TH_desc_cards[$this->card_value($twop['cards'][4])]
																						.' '.
																						$this->TH_desc_cards[203],

																	'cards'			=> $twop['cards']
																	);


				$i = -1;
			}
		}


		/* *** Checking for One Pairs *** */

		for ($i = 5; $i >= 0; --$i)
		{
			$pair = $this->onepair_match($i, $higher);

			# only if player doesn't have a better point yet
			if (!isset($this->TH_players_points['player_' . $player]['value']) && $pair['is_pair'])
			{
				$point = 	$this->card_value($pair['cards'][0])	+
							$this->card_value($pair['cards'][1])	+
							$this->card_value($pair['cards'][2])	+
							$this->card_value($pair['cards'][3])	+
							$this->card_value($pair['cards'][4])	+
							236;

				$this->TH_players_points['player_' . $player] = array(
																	'value' 		=> 	$point,
																	'description' 	=>	$this->TH_desc_points[300]
																						.', '.
																						$this->TH_desc_cards[204]
																						.' '.
																						$this->TH_desc_cards[$this->card_value($pair['cards'][1])]
																						.', '.
																						$this->TH_desc_cards[$this->card_value($pair['cards'][4])]
																						.' '.
																						$this->TH_desc_cards[203],

																	'cards'			=> $pair['cards']
																	);
			}
		}


		/* *** Checking for High Card *** */

		# only if player doesn't have a better point yet
		if (!isset($this->TH_players_points['player_' . $player]['value']))
		{
			$point = 	$this->card_value($higher[6])	+
						$this->card_value($higher[5])	+
						$this->card_value($higher[4])	+
						$this->card_value($higher[3])	+
						$this->card_value($higher[2])	+
						141;


			$this->TH_players_points['player_' . $player] = array(
																	'value' 		=> 	$point,
																	'description' 	=>	$this->TH_desc_points[200]
																						.', '.
																						$this->TH_desc_cards[$this->card_value($higher[6])]
																						.' '.
																						$this->TH_desc_cards[200],

																	'cards'			=> array($higher[6],$higher[5],$higher[4],$higher[3],$higher[2])
																	);
		}



		$this->TH_players_points['player_' . $player]['hand'] = array(
																		$player_cards['higher'][5],
																		$player_cards['higher'][6]
																	);
	}


	/** * Simple One Pairs check
	*/
	private function onepair_match($id, $cards)
	{
		$pair = array('is_pair' => false);

		if ($this->card_value($cards[$id]) == $this->card_value($cards[$id+1]))
		{
			switch ($id)
			{
				case 5: $pair = array('is_pair' => true, 'cards' => array($cards[5], $cards[6], $cards[2], $cards[3], $cards[4])); break;
				case 4: $pair = array('is_pair' => true, 'cards' => array($cards[4], $cards[5], $cards[3], $cards[4], $cards[6])); break;
				case 3: $pair = array('is_pair' => true, 'cards' => array($cards[3], $cards[4], $cards[2], $cards[5], $cards[6])); break;
				case 2: $pair = array('is_pair' => true, 'cards' => array($cards[2], $cards[3], $cards[4], $cards[5], $cards[6])); break;
				case 1: $pair = array('is_pair' => true, 'cards' => array($cards[1], $cards[2], $cards[4], $cards[5], $cards[6])); break;
				case 0: $pair = array('is_pair' => true, 'cards' => array($cards[0], $cards[1], $cards[4], $cards[5], $cards[6])); break;
			}
		}

		return $pair;
	}

	/** * Simple Two Pairs check
	*/
	private function twopair_match($id, $cards)
	{
		$twopair = array('is_twopair' => false);

		switch ($id)
		{
			case 3:

				if ($this->card_value($cards[3]) == $this->card_value($cards[4]) &&
					$this->card_value($cards[5]) == $this->card_value($cards[6])
				)
				{

					$twopair = array('is_twopair' => true, 'cards' => array($cards[3], $cards[4], $cards[5], $cards[6], $cards[2]));

				}elseif (	$this->card_value($cards[3]) == $this->card_value($cards[4]) &&
							$this->card_value($cards[1]) == $this->card_value($cards[2])
				)
				{
					$twopair = array('is_twopair' => true, 'cards' => array($cards[1], $cards[2], $cards[3], $cards[4], $cards[6]));

				}elseif (	$this->card_value($cards[3]) == $this->card_value($cards[4]) &&
							$this->card_value($cards[0]) == $this->card_value($cards[1])
				)
				{
					$twopair = array('is_twopair' => true, 'cards' => array($cards[0], $cards[1], $cards[3], $cards[4], $cards[6]));
				}

			break;

			case 2:

				if ($this->card_value($cards[2]) == $this->card_value($cards[3]) &&
					$this->card_value($cards[4]) == $this->card_value($cards[5])
				)
				{

					$twopair = array('is_twopair' => true, 'cards' => array($cards[2], $cards[3], $cards[4], $cards[5], $cards[6]));

				}elseif (	$this->card_value($cards[2]) == $this->card_value($cards[3]) &&
							$this->card_value($cards[5]) == $this->card_value($cards[6])
				)
				{
					$twopair = array('is_twopair' => true, 'cards' => array($cards[2], $cards[3], $cards[5], $cards[6], $cards[4]));

				}elseif (	$this->card_value($cards[2]) == $this->card_value($cards[3]) &&
							$this->card_value($cards[0]) == $this->card_value($cards[1])
				)
				{
					$twopair = array('is_twopair' => true, 'cards' => array($cards[0], $cards[1], $cards[2], $cards[3], $cards[6]));
				}

			break;
			case 1:

				if ($this->card_value($cards[1]) == $this->card_value($cards[2]) &&
					$this->card_value($cards[3]) == $this->card_value($cards[4])
				)
				{

					$twopair = array('is_twopair' => true, 'cards' => array($cards[1], $cards[2], $cards[3], $cards[4], $cards[6]));

				}elseif (	$this->card_value($cards[1]) == $this->card_value($cards[2]) &&
							$this->card_value($cards[4]) == $this->card_value($cards[5])
				)
				{
					$twopair = array('is_twopair' => true, 'cards' => array($cards[1], $cards[2], $cards[4], $cards[5], $cards[6]));

				}elseif (	$this->card_value($cards[1]) == $this->card_value($cards[2]) &&
							$this->card_value($cards[5]) == $this->card_value($cards[6])
				)
				{
					$twopair = array('is_twopair' => true, 'cards' => array($cards[1], $cards[2], $cards[5], $cards[6], $cards[4]));
				}

			break;
			case 0:

				if ($this->card_value($cards[0]) == $this->card_value($cards[1]) &&
					$this->card_value($cards[2]) == $this->card_value($cards[3])
				)
				{

					$twopair = array('is_twopair' => true, 'cards' => array($cards[0], $cards[1], $cards[2], $cards[3], $cards[6]));

				}elseif (	$this->card_value($cards[0]) == $this->card_value($cards[1]) &&
							$this->card_value($cards[3]) == $this->card_value($cards[4])
				)
				{
					$twopair = array('is_twopair' => true, 'cards' => array($cards[0], $cards[1], $cards[3], $cards[4], $cards[6]));

				}elseif (	$this->card_value($cards[0]) == $this->card_value($cards[1]) &&
							$this->card_value($cards[4]) == $this->card_value($cards[5])
				)
				{
					$twopair = array('is_twopair' => true, 'cards' => array($cards[0], $cards[1], $cards[4], $cards[5], $cards[6]));

				}elseif (	$this->card_value($cards[0]) == $this->card_value($cards[1]) &&
							$this->card_value($cards[5]) == $this->card_value($cards[6])
				)
				{
					$twopair = array('is_twopair' => true, 'cards' => array($cards[0], $cards[1], $cards[5], $cards[6], $cards[4]));
				}

			break;
		}

		return $twopair;
	}


	/** * simple Tris check
	*/
	private function tris_match($id, $cards)
	{
		$cond = array('is_tris' => false);

		if ($this->card_value($cards[$id]) == $this->card_value($cards[$id+1]) &&
			$this->card_value($cards[$id]) == $this->card_value($cards[$id+2])
			)
		{
			switch ($id)
			{
				case 4:
						$cond = array('is_tris' => true, 'cards' => array($cards[$id], $cards[$id+1], $cards[$id+2], $cards[2], $cards[3]));
				break;

				case 3:
						$cond = array('is_tris' => true, 'cards' => array($cards[$id], $cards[$id+1], $cards[$id+2], $cards[2], $cards[6]));
				break;

				case 2:
						$cond = array('is_tris' => true, 'cards' => array($cards[$id], $cards[$id+1], $cards[$id+2], $cards[5], $cards[6]));
				break;

				case 1:
						$cond = array('is_tris' => true, 'cards' => array($cards[$id], $cards[$id+1], $cards[$id+2], $cards[5], $cards[6]));
				break;

				case 0:
						$cond = array('is_tris' => true, 'cards' => array($cards[$id], $cards[$id+1], $cards[$id+2], $cards[5], $cards[6]));
				break;
			}
		}


		return $cond;
	}


	/** * simple flush check
	*/
	private function flush_match($id, $cards)
	{
		$cond = array('is_flush' => false);

		if ($this->card_seed($cards[$id]) == $this->card_seed($cards[$id+1])	&&
			$this->card_seed($cards[$id]) == $this->card_seed($cards[$id+2])	&&
			$this->card_seed($cards[$id]) == $this->card_seed($cards[$id+3])	&&
			$this->card_seed($cards[$id]) == $this->card_seed($cards[$id+4])
			)
		{
			$cond = array('is_flush' => true, 'cards' => array($cards[$id],$cards[$id+1],$cards[$id+2],$cards[$id+3],$cards[$id+4]));
		}

		return $cond;
	}


	/** * Full house check
	*/
	private function full_match($id, $cards)
	{
		$cond = array('is_full' => false);

		switch ($id)
		{
			case 4:

					if ($this->card_value($cards[$id]) == $this->card_value($cards[$id+1])	&&
						$this->card_value($cards[$id]) == $this->card_value($cards[$id+2])
						)
					{
						if ($this->card_value($cards[$id-1]) == $this->card_value($cards[$id-2]))
							$full = array($cards[$id-1], $cards[$id-2]);

						if ($this->card_value($cards[$id-2]) == $this->card_value($cards[$id-3]))
							$full = array($cards[$id-2], $cards[$id-3]);

						if ($this->card_value($cards[$id-3]) == $this->card_value($cards[$id-4]))
							$full = array($cards[$id-3], $cards[$id-4]);

						if(isset($full))
                        {
                            if (is_array($full))
                            {
                                $full[] = $cards[$id];
                                $full[] = $cards[$id+1];
                                $full[] = $cards[$id+2];

                                $cond = array('is_full' => true, 'cards' => $full);
                            }
                        }
					}

			break;
			case 3:
					if ($this->card_value($cards[$id]) == $this->card_value($cards[$id+1])	&&
						$this->card_value($cards[$id]) == $this->card_value($cards[$id+2])
						)
					{
						if ($this->card_value($cards[$id-1]) == $this->card_value($cards[$id-2]))
							$full = array($cards[$id-1], $cards[$id-2]);

						if ($this->card_value($cards[$id-2]) == $this->card_value($cards[$id-3]))
							$full = array($cards[$id-2], $cards[$id-3]);


                        if(isset($full))
                        {
                            if (is_array($full))
                            {
                                $full[] = $cards[$id];
                                $full[] = $cards[$id+1];
                                $full[] = $cards[$id+2];

                                $cond = array('is_full' => true, 'cards' => $full);
                            }
                        }
					}

			break;
			case 2:

					if ($this->card_value($cards[$id]) == $this->card_value($cards[$id+1])	&&
						$this->card_value($cards[$id]) == $this->card_value($cards[$id+2])
						)
					{
						if ($this->card_value($cards[$id-1]) == $this->card_value($cards[$id-2]))
							$full = array($cards[$id-1], $cards[$id-2]);

						if ($this->card_value($cards[5]) == $this->card_value($cards[6]))
							$full = array($cards[5], $cards[6]);


                        if(isset($full))
                        {
                            if (is_array($full))
                            {
                                $full[] = $cards[$id];
                                $full[] = $cards[$id+1];
                                $full[] = $cards[$id+2];

                                $cond = array('is_full' => true, 'cards' => $full);
                            }
                        }
					}
			break;
			case 1:

					if ($this->card_value($cards[$id]) == $this->card_value($cards[$id+1])	&&
						$this->card_value($cards[$id]) == $this->card_value($cards[$id+2])
						)
					{
						if ($this->card_value($cards[4]) == $this->card_value($cards[5]))
							$full = array($cards[4], $cards[5]);

						if ($this->card_value($cards[5]) == $this->card_value($cards[6]))
							$full = array($cards[5], $cards[6]);


                        if(isset($full))
                        {
                            if (is_array($full))
                            {
                                $full[] = $cards[$id];
                                $full[] = $cards[$id+1];
                                $full[] = $cards[$id+2];

                                $cond = array('is_full' => true, 'cards' => $full);
                            }
                        }
					}
			break;
			case 0:

					if ($this->card_value($cards[$id]) == $this->card_value($cards[$id+1])	&&
						$this->card_value($cards[$id]) == $this->card_value($cards[$id+2])
						)
					{
						if ($this->card_value($cards[3]) == $this->card_value($cards[4]))
							$full = array($cards[3], $cards[4]);

						if ($this->card_value($cards[4]) == $this->card_value($cards[5]))
							$full = array($cards[4], $cards[5]);

						if ($this->card_value($cards[5]) == $this->card_value($cards[6]))
							$full = array($cards[5], $cards[6]);


                        if(isset($full))
                        {
                            if (is_array($full))
                            {
                                $full[] = $cards[$id];
                                $full[] = $cards[$id+1];
                                $full[] = $cards[$id+2];

                                $cond = array('is_full' => true, 'cards' => $full);
                            }
                        }
					}
			break;
		}


		return $cond;
	}


	/** * Simple poker check
	*/
	private function poker_match($id, $cards)
	{
		$poker = array('is_poker' => false);

		if ($this->card_value($cards[$id]) == $this->card_value($cards[$id+1])	&&
			$this->card_value($cards[$id]) == $this->card_value($cards[$id+2])	&&
			$this->card_value($cards[$id]) == $this->card_value($cards[$id+3])
			)
		{
			$poker = array('is_poker' => true, 'cards' => array($cards[$id],$cards[$id+1],$cards[$id+2],$cards[$id+3]));
		}

		return $poker;
	}


	/** * return the strenght value
	*/
	private function straight_value($val)
	{
		return ($this->card_value($val)*5)+10;
	}


	/** * Any strenght would be matched with their unique 4 number series
	*/
	private function straight_match($id, $cards)
	{
		$straight = array('is_straight' => false);


		if (
			(
				$this->straight_value($cards[$id]) == (
														$this->card_value($cards[$id]) 	+
														$this->card_value($cards[$id+1])+
														$this->card_value($cards[$id+2])+
														$this->card_value($cards[$id+3])+
														$this->card_value($cards[$id+4])
														)
			)

			&&

			(($this->straight_value($cards[$id])-15)/5) ==
			(($this->card_value($cards[$id])+$this->card_value($cards[$id+1])+$this->card_value($cards[$id+2])+$this->card_value($cards[$id+3])-10)/4)

			&&

			(($this->straight_value($cards[$id])-15)/5) ==
			(($this->card_value($cards[$id])+$this->card_value($cards[$id+1])+$this->card_value($cards[$id+2])-6)/3)

			&&

			(($this->straight_value($cards[$id])-15)/5) ==
			(($this->card_value($cards[$id])+$this->card_value($cards[$id+1])-3)/2)
		   )
		{
			$straight = array('is_straight' => true, 'cards' => array($cards[$id],$cards[$id+1],$cards[$id+2],$cards[$id+3],$cards[$id+4]));
		}



		return $straight;
	}


	/** * Really simple seed matching
	*/
	private function seed_match($id, $seeds)
	{
		return (	$this->card_seed($seeds[$id]) == $this->card_seed($seeds[$id+1]) &&
					$this->card_seed($seeds[$id]) == $this->card_seed($seeds[$id+2]) &&
					$this->card_seed($seeds[$id]) == $this->card_seed($seeds[$id+3]) &&
					$this->card_seed($seeds[$id]) == $this->card_seed($seeds[$id+4])
				);
	}


	/** * ording card by seed or their value.
		* may be declared in second parameter of method
	*/
	private function card_order($cards, $by_seed = true)
	{
		$ord_cards = array();
		$order = array();

		usort($cards, create_function('$a,$b', 'return intval(substr($a,0,-1))-intval(substr($b,0,-1));'));


		if ($by_seed === true)
		{
			foreach ($cards as $card)
					$order[$this->card_seed($card)][] = $card;

			foreach ($order as $seedordcards)
					foreach ($seedordcards as $card)
							$ord_cards[] = $card;

		}elseif ($by_seed === false)
		{
			$order = array('primary' => array(), 'secondary' => array());

			foreach ($cards as $card)
					if (!in_array($this->card_value($card), $order['primary']))
					{
							$ord_cards[] = $card;
							$order['primary'][] = $this->card_value($card);

					}else
							$order['secondary'][] = $card;


			foreach ($order['secondary'] as $card)
					$ord_cards[] = $card;

		}else
		{
			$ord_cards = $cards;
		}

		unset($order);


		return $ord_cards;
	}


	private function get_all_player_card($player)
	{
		$all_cards = $this->TH_game_state['all'];
		$higher = array();
		$lower 	= array();

		foreach ($this->TH_players_hands['player_' . $player] as $card)
				$all_cards[] = $card;
								
		foreach ($all_cards as $card)
		{	
			$card = str_replace('J', 11, $this->card_value($card)) . $this->card_seed($card);
			$card = str_replace('Q', 12, $this->card_value($card)) . $this->card_seed($card);
			$card = str_replace('K', 13, $this->card_value($card)) . $this->card_seed($card);
			$card = str_replace('A', 14, $this->card_value($card)) . $this->card_seed($card);
			
			$higher[] = $card;
			
			$card = str_replace(14, 1, $this->card_value($card)) . $this->card_seed($card);
			
			$lower[] = $card;
		}
		
		return array('all' => $all_cards, 'higher' => $higher, 'lower' => $lower);
	}


	public function free_resources()
	{
		$this->reset_pokerdeck();
		
		unset($this->TH_cards);
		unset($this->TH_players_hands);
		unset($this->TH_players_points);
		unset($this->TH_game_state);
		unset($this->TH_desc_cards);
		unset($this->TH_desc_points);
	}
	
}

?>