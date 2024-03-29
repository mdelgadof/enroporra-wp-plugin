<?php

define('MAX_GOALS_PER_FIXTURE',20);

function enroporra_metaboxes() {
	add_meta_box( 'fixtures-metabox-1', __('Goleadores','enroporra'), 'ep_fixture_metabox_scorers', 'fixture', 'normal', 'low' );
	add_meta_box( 'fixtures-metabox-2', __('Nuevos goleadores','enroporra'), 'ep_fixture_metabox_new_scorers', 'fixture', 'normal', 'low' );
	add_meta_box( 'players-metabox-1', __('Torneos disputados','enroporra'), 'ep_player_metabox_competitions', 'player', 'normal', 'low' );
	add_meta_box( 'competitions-metabox-1', __('Competición en curso','enroporra'), 'ep_competition_metabox_current', 'competition', 'normal', 'high' );
	add_meta_box( 'bets-metabox-1', __('Datos del apostante','enroporra'), 'ep_bet_user_data', 'bet', 'normal', 'high' );
	add_meta_box( 'bets-metabox-2', __('Pichichi','enroporra'), 'ep_bet_metabox_scorer', 'bet', 'advanced', 'high' );
	add_meta_box( 'bets-metabox-3', __('Árbitro de la final','enroporra'), 'ep_bet_metabox_referee', 'bet', 'advanced', 'high' );
	add_meta_box( 'bets-metabox-4', __('Partidos fase 2','enroporra'), 'ep_bet_metabox_fixtures2', 'bet', 'normal', 'low' );
    add_meta_box( 'bets-metabox-5', __('Partidos fase 1','enroporra'), 'ep_bet_metabox_fixtures1', 'bet', 'normal', 'low' );
	add_meta_box( 'bets-metabox-6', __('Meter apuesta segunda fase','enroporra'), 'ep_bet_metabox_link2stage', 'bet', 'side', 'low' );
}
add_action( 'add_meta_boxes', 'enroporra_metaboxes' );

function ep_player_metabox_competitions($post) {
    $player = new EP_Player($post->ID);
    $competitions = EP_Competition::getAllCompetitions();
	wp_nonce_field( 'ep_player_competitions_metabox_nonce', 'ep_player_competitions_nonce' );
	foreach ($competitions as $index => $competition) {
        $checked = ($player->isMyCompetition($competition)) ? "checked" : "";
        echo '<input type="checkbox" name="competition_'.($index+1).'" value='.$competition->getId().' '.$checked.' /> '.$competition->getName().'&nbsp;&nbsp;&nbsp;';
    }
    $checked = ($player->isBetScorer()) ? "checked":"";
	echo '<hr /><input type="checkbox" name="bet_scorer" value="true" '.$checked.' /> '.__('Presente en listado de goleadores de formulario de apuesta.','enroporra').'<br />';
}

function ep_fixture_metabox_scorers($post) {
	$fixture = new EP_Fixture($post->ID);
	$scorers = $fixture->getScorers();
    $option_teams = '
        <option value=""></option>
        <option value="1" data-order="1">'.$fixture->getTeam(1)->getName().'</option>
        <option value="2" data-order="2">'.$fixture->getTeam(2)->getName().'</option>
    ';
    $option_types = '
        <option value=""></option>
        <option value="p">Penalti</option>
        <option value="og">Propia meta</option>
    ';
    $option_players = '<option value=""></option>';
    foreach ($fixture->getPlayers() as $player) {
        $option_players .= '<option value="'.$player->getId().'">'.$player->getName().' ('.$player->getTeam()->getName().')</option>';
    }

	wp_nonce_field( 'ep_goals_metabox_nonce', 'ep_goals_nonce' );
    $counter_score = 1;
	foreach ($scorers as $scorer) {
        $score_for = ($fixture->getTeam(1)->getId()==$scorer["team_for"]->getId()) ? 1:2;
		echo ep_form_existent_scorer($counter_score,$option_players,$option_teams,$option_types,'',$score_for,$scorer);
        $counter_score++;
	}
    for ($i=$counter_score; $i<=MAX_GOALS_PER_FIXTURE; $i++) {
        $class = ($i==$counter_score) ? 'active':'hidden';
	    echo ep_form_existent_scorer($i,$option_players,$option_teams,$option_types,$class);
    }
}

