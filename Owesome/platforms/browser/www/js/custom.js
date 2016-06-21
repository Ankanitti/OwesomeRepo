/////////// SPLASHSCREEN ///////////

// defining global Variables
var url = "http://kevin.chalier.io/owesome_api/web/api",
    profileId = "",
    currentTotal = 0,
    transitioningSum = 0,
    eventId= "";


$(function() {
    setTimeout(hideSplash, 2000);
});

function hideSplash() {
    $.mobile.changePage("#homepage", "fade");
}

// LOGIN AJAX
$("#homepage__login-form").on('submit', function(e) {
    e.preventDefault();
    var form = $(this),
        data = form.serialize();
    $.ajax({
        type: "POST",
        data: data,
        url: url+"/users",
        dataType: "json",
        success: function(_data) {
            window.localStorage.setItem("profile_id", _data.id.id);
            profileId = window.localStorage.getItem("profile_id");
            $.mobile.navigate('#hub');
        }
    });
});

// MY EVENTS
$('#hub').on('click', '#hub__content-events', function(e){
    e.preventDefault();
    console.log(profileId);

    $.ajax({
        type: "GET",
        url: url+"/profiles/"+profileId,
        dataType: "json",
        success: function(data) {
            console.log(data);
            var organizedEvent = data[0].organizer;
            $('#my-events__content').empty();
            $.each(organizedEvent, function(i){
                $('#my-events__content')
                    .append('<div class="my-events__event" data-event-sum="'+organizedEvent[i].total+'" data-event-id="'+data[0].organizer[i].id+'"><div class="event_info"><h1 class="event_name">'+ data[0].organizer[i].name +'</h1><h2 class="event_date">'+ new Date(data[0].organizer[i].date.date).toDateString() +'</h2></div></div>')
            });
            $.mobile.navigate("#my-events", { transition : "slide"});
        }
    });
});

// SUBSCRIBE
$('#subscribe__sub-form').on('submit', function(e) {
    e.preventDefault();
    var form = $(this);
    var data = form.serialize();
    $.ajax({
        type: "POST",
        data: data,
        url: url+"/profiles",
        dataType: "json",
        success: function(_data) {
                $.mobile.navigate("#homepage"); // if success go to hub page
        }
    })
});

// CREATE EVENT
$('#new-event__create-form').on('submit', function(e) {
    e.preventDefault();
    var form = $(this);
    var eventName = form.find('#new-event__name').val(),
        eventDate = form.find('#new-event__date').val();
    console.log(eventName,eventDate);
    var data = form.serialize();
    $.ajax({
        type: "POST",
        data: data+"&organizer="+profileId,
        url: url+'/events',
        dataType: "json",
        success: function(_data) {
            $.ajax({
                type: "GET",
                url: url+'/profiles',
                dataType: 'json',
                success: function(_profiles) {
                    $.each(_profiles, function(i) {
                        if(_profiles[i].id != profileId) {
                            $('#invite__content')
                                .append('<div class="invite-profile" data-id="'+_profiles[i].id+'"><p>'+_profiles[i].user.username+'</p></div>');
                        }
                    });
                    console.log(_profiles);
                }
            });
            $('.invite__event-details')
                .append('<p class="event-details" data-event-id="'+_data[1]+'">'+eventName+' '+eventDate+'</p>');
            $.mobile.navigate('#invite');
            console.log(_data);
        }
    });
});

////////// ADD USER + TRANSACTION //////////
$('#invite').on('click', '.invite-profile', function(e) {
    e.preventDefault();
    var eventId = $('.event-details').data('event-id'),
        profileId = $(this).data('id');
    $(this).fadeOut();
    $.ajax({
        type: "POST",
        url: url+'/transactions',
        data: 'event='+eventId+'&profile='+profileId,
        dataType: 'json',
        success: function(_transaction) {
            console.log(_transaction);
        }
    })
});

///////// INVITE VALIDATE /////////
$('#invite__validate').on('click',function(e) {
    e.preventDefault();
    $.mobile.navigate('#hub');
});

///////// GOTO EVENT BILLING PAGE /////////////
$('#my-events').on('click','.my-events__event', function(e) {
    e.preventDefault();
    var eventId = $(this).data('event-id'),
        eventSet = $(this).data('event-sum');
    $('#bill').find('#bill__text').data('event-id', eventId);
    if(eventSet == '0') {
        $.mobile.navigate('#bill');
    } else {
        $.ajax({
            type: "GET",
            url: url+'/events/'+eventId,
            dataType: 'json',
            success: function(_data) {
                var transaction = _data[0].transactions;
                console.log(_data);
                $('#closing__content').empty();
                $.each(transaction, function(i){
                    if(transaction[i].settled == true) {
                        $('#closing').find('#closing__content')
                            .append('<div class="closing-profile locked" data-transaction-id="'+transaction[i].id+'"><div class="settled" style="display: block"></div>'+transaction[i].profile.user.username+'<div class="owed-sum">'+transaction[i].sum+'</div></div>');
                    } else {
                        $('#closing').find('#closing__content')
                            .append('<div class="closing-profile" data-transaction-id="'+transaction[i].id+'"><div class="not-settled"></div><div class="settled"></div>'+transaction[i].profile.user.username+'<div class="owed-sum">'+transaction[i].sum+'</div></div>');
                    }
                });
                $.mobile.navigate('#closing', "slide");
            }
        });
    }
});

