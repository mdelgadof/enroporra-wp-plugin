<?php

class EP_Team {

	/**
	 * @var WP_Post|object
	 */
	protected $post;

	/**
	 * @param int $id "team" custom post type id.
	 * @throws Exception
	 */
	public function __construct(int $team_id = 0) {
		if ($team_id == 0) { $this->post = (object) array('ID' => 0); return; }
		if ($team_id < 0) throw new Exception(sprintf('%s is not a valid ID creating a Team at EP_Team',$team_id),-1);
		if (is_null($this->post = get_post($team_id))) throw new Exception(sprintf('Post does not exist creating a Team at EP_Team'),-1);
		if (get_post_type($team_id)!="team") throw new Exception(sprintf('Wrong post id %s creating a Team at EP_Team. Post is not a team',$team_id),-1);
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
	public function getName() : string {
		return ($this->getId()) ? get_the_title($this->getId()) : __('Por conocer','enroporra');
	}

	/**
	 * @return string|null
	 */
	public function getEnglishName() : ?string {
		$response = get_post_meta($this->getId(),'english_name',$single=true);
		return ($response) ?: null;
	}

	/**
	 * @return int
	 * @throws Exception
	 */
	public function getAPIid() : int {

		$response = get_post_meta($this->getId(),'team_api_id',$single=true);

		if ($response) return $response;
		if (!$this->getEnglishName()) return 0;

		$api = new API_Football();
		$api_result = $api->getTeamByName($this->getEnglishName());
		if ($api_result->id) {
			update_post_meta($this->getId(),'team_api_id',(int)$api_result->id);
			return (int) $api_result->id;
		}
		else return 0;
	}

	/**
	 * @return string flag image url of the team
	 */
	public function getFlagUrl() : string {
		if (file_exists($filename = ENROPORRA_PATH."images/flags/".get_post_meta($this->getId(),"alpha2",true).".png")) {
			return str_replace(ENROPORRA_PATH,ENROPORRA_PLUGIN_URI,$filename);
		}
		else return ENROPORRA_PLUGIN_URI."images/flags/unknown.png";
	}

	/**
	 * @return WP_Post|object
	 */
	public function getPost(): object {
		return $this->post;
	}

	/**
	 * @param int $width
	 *
	 * @return string
	 */
	public function getFlagHTML(int $width=0): string {
		$width = ($width) ? 'width:'.$width.'px;' : '';
		return '<img src="'.$this->getFlagUrl().'" style="margin: 0 10px;'.$width.'" alt="'.$this->getName().'"/>';
	}

	/**
	 * @throws Exception
	 */
	public function getPlayersFromAPI(): array {
		$api_connector = new API_Football();
		if (!$this->getAPIid()) return array();
		return $api_connector->getPlayersByTeam($this->getAPIid());
	}

	/**
	 * @param mixed $team
	 *
	 * @return bool
	 */
	public static function isTeam($team): bool {
		return (is_object($team) && get_class($team)=="EP_Team");
	}

	/**
	 * @param int $api_id
	 *
	 * @return EP_Team|null
	 * @throws Exception
	 */
	public static function getTeamByAPIid(int $api_id) : ?EP_Team {
		$posts = get_posts(array(
			'post_type' => 'team',
			'fields' => 'ids',
			'meta_key' => 'team_api_id',
			'meta_value' => (string)$api_id
		));
		if (isset($posts[0])) return new EP_Team($posts[0]);
		else return null;
	}

	/**
	 * @throws Exception
	 */
	public static function getTeamByName(string $teamname) : ?EP_Team {
		$posts = get_posts(array(
			'post_type' => 'team',
			'title' => $teamname
		));
		if (isset($posts[0])) return new self($posts[0]->ID);
		else return null;
	}

	/**
	 * @return EP_Team[]
	 * @throws Exception
	 */
	public static function getAllTeams() : array {
		$response = array();
		$posts = get_posts(array(
			'post_type' => 'team',
			'post_status' => 'publish',
			'posts_per_page' => -1
		));
		foreach ($posts as $post) $response[] = new EP_Team($post->ID);
		return $response;
	}

}