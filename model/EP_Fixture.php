<?php

class EP_Fixture {

    /**
     * @var WP_Post
     */
    protected $post;

	protected $team1,$team2;

	protected $players;

	protected $competition;

    /**
     * @param int|array $args "fixture" custom post type id or set of fields to create a new fixture.
     * @throws Exception
     */
    public function __construct(int $fixture_id = 0) {
        if ($fixture_id < 0) throw new Exception(sprintf('%s is not a valid ID getting a fixture at EP_Fixture.', $fixture_id), -1);
        else if (is_null($this->post = get_post($fixture_id))) throw new Exception(sprintf('Post does not exist getting a fixture at EP_Fixture.'),-1);
        else if (get_post_type($fixture_id) != "fixture") throw new Exception(sprintf('Wrong post id %s getting a fixture at EP_Fixture. Post is not a fixture.', $fixture_id), -1);
    }

    public function getId() {
        return $this->post->ID;
    }

	public function getLabelTeam($order) {
		if (!in_array($order,array(1,2))) throw new Exception('Bad argument order at EP_Fixture::getLabelTeam',-1);
		return get_post_meta($this->getId(),"label_team".$order,true);
	}

	public function getGroup() {
		return get_post_meta($this->getId(),'group',true);
	}

	public function getTeam(int $order) {
		if (!in_array($order,array(1,2))) throw new Exception('Bad argument order at EP_Fixture::getTeam',-1);
		if ($this->{"team".$order}) return $this->{"team".$order};
		if ($team_id = get_post_meta($this->getId(),'name_team'.$order,true)) {
			try {
				return $this->{"team" . $order} = new EP_Team( $team_id );
			} catch ( Exception $e ) {
				throw new Exception( sprintf( '%s at EP_Fixture::getTeam', $e->getMessage() ), - 1 );
			}
		}
		else { return new EP_Team(0); }
	}

	public function getTournament() {
		return get_post_meta($this->getId(),'tournament',true);
	}

	public function getTournamentLabel() {
		$labelTournaments = array(
			'groups' => __('Fase de grupos','enroporra'),
			'last16' => __('Octavos de final','enroporra'),
			'last8' => __('Cuartos de final','enroporra'),
			'last4' => __('Semifinales','enroporra'),
			'third' => __('Partido por el tercer puesto','enroporra'),
			'final' => __('Final','enroporra')
		);
		$label = $labelTournaments[$this->getTournament()];
		if ($this->getTournament()=="groups") $label.=' - '.__('Grupo','enroporra').' '.$this->getGroup();
		return $label;
	}

	public function getFixtureNumber() {
		return get_post_meta($this->getId(),"fixture_number",true);
	}

	public function getCompetition() {
		if ($this->competition) return $this->competition;
        if (get_post_meta($this->getId(),"competition",true)) {
            $this->competition = new EP_Competition(get_post_meta($this->getId(), "competition", true));
        }
        else {
            update_post_meta($this->getId(), "competition", EP_Competition::getCurrentCompetition()->getId());
            $this->competition = EP_Competition::getCurrentCompetition();
        }
        return $this->competition;
	}

	public function getGoals($order,$live=false) {
		if (!in_array($order,array(1,2))) throw new Exception('Bad argument order at EP_Fixture::getGoals',-1);
		$labelLive = ($live) ? "live_":"";
		return get_post_meta($this->getId(),$labelLive."goals_team".$order,true);
	}

	public function getLiveMinute() {
		return get_post_meta($this->getId(),"live_minute",true);
	}

	public function setLiveMinute(int $minute) {
		return update_post_meta($this->getId(),'live_minute',$minute);
	}

	public function setGoals($order,$goals,$live=false) {
		if ($goals!="" && (intval($goals)!=$goals || $goals<0)) throw new Exception(sprintf('Invalid number of goals %s at EP_Fixture::setGoals',$goals),-1);
		if (!in_array($order,array(1,2))) throw new Exception('Bad argument order at EP_Fixture::setGoals',-1);
		$labelLive = ($live) ? "live_":"";
		update_field($labelLive."goals_team".$order,$goals,$this->getId());
	}

	public function isFuture() {
		return (!$this->isLive() && !$this->isPlayed());
	}

	public function isLive() {
		if ($this->isPlayed()) return false;
		else if (date("Y-m-d H:i:s")>$this->getRawDate()) return true;
		else return false;
	}

	public function isPlayed() {
		return ($this->getGoals(1)!="" && $this->getGoals(2)!="");
	}

	public function setScorer($args) {
		$prev = "";
		if (isset($args["prev"])) {
			$prev=$args["prev"];
			unset($args["prev"]);
		}
		try {
			$serialized = $this->serializeGoal($args);
		}
		catch (Exception $e) {
			throw new Exception( 'Error at EP_Fixture::setScorer: '.$e->getMessage(),-1);
		}
		return ($prev) ? update_post_meta($this->getId(),'goal', $serialized, $prev) : add_post_meta($this->getId(),'goal',$serialized);
	}

