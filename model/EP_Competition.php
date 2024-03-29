<?php

class EP_Competition {

    public static $tournament = array(
        "groups"=>"Fase de grupos",
        "last16"=>"Octavos de final",
        "last8"=>"Cuartos de final",
        "last4"=>"Semifinal",
        "third"=>"Tercer y cuarto puesto",
        "final"=>"Final"
    );

	public static $teams_per_group = 4;
	public static $amount = 10;

	const BEFORE_KICK_OFF = 1;
	const GROUP_STAGE_PLAYING = 2;
	const BEFORE_PLAYOFF = 3;
	const PLAYOFF_PLAYING = 4;
	const AFTER_FINAL_GAME = 5;

    /**
     * @var WP_Post
     */
    protected $post;

	protected $fixtures;

	protected $teams;

	protected $players;

	protected $bets;

	protected $top_scorers;

	protected $stage;

	protected ?EP_Referee $referee = null;

	/**
	 * @var int
	 */
	protected $closedBetsTime = 30*60; // Bets are closed 30 minutes before the first match starts.

    /**
     * @param int $id "competition" custom post type id.
     * @throws Exception
     */
    public function __construct($id=0) {
        if (intval($id)<=0) throw new Exception(sprintf('%s is not a valid ID creating a Competition at EP_Competition',$id),-1);
        if (is_null($this->post = get_post($id))) throw new Exception(sprintf('Post does not exist creating a Competition at EP_Competition'),-1);
        if (get_post_type($id)!="competition") throw new Exception(sprintf('Wrong post id %s creating a Competition at EP_Competition. Post is not a competition',$id),-1);
    }

	public function __toString() : string {
		return (string) $this->getId()." ".$this->getName();
	}

    /**
     * @return int
     */
    public function getId() {
        return $this->post->ID;
    }

    /**
     * @return string
     */
    public function getRulesUrl(): string {
        $post = get_post(get_post_meta($this->getId(),'rules',true));
		return $post->guid;
    }

	public function getReferee() : ?EP_Referee {
		if ($this->referee) return $this->referee;
		try {
			return $this->referee = new EP_Referee(get_post_meta($this->getId(),'referee',true));
		}
		catch (Throwable $e) {
			return null;
		}
	}

	/**
	 * @return EP_Player[]
	 * @throws Exception
	 */
	public function getTopScorers() : array {
		$tops_scorers = get_post_meta($this->getId(),'top_scorers',true);
		$players = array();
		for ($i=0; $i<$tops_scorers; $i++ ) {
			$players[] = new EP_Player(get_post_meta($this->getId(),'top_scorers_'.$i."_scorer",true));
		}
		return $players;
	}

    /**
     * @return int
     */
    public function getTeamsNumber():int {
        return get_post_meta($this->getId(),'teams_number',true);
    }

	/**
	 * @throws Exception
	 */
	public function getLastGroupFixture():EP_Fixture {
		$lastGroupFixtureId = get_post_meta($this->getId(),'last_group_fixture',true);
		if (!$lastGroupFixtureId) {
			$fixtures = $this->getGroupFixtures();
			$lastGroupFixtureId = 0;
			foreach ($fixtures as $fixture) {
				if ($fixture->getFixtureNumber()>$lastGroupFixtureId) $lastGroupFixtureId=$fixture->getFixtureNumber();
			}
			update_post_meta($this->getId(),'last_group_fixture',$lastGroupFixtureId);
		}
		return $this->getFixtureById($lastGroupFixtureId);
	}

	/**
	 * @throws Exception
	 */
	public function getLastFixture():EP_Fixture {
		$lastFixtureId = get_post_meta($this->getId(),'last_fixture',true);
		if (!$lastFixtureId) {
			$fixtures = $this->getFixtures();
			$lastFixtureId = 0;
			foreach ($fixtures as $fixture) {
				if ($fixture->getFixtureNumber()>$lastFixtureId) $lastFixtureId=$fixture->getFixtureNumber();
			}
			update_post_meta($this->getId(),'last_fixture',$lastFixtureId);
		}
		return $this->getFixtureById($lastFixtureId);
	}