function ep_fixture_metabox_new_scorers ($post) {
	$fixture = new EP_Fixture( $post->ID );
	$option_teams = '
        <option value=""></option>
        <option value="1" data-order="1">'.$fixture->getTeam(1)->getName().'</option>
        <option value="2" data-order="2">'.$fixture->getTeam(2)->getName().'</option>
    ';
	$option_types = '
        <option value=""></option>
        <option value="p">Penalti</option>
        <option value="og">Propia meta</option>
    ';
    for ($i=1; $i<=MAX_GOALS_PER_FIXTURE; $i++) {
        $class = ($i==1) ? "active":"hidden";
        echo ep_form_new_scorer($i,$option_teams,$option_types,$class);
    }
}

function ep_bet_user_data($post) {
    $bet = new EP_Bet($post->ID);
    $owner = $bet->getOwner();
    ?>
    <div>
        <b><?php _e('Nombre del apostante','enroporra') ?>:</b> <?php echo $owner->display_name ?><br />
        <b><?php _e('Email','enroporra') ?>:</b> <a href="<?php echo "mailto:".$bet->getEmail() ?>"><?php echo $bet->getEmail() ?></a><br />
        <b><?php _e('Teléfono','enroporra') ?>:</b> <?php echo get_post_meta($owner->ID,'phone',true) ?><br />
    </div>
    <?php
}

function ep_bet_metabox_link2stage($post) {
    ?>
    <a target="_blank" href="/apuesta/?id=<?php echo $post->ID ?>&admin=second_stage"><?php _e("Subir segunda fase","enroporra") ?></a>
    <?php
}

function ep_bet_metabox_scorer ($post) {
	$bet = new EP_Bet($post->ID);
	$competition = $bet->getCompetition();
	$players = $competition->getBetScorers();
    $scorer = $bet->getPlayer(); ?>
    <div class="dropdown">
        <div class="dropdown-launcher"><?php echo EP_Player::getUnknownPhotoHTML(40).'&nbsp;&nbsp;&nbsp;'.__('Cargando jugador','enroporra') ?>...</div>
            <ul id="betScorers" class="dropdown-content">
            <?php foreach ($players as $player) { ?>
                <li id="player_<?php echo $player->getId() ?>" data-player_id="<?php echo $player->getId() ?>"><?php echo $player->getPhotoHTML(40)." ".$player->getTeam()->getFlagHTML(20)." ".$player->getName()." (".$player->getTeam()->getName().")" ?></li>
            <?php
                if ($player->getId() == $scorer->getId()) { ?>
                    <script type="text/javascript">
                        jQuery(document).ready(function($) { $( "#player_<?php echo $player->getId() ?>" ).trigger( "click" ); });
                    </script>
            <?php }
            } ?>
            </ul>
    </div>
    <input type="hidden" id="enroporra_scorer" name="enroporra_scorer" value="<?php echo $scorer->getId() ?>" />
    <?php
}

