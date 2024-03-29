<?php
/**
 * COMPETITION
 */
add_filter('manage_competition_posts_columns',function($columns) {
	unset($columns['date']);
	unset($columns['title']);
	$columns['competition'] = __('Nombre del torneo','enroporra');
	return $columns;
});

function enroporra_manage_competition_posts_custom_column($column,$post_id) {

    try { $competition = new EP_Competition($post_id); }
    catch (Exception $e) { return; }

    switch($column) {
        case 'competition' :
            echo $competition->getName();
            if ($competition->isCurrentCompetition()) echo " <strong>(".__('Competición actual','enroporra').")</strong>";
            break;
    }
}
add_action('manage_competition_posts_custom_column', 'enroporra_manage_competition_posts_custom_column', 10, 2);

/**
 * FIXTURE
 */
add_filter('manage_fixture_posts_columns',function($columns) {
	unset($columns['date']);
	$columns['fixture_date'] = __('Fecha del partido','enroporra');
	$columns['team1'] = __('Equipo 1','enroporra');
	$columns['goals'] = __('Resultado','enroporra');
	$columns['team2'] = __('Equipo 2','enroporra');
	return $columns;
});

add_filter('manage_edit-fixture_sortable_columns','enroporra_fixture_sortable_columns');
function enroporra_fixture_sortable_columns( $columns ) {
	$columns['fixture_date'] = 'fixture_date';
	return $columns;
}
add_action('manage_fixture_posts_custom_column', 'enroporra_manage_fixture_posts_custom_column', 10, 2);
function enroporra_manage_fixture_posts_custom_column( $column, $post_id) {
//echo $column." ".$post_id."<hr>";
	try { $fixture = new EP_Fixture($post_id); }
	catch (Exception $e) { return; }

	switch ($column) {
		case 'fixture_date' :
			if ($fixture->getRawDate()) echo date('d/m/Y H:i',strtotime($fixture->getRawDate()));
			break;
		case 'team1' :
			$team = $fixture->getTeam(1);
			if (!is_null($team)) echo "<div class='flag'>".$team->getFlagHTML(30)."</div><div class='teamName'>".$team->getName()."</div>";
			break;
		case 'goals' :
			if ($fixture->getGoals(1)!="") echo "<span>{$fixture->getGoals(1)}-{$fixture->getGoals(2)}</span>";
			break;
		case 'team2' :
			$team = $fixture->getTeam(2);
			if (!is_null($team)) echo "<div class='flag'>".$team->getFlagHTML(30)."</div><div class='teamName'>".$team->getName()."</div>";
			break;
	}

};

add_action( 'pre_get_posts', 'enroporra_fixture_sort_column_query',9 );
function enroporra_fixture_sort_column_query( $query ) {

	global $pagenow;

	if($query->is_admin && $pagenow == 'edit.php') {

		if ($query->get('post_type') == 'fixture') {

			$orderby = $query->get( 'orderby' );

			if ( 'fixture_date' == $orderby ) {

				$meta_query = array(
					'relation' => 'OR',
					array(
						'key'     => 'date',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key' => 'date',
					),
				);

				$query->set( 'meta_query', $meta_query );
				$query->set( 'orderby', 'meta_value' );
			}

			if ( $orderby == '' ) {
				$meta_query = array(
					array(
						'key' => 'fixture_number',
					),
					array(
						'key'   => 'competition',
						'value' => EP_Competition::getCurrentCompetition()->getId()
					)
				);

				$query->set( 'meta_query', $meta_query );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'ASC' );
			}
		}
    }
}
/**
 * TEAM
 */
add_filter('manage_team_posts_columns',function($columns) {
	unset($columns['date']);
	unset($columns['title']);
	$columns['team'] = __('Equipo','enroporra');
	return $columns;
});

add_action('manage_team_posts_custom_column', 'enroporra_manage_team_posts_custom_column', 10, 2);
function enroporra_manage_team_posts_custom_column($column,$post_id) {
	try { $team = new EP_Team($post_id); }
	catch (Exception $e) { return; }

	switch($column) {
		case 'team' :
			echo "<div class='flag'>".$team->getFlagHTML(30)."</div><div class='teamName'>".$team->getName()."</div>";
	}
}

