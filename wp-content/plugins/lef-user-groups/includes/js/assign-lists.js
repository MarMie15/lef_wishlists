jQuery(document).ready(function($) {
    const assignListButton = document.getElementById("lef-assign-lists-button");

    if (assignListButton) {
        assignListButton.addEventListener('click', function() {
            // Get data attributes from the button
            const usersWishLists = parseInt(assignListButton.getAttribute('data-users-wishlists'));
            const totalUsers = parseInt(assignListButton.getAttribute('data-total-users'));
            
            console.log('Wishlists:', usersWishLists);
            console.log('Total users:', totalUsers);
            
            if (usersWishLists < totalUsers) {
                console.log('There are more users than wishlists to assign them to');
                //display a message to the user that there are more users than wishlists to assign them to
                // Create modal elements
                const modal = document.createElement('div');
                modal.classList.add('lef-modal');
                
                const modalContent = document.createElement('div'); 
                modalContent.classList.add('lef-modal-content');
                
                // Add message
                modalContent.innerHTML = `
                    <p>There are more users than wishlists to assign them to.<br> Each user needs to create a wishlist before assignments can be made.</p>
                    <div class="lef-modal-actions">
                        <button id="lef-modal-close" class="lef-list-item">Close</button>
                    </div>
                `;
                
                modal.appendChild(modalContent);
                document.body.appendChild(modal);
                
                // Add close button handler
                document.getElementById('lef-modal-close').addEventListener('click', function() {
                    modal.remove();
                });
                
                // Close modal when clicking outside the content
                modal.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        modal.remove();
                    }
                });
            } else {
                console.log('Ready to assign wishlists to users');
                //take everyone's wishlists and assign them to a random user, excluding themselves
            }
        });
    }
});