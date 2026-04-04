<?php

class API_Football {

	public function __construct() {
	}

	private function call($method) {
		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL => "https://api.football-data.org/v4/".$method,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => [
				"X-Auth-Token: 707980324fb14e3facd86b0621058b4a"
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

	public function getTeamsByLeague($competition_id, $season) {
		try {
			$response = json_decode($this->call("competitions/".$competition_id."/teams?season=".$season));
			if (!empty($response->teams)) return $response->teams;
			else throw new Exception(sprintf('Error: No teams found for competition %s season %s at API Football', $competition_id, $season),-1);
		}
		catch (Exception $e) { throw new Exception(sprintf('Error trying to connect to API at API_Football::getTeamsByLeague : %s',$e->getMessage()),-1); }
	}

	public function getPlayersByTeam($team_id) {
		if (intval($team_id)!=$team_id) throw new Exception(sprintf('Bad parameter team_id "%s" at API_Football::getPlayersByTeam. Must be an integer',$team_id),-1);
		try {
			// football-data.org v4: GET /teams/{id} returns squad array
			$response = json_decode($this->call("teams/".$team_id));
			if (!empty($response->squad)) return $response->squad;
			else throw new Exception(sprintf('Error: No squad found for team %s at API Football', $team_id),-1);
		}
		catch (Exception $e) { throw new Exception(sprintf('Error trying to connect to API at API_Football::getPlayersByTeam : %s',$e->getMessage()),-1); }
		return array();
	}

	public function getPlayerById($player_id) {
		if (intval($player_id)!=$player_id) throw new Exception(sprintf('Bad parameter player_id "%s" at API_Football::getPlayerById. Must be an integer',$player_id),-1);
		try {
			$response = json_decode($this->call("persons/".$player_id));
			if (!empty($response->id)) return $response;
			else throw new Exception(sprintf('Error: Player %s not found at API Football', $player_id),-1);
		}
		catch (Exception $e) { throw new Exception(sprintf('Error trying to connect to API at API_Football::getPlayerById : %s',$e->getMessage()),-1); }
		return null;
	}

}
