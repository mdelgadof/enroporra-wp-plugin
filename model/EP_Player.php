<?php

class EP_Player {

	/**
	 * @var WP_Post
	 */
	protected $post;
	/**
	 * @var EP_Team
	 */
	protected $team;
	/**
	 * @var EP_Competition[]
	 */
	protected $competitions;

	/**
	 * @param int $post_id "player" custom post type id.
	 *
	 * @throws Exception
	 */
	public function __construct( int $post_id=0) {
		if ($post_id<=0) throw new Exception(sprintf('%s is not a valid ID creating a Player at EP_Player',$post_id),-1);
		if (is_null($this->post = get_post($post_id))) throw new Exception(sprintf('Post %s does not exist creating a Player at EP_Player',$post_id),-1);
		if (get_post_type($post_id)!="player") throw new Exception(sprintf('Wrong post id %s creating a Player at EP_Player. Post is not a player',$post_id),-1);
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
	public function getName() {
		return get_the_title($this->getId());
	}

	/**
	 * @return EP_Team
	 * @throws Exception
	 */
	public function getTeam() {
		if ($this->team) return $this->team;
		return $this->team = new EP_Team(intval(get_post_meta($this->getId(),"team",$single=true)));
	}

	/**
	 * @return int
	 */
	public function getAPIid() {
		return (int) get_post_meta($this->getId(),"player_api_id",$single=true);
	}

	public function getSurname() {
		return get_post_meta($this->getId(),"surname",true);
	}

	/**
	 * @param int $api_id
	 *
	 * @return bool|int
	 */
	public function setAPIid(int $api_id) {
		if ($api_id>0) {
			update_post_meta( $this->getId(), "player_api_id", $api_id );
			return true;
		}
		else return false;
	}

	public function getPost() {
		return $this->post;
	}

	/**
	 * @param string $file
	 *
	 * @return bool|int
	 * @throws Exception
	 */
	public function setImage( string $file ){

		require_once ABSPATH."wp-admin/includes/file.php";
		require_once ABSPATH."wp-admin/includes/media.php";
		require_once ABSPATH."wp-admin/includes/image.php";

		// Set variables for storage, fix file filename for query strings.
		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
		if ( ! $matches ) throw new Exception('Invalid filename at EP_Player::setImage',-1);

		$file_array = array();
		$file_array['name'] = basename( $matches[0] );

		// Download file to temp location.
		$file_array['tmp_name'] = download_url( $file );

		// If error storing temporarily, return the error.
		if ( is_wp_error( $file_array['tmp_name'] ) ) throw new Exception('Couldn\'t download url at EP_Player::setImage',-1);

		// Do the validation and storage stuff.
		$id = media_handle_sideload( $file_array, $this->getId() );

		// If error storing permanently, unlink.
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			throw new Exception('Couldn\'t store image locally at EP_Player::setImage',-1);
		}
		return set_post_thumbnail( $this->getId(), $id );

	}

	/**
	 * @param int $width
	 *
	 * @return string
	 */
	public function getPhotoHTML(int $width=150) : string {
		if (!is_integer($width)) return '';
		if ($url=get_the_post_thumbnail_url($this->getId())) {
			return "<img src='".$url."' style='width:".$width."px;' />";
		}
		else return EP_Player::getUnknownPhotoHTML($width);
	}

	public function getImage() {
		return get_the_post_thumbnail_url($this->getId());
	}

	public function getImageHTML(int $width) {
		return "<img src='".$this->getImage()."' width='".$width."' />";
	}

	/**
	 * @return EP_Competition[]
	 * @throws Exception
	 */
	public function getCompetitions() {
		if (is_array($this->competitions)) return $this->competitions;
		$serialized_array = get_post_meta($this->getId(),'competitions',$single=true);
		$response=array();
		if ($serialized_array) {
			$competitions_id = unserialize($serialized_array);
			foreach ($competitions_id as $competition_id) $response[] = new EP_Competition($competition_id);
		}
		return $this->competitions = $response;
	}

	/**
	 * @param EP_Competition $competition
	 *
	 * @return bool|int
	 */
	public function setCompetition(EP_Competition $competition) {
		if (in_array($competition,$this->getCompetitions())) return false;
		$this->competitions[] = $competition;
		$competitions_id = array();
		foreach ($this->getCompetitions() as $competition) {
			$competitions_id[] = $competition->getId();
		}
		return update_post_meta($this->getId(),'competitions',serialize($competitions_id));
	}