	public function getPaymentData():array {
		$response = array();
		$response['owner'] = get_post_meta($this->getId(),'payment_owner',true);
		$response['bank'] = get_post_meta($this->getId(),'payment_bank',true);
		$response['account'] = get_post_meta($this->getId(),'payment_account',true);
		$response['bizum'] = get_post_meta($this->getId(),'payment_bizum',true);
		$response['amount'] = get_post_meta($this->getId(),'payment_amount',true);
		return $response;
	}


	/**
	 * @return string
	 */
	public function getName():string {
        return get_the_title($this->getId());
    }

    public function getCategoryId(): int {
		$category = (object) get_term_by('name', $this->getName(), 'category');
        return $category->ID;
    }

	public function getPoints($type,$tournament="") : float {
		$label = ($tournament) ? "points_".$type."_".$tournament : "points_".$type;
		return (float) get_post_meta($this->getId(),$label,true);
	}

    /**
     * Returns all the fixtures of the current competition as array of EP_Fixture objects. If an array with key = "tournament" and value one of the tournaments, it will return this kind of fixtures.
     *
     * @param array $args
     *
     * @return EP_Fixture[]
     * @throws Exception
     */
    public function getFixtures( array $args=array()): array {
		if ($this->fixtures && empty($args)) return $this->fixtures;
		$meta_query = array('relation'=>'AND',array('key'=>'competition','value'=>$this->getId()) );
		foreach ($args as $key => $value) {
			$meta_query[]=array('key'=>$key,'value'=>$value);
		}
	    try {
			$fixtures = query_posts(
				array(
					'post_type'=>'fixture',
					'posts_per_page'=>-1,
					'meta_query' => $meta_query
				)
			);
	        $response = array();
			foreach ($fixtures as $fixture) {
				$response[] = new EP_Fixture($fixture->ID);
			}
			usort($response,'cmp_fixtures');
		}
        catch (Exception $e) { throw new Exception($e->getMessage().' at EP_Competition::getFixtures',-1); }
	    if (empty($args)) $this->fixtures = $response;
        return $response;
    }

	public function getTableBet($paid=true) {
		function cmp_bet(EP_Bet $a, EP_Bet $b) {
			if ($a->getPoints()>$b->getPoints()) return false;
			if ($a->getPoints()<$b->getPoints()) return true;
			$name1 = str_replace(array("á","é","í","ó","ú"),array("a","e","i","o","u"),mb_strtolower($a->getName()));
			$name2 = str_replace(array("á","é","í","ó","ú"),array("a","e","i","o","u"),mb_strtolower($b->getName()));
			return ($name1>$name2);
		}
		$bets = $this->getBets($paid);
		usort($bets,"cmp_bet");
		$response = array();
		$position = 1;
		foreach ($bets as $key => $bet) {
			$response[$key]["bet"]=$bet;
			$response[$key]["points"]=$bet->getPoints();
			if ($key!=0 && $response[$key-1]["points"]>$response[$key]["points"]) $position = $key+1;
			$response[$key]["position"]=$position;
			$response[$key]["paid"]=$bet->isPaid();
		}
		return $response;
	}

	/**
	 * @return EP_Fixture[]
	 * @throws Exception
	 */
	public function getGroupFixtures() : array {
		return $this->getFixtures(array('tournament'=>'groups'));
	}

	/**
	 * @param $group string name of group, mainly a capital letter between A and H
	 *
	 * @return EP_Fixture[] fixtures of the group
	 * @throws Exception
	 */
	public function getFixturesByGroup($group) {
		if (!in_array($group."1",$this->getTeamLabels())) throw new Exception(sprintf('Wrong team group %s at EP_Competition::getFixturesByGroup.',$group),-1);
		$response = array();
		foreach ($this->getFixtures() as $fixture) {
			if ($fixture->getGroup()==$group) $response[]=$fixture;
		}
		return $response;
	}

