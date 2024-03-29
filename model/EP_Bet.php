<?php

class EP_Bet {
	/**
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * @var EP_Competition
	 */
	protected $competition;

	/**
	 * @var EP_Player
	 */
	protected $player;

	/**
	 * @var EP_Referee
	 */
	protected $referee;

	/**
	 * @var EP_User
	 */
	protected $owner;

	/**
	 * @var bool
	 */
	protected $paid;

	/**
	 * @var int
	 */
	protected $points;

	/**
	 * @var array
	 */
	protected $scores;

	/**
	 * @param int $post_id
	 *
	 * @throws Exception
	 */
	public function __construct( int $post_id=0) {
		if ( $post_id <= 0) throw new Exception(sprintf('%s is not a valid ID creating a Bet at EP_Bet',$post_id),-1);
		if (is_null($this->post = get_post($post_id))) throw new Exception(sprintf('Post %s does not exist creating a Bet at EP_Bet',$post_id),-1);
		if (get_post_type($post_id)!="bet") throw new Exception(sprintf('Wrong post id %s creating a Bet at EP_Bet. Post is not a bet',$post_id),-1);
	}

	public function getId() : int {
		return $this->post->ID;
	}

	public function getName() : string {
		return $this->post->post_title;
	}

	public function getOwner() : EP_User {
		if ($this->owner) return $this->owner;
		return $this->owner = new EP_User($this->post->post_author);
	}

	public function getEmail() : string {
		return $this->getOwner()->user_email;
	}

	public function getBetNumber() : int {
		return (int) get_post_meta($this->getId(),'bet_number',true);
	}

	/**
	 * @throws Exception
	 */
	public function getPlayer() : EP_Player {
		if ($this->player) return $this->player;
		try { return $this->player = new EP_Player(get_post_meta($this->getId(),'player',true)); }
		catch (Exception $e) { throw new Exception(sprintf('%s at EP_Bet::getPlayer',$e->getMessage()),-1); }
	}

	public function setPlayer(EP_Player $player) {
		update_post_meta($this->getId(),'player',$player->getId());
	}

	/**
	 * @throws Exception
	 */
	public function getReferee() : ?EP_Referee {
		if ($this->referee) return $this->referee;
		if (!get_post_meta($this->getId(),'referee',true)) return null;
		try { return $this->referee = new EP_Referee(get_post_meta($this->getId(),'referee',true)); }
		catch (Exception $e) { throw new Exception(sprintf('%s at EP_Bet::getReferee',$e->getMessage()),-1); }
	}

	public function setReferee(int $referee_id) {
		$this->referee = new EP_Referee($referee_id);
		update_post_meta($this->getId(),'referee',$referee_id);
	}

	public function getPoints() : float {
		return (float) get_post_meta($this->getId(),'points',true);
	}

	public function setPoints($points) {
		return update_post_meta($this->getId(),'points',$points);
	}

	/**
	 * @throws Exception
	 */
	public function refereeHit() : bool {
		return (
			!is_null($this->getCompetition()->getReferee()) &&
			!is_null($this->getReferee()) &&
			$this->getCompetition()->getReferee()->getId()==$this->getReferee()->getId()
		);
	}

	/**
	 * @throws Exception
	 */
	public function topScorerHit() : bool {
		return (
			!is_null($this->getCompetition()->getTopScorers()) &&
			!empty($this->getCompetition()->getTopScorers()) &&
			EP_Player::isPlayer($this->getPlayer()) &&
			EP_Player::inArrayPlayers($this->getPlayer(),$this->getCompetition()->getTopScorers())
		);
	}

