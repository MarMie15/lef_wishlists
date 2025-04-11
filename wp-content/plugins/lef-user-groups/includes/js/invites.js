jQuery(document).ready(function($) {
    $('.lef-accept-invite, .lef-decline-invite').on('click', function(e) {
        e.preventDefault();
        
        let groupID = $(this).data('group-id');
        let actionType = $(this).hasClass('lef-accept-invite') ? 'accept' : 'decline';
        let button = $(this);

        console.log("clicked");

        $.ajax({
            url: lefDeleteData.ajax_url,
            type: 'POST',
            data: {
                action: 'lef_handle_invite_action',
                group_id: groupID,
                action_type: actionType
            },
            success: function(response) {
                if (response.success) {
                    button.parent().fadeOut();
                    if(actionType == 'accept'){
                        $('<div class="lef-notice lef-notice-success"><p>Invite accepted successfully.</p></div>')
                            .appendTo('body')
                            .delay(3000)
                            .fadeOut(400, function() { $(this).remove(); });
                    }
                } else {
                    $('<div class="lef-notice lef-notice-error"><p>' + (response.data || 'An error occurred. Please try again.') + '</p></div>')
                        .appendTo('body')
                        .delay(3000)
                        .fadeOut(400, function() { $(this).remove(); });
                }
                setTimeout(function() {
                    location.reload();
                }, 3000);
            }
        });
    });

    
    // Invite user logic
    $('#lef_invite_user').on('submit', function(e) {
        e.preventDefault();
    
        let form = $(this);
        let emailInput = form.find('.lef_invite-user-input');
        let email = emailInput.val().trim();
        let groupID = form.data('group-id'); 
    
        if (!email || !groupID) {
            $('<div class="lef-notice lef-notice-error"><p>Error: Missing email or group information.</p></div>')
                .appendTo('body')
                .delay(3000)
                .fadeOut(400, function() { $(this).remove(); });
            return;
        }
    
        $.ajax({
            url: lefDeleteData.ajax_url,
            type: 'POST',
            data: {
                action: 'lef_send_invite',
                email: email,
                group_id: groupID
            },
            beforeSend: function() {
                emailInput.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $('<div class="lef-notice lef-notice-success"><p>Invite sent successfully!</p></div>')
                        .appendTo('body')
                        .delay(3000)
                        .fadeOut(400, function() { $(this).remove(); });
                        setTimeout(function() {
                            location.reload();
                        }, 2500);
                } else {
                    $('<div class="lef-notice lef-notice-error"><p>' + (response.data || 'Failed to send invite. Please try again.') + '</p></div>')
                        .appendTo('body')
                        .delay(3000)
                        .fadeOut(400, function() { $(this).remove(); });
                }
                emailInput.val("").prop('disabled', false);
            },
            error: function(error) {
                console.error("AJAX Error:", error);
                $('<div class="lef-notice lef-notice-error"><p>An error occurred. Please try again.</p></div>')
                    .appendTo('body')
                    .delay(3000)
                    .fadeOut(400, function() { $(this).remove(); });
                emailInput.prop('disabled', false);
            }
        });        
    });
});