///////// SUBMIT EVENT TOTAL /////////////
$('#bill__content-form').on('submit', function(e){
    e.preventDefault();
    var form = $(this),
        data = form.serialize(),
        event = $('#bill__text').data('event-id');
    currentTotal = parseInt($('#bill__montant').val());
    eventId = event;

    // add EVENT total
    $.ajax({
        type: "PUT",
        url: url+'/events/'+event,
        data: data+'&event='+event,
        dataType:'json',
        success: function(_data) {

        }
    });

    // get EVENT data
    $.ajax({
        type: "GET",
        url: url+'/events/'+event,
        dataType: 'json',
        success: function(_event) {
            var transaction = _event[0].transactions;
            $('#sharing__content').empty();
            $('.sharing__event-details').empty();
            $('#sharing').find('.sharing__event-details')
                .append('<p class="event-details">'+_event[0].name+'</p>');
            $.each(transaction, function(i) {
                $('#sharing').find('#sharing__content')
                    .append('<div class="sharing-profile" data-transaction-id="'+transaction[i].id+'"><div class="lockit"></div><div class="lockitlock"></div>'+transaction[i].profile.user.username+'<div class="plus-sum off"></div><div class="owed-sum">'+Math.ceil(currentTotal/transaction.length)+'</div><div class="minus-sum"></div></div>');
            });
            $.mobile.navigate('#sharing');
        }
    });

});

// GESTION DES MONTANTS

$('#sharing').on('click', '.plus-sum', function(e) {
    if($(this).hasClass('off')) {

    } else {
        e.preventDefault();
        var currentSum = parseInt($(this).closest('.sharing-profile').find('.owed-sum').html());
        currentSum = currentSum + 1;
        transitioningSum-=1;
        $(this).closest('.sharing-profile').find('.owed-sum').html(currentSum);
        if(transitioningSum == 0) {
            $('.plus-sum').addClass('off');
        }
        if(currentSum > 0) {
            $(this).find('.sharing-profile').find('.minus-sum').removeClass('off');
        }
        console.log(currentSum, transitioningSum);
    }
});

$('#sharing').on('click', '.minus-sum', function(e) {
    if($(this).hasClass('off')) {

    } else {
        e.preventDefault();
        var currentSum = parseInt($(this).closest('.sharing-profile').find('.owed-sum').html());
        currentSum = currentSum - 1;
        transitioningSum++;
        $(this).closest('.sharing-profile').find('.owed-sum').html(currentSum);
        if(transitioningSum > 0) {
            $('.plus-sum').removeClass('off');
        }
        if (currentSum == 0) {
            $(this).addClass('off');
        }
        console.log(currentSum, transitioningSum);
    }
});

$('#sharing').on('click', '.lockit', function(e){
    e.preventDefault();
    var targeted = $(this).closest('.sharing-profile'),
        tId = targeted.data('transaction-id'),
        valSum = parseInt(targeted.closest('.sharing-profile').find('.owed-sum').html());
    $.ajax({
        type: "PUT",
        url: url+'/transactions/'+tId,
        data: 'sum='+valSum+'&transaction='+tId,
        dataType: 'json',
        success: function() {
            targeted.find('.lockit').remove();
            targeted.find('.lockitlock').css('display','block');
            targeted.addClass('locked');
            targeted.find('.plus-sum').remove();
            targeted.find('.minus-sum').remove();
            console.log('Transaction sum updated to '+valSum);
            if($('#sharing').find('.lockit').length == '') {
                $('#sharing__validate').fadeIn();
            }
        }
    });
});


// VALIDATE SHARING PAGE
$('#sharing').on('click', '#sharing__validate', function(e) {
    e.preventDefault();
    $.ajax({
        type: "GET",
        url: url+'/events/'+eventId,
        dataType: 'json',
        success: function(_data) {
            var transaction = _data[0].transactions;
            $('#closing').find('#closing__content').empty();
            $.each(transaction, function(i){
                $('#closing').find('#closing__content')
                    .append('<div class="closing-profile" data-transaction-id="'+transaction[i].id+'"><div class="not-settled"></div><div class="settled"></div>'+transaction[i].profile.user.username+'<div class="owed-sum">'+transaction[i].sum+'</div></div>');
            });
            $.mobile.navigate('#closing');
        }
    })
});

$('#closing').on('click', '.not-settled', function(e) {
    e.preventDefault();
    var targeted = $(this).closest('.closing-profile'),
        tId= targeted.data('transaction-id'),
        sum = parseInt(targeted.find('.owed-sum').html());
    $.ajax({
        type: "PUT",
        url: url+'/transactions/'+tId,
        data: 'sum='+sum+'&settled=1&transaction='+tId,
        dataType: 'json',
        success: function() {
            targeted.find('.not-settled').remove();
            targeted.find('.settled').css('display','block');
            targeted.addClass('locked');
            if($('#closing').find('.not-settled').length == '') {
                $('#closing__validate').fadeIn();
            }
        }
    })
});

$('#closing').on('click','#closing__validate', function() {
    $.mobile.navigate("#hub");
});