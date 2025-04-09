jQuery(document).ready(function($) {
    // Only initialize when we're on a group page with the user list
    if (!$('.lef-group-users').length) return;
    
    let selectMode = false;
    let selectedUserId = null;
    const groupId = $('#lef_invite_user').data('group-id');
    const addOwnerBtn = $("#lef-add-owner-btn");
    const userItems = $(".lef-regular-user");
    const modalConfirm = $("#lef-confirm-modal");
    const confirmYes = $("#lef-confirm-yes");
    const confirmNo = $("#lef-confirm-no");
    
    // Initialize event listeners
    addOwnerBtn.on("click", toggleSelectMode);
    
    function toggleSelectMode() {
        selectMode = !selectMode;
        
        if (selectMode) {
            // Enable selection mode
            addOwnerBtn.text("Cancel").addClass("lef-cancel-btn");
            $(".lef-group-users h3, .lef-form-item, .lef-owner-user, h3:contains('Pending invites'), h3 + ul").addClass("lef-dimmed");
            userItems.addClass("lef-highlight");
            
            // Add click handlers to non-owner users
            userItems.on("click", handleUserSelection);
        } else {
            // Disable selection mode
            resetUI();
        }
    }
    
    function handleUserSelection() {
        selectedUserId = $(this).data("user-id");
        modalConfirm.show();
    }
    
    function resetUI() {
        // Reset all UI elements
        addOwnerBtn.text("Add owner").removeClass("lef-cancel-btn");
        $(".lef-dimmed").removeClass("lef-dimmed");
        userItems.removeClass("lef-highlight").off("click");
        selectedUserId = null;
        selectMode = false;
    }
    
    // Handle confirmation dialog
    confirmYes.on("click", function() {
        if (selectedUserId) {
            promoteToOwner(selectedUserId);
        }
        modalConfirm.hide();
    });
    
    confirmNo.on("click", function() {
        modalConfirm.hide();
        resetUI(); //resets the UI to before, delete to allow a user to select again when they cancel
    });
    
    function promoteToOwner(userId) {
        $.ajax({
            url: lefWishlistData.ajax_url,
            type: "POST",
            data: {
                action: "lef_promote_to_owner",
                user_id: userId,
                group_id: groupId,
                security: lefOwnerData.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Refresh the page to show updated owners
                    location.reload();
                } else {
                    console.error("Error promoting user:", response.data.message);
                    alert("Error: " + response.data.message);
                    resetUI();
                }
            },
            error: function() {
                alert("An error occurred. Please try again.");
                resetUI();
            }
        });
    }
});