function ep_bet_metabox_referee ($post) {
	$bet = new EP_Bet($post->ID);
	$chosenReferee = $bet->getReferee();
    if (is_null($chosenReferee)) return;
	$competition = $bet->getCompetition();
	$referees = EP_Referee::getAllReferees(); // TODO Need to develop EP_Competition::getReferees()
	 ?>
    <div class="dropdown">
        <div class="dropdown-launcher"><?php __('Cargando árbitro','enroporra') ?>...</div>
        <ul id="betReferees" class="dropdown-content">
			<?php foreach ($referees as $referee) { ?>
                <li id="referee_<?php echo $referee->getId() ?>" data-referee_id="<?php echo $referee->getId() ?>"><?php echo $referee->getTeam()->getFlagHTML(20)." ".$referee->getName()." (".$referee->getTeam()->getName().")" ?></li>
			<?php
			if ($referee->getId() == $chosenReferee->getId()) { ?>
                <script type="text/javascript">
                    jQuery(document).ready(function($) { $( "#referee_<?php echo $referee->getId() ?>" ).trigger( "click" ); });
                </script>
			<?php }
			} ?>
        </ul>
    </div>
    <input type="hidden" id="enroporra_referee" name="enroporra_referee" value="<?php echo $chosenReferee->getId() ?>" />
	<?php
}

/**
 * @throws Exception
 */
function ep_bet_metabox_fixtures1 ($post) {
	$bet = new EP_Bet($post->ID);
	$competition = $bet->getCompetition();
	$fixtures = $competition->getFixtures(array('tournament'=>'groups'));
	$scores = $bet->getScores();
	wp_nonce_field( 'ep_bets_metabox_nonce', 'ep_bets_nonce' );
	foreach ($fixtures as $fixture) {
		$team1 = $fixture->getTeam(1);
		$team2 = $fixture->getTeam(2);
		?>
        <div class="betFixture">
            <div class="betFixtureDate"><?php echo $fixture->getDate().' - '.__('Grupo','enroporra').' '.$fixture->getGroup(); ?></div>
            <div class="betFixtureResult">
                <div class="betTeamContainer"><?php echo $team1->getFlagHTML(40); ?> <span class="betTeamName"><?php echo $team1->getName() ?></span> <input type="number" name="enroporra_result_<?php echo $fixture->getFixtureNumber() ?>_1" class="betTeamResult" max="15" min="0" value="<?php echo $scores[$fixture->getFixtureNumber()]["s1"] ?>" required /></div>
                <div class="betTeamContainer"><?php echo $team2->getFlagHTML(40); ?> <span class="betTeamName"><?php echo $team2->getName() ?></span> <input type="number" name="enroporra_result_<?php echo $fixture->getFixtureNumber() ?>_2" class="betTeamResult" max="15" min="0" value="<?php echo $scores[$fixture->getFixtureNumber()]["s2"] ?>" required /></div>
            </div>
        </div>
		<?php
	}
}

