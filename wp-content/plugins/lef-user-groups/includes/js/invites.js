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
                        alert("Invite accepted");
                    }
                } else {
                    alert(response);
                }
                location.reload();
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
            alert("Error: Missing email or group information.");
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
                    alert("Invite sent successfully!");
                } else {
                    alert(response.data); // Show the returned error message
                }
                emailInput.val("").prop('disabled', false);
                location.reload();
            },
            error: function(error) {
                console.error("AJAX Error:", error);
                emailInput.prop('disabled', false);
            }
        });        
    });
});
