jQuery(document).ready(function($) {
    
    $('#content-calendar-form').submit(function( event ) {
        
        if( ! $("#confirmation").is(':checked') ) {
            alert( "Please check confirmation box." );
            return false;
            event.preventDefault();
        }
        
        $('#cc-spiner').show();
        $("#cc-connection-message").removeClass("cc-error cc-success");
        $("#progressbar-container").show();
        $("#content-calendar-form").hide();
        $("#cc-connection-message").html('');
        
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: $("#content-calendar-form").serialize(),
            success: function(data) {                
                if( data.status ) {
                    
                    $("#cc-package").html(data.package);
                    
                    if( data.trial ) {
                        $("#days-remaining-in-trial").html( data.days_remaining_in_trial );
                    }
                    else {
                        $("#trial-date-info").hide();
                    }
                    
                    
                    second_activation_call();
                }
                else {
                    $("#cc-connection-message").addClass("cc-error");
                    $("#cc-connection-message").html( data.message );
                    $("#progressbar-container").hide();
                    $("#content-calendar-form").show();
                }
            }
        });
        
        event.preventDefault();
    }); 
    
    
    $('#content-calendar-form-deactivation').submit(function( event ) {
        
        if (! confirm('Are you sure you want to do this?')) {
            event.preventDefault();
            return;
        }
        
        
        $('#cc-spiner-deactivate').show();
        $('#cc-submit-deactivate').hide();

        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: $("#content-calendar-form-deactivation").serialize(),
            success: function( data ) {                
                if( data.status ) {
                    $('#cc-spiner-deactivate').hide();
                    $('#cc-submit-deactivate').show();
                    $("#cc-package-info-container").hide();
                    $("#cc-plugin-form-title").html("Connect your blog");
                    $("#progressbar-container").hide();
                    $("#content-calendar-form").show();
                    $("#disconnect-container").hide();
                    $(".progressbar-item").removeClass("active");
                }
            }
        });
        
        event.preventDefault();
    }); 
    
});

function second_activation_call() {
    
    jQuery("#progressbar-connecting").addClass("active");
    
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'activation_create_user',
            cc_activaction_nonce: jQuery('#cc_activaction_nonce').val()
        },
        success: function(data) {
            if( data.status ) {
                sync_categories();
            }
            else {
                jQuery("#cc-connection-message").addClass("cc-error");
                jQuery("#cc-connection-message").html( data.message );
                jQuery("#progressbar-container").hide();
                jQuery("#content-calendar-form").show();
            }
        }
    });
}


function sync_categories() {
    
    jQuery("#progressbar-categories").addClass("active");
    
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'activation_sync_categories',
            cc_activaction_nonce: jQuery('#cc_activaction_nonce').val()
        },
        success: function(data) {
            if( data.status ) {
                sync_users();
            }
            else {
                jQuery("#cc-connection-message").addClass("cc-error");
                jQuery("#cc-connection-message").html( data.message );
                jQuery("#progressbar-container").hide();
                jQuery("#content-calendar-form").show();
            }
        }
    });
    
}


function sync_users() {
    
    jQuery("#progressbar-users").addClass("active");
    
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'activation_sync_users',
            cc_activaction_nonce: jQuery('#cc_activaction_nonce').val()
        },
        success: function(data) {
            if( data.status ) {
                sync_posts();
            }
            else {
                jQuery("#cc-connection-message").addClass("cc-error");
                jQuery("#cc-connection-message").html( data.message );
                jQuery("#progressbar-container").hide();
                jQuery("#content-calendar-form").show();
            }
        }
    });
    
}



function sync_posts() {
    
    jQuery("#progressbar-posts").addClass("active");
    
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'activation_sync_posts',
            cc_activaction_nonce: jQuery('#cc_activaction_nonce').val()
        },
        success: function(data) {
            if( data.status ) {
                sync_completed();
            }
            else {
                jQuery("#cc-connection-message").addClass("cc-error");
                jQuery("#cc-connection-message").html( data.message );
                jQuery("#progressbar-container").hide();
                jQuery("#content-calendar-form").show();
            }
        }
    });  
}


function sync_completed() {
    jQuery("#content-calendar-form").hide();
    jQuery("#cc-plugin-form-title").html( "Your blog is connected" );
    jQuery('#progress-spinner-container').remove();
    jQuery("#cc-package-info-container").show();
    jQuery("#disconnect-container").show();
}

