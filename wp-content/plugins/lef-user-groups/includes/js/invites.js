jQuery(document).ready(function($) {
    $('.lef-accept-invite, .lef-decline-invite').on('click', function(e) {
        e.preventDefault();
        
        let groupID = $(this).data('group-id');
        let actionType = $(this).hasClass('lef-accept-invite') ? 'accept' : 'decline';
        let button = $(this);

        console.log("clicked");

        $.ajax({
            url: typeof ajaxurl !== 'undefined' ? ajaxurl : lefDeleteData.ajax_url,
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
});