	/**
	 * @throws Exception
	 */
	public function calculatePoints() {
		$points = 0;
		$scores = $this->getScores();
		if ($this->refereeHit()) $points+=5; // TODO : Write a method EP_Competition::getRefereePoints()
		if ($this->topScorerHit()) $points+=5; // TODO: Write a method EP_Competition::getTopScorerPoints()
		foreach ($scores as $key => $score) {
			if ($key>=49 && $score["winner"]=="X") {
				if ($_GET["winnerRaro"]) echo "<a href='https://www.enroporra.es/wp-admin/post.php?post=".$this->getId()."&action=edit' target='_blank'>".$this->getName()."</a><br>";
			}

			$scores[$key]["points_winner"]=$scores[$key]["points_score"]=$scores[$key]["player_goals"]=0;
			if ($score["fixture"]->isPlayed()) {
				$gotWinner = false;
				// If bet hits a tie, we don't worry about teams
				if ($score["fixture"]->getWinner()==$score["winner"] && $score["winner"]=="X") {
					$gotWinner=true;
					$pointsWinner = $this->getCompetition()->getPoints("winner",$score["fixture"]->getTournament());
					$points+=$pointsWinner;
					$scores[$key]["points_winner"]=$pointsWinner;
				}
				// If bet is a win-lose we need to compare real teams in fixture with teams in bet.
				else if ($score["fixture"]->getWinner()==$score["winner"] && $score["fixture"]->getTeam($score["winner"])->getId()==$score["t".$score["winner"]]->getId()) {
					$gotWinner=true;
					$pointsWinner = $this->getCompetition()->getPoints("winner",$score["fixture"]->getTournament());
					$points+=$pointsWinner;
					$scores[$key]["points_winner"]=$pointsWinner;
				}
				// Only if bet hits winner/tie, we compare scoring with bet
				if ($gotWinner && $score["fixture"]->getGoals(1)==$score["s1"] && $score["fixture"]->getGoals(2)==$score["s2"]) {
					$pointsScore = $this->getCompetition()->getPoints("score",$score["fixture"]->getTournament());
					$points+=$pointsScore;
					$scores[$key]["points_score"]=$pointsScore;
				}
				$playerGoals=0;
				foreach ($score["fixture"]->getScorers() as $scorer) {
					if ($scorer["player"]->getId()==$this->getPlayer()->getId() && $scorer["team_for"]->getId()==$this->getPlayer()->getTeam()->getId()) {
						$points += $this->getCompetition()->getPoints( "scorer", $score["fixture"]->getTournament() );
						$playerGoals++;
					}
				}
				$scores[$key]["player_goals"]=$playerGoals;
				$scores[$key]["points_counted"]=true;
			}
		}
		$this->setPoints($points);
		$this->setScores($scores);
	}

	/**
	 * @throws Exception
	 */
	public function getCompetition() : EP_Competition {
		if ($this->competition) return $this->competition;
		return $this->competition = new EP_Competition(get_post_meta($this->getId(),'competition',true));
	}

	public function isPaid() : bool {
		return (bool) get_post_meta($this->getId(),'paid',true);
	}

	public function setPaid($paid) {
		if ($paid===true) $paid=1;
		if ($paid!=1) $paid=0;
		return update_post_meta($this->getId(),'paid',$paid);
	}