/**
 * PLAYER
 */
add_filter('manage_player_posts_columns',function($columns) {
    unset($columns);
    $columns['team'] = __('Equipo','enroporra');
    $columns['photo'] = '';
	$columns['title'] = __('Nombre','enroporra');
	return $columns;
});
add_action('manage_player_posts_custom_column', 'enroporra_manage_player_posts_custom_column', 10, 2);
function enroporra_manage_player_posts_custom_column($column,$post_id) {
	try { $player = new EP_Player($post_id); }
	catch (Exception $e) { return; }

	switch($column) {
		case 'team' :
			echo "<div class='flag'>".$player->getTeam()->getFlagHTML(30)."</div>";
            break;
        case 'photo' :
            echo "<div class='photo'>".$player->getPhotoHTML(50)."</div>";
            break;
	}
}
/**
 * BET
 */

add_filter('manage_bet_posts_columns',function($columns) {
	$columns_new = array();
	$columns_new['bet_number'] = __('Nº apuesta','enroporra');
	$columns_new['title'] = $columns['title'];
	$columns_new['date'] = $columns['date'];
	return $columns_new;
});

add_action('manage_bet_posts_custom_column', 'enroporra_manage_bet_posts_custom_column', 10, 2);
function enroporra_manage_bet_posts_custom_column($column,$post_id) {
	try { $bet = new EP_Bet($post_id); }
	catch (Exception $e) { return; }

	switch($column) {
		case 'bet_number' :
			$class = ($bet->isPaid()) ? "paid":"nopaid";
			echo '<span class="'.$class.'" id="bet_number-'.$post_id.'">'.$bet->getBetNumber().'</span>';
			break;
	}
}

/**
 * GENERAL
 */

function enroporra_order_post_type($query) {
	if($query->is_admin) {

		if ($query->get('post_type') == 'team')
		{
			$query->set('orderby', 'title');
			$query->set('order', 'ASC');
		}
	}
	return $query;
}
add_filter('pre_get_posts', 'enroporra_order_post_type');

function enroporra_admin_error_message() {
	global $post;
	$user_id = get_current_user_id();
	if ( $error = get_transient( "fixture_error_{$post->ID}_{$user_id}" ) ) { ?>
		<div class="error">
		<p><?php echo $error->get_error_message(); ?></p>
		</div><?php
		delete_transient("fixture_error_{$post->ID}_{$user_id}");
	}
}
add_action( 'admin_notices', 'enroporra_admin_error_message' );

function enroporra_custom_menu_page_removing() {
    remove_menu_page( 'edit.php' );
	remove_menu_page( 'index.php' );
	remove_menu_page( 'upload.php' );
	remove_menu_page( 'edit-comments.php' );
}
add_action( 'admin_menu', 'enroporra_custom_menu_page_removing' );

add_filter( 'post_row_actions', 'enroporra_modify_list_row_actions', 10, 2 );
function enroporra_modify_list_row_actions( $actions, $post ) {
	// Check for your post type.
	if ( $post->post_type == "bet" && $post->post_status=="publish") {

		try {
            $bet = new EP_Bet($post->ID);
		}
        catch (Exception $e) {
	        return $actions;
        }

        $edit = $actions['edit'];
		$trash = $actions['trash'];

        $labelpaid = ($bet->isPaid()) ? __("Marcar como no pagado","enroporra") : __("Marcar como pagado","enroporra");
        $markedas = ($bet->isPaid()) ? "nopaid":"paid";
        $actions = array(
            'edit' => $edit,
            'trash' => $trash,
            'payment' => '<a class="adminpay-link" data-markedas="'.$markedas.'" data-bet_id="'.$bet->getId().'" href="#">'.$labelpaid.'</a>'
        );
	}

	return $actions;
}

// AJAX FUNCTIONS

function enroporra_modify_paid_status() {
    try {
        $bet = new EP_Bet(intval($_POST["bet_id"]));
    }
    catch (Exception $e) {
        die('Error: '.$e->getMessage());
    }
    if ($_POST["markedas"]=="paid") {
        $bet->setPaid(true);
	}
    else $bet->setPaid(false);
	die('OK');
}
add_action('wp_ajax_modifyPaidStatus','enroporra_modify_paid_status');

