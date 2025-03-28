var lefGroupWishlist = (function() {
    var groupId;

    function init(gId) {
        groupId = gId;
        setupEventListeners();
        fetchWishlists(""); // Load all wishlists initially
    }

    function setupEventListeners() {
        let input = document.getElementById('group-wishlist-input');
        let dropdown = document.getElementById('group-wishlist-dropdown');
    
        input.addEventListener('focus', function() {
            fetchWishlists(""); // Show all options when clicked
            dropdown.style.display = 'block';
        });

        document.addEventListener('click', function(event) {
            if (!input.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });

        input.addEventListener('input', function() {
            fetchWishlists(input.value);
        });

        dropdown.addEventListener('click', function(e) {
            if (e.target.tagName === 'LI') {
                addWishlistToGroup(e.target.dataset.id);
                dropdown.style.display = 'none';
                input.value = ''; 
            }
        });
    }

    function fetchWishlists(searchTerm) {
        jQuery.ajax({
            url: lefWishlistData.ajax_url,
            type: 'POST',
            data: {
                action: 'lef_get_user_wishlists',
                search: searchTerm
            },
            success: function(response) {
                let dropdown = document.getElementById('group-wishlist-dropdown');
                dropdown.innerHTML = '';
    
                if (!response || response.length === 0) {
                    dropdown.style.display = 'none';
                    return;
                }
    
                response.forEach(function(wishlist) {
                    let item = document.createElement('li');
                    item.textContent = wishlist.title;
                    item.dataset.id = wishlist.id;
                    item.classList.add('lef-list-item');
                    dropdown.appendChild(item);
                });
            }
        });
    }    

    function addWishlistToGroup(wishlistID) {
        let message = document.getElementById("group-wishlist-message");
    
        if (!message) {
            console.error("group-wishlist-message element not found in the DOM.");
            return;
        }
        
        jQuery.ajax({
            url: lefWishlistData.ajax_url,
            type: "POST",
            data: {
                action: "lef_add_wishlist_to_group",
                wishlist_id: wishlistID,
                groepen_id: groupId
            },
            success: function(response) {
                if (response.success) {
                    message.textContent = `Your wishlist has been added to this group`;
                    message.style.display = "block";
                    
                    setTimeout(() => {
                        message.style.display = "none";
                        location.reload();
                    }, 1500);
                    
                } else {
                    alert("Failed to add wishlist. Please try again.");
                }
            }
        });
    }
    return { init };
})();