function ep_bet_metabox_fixtures2 ($post) {
	$bet = new EP_Bet($post->ID);
	$competition = $bet->getCompetition();
	$fixtures = array_merge($competition->getFixtures(array('tournament'=>'last16')), $competition->getFixtures(array('tournament'=>'last8')), $competition->getFixtures(array('tournament'=>'last4')), $competition->getFixtures(array('tournament'=>'final')));
	$scores = $bet->getScores();
	wp_nonce_field( 'ep_bets_metabox_nonce', 'ep_bets_nonce' );
	foreach ($fixtures as $fixture) {
        $fixtureNumber = $fixture->getFixtureNumber();
		$team1 = $scores[$fixtureNumber]["t1"];
		$team2 = $scores[$fixtureNumber]["t2"];
        if (is_null($team1) || is_null($team2)) continue;
		?>
        <div class="betFixture">
            <div class="betFixtureDate"><?php echo $fixture->getDate().' - '.$fixture->getTournamentLabel(); ?></div>
            <div class="betFixtureResult">
                <div class="betTeamContainer"><?php echo $team1->getFlagHTML(40); ?> <span class="betTeamName"><?php echo $team1->getName() ?></span> <input type="number" name="enroporra_result_<?php echo $fixtureNumber ?>_1" class="betTeamResult" max="15" min="0" value="<?php echo $scores[$fixtureNumber]["s1"] ?>" required /></div>
                <div class="betTeamContainer"><?php echo $team2->getFlagHTML(40); ?> <span class="betTeamName"><?php echo $team2->getName() ?></span> <input type="number" name="enroporra_result_<?php echo $fixtureNumber ?>_2" class="betTeamResult" max="15" min="0" value="<?php echo $scores[$fixtureNumber]["s2"] ?>" required /></div>
                <input type="hidden" name="enroporra_team_<?php echo $fixtureNumber ?>_1" value="<?php echo $team1->getId() ?>" />
                <input type="hidden" name="enroporra_team_<?php echo $fixtureNumber ?>_2" value="<?php echo $team2->getId() ?>" />
            </div>
            <div>Penaltis:
                <input type="radio" name="enroporra_winner_<?php echo $fixtureNumber ?>" value="0" <?php echo ( ($scores[$fixtureNumber]["winner"]=="X" && $scores[$fixtureNumber]["s1"]==$scores[$fixtureNumber]["s2"]) || $scores[$fixtureNumber]["s1"]!=$scores[$fixtureNumber]["s2"]) ? "checked":"" ?>/> No hay penaltis
                <input type="radio" name="enroporra_winner_<?php echo $fixtureNumber ?>" value="1" <?php echo ($scores[$fixtureNumber]["winner"]==1 && $scores[$fixtureNumber]["s1"]==$scores[$fixtureNumber]["s2"]) ? "checked":"" ?>/> Gana <?php echo $team1->getName() ?>
                <input type="radio" name="enroporra_winner_<?php echo $fixtureNumber ?>" value="2" <?php echo ($scores[$fixtureNumber]["winner"]==2 && $scores[$fixtureNumber]["s1"]==$scores[$fixtureNumber]["s2"]) ? "checked":"" ?>/> Gana <?php echo $team2->getName() ?>
            </div>
            <hr>
        </div>
		<?php
	}
}

/**
 * @throws Exception
 */
function ep_competition_metabox_current($post) {
	$competition = new EP_Competition($post->ID);
	$checked = ($competition->isCurrentCompetition()) ? "checked":"";
	wp_nonce_field( 'ep_competitions_metabox_nonce', 'ep_competitions_nonce' );
	echo '<input type="checkbox" name="current_competition" value="1" '.$checked.' /> '.__('Competición actualmente vigente','enroporra');
}

function ep_form_existent_scorer($counter_score,$option_players,$option_teams,$option_types,$class="",$score_for=false,$scorer=false) {

    $option_players = ($scorer) ? str_replace('value="'.$scorer["player"]->getId().'"','value="'.$scorer["player"]->getId().'" selected',$option_players) : $option_players;
    $option_teams = ($scorer) ? str_replace('data-order="'.$score_for.'"','selected',$option_teams) : $option_teams;
    $option_types = ($scorer) ? str_replace('"'.$scorer["type"].'"','"'.$scorer["type"].'" selected',$option_types) : $option_types;
    ob_start();
    ?>
    <div id="goal_<?php echo $counter_score ?>" class="<?php echo $class ?>">
        <select class="select-player" data-next="<?php echo ($counter_score+1) ?>" name="player_<?php echo $counter_score ?>"><?php echo $option_players; ?></select>
        <select name="team_<?php echo $counter_score ?>"><?php echo $option_teams; ?></select>
        <select name="type_<?php echo $counter_score ?>"><?php echo $option_types; ?></option></select>
        <label for="minute_<?php echo $counter_score ?>">Minuto</label>
        <input type='text' style='width:60px' name='minute_<?php echo $counter_score ?>' value='<?php if ($scorer) echo $scorer["minute"] ?>' />
        <?php if ($scorer) { ?><input type='hidden' name='prev_<?php echo $counter_score ?>' value='<?php echo $scorer["prev_value"] ?>' />
        <input type='checkbox' style="margin-left:15px" name='delete_<?php echo $counter_score ?>' value='1' /> Eliminar gol
        <?php } ?>
        <hr />
    </div>
    <?php
    return ob_get_clean();
}

