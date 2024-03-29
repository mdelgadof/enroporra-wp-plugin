<?php

class EP_Referee {
	/**
	 * @var WP_Post
	 */
	protected $post;
	protected $id;
	protected $name;

	/**
	 * @var EP_Competition[]
	 */
	protected $competitions;

	public function __construct( int $post_id=0) {
		if ( $post_id <= 0) throw new Exception(sprintf('%s is not a valid ID creating a Referee at EP_Referee',$post_id),-1);
		if (is_null($this->post = get_post($post_id))) throw new Exception(sprintf('Post %s does not exist creating a Referee at EP_Referee',$post_id),-1);
		if (get_post_type($post_id)!="referee") throw new Exception(sprintf('Wrong post id %s creating a Referee at EP_Referee. Post is not a referee',$post_id),-1);
		$this->id = $post_id;
		$this->name = $this->post->post_title;
	}

	/**
	 * @throws Exception
	 */
	public static function getAllReferees() : array {
		$response = array();
		$posts = get_posts(array(
			'post_type' => 'referee',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'orderby' => 'post_title',
			'order' => 'ASC'
		));
		foreach ($posts as $post) {
			$response[] = new EP_Referee($post->ID);
		}
		return $response;
	}

	public function getId() : int {
		return $this->id;
	}

	public function getTeam() : EP_Team {
		$team_id = get_post_meta($this->getId(),'team',true);
		return new EP_Team($team_id);
	}

	public function getName() : string {
		return $this->name;
	}

	public static function createReferee($args): self {
		if (is_array($args)) {
			// Mandatory fields to set a player on Enroporra
			if (!isset($args["team"])||!isset($args["name"]))
				throw new Exception( 'Malformed array of args creating a referee at EP_referee::createReferee, some required field/s is/are missing.',-1);
			if (!EP_Team::isTeam($args["team"]))
				throw new Exception( 'Argument "team" is not a valid EP_Team object.',-1);
			if (get_posts( array(
				'post_type' => 'referee',
				'post_status' => 'publish',
				'posts_per_page' => 1,
				'meta_query' => array(
					array(
						'key'     => 'name',
						'value'   => $args["name"],
					),
					array(
						'key' => 'team',
						'value' => $args["team"]->getId()
					)
				),
			) ) ) {
				throw new Exception(sprintf('Referee %s already exists creating new EP_referee::createReferee.',$args["name"]),-1);
			}

			$new_referee_id = wp_insert_post(array('post_title'=>$args["name"],'post_type'=>'referee','post_status'=>'publish'));
			if (is_wp_error($new_referee_id))
				throw new Exception(sprintf('Could not create wordpress post at EP_referee::createReferee: %s.',$new_referee_id->get_error_message()),-1);
			try {
				$referee = new EP_Referee($new_referee_id);
			}
			catch (Exception $e) {
				wp_delete_post($new_referee_id,$force_delete=true);
				throw new Exception(sprintf('Could not create EP_Referee at EP_Referee::createReferee: %s',$e->getMessage()),-1);
			}

			if (
				!add_post_meta($new_referee_id, "name", $args["name"], true) ||
				!add_post_meta($new_referee_id, "team", $args["team"]->getId(), true)
			) {
				wp_delete_post($new_referee_id,true);
				throw new Exception( 'Name, surname or team could not be filled creating a referee at EP_Referee::createReferee.',-1);
			}
		}
		else throw new Exception( 'Bad param creating a referee at EP_Referee::createReferee, must be an array.',-1);
		return $referee;
	}
	
}
