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

        // Create modal elements
        const modal = document.createElement('div');
        modal.classList.add('lef-modal');
        
        const modalContent = document.createElement('div'); 
        modalContent.classList.add('lef-modal-content');
        
        // Add message
        modalContent.innerHTML = `
            <p>Are you sure you want to delete this item?</p>
            <div class="lef-modal-actions">
                <button id="lef-delete-confirm" class="lef-list-item" style="background-color: #4CAF50; color: white;">Yes</button>
                <button id="lef-delete-cancel" class="lef-list-item lef-cancel-btn">No</button>
            </div>
        `;
        
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        // Add button handlers
        document.getElementById('lef-delete-confirm').addEventListener('click', function() {
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
                url: lefDeleteData.ajax_url,
                type: 'POST',
                data: requestData,
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Failed to delete item. Please try again.');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
            modal.remove();
        });
        
        document.getElementById('lef-delete-cancel').addEventListener('click', function() {
            modal.remove();
        });
        
        // Close modal when clicking outside the content
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.remove();
            }
        });
    });
});