function ep_form_new_scorer($counter_score,$option_teams,$option_types,$class="") {

	ob_start();
	?>
    <div id="goal_new_<?php echo $counter_score ?>" class="<?php echo $class ?>">
        <input type="text" class="new-player" data-next="<?php echo ($counter_score+1) ?>" name="new_player_name_<?php echo $counter_score ?>" placeholder="Nombre" />
        <input type="text" name="new_player_surname_<?php echo $counter_score ?>" placeholder="Apellido" />
        <select name="new_player_team_<?php echo $counter_score ?>"><?php echo $option_teams; ?></select>
        <select name="new_player_teamfor_<?php echo $counter_score ?>"><?php echo $option_teams; ?></select>
        <select name="new_player_type_<?php echo $counter_score ?>"><?php echo $option_types; ?></option></select>
        <label for="new_player_minute_<?php echo $counter_score ?>">Minuto</label>
        <input type='text' style='width:60px' name='new_player_minute_<?php echo $counter_score ?>' />
        <hr />
    </div>
	<?php
	return ob_get_clean();
}


/**
 * @throws Exception
 */
function ep_fixture_metabox_scorers_save($post_id) {

	if( !isset( $_POST['ep_goals_nonce'] ) || !wp_verify_nonce( $_POST['ep_goals_nonce'],'ep_goals_metabox_nonce') )
		return;
	if ( !current_user_can( 'edit_post', $post_id ))
		return;

    $fixture = new EP_Fixture($post_id);
    $api = new API_Football();

    for ($i=1; $i<=MAX_GOALS_PER_FIXTURE; $i++) {
        try {
            if ($_POST['delete_'.$i]=="1") {
	            $fixture->deleteScorer(stripcslashes($_POST["prev_".$i]));
            }
            else if (isset($_POST['player_'.$i]) && $_POST['player_'.$i]!="") {
                $player = new EP_Player($_POST["player_".$i]);
                $fixture->setScorer(array("player"=>$player,"for"=>intval($_POST["team_".$i]),"type"=>$_POST["type_".$i],"minute"=>intval($_POST["minute_".$i]),"prev"=>stripcslashes($_POST["prev_".$i])));
            }
            if ($_POST['new_player_name_'.$i]!="" || $_POST['new_player_surname_'.$i]!="") {
                $player_name = trim($_POST['new_player_name_'.$i].' '.$_POST['new_player_surname_'.$i]);
                $team = $fixture->getTeam($_POST['new_player_team_'.$i]);
                $player = EP_Player::createPlayerFromAPI($player_name,$team);
                $player->setCompetition($fixture->getCompetition());
                $fixture->setScorer(array("player"=>$player,"for"=>intval($_POST["new_player_teamfor_".$i]),"type"=>$_POST["new_player_type_".$i],"minute"=>intval($_POST["new_player_minute_".$i])));
            }
        }
        catch (Exception $e) {
            $user = get_current_user();
	        set_transient("fixture_error_{$post_id}_{$user->ID}", $e->getMessage(), 45);
            break;
        }
    }

}
add_action('save_post_fixture', 'ep_fixture_metabox_scorers_save');

/**
 * @throws Exception
 */
function ep_fixture_save_after_acf($post_id) {

	if (get_post_type($post_id)==='fixture') {
        $fixture = new EP_Fixture($post_id);
        if ($fixture->isPlayed()) {
            if ($fixture->getGoals(1)>$fixture->getGoals(2)) $fixture->setWinner(1);
	        if ($fixture->getGoals(1)<$fixture->getGoals(2)) $fixture->setWinner(2);
        }
		// Calculate all bet points each time a fixture score is saved
		foreach ($fixture->getCompetition()->getBets(false) as $bet) {
			$bet->calculatePoints();
		}
	}
}
add_action('acf/save_post', 'ep_fixture_save_after_acf');


