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