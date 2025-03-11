jQuery(document).ready(function($) {
    $(".lef-delete-button").on("click", function() {
        let itemID = $(this).data('id');
        let deleteType = $(this).data('type');
        let groupID = $(this).data('group-id') || null;
        let userID = $(this).data('user-id') || null;
        let wishlistID = $(this).data('wishlist-id') || null;
        let productID = $(this).data('product-id') || null;

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

        $.ajax({
            type: 'POST',
            url: typeof ajaxurl !== 'undefined' ? ajaxurl : lefDeleteData.ajax_url,
            data: requestData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
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