	public function getNextFixtures($number = 3, $returnMatchForThird=false) : array {
		$response = array();
		$posts = get_posts(array(
			'post_type'=>'fixture',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key'     => 'competition',
					'value'   => $this->getId(),
				)
			),
			'meta_key'=>'date',
			'orderby'=>'meta_value',
			'order'=>'ASC'
		));
		$found = 0;
		foreach ($posts as $post) {
			$fixture = new EP_Fixture($post->ID);
			if ($fixture->isFuture()) {
				if (!$returnMatchForThird && $fixture->getTournament()=="third") continue;
				$found++;
				$response[]=$fixture;
				if ($found==$number) return $response;
			}
		}
		return $response;
	}

	public function getLastFixtures($number = 3) : array {
		$response = array();
		foreach ($this->getFixtures() as $fixture) {
			if (!$fixture->isPlayed()) continue;
			$response[]=$fixture;
		}
		function cmp_getLastFixtures(EP_Fixture $a,EP_Fixture $b) {
			return $a->getRawDate()<$b->getRawDate();
		}
		usort($response,"cmp_getLastFixtures");
		return array_slice($response,0,$number);
	}

		/**
	 * @throws Exception
	 */
	public function getFixtureById($id): ?EP_Fixture {
		$fixtures = $this->getFixtures();
		if (intval($id)!=$id || $id<1 || $id>count($fixtures)) throw new Exception(sprintf('Wrong fixture ID %s at EP_Competition::getFixtureById.',$id),-1);
		foreach ($fixtures as $fixture) {
			if ($fixture->getFixtureNumber()==$id) return $fixture;
		}
		return null;
	}

	/**
	 * @throws Exception
	 */
	public function getStage() : int {
		if ($_SERVER["HTTP_HOST"]=="2.enroporra.test") return self::PLAYOFF_PLAYING;
		if ($this->stage) return $this->stage;
		if (date("Y-m-d H:i:s",time()+3600+$this->closedBetsTime)<$this->getFixtureById(1)->getRawDate()) return $this->stage = self::BEFORE_KICK_OFF;
		foreach ($this->getFixtures(array("tournament"=>"groups")) as $fixture) if (!$fixture->isPlayed()) return $this->stage = self::GROUP_STAGE_PLAYING;
		if (date("Y-m-d H:i:s",time()+3600+$this->closedBetsTime)<$this->getFixtureById($this->getLastGroupFixture()->getFixtureNumber()+1)->getRawDate()) return $this->stage = self::BEFORE_PLAYOFF;
		if (!$this->getLastFixture()->isPlayed()) return $this->stage = self::PLAYOFF_PLAYING;
		return $this->stage = self::AFTER_FINAL_GAME;
	}

	public function getTable($group,$just_with_these_teams=array()) {
		if (!in_array($group."1",$this->getTeamLabels())) throw new Exception(sprintf('Wrong team group %s at EP_Competition::getTable.',$group),-1);
		if (!is_array($just_with_these_teams)) throw new Exception(sprintf('Wrong type param $just_with_these_teams at EP_Competition::getTable.'),-1);
		for ($i=EP_Competition::$teams_per_group; $i>=1; $i--) {
			if (!empty($just_with_these_teams) && !in_array($group.$i,$just_with_these_teams)) continue;
			$table[$group.$i]=array();
			$table[$group.$i]["played"]=$table[$group.$i]["points"]=$table[$group.$i]["won"]=$table[$group.$i]["drawn"]=$table[$group.$i]["lost"]=$table[$group.$i]["goals_for"]=$table[$group.$i]["goals_against"]=0;
			$table[$group.$i]["team"]=$this->getTeamByLabel($group.$i);
			$table[$group.$i]["label"]=$group.$i;
		}
		foreach ($this->getFixturesByGroup($group) as $fixture) {
			if (!EP_Team::isTeam($fixture->getTeam(1)) || !EP_Team::isTeam($fixture->getTeam(2)) || !$fixture->isPlayed()) continue;
			if (!empty($just_with_these_teams) && (!in_array($fixture->getLabelTeam(1),$just_with_these_teams)||!in_array($fixture->getLabelTeam(2),$just_with_these_teams))) continue;
			$table[$fixture->getLabelTeam(1)]["played"]++;
			$table[$fixture->getLabelTeam(2)]["played"]++;
			if ($fixture->getGoals(1)>$fixture->getGoals(2)) {
				$table[$fixture->getLabelTeam(1)]["points"]+=3;
				$table[$fixture->getLabelTeam(1)]["won"]++;
				$table[$fixture->getLabelTeam(2)]["lost"]++;
			}
			else if ($fixture->getGoals(2)>$fixture->getGoals(1)) {
				$table[$fixture->getLabelTeam(2)]["points"]+=3;
				$table[$fixture->getLabelTeam(2)]["won"]++;
				$table[$fixture->getLabelTeam(1)]["lost"]++;
			}
			else {
				$table[$fixture->getLabelTeam(2)]["points"]+=1;
				$table[$fixture->getLabelTeam(1)]["points"]+=1;
				$table[$fixture->getLabelTeam(2)]["drawn"]++;
				$table[$fixture->getLabelTeam(1)]["drawn"]++;
			}
			$table[$fixture->getLabelTeam(1)]["goals_for"]+=$fixture->getGoals(1);
			$table[$fixture->getLabelTeam(1)]["goals_against"]+=$fixture->getGoals(2);
			$table[$fixture->getLabelTeam(2)]["goals_for"]+=$fixture->getGoals(1);
			$table[$fixture->getLabelTeam(2)]["goals_against"]+=$fixture->getGoals(2);
		}
		return $this->orderTable($table,$more_recursive = empty($just_with_these_teams));
	}

	public function getFirstDate() {
		return $this->getFixtureById(1)->getDate();
	}

	public function getSecondsToStart() {
		$today = time()+60*60; // Spain timezone at WorldCup: GMT+1
		$firstFixture = strtotime(str_replace("/","-",$this->getFirstDate()));
		if ($today>=$firstFixture) return 0;
		return $firstFixture-$today-$this->closedBetsTime;
	}

	public function getTimeToStart() {
		$seconds = $this->getSecondsToStart();
		if (!$seconds) return false;
		$days = floor($seconds/86400);
		$seconds-=$days*86400;
		$hours = floor($seconds/3600);
		$seconds-=$hours*3600;
		$minutes = floor($seconds/60);
		$seconds-=$minutes*60;
		if ($hours<10) $hours="0".$hours; if ($minutes<10) $minutes="0".$minutes; if ($seconds<10) $seconds="0".$seconds;
		return (object) array('days'=>$days,'hours'=>$hours,'minutes'=>$minutes,'seconds'=>$seconds);
	}

	public function getClassifiedTeams($table) {
		$max_fixtures_played = EP_Competition::$teams_per_group-1;
		$completed = true;
		$same_daygame = true;
		$daygame = -1;
		foreach ($table as $teamrow) {
			if (!isset($teamrow["played"]) || !is_integer($teamrow["played"])) throw new Exception('Malformed table at EP_Competition::classifiedTeams',-1);
			if ($teamrow["played"]!=$daygame) {
				if ($daygame!=-1) $same_daygame = false;
				$daygame = $teamrow["played"];
			}
			if ($teamrow["played"]<$max_fixtures_played) {
				$completed = false;
			}
		}
		if ($completed) return array($table[0]["label"],$table[1]["label"]);
		else if ($same_daygame) {
			if ($table[0]["points"]>$table[1]["points"]+(3*($max_fixtures_played-$table[1]["played"]))) return array($table[0]["label"]);
		}
		else return array();
	}

	private function orderTable($table, $more_recursive=true) {
		global $teams_for_next_step;
		$teams_for_next_step = array();
		usort($table,"cmp_table_step1");
		if (!$more_recursive) return $table;
		if (!empty($teams_for_next_step)) {
			$teams_for_step2 = array_unique($teams_for_next_step);
			$table2=$this->getTable(substr($teams_for_step2[0],0,1),$teams_for_step2);
			$tableReordered = $reorderedTeams = array();
			for ($i=0; $i<count($table); $i++) {
				$row = $table[$i];
				if (!in_array($row["label"],$teams_for_step2)) {
					$tableReordered[] = $row;
					continue;
				}
				if (in_array($row["label"],$reorderedTeams)) {
					continue;
				}
				$team2 = array_shift($table2);
				if ($row["label"]!=$team2["label"]) {
					foreach ($table as $row2) {
						if ($row2["label"]==$team2["label"]) {
							$tableReordered[] = $row2;
							$reorderedTeams[] = $row2["label"];
							break;
						}
					}
					$i--;
				}
				else {
					$tableReordered[] = $row;
					continue;
				}
			}
			$table = $tableReordered;
		}
		return $table;
	}

    public function setCompetitionFixtures() {
        if (!file_exists($json_filename = ENROPORRA_PATH."data/".$this->getTeamsNumber().".json"))
            throw new Exception('Configuration file not found at EP_Competition::setCompetitionFixtures',-1);
        if (is_null($competition_data = json_decode(file_get_contents($json_filename),$associative_array=true)))
            throw new Exception('Malformed configuration file at EP_Competition::setCompetitionFixtures',-1);
        $fixtures = $competition_data["fixtures"];
        foreach ($fixtures as $fixture_array) {
            $fixture_array["competition"]=$this;
            try {
                $fixture = EP_Fixture::createFixture($fixture_array);
            }
            catch (Exception $e) {
                throw new Exception($e->getMessage()." at EP_Competition::setCompetitionFixtures",-1);
            }
            if ($fixture->getTournament()=='groups') {
                try {
                    for ($i=1; $i<=2; $i++) {
                        $fixture->setTeam($i,$this->getTeamByLabel($fixture->getLabelTeam($i)));
                    }
                }
                catch (Exception $e) {
                    throw new Exception($e->getMessage()." at EP_Competition::setCompetitionFixtures",-1);
                }
            }
        }
    }

	public function setCompetitionPoints() {
		if (file_exists($settings = ENROPORRA_PATH."data/".$this->getTeamsNumber().".json")) {
			$settings = json_decode(file_get_contents($settings),true);
			foreach ($settings["points"] as $tournament => $types) {
				foreach ($types as $type => $points)
					add_post_meta($this->getId(),"points_".$type."_".$tournament, $points,true);
			}
		}
	}

	/**
	 * Delete all the Wordpress posts with custom post type 'fixture' of the current competition. Function for testing, take care.
	 *
	 * @throws Exception
	 */
	public function deleteCompetitionFixtures() {

        try {
	        $fixtures = $this->getFixtures();
	        foreach ( $fixtures as $fixture ) {
		        wp_delete_post( $fixture->getId(), $force_delete = true );
	        }
        }
		catch (Exception $e) { throw new Exception($e->getMessage().' at EP_Competition::deleteCompetitionFixtures',-1); }
    }

	public function getTeamLabels() {
		$letters = array("A","B","C","D","E","F");
		$response = array();
		if ($this->getTeamsNumber()==32) {
			$letters[]="G"; $letters[]="H";
		}
		foreach ($letters as $letter) {
			for ( $i = 1; $i <= EP_Competition::$teams_per_group; $i ++ ) {
				$response[]=$letter.$i;
			}
		}
		return $response;
	}

	public function getTeamByLabel($label): ?EP_Team {
		if (!in_array($label,$this->getTeamLabels())) throw new Exception(sprintf('Wrong team label %s at EP_Competition::getTeamByLabel.',$label),-1);
		try {
			if (get_post_meta($this->getId(),mb_strtolower($label),true)) return new EP_Team(get_post_meta($this->getId(),mb_strtolower($label),true));
			else return null;
		}
		catch (Exception $e) { throw new Exception( sprintf("Can't get Team %s at EP_Competition::getTeamByLabel: %s",$label,$e->getMessage()) ); }
	}

	public function getTeams() {
		if (is_array($this->teams) && !empty($this->teams)) return $this->teams;
		$response = array();
		foreach ($this->getTeamLabels() as $label) if ($team=$this->getTeamByLabel($label)) $response[]=$team;
		return $this->teams=$response;
	}

	/**
	 * @return EP_Player[]
	 */
	public function getPlayers(): array {
		if (is_array($this->players) && !empty($this->players)) return $this->players;
		$response = array();
		foreach (EP_Player::getAllPlayers() as $player) if ($player->isMyCompetition($this)) $response[]=$player;
		return $this->players=$response;
	}

	/**
	 * @return EP_Player[]
	 */
	public function getBetScorers(): array {
		$players = $this->getPlayers();
		$response = array();
		foreach ($players as $player) {
			if ($player->isBetScorer()) $response[]=$player;
		}
		return $response;
	}

	/**
	 * @return EP_Bet[]
	 * @throws Exception
	 */
	public function getBets($paid=true) : array {
		if (is_array($this->bets) && !empty($this->bets)) return $this->bets;
		$response = array();
		$posts = get_posts(array(
			'post_type' => 'bet',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'meta_query' => array(
				// meta query takes an array of arrays, watch out for this!
				array(
					'key'     => 'competition',
					'value'   => $this->getId(),
				)
			)
		));
		foreach ($posts as $post) {
			try {
				$bet = new EP_Bet($post->ID);
				if ($paid && !$bet->isPaid()) continue;
				$response[] = $bet;
			}
			catch (Exception $e) { throw new Exception(sprintf('%s at EP_Competition::getBets',$e->getMessage()),-1); }
		}
		return $this->bets = $response;
	}

	public function setCurrentCompetition(): bool {
		return update_option('current_competition',$this->getId());
	}

	public function isCurrentCompetition(): bool {
		return (get_option('current_competition')==$this->getId());
	}

	public function updateTeamsOnFixtures($tournament='') {
		$fixtures = $this->getFixtures();
		foreach ($fixtures as $fixture) {
			if ($tournament!='' && $tournament!=$fixture->getTournament()) continue;
			$label1 = $fixture->getLabelTeam(1);
			$label2 = $fixture->getLabelTeam(2);
			if ($fixture->getTournament()=="groups") {
				if ( $team1 = $this->getTeamByLabel( $label1 ) ) {
					$fixture->setTeam( 1, $team1 );
				}
				else $fixture->deleteTeam(1);
				if ( $team2 = $this->getTeamByLabel( $label2 ) ) {
					$fixture->setTeam( 2, $team2 );
				}
				else $fixture->deleteTeam(2);
			}
			else if ($fixture->getTournament()=="last16") {
				$pos1 = substr($label1,0,1);
				$pos2 = substr($label2,0,1);
				$group1 = substr($label1,1,1);
				$group2 = substr($label2,1,1);
				$classified1 = $this->getClassifiedTeams($this->getTable($group1));
				$classified2 = $this->getClassifiedTeams($this->getTable($group2));
				if (isset($classified1[$pos1-1])) $fixture->setTeam(1,$this->getTeamByLabel($classified1[$pos1-1]));
				if (isset($classified2[$pos2-1])) $fixture->setTeam(2,$this->getTeamByLabel($classified2[$pos2-1]));
			}
			else if ($fixture->getTournament()=="third") {
				$previousFixture1 = $this->getFixtureById(substr($label1,2));
				$previousFixture2 = $this->getFixtureById(substr($label2,2));
				if ($previousFixture1->getLoser()=="1") $fixture->setTeam(1,$previousFixture1->getTeam(1));
				else if ($previousFixture1->getLoser()=="2") $fixture->setTeam(1,$previousFixture1->getTeam(2));
				if ($previousFixture2->getLoser()=="1") $fixture->setTeam(2,$previousFixture2->getTeam(1));
				else if ($previousFixture2->getLoser()=="2") $fixture->setTeam(2,$previousFixture2->getTeam(2));
			}
			else {
				$previousFixture1 = $this->getFixtureById(substr($label1,1));
				$previousFixture2 = $this->getFixtureById(substr($label2,1));
				if ($previousFixture1->getWinner()=="1") $fixture->setTeam(1,$previousFixture1->getTeam(1));
				else if ($previousFixture1->getWinner()=="2") $fixture->setTeam(1,$previousFixture1->getTeam(2));
				if ($previousFixture2->getWinner()=="1") $fixture->setTeam(2,$previousFixture2->getTeam(1));
				else if ($previousFixture2->getWinner()=="2") $fixture->setTeam(2,$previousFixture2->getTeam(2));
			}
		}
	}

	public function getBetNumber() {
		$bet_number = get_post_meta($this->getId(),'current_bet_number',true);
		if (!$bet_number) $bet_number = 1;
		update_post_meta($this->getId(),'current_bet_number',($bet_number+1));
		return $bet_number;
	}

	public function getOfficialSite() {
		return get_post_meta($this->getId(),'official_website',true);
	}

	public function getMatchCalendarSite() {
		return get_post_meta($this->getId(),'match_calendar',true);
	}

	/**
	 * @return EP_Competition[]
	 * @throws Exception
	 */
	public static function getAllCompetitions(): array {
		$posts = get_posts(array(
			'post_type'=>'competition',
			'posts_per_page'=>-1,
			'post_status'=>'publish'
		));
		$response = array();
		foreach ($posts as $post) {
			$response[] = new EP_Competition($post->ID);
		}
		return $response;
	}

	public static function getCurrentCompetition(): EP_Competition {
		try {
			$competition = new EP_Competition( get_option( 'current_competition' ) );
		}
		catch (Exception $e) {
			return false;
		}
		return $competition;
	}

	public static function deleteCurrentCompetition() {
		return update_option('current_competition','');
	}

	public function getTopScorersSite() : string {
		return get_post_meta($this->getId(),'top_scorers_site',true);
	}

	public function getEmail() : string {
		return get_post_meta($this->getId(),'email',true);
	}

	public function beforeStart() : bool {
		return ($this->getStage()==self::BEFORE_KICK_OFF);
	}

	public function getTwitter() : string {
		return get_post_meta($this->getId(),'twitter',true);
	}

	public function betRepeated(string $email, array $scores) : ?EP_Bet {
		$bets = $this->getBets($paid=false);
		foreach ($bets as $bet) {
			if ($bet->getEmail()==$email && unserialize($bet->getScoresRaw())==$scores) {
				return $bet;
			}
		}
		return null;
	}

	public function getGoalsByPlayer(EP_Player $player) : int {
		$totalGoals = 0;
		$fixtures = $this->getFixtures();
		foreach ($fixtures as $fixture) {
			if ($fixture->isPlayed()) {
				$goals = $fixture->getScorers();
				foreach ($goals as $goal) {
					if ($goal["player"]->getId()==$player->getId()) $totalGoals++;
				}
			}
		}
		return $totalGoals;
	}

}