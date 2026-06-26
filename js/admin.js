jQuery(document).ready(function($) {
    $('.disabled input, .disabled select').attr('disabled','true');
    $('.select-player').on('change',function() {
        $('#goal_'+$(this).data('next')).removeClass('hidden');
    });
    $('.new-player').on('change',function() {
        $('#goal_new_'+$(this).data('next')).removeClass('hidden');
    });
    $('.adminpay-link').on('click',function() {
        var bet_id = $(this).data('bet_id');
        var markedas = $(this).data('markedas');
        var nonmarkedas = (markedas=="paid") ? "nopaid" : "paid";
        var label = $(this);

        label.html('<img src="'+global_vars.pluginUrl+'/images/ajax-loader.gif" />');

        $.ajax({
            url: global_vars.ajaxUrl,
            type: 'post',
            data: {
                action: 'modifyPaidStatus',
                bet_id: bet_id,
                markedas: markedas
            },
            success: function(response) {
                if (response=="OK") {
                    $('#bet_number-' + bet_id).removeClass(nonmarkedas);
                    $('#bet_number-' + bet_id).addClass(markedas);
                    label.html((markedas == "paid") ? "Marcar como no pagado" : "Marcar como pagado");
                    label.data('markedas', nonmarkedas);
                    label.blur();
                }
                else console.log(response);
            }
        });


    });

    // Referee competition toggle
    $('.referee-competition-toggle').on('change', function() {
        var checkbox = $(this);
        var referee_id = checkbox.data('referee_id');
        var competition_id = checkbox.data('competition_id');
        var action_type = checkbox.is(':checked') ? 'add' : 'remove';
        checkbox.prop('disabled', true);
        $.ajax({
            url: global_vars.ajaxUrl,
            type: 'post',
            data: { action: 'toggleRefereeCompetition', referee_id: referee_id, competition_id: competition_id, action_type: action_type },
            success: function(response) {
                checkbox.prop('disabled', false);
                if (response != 'OK') {
                    checkbox.prop('checked', !checkbox.is(':checked'));
                    console.log(response);
                }
            },
            error: function() {
                checkbox.prop('checked', !checkbox.is(':checked'));
                checkbox.prop('disabled', false);
            }
        });
    });

    // General dropdown selectors
    $('div.dropdown-launcher').on('click',function() {
        $(this).parent().children('ul.dropdown-content').toggle();
    });
    $('ul.dropdown-content li').on('click',function() {
        $(this).parent().parent().children('div.dropdown-launcher').html($(this).html());
        $(this).parent().hide();
    });

    // Bet 1 dropdown exclusive: filling "scorer_id" field in form.
    $('#betScorers li').on('click',function() {
        $('#enroporra_scorer').val($(this).data('player_id'));
    });

    // Bet 2 dropdown exclusive: filling "referee_id" field in form.
    $('#betReferees li').on('click',function() {
        $('#enroporra_referee').val($(this).data('referee_id'));
    });


});