	public function deleteScorer($prev) {
		return delete_post_meta($this->getId(),'goal',$prev);
	}

	public function serializeGoal($args) {
		if (!EP_Player::isPlayer($args["player"]) || !in_array($args["for"],array(1,2)))
			throw new Exception('Malformed arguments from EP_Fixture::serializeGoal: "player" is not a EP_Player object or for is not 1 or 2',-1);
		if (isset($args["type"]) && !in_array($args["type"],array('','p','og')))
			throw new Exception('Malformed argument "type" at EP_Fixture::serializeGoal: Must be empty, "p" or "og"',-1);
		return serialize(array('player'=>$args["player"]->getId(),'team_for'=>$args["for"],'minute'=>$args["minute"],'type'=>$args["type"]));
	}

	public function getScorers(): array {
		$scorers = get_post_meta($this->getId(),"goal");
		$response = array();
		foreach ($scorers as $scorer) {
			$row = unserialize($scorer);
			$row["player"] = new EP_Player($row["player"]);
			$row["team_for"] = $this->getTeam($row["team_for"]);
			$row["prev_value"] = $scorer;
			$response[] = $row;
		}
		usort($response,'cmp_scorers');
		return $response;
	}

	public function getPlayers(): array {
		if (is_array($this->players)) return $this->players;
		$response = array();
		foreach ($this->getCompetition()->getPlayers() as $player) {
			if ($player->getTeam()==$this->getTeam(1) || $player->getTeam()==$this->getTeam(2))
				$response[] = $player;
		}
		return $this->players = $response;
	}

	/**
	 * @throws Exception
	 */
	public function getBetsStatsPost() : array {

		if (!$this->isPlayed()) return array('results'=>0,'winners'=>0);
		$check = $this->getBetsResultOk();

		if ($check) {
			return array(
				'results' => $this->getBetsResultOk(),
				'winners' => $this->getBetsWinnerOk()
			);
		}

		$bets = $this->getCompetition()->getBets();
		$results = $winners = 0;
		foreach ($bets as $bet) {
			$betScores = $bet->getScores();
			$betScore = $betScores[$this->getFixtureNumber()];

			if ( $betScore["winner"]==$this->getWinner() ) {
				if ($this->getTournament()=="groups" || ($this->getWinner()!="X" && $betScore["t".$this->getWinner()]->getId()==$this->getTeam($this->getWinner())->getId()))
					$winners++;
				if ( $betScore["s1"]==$this->getGoals(1) && $betScore["s2"]==$this->getGoals(2) )  {
					if ($this->getTournament()=="groups" || ($this->getWinner()!="X" && $betScore["t".$this->getWinner()]->getId()==$this->getTeam($this->getWinner())->getId()))
						$results++;
				}
			}
		}

		$this->setBetsResultOk($results);
		$this->setBetsWinnerOk($winners);

		return array(
			'results' => $results,
			'winners' => $winners
		);
	}

	/**
	 * @throws Exception
	 */
	public function getBetsStatsPre(): array {

		$check = $this->getBetsStatsScores();

		if (is_array($check) && count($check)) {
			return array(
				'scores' => $this->getBetsStatsScores(),
				'players' => $this->getBetsStatsPlayers(),
				'winners' => $this->getBetsStatsWinners(),
				'total' => $this->getBetsStatsTotal()
			);
		}

		$bets = $this->getCompetition()->getBets();
		$players = $scores = $winners = array();
		$totals = 0;
		foreach ($bets as $bet) {
			$betScores = $bet->getScores();
			$betScore = $betScores[$this->getFixtureNumber()];
			if (!isset($betScore["s1"]) || is_null($betScore["t1"]) || !isset($betScore["s2"]) || is_null($betScore["t2"])) continue;
			$t1id = $betScore["t1"]->getId();
			$t2id = $betScore["t2"]->getId();
			if (!$t1id || !$t2id) continue;

			$result = $t1id."|".$betScore["s1"]."-".$betScore["s2"]."|".$t2id;
			$scores[$result]++;
			$totals++;
			if ($betScore["s1"]>$betScore["s2"]) $winners[$t1id]++;
			else if ($betScore["s1"]<$betScore["s2"]) $winners[$t2id]++;
			else if ($betScore["s1"]==$betScore["s2"]) {
				if ($this->getTournament()=="groups") $winners["X"]++;
				else $winners[($betScore["winner"]==1) ? $t1id : $t2id]++;
			}
			$player_team_id = $bet->getPlayer()->getTeam()->getId();
			if ($player_team_id == $this->getTeam(1)->getId() || $player_team_id == $this->getTeam(2)->getId()) {
				$players[$player_team_id]++;
			}
		}
		arsort($scores);
		arsort($players);
		$this->setBetsStatsScores($scores);
		$this->setBetsStatsPlayers($players);
		$this->setBetsStatsWinners($winners);
		$this->setBetsStatsTotal($totals);
		return array ('scores'=>$scores,'players'=>$players,'winners'=>$winners,'total'=>$totals);
	}