/**
 * @throws Exception
 */
function ep_player_metabox_competitions_save($post_id) {

	if ( ! isset( $_POST['ep_player_competitions_nonce'] ) || ! wp_verify_nonce( $_POST['ep_player_competitions_nonce'], 'ep_player_competitions_metabox_nonce' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta($post_id,'competitions',serialize(array()));
    $player = new EP_Player($post_id);

    foreach ($_POST as $key => $value) {
        if (substr($key,0,12)=="competition_") {
            $competition = new EP_Competition($value);
            $player->setCompetition($competition);
        }
    }

    $player->setBetScorer((bool)$_POST["bet_scorer"]);
}
add_action('save_post_player', 'ep_player_metabox_competitions_save');

/**
 * @throws Exception
 */
function ep_player_save_after_acf($post_id) {

    if (get_post_type($post_id)==='player') {
        $player = new EP_Player($post_id);
	    if (!$player->getAPIid()) {
		    $api = new API_Football();
		    $team = $player->getTeam();
		    $data = $api->getPlayerByName(urlencode($player->getSurname()),$team->getAPIid());
		    if (!empty($data)) {
			    $player->setAPIid( intval( $data->id ) );
			    $player->setImage( $data->photo );
		    }
	    }
        if ($player->getAPIid() && !$player->getImage()) {
            $api = new API_Football();
            $player_api = $api->getPlayerById($player->getAPIid(),date("Y"));
            $player->setImage($player_api->photo);
        }
    }
}
add_action('acf/save_post', 'ep_player_save_after_acf');

function ep_competition_metabox_current_save($post_id) {
	if ( ! isset( $_POST['ep_competitions_nonce'] ) || ! wp_verify_nonce( $_POST['ep_competitions_nonce'], 'ep_competitions_metabox_nonce' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
    if ($_POST['current_competition']=="1") {
        $competition = new EP_Competition($post_id);
        $competition->setCurrentCompetition();
    }
    else {
        if ($current_competition = EP_Competition::getCurrentCompetition()) {
            if ($current_competition->getId()==$post_id) EP_Competition::deleteCurrentCompetition();
        }
    }
}
add_action('save_post_competition', 'ep_competition_metabox_current_save');

function ep_bet_metabox_current_save($post_id) {
	if ( ! isset( $_POST['ep_bets_nonce'] ) || ! wp_verify_nonce( $_POST['ep_bets_nonce'], 'ep_bets_metabox_nonce' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
    $bet = new EP_Bet($post_id);
    $scores = array();

	foreach ($_POST as $key => $value) {
		$temp = explode("_",$key);
		if ($temp[0]=="enroporra" && $temp[1]=="result" && count($temp)==4) {
			$scores[$temp[2]]["s".$temp[3]] = intval($value);
            if (!isset($scores[$temp[2]]["fixture"])) $scores[$temp[2]]["fixture"] = $bet->getCompetition()->getFixtureById($temp[2]);
		}
		if ($temp[0]=="enroporra" && $temp[1]=="team" && count($temp)==4) {
			$scores[$temp[2]]["t".$temp[3]] = new EP_Team(intval($value));
		}
	}
    foreach ($_POST as $key => $value) {
	    $temp = explode("_",$key);
        if ($temp[0]=="enroporra" && $temp[1]=="winner") {
            if ($scores[$temp[2]]["s1"]==$scores[$temp[2]]["s2"])
                $scores[$temp[2]]["winner"]=$value;
        }
    }
    $bet->setScores($scores); // TODO fix setScores
    $bet->setPlayer(new EP_Player(intval($_POST["enroporra_scorer"])));
	$bet->setReferee(intval($_POST["enroporra_referee"])); // TODO Parameter should be EP_Referee
}
add_action('save_post_bet', 'ep_bet_metabox_current_save');