	/**
	 * @return array
	 *
	 * @throws Exception
	 */
	public function getScores(): array {
		if (is_array($this->scores) && !empty($this->scores)) return $this->scores;
		$response = unserialize(get_post_meta($this->getId(),'scores',true));
		$rewrite = false;
		foreach ($response as $key => $score) {
			if ($key==0) $rewrite=true;
			if ($rewrite) $key++;
			try {
				$response[ $key ]["fixture"] = $this->getCompetition()->getFixtureById( $key );
				if ($response[ $key ]["fixture"]->getTournament()=="groups" || $response[ $key ]["fixture"]->getTournament()=="last16") {
					$response[$key]["t1"]=$response[ $key ]["fixture"]->getTeam(1);
					$response[$key]["t2"]=$response[ $key ]["fixture"]->getTeam(2);
				}
				else {
					if  (!$score["t1"]) {
						$label = $this->getCompetition()->getFixtureById( $key )->getLabelTeam( 1 );
						if ( $label[0] == "W" ) {
							$previousFixtureNumberId = substr( $label, 1 );
							if ( $response[ $previousFixtureNumberId ]["s1"] > $response[ $previousFixtureNumberId ]["s2"] ) {
								$response[ $key ]["t1"] = $response[ $previousFixtureNumberId ]["t1"];
							} else if ( $response[ $previousFixtureNumberId ]["s1"] < $response[ $previousFixtureNumberId ]["s2"] ) {
								$response[ $key ]["t1"] = $response[ $previousFixtureNumberId ]["t2"];
							} else if ( $response[ $previousFixtureNumberId ]["winner"] != "X" ) {
								$response[ $key ]["t1"] = $response[ $previousFixtureNumberId ][ "t" . $response[ $previousFixtureNumberId ]["winner"] ];
							} else {
								$response[ $key ]["t1"] = new EP_Team( 0 ); // FAIL
							}
						}
						else {
							$response[ $key ]["t1"] = new EP_Team( 0 ); // FAIL
						}
					}
					else if (isset($score["t1"])) $response[ $key ]["t1"] = new EP_Team( $score["t1"] );
					if  (!$score["t2"]) {
						$label = $this->getCompetition()->getFixtureById( $key )->getLabelTeam(2);
						if ($label[0]=="W") {
							$previousFixtureNumberId = substr($label,1);
							if ($response[$previousFixtureNumberId]["s1"]>$response[$previousFixtureNumberId]["s2"])
								$response[ $key ]["t2"] = $response[ $previousFixtureNumberId ]["t1"];
							else if ($response[$previousFixtureNumberId]["s1"]<$response[$previousFixtureNumberId]["s2"])
								$response[ $key ]["t2"] = $response[ $previousFixtureNumberId ]["t2"];
							else if ($response[ $previousFixtureNumberId ]["winner"]!="X")
								$response[ $key ]["t2"] = $response[ $previousFixtureNumberId ]["t".$response[ $previousFixtureNumberId ]["winner"]];
							else $response[ $key ]["t2"] = new EP_Team(0); // FAIL
						}
						else $response[ $key ]["t2"] = new EP_Team(0); // FAIL
					}
					else if (isset($score["t2"])) $response[ $key ]["t2"] = new EP_Team( $score["t2"] );
				}
				if ($score["s1"]>$score["s2"]) $response[$key]["winner"]="1";
				else if ($score["s1"]<$score["s2"]) $response[$key]["winner"]="2";
				else if (isset($score["winner"]) && ($score["winner"]==1||$score["winner"]==2)) $response[$key]["winner"]=$score["winner"];
				else $response[$key]["winner"]="X";
			}
			catch (Exception $e) { throw new Exception(sprintf('%s at EP_Bet::getScores',$e->getMessage()),-1); }
		}
		unset($response[0]);
		return $this->scores = $response;
	}

	/**
	 * @throws Exception
	 */
	public function isPlayoffFulfilled() : bool {

		$scoresInBet = count($this->getScores());
		$allFixtures = count($this->getCompetition()->getFixtures());
		$third = count($this->getCompetition()->getFixtures(array("tournament"=>"third")));

		return ($scoresInBet==($allFixtures-$third) && $this->getReferee());
	}

	public function getFixtureBet(int $fixtureNumber) : array {
		return isset($this->getScores()[$fixtureNumber]) ? $this->getScores()[$fixtureNumber] : array();
	}

	public function getScoresRaw() {
		return get_post_meta($this->getId(),'scores',true);
	}

	public function setScores($scores) {
		$this->scores = $scores;
		foreach ($scores as $key => $score) {
			if (isset($score["t1"])) $scores[$key]["t1"] = $score["t1"]->getId();
			if (isset($score["t2"])) $scores[$key]["t2"] = $score["t2"]->getId();
			if ($score["s1"]>$score["s2"]) $scores[$key]["winner"]=1;
			if ($score["s1"]<$score["s2"]) $scores[$key]["winner"]=2;
			if (isset($score["fixture"])) $scores[$key]["fixture"] = $score["fixture"]->getId();
		}
		return update_post_meta($this->getId(),'scores',serialize($scores));
	}

