<?php

class EP_User extends WP_User {

	const OBJECT = 1;
	const INTEGER = 2;

	function getBetFriendsIds() {
		$friends = get_user_meta($this->getId(),'betFriends',true);
		return ($friends) ?: array();
	}

	function setBetFriendsIds(array $friends) {
		update_user_meta($this->getId(),'betFriends',$friends);
	}

	/**
	 * @throws Exception
	 * @return EP_Bet[]
	 */
	function getBets(EP_Competition $competition, int $type=self::OBJECT) : array {
		$bets = $competition->getBets(false);
		$response = array();
		foreach ($bets as $bet) {
			if ($bet->getOwner()->getId()==$this->getId())
				$response[] = ($type==self::OBJECT) ? $bet : $bet->getId();
		}
		return $response;
	}

	function getId() : int {
		return $this->ID;
	}

	function getEmail() : string {
		return $this->user_email;
	}

	public function isViewing() : bool {
		return ($this->getId()==get_current_user_id());
	}

	public function isAdmin() {
		return (in_array('administrator',$this->roles));
	}
}