	/**
	 * @param EP_Competition $competition
	 *
	 * @return bool
	 */
	public function isMyCompetition(EP_Competition $competition) {
		foreach ($competitions = $this->getCompetitions() as $competition_single) {
			if ($competition_single->getId() == $competition->getId()) return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function isBetScorer() : bool {
		return (bool) get_post_meta($this->getId(),'bet_scorer',true);
	}

	public function setBetScorer(bool $betScorer) {
		return update_post_meta($this->getId(),'bet_scorer',$betScorer);
	}

	/**
	 * @param mixed $player
	 *
	 * @return bool
	 */
	public static function isPlayer($player) : bool {
		return (is_object($player) && get_class($player)=="EP_Player");
	}

	/**
	 * @param EP_Player $playerToFind
	 * @param EP_Player[] $players
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function inArrayPlayers(EP_Player $playerToFind,array $players) : bool {
		foreach ($players as $player) {
			if (!self::isPlayer($player)) throw new Exception('Element is not a EP_Player on EP_Player::inArrayPlayers');
			if ($player->getId()==$playerToFind->getId()) return true;
		}
		return false;
	}

	/**
	 * @param int $api_id
	 *
	 * @return EP_Player|null
	 * @throws Exception
	 */
	public static function getPlayerByAPIid(int $api_id) : ?EP_Player {
		$posts = get_posts(array(
			'post_type' => 'player',
			'fields' => 'ids',
			'meta_key' => 'player_api_id',
			'meta_value' => (string)$api_id
		));
		if (isset($posts[0]->ID)) return new EP_Player($posts[0]->ID);
		else return null;
	}

	/**
	 * @return EP_Player[]
	 * @throws Exception
	 */
	public static function getAllPlayers() : array {
		$response = array();
		$posts = get_posts(array(
			'post_type' => 'player',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'orderby' => 'post_title',
			'order' => 'ASC'
		));
		foreach ($posts as $post) {
			$response[] = new EP_Player($post->ID);
		}
		return $response;
	}

	/**
	 * @param string $name
	 * @param EP_Team $team
	 *
	 * @return EP_Player
	 * @throws Exception
	 */
	public static function createPlayerFromAPI( string $name, EP_Team $team) {
		$api = new API_Football();
		if (!EP_Team::isTeam($team)) throw new Exception('Invalid parameter $team is not a EP_Team at EP_Player::createPlayerFromAPI');
		if ($name=="") throw new Exception('Invalid parameter $name empty string at EP_Player::createPlayerFromAPI');
		$players_api = $team->getPlayersFromAPI();
		$max_response = 0;
		$chosen_player = null;
		foreach ($players_api as $player_api) {
			$response = 0;
			similar_text(mb_strtolower($player_api->name),mb_strtolower($name),$response);
			if ($response>$max_response) {
				$chosen_player = $player_api;
				$max_response = $response;
			}
		}
		$player_api = $api->getPlayerById($chosen_player->id,date("Y"));
		return EP_Player::createPlayer(
			array(
				"name"=>$player_api->firstname,
				"surname"=>$player_api->lastname,
				"team"=>$team,
				"api_id"=>$player_api->id,
				"image"=>$player_api->photo
			)
		);
	}

	/**
	 * @param array $args "team"* => EP_Team, "name"* => string, "surname"* => string, "image" => external url, "api_id" => int
	 *
	 * @return EP_Player
	 * @throws Exception
	 */
	public static function createPlayer($args): EP_Player {
		if (is_array($args)) {
			// Mandatory fields to set a player on Enroporra
			if (!isset($args["team"])||!isset($args["name"])||!isset($args["surname"]))
				throw new Exception( 'Malformed array of args creating a player at EP_player::createPlayer, some required field/s is/are missing.',-1);
			if (!EP_Team::isTeam($args["team"]))
				throw new Exception( 'Argument "team" is not a valid EP_Team object.',-1);
			if (get_posts( array(
				'post_type' => 'player',
				'post_status' => 'publish',
				'posts_per_page' => 1,
				'meta_query' => array(
					array(
						'key'     => 'name',
						'value'   => $args["name"],
					),
					array(
						'key' => 'surname',
						'value'   => $args["surname"],
					),
					array(
						'key' => 'team',
						'value' => $args["team"]->getId()
					)
				),
			) ) ) {
				throw new Exception(sprintf('Player %s %s already exists creating new EP_player::createPlayer.',$args["name"],$args["surname"]),-1);
			}

			$new_player_id = wp_insert_post(array('post_title'=>$args["name"]." ".$args["surname"],'post_type'=>'player','post_status'=>'publish'));
			if (is_wp_error($new_player_id))
				throw new Exception(sprintf('Could not create wordpress post at EP_player::createPlayer: %s.',$new_player_id->get_error_message()),-1);
			try {
				$player = new EP_Player($new_player_id);
			}
			catch (Exception $e) {
				wp_delete_post($new_player_id,$force_delete=true);
				throw new Exception(sprintf('Could not create EP_Player at EP_Player::createPlayer: %s',$e->getMessage()),-1);
			}

			if (
				!add_post_meta($new_player_id, "name", $args["name"], true) ||
				!add_post_meta($new_player_id, "surname", $args["surname"], true) ||
				!add_post_meta($new_player_id, "team", $args["team"]->getId(), true)
			) {
				wp_delete_post($new_player_id,true);
				throw new Exception( 'Name, surname or team could not be filled creating a player at EP_player::createPlayer.',-1);
			}
			if ($args["image"]) $player->setImage($args["image"]);
			if (isset($args["api_id"]) && intval($args["api_id"])==$args["api_id"]) $player->setAPIid($args["api_id"]);
		}
		else throw new Exception( 'Bad param creating a player at EP_Player::createPlayer, must be an array.',-1);
		return $player;
	}

	public static function getUnknownPhotoHTML(int $width=150) {
		if (!is_integer($width)) return;
		return "<img src='".plugin_dir_url( __DIR__ )."images/unknown-player.png' style='width:".$width."px;' />";
	}


}