	public function getBetsStatsScores() {
		return get_post_meta($this->getId(),'scores',true);
	}

	public function getBetsStatsPlayers() {
		return get_post_meta($this->getId(),'players',true);
	}

	public function getBetsStatsWinners() {
		return get_post_meta($this->getId(),'winners',true);
	}

	public function getBetsStatsTotal() {
		return get_post_meta($this->getId(),'total',true);
	}

	public function getBetsResultOk() {
		return get_post_meta($this->getId(),'result_ok',true);
	}

	public function getBetsWinnerOk() {
		return get_post_meta($this->getId(),'winners',true);
	}

	public function setBetsStatsScores($scores) {
		return update_post_meta($this->getId(),'scores',$scores);
	}

	public function setBetsStatsPlayers($players) {
		return update_post_meta($this->getId(),'players',$players);
	}

	public function setBetsStatsWinners($winners) {
		return update_post_meta($this->getId(),'winners',$winners);
	}

	public function setBetsStatsTotal($total) {
		return update_post_meta($this->getId(),'total',$total);
	}

	public function setBetsResultOk($results) {
		return update_post_meta($this->getId(),'result_ok',$results);
	}

	public function setBetsWinnerOk($winners) {
		return update_post_meta($this->getId(),'winners',$winners);
	}

	public function getDate() {
		return date ("d/m/Y H:i",strtotime(get_post_meta($this->getId(),'date',true)));
	}

    public function setDate($date) {
        return update_post_meta($this->getId(),'date',$date);
    }

	public function getRawDate() {
		return get_post_meta($this->getId(),'date',true);
	}

	public function getWinner() {
		return get_post_meta($this->getId(),'winner',true);
	}

	public function getLoser() {
		$winner = get_post_meta($this->getId(),'winner',true);
		if ($winner=="1") return "2";
		if ($winner=="2") return "1";
		return "X";
	}

	public function setWinner($winner) {
		if ($winner!="" && !in_array($winner,array("1","X","2"))) throw new Exception(sprintf('Malformed value $winner = %s at EP_Fixture::setWinner',$winner),-1);
		return update_post_meta($this->getId(),'winner',$winner);
	}

	public function restoreFixture() {
		$this->setWinner("");
		$this->deleteTeam(1);
		$this->deleteTeam(2);
		$this->setGoals(1,"");
		$this->setGoals(2,"");
	}

	/**
	 * @param $order int
	 * @param $team EP_Team
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function setTeam($order,$team) {
		if (!EP_Team::isTeam($team)) throw new Exception('Argument is not an EP_Team at EP_Fixture::setTeam',-1);
		if (!in_array($order,array(1,2))) throw new Exception('Bad argument order at EP_Fixture::setTeam',-1);
		// ACF's update_field returns false if value does not change either if there was a problem updating the field, so we don't know how to manage a false returned value.
		update_field("name_team".$order,$team->getPost(),$this->getId());
		$this->{"team".$order} = $team;
		return true;
	}

	public function deleteTeam(int $order) {
		if (!in_array($order,array(1,2))) throw new Exception('Bad argument order at EP_Fixture::deleteTeam',-1);
		// ACF's update_field returns false if value does not change either if there was a problem updating the field, so we don't know how to manage a false returned value.
		update_field("name_team".$order,null,$this->getId());
		$this->{"team".$order} = null;
		return true;
	}

	public static function createFixture(array $args) : EP_Fixture {
		// Mandatory fields to set a fixture on Enroporra
		if (!isset($args["competition"])||!isset($args["fixture_number"])||!isset($args["tournament"])||!isset($args["label_team1"])||!isset($args["label_team2"]))
			throw new Exception(sprintf('Malformed array of args creating a fixture at EP_Fixture, some required field/s is/are missing.'),-1);
		if (!is_object($args["competition"]) || get_class($args["competition"])!="EP_Competition")
			throw new Exception(sprintf('Argument "competition" is not a valid EP_Competition object.'),-1);
		$new_fixture_id = wp_insert_post(array('post_title'=>$args["fixture_number"].' - '.EP_Competition::$tournament[$args["tournament"]],'post_type'=>'fixture','post_status'=>'publish'));
		if (is_wp_error($new_fixture_id))
			throw new Exception(sprintf('Could not create wordpress post at EP_Fixture: %s.',$new_fixture_id->get_error_message()),-1);
		foreach ($args as $key => $value) {
			if ($key=="competition") $value=$value->getId();
			if (!update_field($key,$value,$new_fixture_id)) {
				wp_delete_post($new_fixture_id,$force_delete=true);
				throw new Exception(sprintf('Field %s could not be filled with %s value creating a fixture at EP_Fixture.',$key,$value),-1);
			}
		}
		return new EP_Fixture($new_fixture_id);
	}


}