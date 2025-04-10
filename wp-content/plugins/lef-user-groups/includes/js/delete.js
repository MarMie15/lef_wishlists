jQuery(document).ready(function($) {

    //to check if a delete button exists for styling purposes
    document.querySelectorAll('.lef-list-item, .lef-wishlist-item').forEach(function(item) {
        if (item.querySelector('.lef-delete-button')) {
            // If no delete button exists, add the "has-delete-button" class
            item.classList.add('has-delete-button');
        } else {
            // If the delete button exists, remove the "has-delete-button" class
            item.classList.remove('has-delete-button');
        }
    });

    //deletes the selected item on click based on the given data
    $(".lef-delete-button, .lef-delete-group-button" ).on("click", function() {
        let deleteType = $(this).data('type');
        let itemID = $(this).data('id') || null;
        let groupID = $(this).data('group-id') || null;
        let userID = $(this).data('user-id') || null;
        let wishlistID = $(this).data('wishlist-id') || null;
        let productID = $(this).data('product-id') || null;
        let userEmail = $(this).data('user-email') || null;

        if (!confirm("Are you sure you want to delete this item?")) return;

        let requestData = {
            action: 'lef_delete_item',
            delete_type: deleteType,
            item_id: itemID,
        };

        if (groupID) requestData.group_id = groupID;
        if (userID) requestData.user_id = userID;
        if (wishlistID) requestData.wishlist_id = wishlistID;
        if (productID) requestData.product_id = productID;
        if (userEmail) requestData.user_email = userEmail;

        $.ajax({
            type: 'POST',
            url: lefDeleteData.ajax_url,
            data: requestData,
            success: function(response) {  
                if (response.success) {
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;  
                    } else {
                        location.reload(); // Only reload if no redirect
                    }
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert("An error occurred. Please try again.");
            }
        });
    });
});
