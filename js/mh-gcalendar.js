jQuery(document).ready(function() {
    var ev = eval(jQuery("div.eventJson").attr("data"));
    var defaultDate = jQuery("div.eventJson").attr("defaultDate");
    jQuery('#cancebtn').on('click', function () {
        var form = jQuery("#eventform");
        form.validate().resetForm(); // clear out the validation errors
        form[0].reset(); // clear out the form data
        jQuery('#eventContent').dialog('close');
        jQuery(".error").removeClass("error");
    });
    jQuery('#calendar').fullCalendar({
        height: 200,
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        defaultDate: defaultDate,
        selectable: false,
        selectHelper: false,
        select: function(start, end) {
            var title = prompt('Event Title:');
            var eventData;
            if (title) {
                eventData = {
                    title: title,
                    start: start,
                    end: end
                };
                jQuery('#calendar').fullCalendar('renderEvent', eventData, true);
            }
            jQuery('#calendar').fullCalendar('unselect');
        },
        editable: false,
        eventLimit: false,
        events: ev,
        eventRender: function (event, element) {
            if (event.busy == 1) {
                element.addClass('busy');
            } else {
                element.attr('href', 'javascript:void(0);');
                element.click(function () {
                    jQuery("#start_date").val(moment(event.start).format('YYYY-MM-DD h:mm:ss'));
                    jQuery("#end_date").val(moment(event.end).format('YYYY-MM-DD h:mm:ss'));
                    /*jQuery("#start_date").val(event.start);
                    jQuery("#end_date").val(event.end);*/
                    jQuery("#eventInfo").html(event.description);
                    jQuery("#eventLink").attr('href', event.url);
                    jQuery('#eventform').validate({
                        submitHandler: function(form) {
                            jQuery("#eventform").fadeOut(200);
                            jQuery('#eventContent .contact-loading').fadeIn(200);
                                jQuery.ajax({
                                    type: "POST",
                                    url:  my_ajax_object.ajax_url,
                                    data : {
                                        'frm_data':jQuery( "#eventform" ).serialize(),
                                        'action': 'add_event_action'
                                    },
                                    success: function( data ) {
                                        jQuery('#eventContent .contact-loading').fadeOut(200, function () {
                                            jQuery('#eventContent .success_msg').html('Thanks for contacting us! We will get in touch with you shortly.');
                                        });
                                        jQuery('#eventform').trigger( 'reset' );
                                    },
                                    error: function( data ) {
                                        jQuery('#eventContent .contact-loading').fadeOut(200, function () {
                                            jQuery('#eventContent').html('Oops Something Went Wrong , Please try again!!');
                                        });
                                    }
                                });
                        },
                        invalidHandler: function() { // optional callback
                            // fires on button click when form is not valid
                        }
                    });
                    jQuery("#eventContent").dialog({
                        modal: true,
                        title: 'Book appointment for '+moment(event.start).format('MMM Do h:mm A'),
                        width: 450
                    });
                    jQuery('#eventContent').on('dialogclose', function(event) {
                        jQuery("#eventform").fadeIn(200);
                        jQuery('#eventContent .success_msg').html('');
                    });
                });
            }
        }
    });
});