var id = '';
var spot = 'stand';
var interval;
var inProgress = false;

var socket = io.connect('/');
socket.on('onconnected', function( data ) {
    id = data.id;
});

function Clicked(move,amount)
{
    $("#controls").css('visibility','hidden');
    $('#amount').val("");

    $.ajax({
        type: "POST",
        url: "http://games.igaming.loc:9509",
        data: { action: 'clicked' , id : id , spot : spot , move: move , amount: amount }
    });
}

function Next()
{
    if(!inProgress)
    {
        inProgress = true;
        $.ajax({
            type: "POST",
            url: "http://games.igaming.loc:9509",
            data: { action: 'next' , id : id , spot : spot },
            dataType: "json",
            success: function(data)
            {
                if(data['cards'] != null)
                {
                    clearInterval(interval);

                    if(data['cards']['hand'] != null)
                    {
                        FillDeck();

                        $(".cards").each(function(){
                            $(this).empty();
                        });

                        $(".player").each(function(){
                            $(this).empty();
                        });

                        $('#dealer').empty();

                        $('#message').empty();

                        if(data['dealer'] != null)
                        {
                            if($('#dealer').is(':empty'))
                            {
                                $("#dealer").text(data['dealer']);
                            }
                        }

                        if($("#" + spot).is(':empty'))
                        {
                            var hand1 = MakeCard(data['cards']['hand'][0]);
                            var hand2 = MakeCard(data['cards']['hand'][1]);

                            for(var i = 0; i < data['ingame'].length; i++)
                            {
                                if(data['ingame'][i] == spot)
                                {
                                    DealCard(spot,hand1);
                                    DealCard(spot,hand2);
                                }
                                else
                                {
                                    DealCard(data['ingame'][i]);
                                    DealCard(data['ingame'][i]);
                                }
                            }
                        }
                    }

                    if(data['cards']['flop'] != null && $("#flop").is(':empty'))
                    {
                        var flop1 = MakeCard(data['cards']['flop'][0]);
                        var flop2 = MakeCard(data['cards']['flop'][1]);
                        var flop3 = MakeCard(data['cards']['flop'][2]);
                        DealCard('flop',flop1);
                        DealCard('flop',flop2);
                        DealCard('flop',flop3);
                    }

                    if(data['cards']['turn'] != null && $("#turn").is(':empty'))
                    {
                        var turn = MakeCard(data['cards']['turn']);
                        DealCard('turn',turn);
                    }

                    if(data['cards']['river'] != null && $("#river").is(':empty'))
                    {
                        var river = MakeCard(data['cards']['river']);
                        DealCard('river',river);
                    }
                }

                if(data['player_turn'] != null)
                {
                    if($('#current').text() != data['player_turn'])
                    {
                        $('#current').text(data['player_turn']);
                    }

                    if(spot == data['player_turn'])
                    {
                        $("#controls").css('visibility','visible');
                    }
                    else
                    {
                        $("#controls").css('visibility','hidden');
                    }
                }

                if(data['chips'] != null)
                {
                    $("#chips").text(data['chips']);
                }

                if(data['bet'] != null)
                {
                    $("#bet").text(data['bet']);
                }

                if(data['maxbet'] != null)
                {
                    $('#high_bet').text(data['maxbet']);

                    if(data['maxbet'] > data['bet'])
                    {
                        var diff = data['maxbet'] - data['bet'];
                        $('#call').prop('value','Call(' + diff + ')');
                    }
                    else
                    {
                        $('#call').prop('value','Check');
                    }
                }

                if(data['winner'] != null)
                {
                    $("#" + spot + "_controls").css('visibility','hidden');
                    setTimeout(function(){
                        interval = setInterval(Request,3000,'restart');
                    },2000);
                }

                if(data['pot'] != null)
                {
                    $('#pot').text(data['pot']);
                }

                inProgress = false;
            }
        });
    }
}

function FreeSpots()
{
    if(!inProgress)
    {
        inProgress = true;
        $.ajax({
            type: "POST",
            url: "http://games.igaming.loc:9509",
            data: { action: 'free_spots' , id : id },
            dataType: "json",
            success: function(data)
            {
                if (data['free_spots'] != null)
                {
                    $('.spots').each(function(){
                        if($.inArray($(this).parent().attr('id'),data['free_spots']) == -1)
                        {
                            $(this).css('display','none');
                        }
                    });

                    if(spot == 'stand')
                    {
                        for(var i = 0; i<data['free_spots'].length; i++)
                        {
                            $('#' + data['free_spots'][i]).children('.spots').css('display','block');
                        }
                    }
                }
                else
                {
                    $('#message').text('Game is full.');
                }
                inProgress = false;
            }
        });
    }
}

function Request(action)
{
    $.ajax({
        type: "POST",
        url: "http://games.igaming.loc:9509",
        data: { action: action , id : id , spot : spot },
        dataType: "json",
        success: function(data)
        {
            if(data['messages'] != null)
            {
                $('#message').text(data['messages']);
            }

            if(data['restart'] != null)
            {
                if(data['restart'] == 'another')
                {
                    Request('restart');
                }
            }
        }
    });
}

function MakeCard(card)
{
    var symbol = card[card.length -1];
    var path = "http://games.igaming.loc/images/";

    if(symbol == 'Q')
    {
        path += "clubs/";
    }
    else if(symbol == 'C')
    {
        path += "hearts/";
    }
    else if(symbol == 'F')
    {
        path += "spades/";
    }
    else if(symbol == 'P')
    {
        path += "diamonds/";
    }

    card = card.slice(0,-1);
    path += card + ".png";
    card = "<div class='back'><img class='deck_card' src='" + path + "' height='90' width='60' /></div>";
    return card;
}

function DealCard(location,back)
{
    var current = $('#deck').children('.flip-container').first();
    $(current).remove();

    $('#poker').append(current);
    $(current).css('top','0px');
    $(current).css('left','0px');

    var prependToDiv = $('#' + location);

    if(typeof back != 'undefined')
    {
        $(current).children('.flipper').append(back);
    }

    var top = prependToDiv.css('top');
    top = top.slice(0,-2);

    var left = prependToDiv.css('left');
    left = left.slice(0,-2);

    current.animate({
        top: top + "px",
        left: left + "px"
    }, 200, function() {
        current.appendTo(prependToDiv).css({
            top: 'auto',
            left: 'auto',
            position:'relative'
        });
    });

    if(typeof back != 'undefined')
    {
        setTimeout(function(){
            $(current).children('.flipper').css('transform','rotateY(180deg)');
        },500);
    }
}

function FillDeck()
{
    var card = '<div class="flip-container"><div class="flipper"><div class="front"><img class="deck_card" src="http://games.igaming.loc/images/back.jpg" height="90" width="60" /></div></div></div>';

    while($('#deck').children().size() < 52)
    {
        $('#deck').append(card);
    }
}

$(document).ready(function(){
    interval = setInterval(FreeSpots,500);
    FillDeck();
});

$(document).on('click','.spots',function(){
    clearInterval(interval);
    spot = $(this).parent().attr('id');
    $('.player').each(function(){
        $(this).empty();
    });
    $('#identity').text(spot);
    Request('user_join');
    Request('start');
    setInterval(Next,1000);
});