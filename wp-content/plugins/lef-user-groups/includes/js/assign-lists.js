jQuery(document).ready(function($) {
    const assignListButton = document.getElementById("lef-assign-lists-button");

    if (assignListButton) {
        assignListButton.addEventListener('click', function() {
            // Get data attributes from the button
            const usersWishLists = parseInt(assignListButton.getAttribute('data-users-wishlists'));
            const totalUsers = parseInt(assignListButton.getAttribute('data-total-users'));
 
            // if there are more users than wishlists to assign them to, display a message to the user that there are more users than wishlists to assign them to
            if (usersWishLists < totalUsers) {
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
                // Get the group ID from the button's data attribute
                const groupId = assignListButton.getAttribute('data-group-id');
                
                // Create confirmation modal
                const modal = document.createElement('div');
                modal.classList.add('lef-modal');
                
                const modalContent = document.createElement('div'); 
                modalContent.classList.add('lef-modal-content');
                
                // Add message
                modalContent.innerHTML = `
                    <p>Are you sure you want to randomly assign wishlists?</p>
                    <div class="lef-modal-actions">
                        <button id="lef-assign-confirm" class="lef-list-item" style="background-color: #4CAF50; color: white;">Yes</button>
                        <button id="lef-assign-cancel" class="lef-list-item lef-cancel-btn">No</button>
                    </div>
                `;
                
                modal.appendChild(modalContent);
                document.body.appendChild(modal);

                // Add button handlers
                document.getElementById('lef-assign-confirm').addEventListener('click', function() {
                    // Get the group ID from the button's data attribute
                    const groupId = assignListButton.getAttribute('data-group-id');
                    
                    // Make AJAX call to get actual wishlists and users
                    $.ajax({
                        url: lefAssignListsData.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'lef_get_group_wishlists_and_users',
                            group_id: groupId,
                            security: lefAssignListsData.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                const wishlists = response.data.wishlists;
                                const users = response.data.users;
                                
                                // Validate we have enough users and wishlists
                                if (wishlists.length === 0) {
                                    $('<div class="lef-notice lef-notice-error"><p>No wishlists found in this group.</p></div>')
                                        .appendTo('body')
                                        .delay(3000)
                                        .fadeOut(400, function() { $(this).remove(); });
                                    modal.remove();
                                    return;
                                }

                                if (users.length < 2) {
                                    $('<div class="lef-notice lef-notice-error"><p>Need at least 2 users to assign wishlists.</p></div>')
                                        .appendTo('body')
                                        .delay(3000)
                                        .fadeOut(400, function() { $(this).remove(); });
                                    modal.remove();
                                    return;
                                }
                                
                                // Log assignments to console
                                console.log('Wishlist Assignments:');
                                
                                // Create a mapping of user IDs to their wishlists
                                const userWishlists = {};
                                wishlists.forEach(list => {
                                    userWishlists[list.owner_id] = list;
                                });

                                // Create arrays of user IDs and wishlist IDs
                                const userIds = users.map(user => user.id);
                                const wishlistIds = wishlists.map(list => list.id);

                                // Create a mapping of user IDs to their display names
                                const userNames = {};
                                users.forEach(user => {
                                    userNames[user.id] = user.display_name;
                                });

                                // Function to shuffle an array
                                function shuffleArray(array) {
                                    for (let i = array.length - 1; i > 0; i--) {
                                        const j = Math.floor(Math.random() * (i + 1));
                                        [array[i], array[j]] = [array[j], array[i]];
                                    }
                                    return array;
                                }

                                // Create assignments ensuring no one gets their own list
                                const assignments = {};
                                let attempts = 0;
                                const maxAttempts = 100; // Prevent infinite loops

                                while (attempts < maxAttempts) {
                                    // Reset assignments
                                    Object.keys(assignments).forEach(key => delete assignments[key]);
                                    
                                    // Create a copy of wishlist IDs and shuffle them
                                    let availableWishlists = shuffleArray([...wishlistIds]);
                                    
                                    // Try to assign each user a list
                                    let validAssignment = true;
                                    for (let i = 0; i < userIds.length; i++) {
                                        const userId = userIds[i];
                                        const userWishlist = userWishlists[userId];
                                        
                                        // Find a wishlist that isn't the user's own
                                        let assigned = false;
                                        for (let j = 0; j < availableWishlists.length; j++) {
                                            const wishlistId = availableWishlists[j];
                                            const wishlist = wishlists.find(l => l.id === wishlistId);
                                            
                                            if (wishlist.owner_id !== userId) {
                                                assignments[wishlistId] = {
                                                    listTitle: wishlist.title,
                                                    owner: wishlist.owner_name,
                                                    assignedTo: userNames[userId],
                                                    assigned_to_id: userId
                                                };
                                                availableWishlists.splice(j, 1);
                                                assigned = true;
                                                break;
                                            }
                                        }
                                        
                                        if (!assigned) {
                                            validAssignment = false;
                                            break;
                                        }
                                    }
                                    
                                    if (validAssignment) {
                                        break;
                                    }
                                    
                                    attempts++;
                                }

                                if (attempts >= maxAttempts) {
                                    $('<div class="lef-notice lef-notice-error"><p>Could not create valid assignments. Please try again.</p></div>')
                                        .appendTo('body')
                                        .delay(3000)
                                        .fadeOut(400, function() { $(this).remove(); });
                                    modal.remove();
                                    return;
                                }

                                // Log the assignments
                                Object.values(assignments).forEach(assignment => {
                                    console.log(`${assignment.assignedTo} was assigned ${assignment.listTitle} from ${assignment.owner}`);
                                });
                                
                                // Save assignments to database
                                $.ajax({
                                    url: lefAssignListsData.ajax_url,
                                    type: 'POST',
                                    data: {
                                        action: 'lef_save_wishlist_assignments',
                                        group_id: groupId,
                                        assignments: assignments,
                                        security: lefAssignListsData.nonce
                                    },
                                    success: function(saveResponse) {
                                        if (saveResponse.success) {
                                            // Show success message
                                            $('<div class="lef-notice lef-notice-success"><p>Wishlists assigned successfully!</p></div>')
                                                .appendTo('body')
                                                .delay(3000)
                                                .fadeOut(400, function() { $(this).remove(); });
                                        } else {
                                            // Show error message
                                            $('<div class="lef-notice lef-notice-error"><p>Failed to save assignments: ' + saveResponse.data.message + '</p></div>')
                                                .appendTo('body')
                                                .delay(3000)
                                                .fadeOut(400, function() { $(this).remove(); });
                                        }
                                        modal.remove();
                                    },
                                    error: function(xhr, status, error) {
                                        // Show error message
                                        $('<div class="lef-notice lef-notice-error"><p>An error occurred while saving assignments: ' + error + '</p></div>')
                                            .appendTo('body')
                                            .delay(3000)
                                            .fadeOut(400, function() { $(this).remove(); });
                                        modal.remove();
                                    }
                                });
                            } else {
                                // Show error message
                                $('<div class="lef-notice lef-notice-error"><p>Failed to get wishlists and users: ' + response.data.message + '</p></div>')
                                    .appendTo('body')
                                    .delay(3000)
                                    .fadeOut(400, function() { $(this).remove(); });
                            }
                            modal.remove();
                        },
                        error: function(xhr, status, error) {
                            // Show error message
                            $('<div class="lef-notice lef-notice-error"><p>An error occurred while getting wishlists and users: ' + error + '</p></div>')
                                .appendTo('body')
                                .delay(3000)
                                .fadeOut(400, function() { $(this).remove(); });
                            modal.remove();
                        }
                    });
                });

                document.getElementById('lef-assign-cancel').addEventListener('click', function() {
                    modal.remove();
                });

                // Close modal when clicking outside
                modal.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        modal.remove();
                    }
                });
            }
        });
    }
});