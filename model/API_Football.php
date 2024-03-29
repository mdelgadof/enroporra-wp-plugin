<?php

class API_Football {

	public function __construct() {
	}

	private function call($method) {
		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL => "https://api-football-v1.p.rapidapi.com/v3/".$method,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => [
				"x-rapidapi-host: api-football-v1.p.rapidapi.com",
				"x-rapidapi-key: 8bc8ed28d3msh1871d58382376cdp12501ajsne31f999389da"
			],
		]);

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			throw new Exception(sprintf('cURL Error #%s at API_Football::call',$err),-1);
		} else {
			return $response;
		}
	}

	public function getTeamByName($teamname) {
		try {
			$response = json_decode($this->call("teams/?name=".$teamname));
			if (empty($response->errors) && $response->results>0) return $response->response[0]->team;
			else {
				echo "<pre>";
				var_dump($response);
				throw new Exception(sprintf('Error: Team %s not found at API Football',$teamname),-1);
			}
		}
		catch (Exception $e) { throw new Exception(sprintf('Error trying to connect to API at API_Football::getTeamByName : %s',$e->getMessage()),-1); }
	}

	public function getLiveFixtures() {
		try {
			$response = json_decode($this->call("fixtures/?live=all"));
			/*if (empty($response->errors) && $response->results>0) return $response->response[0]->team;
			else {*/
				echo "<pre>";
				var_dump($response);
				/*throw new Exception( 'Error: Problem at API Football live fixtures',-1);
			}*/
		}
		catch (Exception $e) { throw new Exception(sprintf('Error trying to connect to API at API_Football::getLiveFixtures : %s',$e->getMessage()),-1); }
	}

	public function getFixture(int $fixture_id) {
		try {
			$response = json_decode($this->call("fixtures/?id=208320"));
			//Final 2014: id 208320
			/*if (empty($response->errors) && $response->results>0) return $response->response[0]->team;
			else {*/
			echo "<pre>";
			var_dump($response);
			/*throw new Exception( 'Error: Problem at API Football live fixtures',-1);
		}*/
		}
		catch (Exception $e) { throw new Exception(sprintf('Error trying to connect to API at API_Football::getFixture : %s',$e->getMessage()),-1); }
	}

	public function getLeague($id) {
		try {
			$response = json_decode($this->call("leagues/?id=".$id));
			if (empty($response->errors) && $response->results>0) return $response->response;
			else throw new Exception(sprintf('Error: League %s not found at API Football',$id),-1);
		}
		catch (Exception $e) { throw new Exception(sprintf('Error trying to connect to API at API_Football::getLeague : %s',$e->getMessage()),-1); }
	}

	public function getTeamsByLeague($league_id,$season) {
		try {
			$response = json_decode($this->call("teams/?league=".$league_id."&season=".$season));
			if (empty($response->errors) && $response->results>0) return $response->response;
			else throw new Exception(sprintf('Error'),-1);
		}
		catch (Exception $e) { throw new Exception(sprintf('Error trying to connect to API at API_Football::getTeamsByLeague : %s',$e->getMessage()),-1); }
	}

	public function getPlayersByTeam($team_id) {
		if (intval($team_id)!=$team_id) throw new Exception(sprintf('Bad parameter team_id "%s" at API_Football::getPlayersByTeam. Must be an integer',$team_id),-1);
		try {
			$response = json_decode($this->call("players/squads/?team=".$team_id));
			if (empty($response->errors) && $response->results>0) {
				return $response->response[0]->players ?? array();
			}
			else throw new Exception(sprintf('Error'),-1);
		}
		catch (Exception $e) { throw new Exception(sprintf('Error trying to connect to API at API_Football::getPlayersByTeam : %s',$e->getMessage()),-1); }
		return array();
	}

	public function getPlayerByName($name,$teamId) {
		try {
			$response = json_decode($this->call("players/?search=".$name."&team=".$teamId));
			if (empty($response->errors) && $response->results>0) {
				return $response->response[0]->player ?? array();
			}
			else {
				return array();
			}
		}
		catch (Exception $e) {
			throw new Exception(sprintf('Error trying to connect to API at API_Football::getPlayerByName : %s',$e->getMessage()),-1);
		}
	}

	public function getPlayerById($player_id,$season) {
		if (intval($player_id)!=$player_id) throw new Exception(sprintf('Bad parameter team_id "%s" at API_Football::getPlayerById. Must be an integer',$player_id),-1);
		try {
			$response = json_decode($this->call("players/?id=".$player_id."&season=".$season));
			if (empty($response->errors) && $response->results>0) {
				return $response->response[0]->player ?? null;
			}
			else throw new Exception(sprintf('Error'),-1);
		}
		catch (Exception $e) { throw new Exception(sprintf('Error trying to connect to API at API_Football::getPlayerById : %s',$e->getMessage()),-1); }
		return array();
	}

}