	/**
	 * @param array $args user_id => int id of owner WP_User, name => string with the name of the bet, player_id => int id of EP_Player, scores => array with all the results of the first part of the bet.
	 *
	 * @return EP_Bet
	 * @throws Exception
	 */
	public static function createBet(array $args) : EP_Bet {

		// Mandatory fields to set a bet on Enroporra
		if (!isset($args["user_id"])||!isset($args["name"])||!isset($args["player_id"])||!isset($args["scores"]))
			throw new Exception( 'Malformed array of args creating a bet at EP_Bet, some required field/s is/are missing.',-1);

		$competition = EP_Competition::getCurrentCompetition();
		if (!$competition) throw new Exception('There is not a current competition at Enroporra right now at EP_Bet::createBet');

		$user = new WP_User($args["user_id"]);
		if ($user->ID!=$args["user_id"]) throw new Exception('Invalid user_id at EP_Bet::createBet.',-1);

		try { $player = new EP_Player((int)$args["player_id"]); }
		catch (Exception $e) { throw new Exception(sprintf('Invalid player_id at EP_Bet::createBet: %s',$e->getMessage()),-1); }

		if (!$player->isMyCompetition($competition)) throw new Exception(sprintf('Player %s does not play competition %s at EP_Bet::createBet',$player->getName(),$competition->getName()),-1);

		$groupFixtures = $competition->getFixtures(array("tournament"=>"groups"));
		$validationScores = true;
		foreach ($groupFixtures as $fixture) {
			if (!isset($args["scores"][$fixture->getFixtureNumber()])) { $validationScores=false; break; }
			if (!isset($args["scores"][$fixture->getFixtureNumber()]["s1"]) || !is_integer($args["scores"][$fixture->getFixtureNumber()]["s1"]) || $args["scores"][$fixture->getId()]["s1"]<0 || $args["scores"][$fixture->getFixtureNumber()]["s1"]>20)  { $validationScores=false; break; }
			if (!isset($args["scores"][$fixture->getFixtureNumber()]["s2"]) || !is_integer($args["scores"][$fixture->getFixtureNumber()]["s2"]) || $args["scores"][$fixture->getId()]["s2"]<0 || $args["scores"][$fixture->getFixtureNumber()]["s2"]>20)  { $validationScores=false; break; }
		}
		if (!$validationScores) throw new Exception('Scores are not properly filled at EP_Bet::createBet',-1);

		if ( ! function_exists( 'post_exists' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/post.php' );
		}

		$title = normalizeSpanishName($args["name"]);
		if (post_exists( $title,'','','bet')) {
			$title.=" (2)";
			$i=2;
		}
		while (post_exists( $title,'','','bet')) {
			$title = str_replace("(".$i.")","(".($i+1).")",$title);
			$i++;
		}

		$new_bet_id = wp_insert_post(array('post_title'=>$title,'post_type'=>'bet','post_status'=>'publish','post_author'=>$user->ID));
		if (is_wp_error($new_bet_id))
			throw new Exception(sprintf('Could not create wordpress post at EP_Bet::createBet: %s.',$new_bet_id->get_error_message()),-1);

		add_post_meta($new_bet_id,'bet_number',$competition->getBetNumber(),true);
		add_post_meta($new_bet_id,'competition',$competition->getId(),true);
		add_post_meta($new_bet_id,'player',$player->getId(),true);
		add_post_meta($new_bet_id,'referee',0,true);
		add_post_meta($new_bet_id,'scores',serialize($args["scores"]),true);
		add_post_meta($new_bet_id,'paid',0,true);
		add_post_meta($new_bet_id,'points',0,true);

		return new EP_Bet($new_bet_id);
	}

	/**
	 * @return array|EP_Bet
	 * @throws Exception
	 */
	public static function createBetFromForm(EP_Competition $competition) {
		$email = mb_strtolower($_POST["enroporra_email"]);
		$user_id = email_exists($email);
		$error = array();
		if ($user_id) {
			$user = get_user_by('ID',$user_id);
			if (!wp_check_password( $_POST["enroporra_password"], $user->data->user_pass, $user_id )) {
				$error["user_password"]=sprintf(__('El usuario %s está protegido por contraseña, y no coincide con la que has escrito.','enroporra'),$_POST["enroporra_email"]);
			}
		}
		else {
			$user_id = wp_create_user($_POST["enroporra_name"],$_POST["enroporra_password"],$email);
			if (is_wp_error($user_id)) {
				$error["new_user"]=__('No se pudo crear el usuario ni la apuesta. Por favor, inténtalo más tarde.','enroporra');
			}
			else $user = get_user_by('ID', $user_id);
		}
		if (count($error)) {
			return $error;
		}
		else {
			update_user_meta($user_id,'phone',$_POST["enroporra_phone"]);
			$scores = array();
			foreach ($_POST as $key => $value) {
				$temp = explode("_",$key);
				if ($temp[0]=="enroporra" && $temp[1]=="result" && count($temp)==4) {
					$scores[$temp[2]]["s".$temp[3]] = intval($value);
				}
			}
			if ($bet = $competition->betRepeated($email,$scores)) {
				$error["bet_repeated"]=sprintf(__('Has enviado ya una apuesta con estos mismos marcadores. Su número es la %s. Si quieres hacer una nueva pulsa el botón.','enroporra'),"<strong>".$bet->getBetNumber()."</strong>");
				return $error;
			}
			try {
				$bet = EP_Bet::createBet(
					array(
						"user_id"   => $user_id,
						"name"      => $_POST["enroporra_name"],
						"player_id" => $_POST["enroporra_player_id"],
						"scores"    => $scores
					)
				);
			}
			catch (Exception $e) {
				return array('new_post_type'=>__('No se pudo crear la apuesta en Enroporra. Por favor, inténtalo más tarde.','enroporra').": ".$e->getMessage());
			}
		}
		return $bet;
	}

	/**
	 * @throws Exception
	 */
	public function getHTMLBet($showPoints=false) : string {
		$response = "<div class='top-scorer-bet'><strong>".__('Pichichi','enroporra').":</strong> ".$this->getPlayer()->getTeam()->getFlagHTML(30)." ".$this->getPlayer()->getImageHTML(30)." ".$this->getPlayer()->getName()." (".$this->getPlayer()->getTeam()->getName().")";
		if ($this->getCompetition()->getStage()>=EP_Competition::PLAYOFF_PLAYING && $this->topScorerHit()) {
			$response.="&nbsp;&nbsp;<span class='points-text-inverse'>5 ".__("puntos","enroporra")."</span>"; // TODO: Write a method EP_Competition::getTopScorerPoints()
		}
		$response.="</div>";
		if ($this->getCompetition()->getStage()>=EP_Competition::PLAYOFF_PLAYING || $this->getOwner()->isViewing()) {
			$refereeLabel = ($this->getReferee()) ? "<strong>".__('Árbitro de la final','enroporra').":</strong> ".$this->getReferee()->getTeam()->getFlagHTML(30)." ".$this->getReferee()->getName()." (".$this->getReferee()->getTeam()->getName().")" : __("Este porrista no participa en la segunda fase","enroporra");
			$refereeHit = ($this->refereeHit()) ? "&nbsp;&nbsp;<span class='points-text-inverse'>5 ".__("puntos","enroporra")."</span>" : ""; // TODO : Write a method EP_Competition::getRefereePoints()
			$response.= "<div class='top-scorer-bet'>".$refereeLabel.$refereeHit."</div>";
		}
		function cmp_scores_priorize_second_stage($a,$b) {
			if ($a["fixture"]->getTournament()=="groups" && $b["fixture"]->getTournament()!="groups") return true;
			else if ($a["fixture"]->getTournament()!="groups" && $b["fixture"]->getTournament()=="groups") return false;
			else if ($a["fixture"]->getRawDate()>$b["fixture"]->getRawDate()) return true;
			else return false;
		}
		$scores = $this->getScores();
		usort($scores,"cmp_scores_priorize_second_stage");
		$labelGroups=$labelPlayoffs=true;
		foreach ($scores as $score) {
			/** @var EP_Fixture $fixture */
			$fixture = $score["fixture"];

			if ($fixture->getTournament()!="groups" && ($this->getCompetition()->getStage()<EP_Competition::PLAYOFF_PLAYING && !$this->getOwner()->isViewing()))
				continue;

			if ($labelGroups && $fixture->getTournament()=="groups") {
				$response.="<h3>".__("Fase de grupos","enroporra")."</h3>";
				$labelGroups = false;
			}
			if ($labelPlayoffs && $fixture->getTournament()!="groups") {
				$response.="<h3>".__("Fase final","enroporra")."</h3>";
				$labelPlayoffs = false;
			}

			$response.="<div class='date-bet'><small>".$fixture->getDate().", ";
			if ($fixture->getTournament()=="groups") $response.= __("Grupo","enroporra")." ".$fixture->getGroup();
			else $response.=$fixture->getTournamentLabel();
			$response.="</small></div>";
			$response.="<div class='fixture-bet'>".$score["t1"]->getFlagHTML(30)." ".$score["t1"]->getName()." <strong class='score-bet'>".$score["s1"]."</strong> ";
			$response.=$score["t2"]->getFlagHTML(30)." ".$score["t2"]->getName()." <strong class='score-bet'>".$score["s2"]."</strong></div>";
			if ($fixture->getTournament()!="groups" && $score["s1"]==$score["s2"]) $response.="Por penaltis: ".$score["t".$score["winner"]]->getFlagHTML(15)."<br /><br />";
			if ($showPoints && $fixture->isPlayed()) {
				$penaltiesLabel = ($fixture->getTournament()!="groups" && $fixture->getGoals(1)==$fixture->getGoals(2)) ? "(".__("penaltis","enroporra")." ".$fixture->getTeam($fixture->getWinner())->getFlagHTML(15).")" : "";
				$response.="Partido jugado: ".$fixture->getTeam(1)->getFlagHTML(15)." ".$fixture->getGoals(1)." ".$fixture->getTeam(2)->getFlagHTML(15)." ".$fixture->getGoals(2)." ".$penaltiesLabel."<br />";
				if ($score["points_winner"]) {
					$plural = ($score["points_winner"]>1) ? "s":"";
					$response.="<span class='points-text-inverse'>".$score["points_winner"]." ".__("punto","enroporra").$plural."</span>";
					if ($score["winner"]=="X") $response.=" ".__("por empate","enroporra");
					else $response.=" ".__("por victoria de","enroporra")." ".$fixture->getTeam($score["winner"])->getName();
				}
				if ($score["points_score"]) {
					$response.="<br /><span class='points-text-inverse'>".$score["points_score"]." ".__("puntos","enroporra")."</span> ".__("por resultado","enroporra");
				}
				if ($score["player_goals"]) {
					$goalLabel = ($score["player_goals"]>1) ? __("goles","enroporra") : __("gol","enroporra");
					$response.="<br /><span class='points-text-inverse'>".($score["player_goals"]*$this->getCompetition()->getPoints("scorer",$fixture->getTournament()))." ".__("puntos","enroporra")."</span> ".__("por","enroporra")." ".$score["player_goals"]." ".$goalLabel." ".__("de","enroporra")." ".$this->getPlayer()->getName();
				}
			}
			$response.="<hr>";
		}
		return $response;
	}

	public function getHTMLBet2($showPoints=false) {
		$response = "<div class='top-scorer-bet'><strong>".__('Árbitro de la final','enroporra').":</strong> ".$this->getReferee()->getTeam()->getFlagHTML(30)." ".$this->getReferee()->getName()." (".$this->getReferee()->getTeam()->getName().")</div>";
		foreach ($this->getScores() as $score) {
			/** @var EP_Fixture $fixture */
			$fixture = $score["fixture"];
			/** @var EP_Team $team1 */
			$team1 = $score["t1"];
			/** @var EP_Team $team2 */
			$team2 = $score["t2"];
			if ($fixture->getTournament()=="groups") continue;
			$response.="<div class='date-bet'><small>".$fixture->getDate().", ".$fixture->getTournamentLabel()."</small></div>";
			$response.="<div class='fixture-bet'>".$team1->getFlagHTML(30)." ".$team1->getName()." <strong class='score-bet'>".$score["s1"]."</strong> ";
			$response.=$team2->getFlagHTML(30)." ".$team2->getName()." <strong class='score-bet'>".$score["s2"]."</strong></div>";
			if ($showPoints && $fixture->isPlayed()) {
				$response.="Partido jugado: ".$fixture->getTeam(1)->getFlagHTML(15)." ".$fixture->getGoals(1)." ".$fixture->getTeam(2)->getFlagHTML(15)." ".$fixture->getGoals(2)."<br />";
				if ($score["points_winner"]) {
					$plural = ($score["points_winner"]>1) ? "s":"";
					$response.="<span class='points-text-inverse'>".$score["points_winner"]." ".__("punto","enroporra").$plural."</span>";
					if ($score["winner"]=="X") $response.=" ".__("por empate","enroporra");
					else $response.=" ".__("por victoria de","enroporra")." ".$fixture->getTeam($score["winner"])->getName();
				}
				if ($score["points_score"]) {
					$response.="<br /><span class='points-text-inverse'>".$score["points_score"]." ".__("puntos","enroporra")."</span> ".__("por resultado","enroporra");
				}
				if ($score["player_goals"]) {
					$goalLabel = ($score["player_goals"]>1) ? __("goles","enroporra") : __("gol","enroporra");
					$response.="<br /><span class='points-text-inverse'>".($score["player_goals"]*$this->getCompetition()->getPoints("score",$fixture->getTournament()))." ".__("puntos","enroporra")."</span> ".__("por","enroporra")." ".$score["player_goals"]." ".$goalLabel." ".__("de","enroporra")." ".$this->getPlayer()->getName();
				}
			}
			$response.="<hr>";
		}
		return $response;
	}

	public function getUrl() {
		return get_permalink($this->getId());
	}
}