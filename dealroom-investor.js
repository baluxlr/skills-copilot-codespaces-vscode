/**
 * DealRoom Investor Tools JavaScript
 * 
 * Handles investor tools functionality: watchlist, deal comparison, investment tracking.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Tab navigation
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            // Update active tab button
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Show the selected tab pane
            tabPanes.forEach(pane => {
                pane.classList.remove('active');
                pane.style.display = 'none';
            });
            document.getElementById(tabName).classList.add('active');
            document.getElementById(tabName).style.display = 'block';
        });
    });
    
    // Watchlist functionality
    initWatchlist();
    
    // Comparison functionality
    initComparison();
    
    // Investment tracking functionality
    initInvestmentTracking();
});

/**
 * Initialize watchlist functionality
 */
function initWatchlist() {
    // Filter and sort functionality
    const filterSelect = document.getElementById('watchlist-filter');
    const sortSelect = document.getElementById('watchlist-sort');
    
    if (filterSelect && sortSelect) {
        filterSelect.addEventListener('change', filterWatchlist);
        sortSelect.addEventListener('change', sortWatchlist);
    }
    
    // Toggle notes functionality
    const toggleNotesButtons = document.querySelectorAll('.toggle-notes');
    toggleNotesButtons.forEach(button => {
        button.addEventListener('click', function() {
            const dealId = this.getAttribute('data-id');
            const notesSection = document.getElementById(`notes-${dealId}`);
            if (notesSection.style.display === 'none' || !notesSection.style.display) {
                notesSection.style.display = 'block';
            } else {
                notesSection.style.display = 'none';
            }
        });
    });
    
    // Save notes functionality
    const saveNotesButtons = document.querySelectorAll('.save-notes');
    saveNotesButtons.forEach(button => {
        button.addEventListener('click', function() {
            const dealId = this.getAttribute('data-id');
            const notesTextarea = document.querySelector(`.deal-notes-content[data-id="${dealId}"]`);
            saveNotes(dealId, notesTextarea.value);
        });
    });
    
    // Remove from watchlist functionality
    const removeButtons = document.querySelectorAll('.remove-from-watchlist');
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const dealId = this.getAttribute('data-id');
            removeFromWatchlist(dealId);
        });
    });
    
    // Add to compare functionality
    const compareButtons = document.querySelectorAll('.add-to-compare');
    compareButtons.forEach(button => {
        button.addEventListener('click', function() {
            const dealId = this.getAttribute('data-id');
            const dealTitle = this.getAttribute('data-title');
            addToCompare(dealId, dealTitle);
        });
    });
    
    // View deal functionality
    const viewButtons = document.querySelectorAll('.view-deal');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const dealUrl = this.getAttribute('data-url');
            window.location.href = dealUrl;
        });
    });
}

/**
 * Filter watchlist items
 */
function filterWatchlist() {
    const sector = this.value;
    const deals = document.querySelectorAll('.watchlist-deal');
    
    deals.forEach(deal => {
        if (sector === 'all' || deal.getAttribute('data-sector') === sector) {
            deal.style.display = 'block';
        } else {
            deal.style.display = 'none';
        }
    });
}

/**
 * Sort watchlist items
 */
function sortWatchlist() {
    const sortMethod = this.value;
    const dealsContainer = document.querySelector('.watchlist-deals');
    const deals = Array.from(document.querySelectorAll('.watchlist-deal'));
    
    deals.sort((a, b) => {
        switch (sortMethod) {
            case 'date_desc':
                return new Date(b.getAttribute('data-date')) - new Date(a.getAttribute('data-date'));
            case 'date_asc':
                return new Date(a.getAttribute('data-date')) - new Date(b.getAttribute('data-date'));
            case 'funding_desc':
                return parseFloat(b.getAttribute('data-funding') || 0) - parseFloat(a.getAttribute('data-funding') || 0);
            case 'funding_asc':
                return parseFloat(a.getAttribute('data-funding') || 0) - parseFloat(b.getAttribute('data-funding') || 0);
            default:
                return 0;
        }
    });
    
    // Re-append the sorted items
    deals.forEach(deal => {
        dealsContainer.appendChild(deal);
    });
}

/**
 * Save notes for a deal
 */
function saveNotes(dealId, notes) {
    const saveButton = document.querySelector(`.save-notes[data-id="${dealId}"]`);
    const originalText = saveButton.textContent;
    
    // Show saving indicator
    saveButton.textContent = dealroomInvestor.i18n.saving;
    saveButton.disabled = true;
    
    const data = new FormData();
    data.append('action', 'dealroom_save_notes');
    data.append('nonce', dealroomInvestor.nonce);
    data.append('deal_id', dealId);
    data.append('notes', notes);
    
    fetch(dealroomInvestor.ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success indicator
            saveButton.textContent = dealroomInvestor.i18n.saved;
            
            // Reset button after a delay
            setTimeout(() => {
                saveButton.textContent = originalText;
                saveButton.disabled = false;
            }, 2000);
        } else {
            // Show error
            saveButton.textContent = dealroomInvestor.i18n.error;
            
            // Reset button after a delay
            setTimeout(() => {
                saveButton.textContent = originalText;
                saveButton.disabled = false;
            }, 2000);
            
            console.error('Error saving notes:', data.data.message);
        }
    })
    .catch(error => {
        // Show error
        saveButton.textContent = dealroomInvestor.i18n.error;
        
        // Reset button after a delay
        setTimeout(() => {
            saveButton.textContent = originalText;
            saveButton.disabled = false;
        }, 2000);
        
        console.error('Error saving notes:', error);
    });
}

/**
 * Remove a deal from the watchlist
 */
function removeFromWatchlist(dealId) {
    if (!confirm('Are you sure you want to remove this deal from your watchlist?')) {
        return;
    }
    
    const data = new FormData();
    data.append('action', 'dealroom_toggle_watchlist');
    data.append('nonce', dealroomInvestor.nonce);
    data.append('deal_id', dealId);
    
    fetch(dealroomInvestor.ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the deal from the UI
            const dealElement = document.querySelector(`.watchlist-deal[data-id="${dealId}"]`);
            if (dealElement) {
                dealElement.remove();
            }
            
            // Check if watchlist is now empty
            const watchlistDeals = document.querySelectorAll('.watchlist-deal');
            if (watchlistDeals.length === 0) {
                const watchlistContent = document.querySelector('.watchlist-content');
                watchlistContent.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <span class="dashicons dashicons-heart"></span>
                        </div>
                        <h3>Your watchlist is empty</h3>
                        <p>Add deals to your watchlist to track them here.</p>
                        <a href="/deals/" class="button button-primary">Explore Deals</a>
                    </div>
                `;
            }
        } else {
            console.error('Error removing from watchlist:', data.data.message);
        }
    })
    .catch(error => {
        console.error('Error removing from watchlist:', error);
    });
}

/**
 * Initialize comparison functionality
 */
function initComparison() {
    const selectedDealsList = document.getElementById('selected-deals-list');
    const startComparisonButton = document.getElementById('start-comparison');
    const clearSelectionButton = document.getElementById('clear-selection');
    const comparisonResults = document.getElementById('comparison-results');
    
    if (!selectedDealsList || !startComparisonButton || !clearSelectionButton || !comparisonResults) {
        return;
    }
    
    // Start comparison
    startComparisonButton.addEventListener('click', function() {
        const selectedDeals = Array.from(selectedDealsList.querySelectorAll('li:not(.empty-selection)'))
            .map(li => li.getAttribute('data-id'));
        
        if (selectedDeals.length < 2) {
            alert('Please select at least 2 deals to compare.');
            return;
        }
        
        compareDeal(selectedDeals);
    });
    
    // Clear selection
    clearSelectionButton.addEventListener('click', function() {
        selectedDealsList.innerHTML = '<li class="empty-selection">No deals selected. Add deals from your watchlist.</li>';
        startComparisonButton.disabled = true;
        clearSelectionButton.disabled = true;
        comparisonResults.innerHTML = '';
    });
}

/**
 * Add a deal to the comparison list
 */
function addToCompare(dealId, dealTitle) {
    const selectedDealsList = document.getElementById('selected-deals-list');
    const startComparisonButton = document.getElementById('start-comparison');
    const clearSelectionButton = document.getElementById('clear-selection');
    
    // Check if deal is already in the list
    if (selectedDealsList.querySelector(`li[data-id="${dealId}"]`)) {
        return;
    }
    
    // Remove empty selection message if present
    const emptySelection = selectedDealsList.querySelector('.empty-selection');
    if (emptySelection) {
        emptySelection.remove();
    }
    
    // Add deal to the list
    const listItem = document.createElement('li');
    listItem.setAttribute('data-id', dealId);
    listItem.innerHTML = `
        ${dealTitle}
        <button type="button" class="remove-from-comparison">
            <span class="dashicons dashicons-no"></span>
        </button>
    `;
    selectedDealsList.appendChild(listItem);
    
    // Add event listener to remove button
    listItem.querySelector('.remove-from-comparison').addEventListener('click', function() {
        listItem.remove();
        
        // Check if list is now empty
        if (selectedDealsList.children.length === 0) {
            selectedDealsList.innerHTML = '<li class="empty-selection">No deals selected. Add deals from your watchlist.</li>';
            startComparisonButton.disabled = true;
            clearSelectionButton.disabled = true;
        }
    });
    
    // Enable buttons
    startComparisonButton.disabled = false;
    clearSelectionButton.disabled = false;
    
    // Show the compare tab
    document.querySelector('.tab-button[data-tab="compare"]').click();
}

/**
 * Compare selected deals
 */
function compareDeal(dealIds) {
    const comparisonResults = document.getElementById('comparison-results');
    
    // Show loading indicator
    comparisonResults.innerHTML = `
        <div class="comparison-loading">
            <div class="spinner"></div>
            <p>${dealroomInvestor.i18n.comparing}</p>
        </div>
    `;
    
    const data = new FormData();
    data.append('action', 'dealroom_compare_deals');
    data.append('nonce', dealroomInvestor.nonce);
    dealIds.forEach(id => data.append('deal_ids[]', id));
    
    fetch(dealroomInvestor.ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Display comparison results
            comparisonResults.innerHTML = data.data.html;
        } else {
            comparisonResults.innerHTML = `
                <div class="comparison-error">
                    <p>${dealroomInvestor.i18n.error}: ${data.data.message}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error comparing deals:', error);
        comparisonResults.innerHTML = `
            <div class="comparison-error">
                <p>${dealroomInvestor.i18n.error}: ${error.message}</p>
            </div>
        `;
    });
}

/**
 * Initialize investment tracking functionality
 */
function initInvestmentTracking() {
    // Load investment data
    loadInvestmentData();
    
    // Set up event listeners
    const trackInvestmentForm = document.getElementById('track-investment-form');
    const cancelTrackingButton = document.getElementById('cancel-tracking');
    
    if (trackInvestmentForm) {
        trackInvestmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveInvestment();
        });
    }
    
    if (cancelTrackingButton) {
        cancelTrackingButton.addEventListener('click', function() {
            document.getElementById('investment-tracking-form').style.display = 'none';
        });
    }
    
    // Add tracking from watchlist
    document.querySelectorAll('.watchlist-deal').forEach(deal => {
        deal.addEventListener('dblclick', function() {
            const dealId = this.getAttribute('data-id');
            const dealTitle = this.querySelector('.deal-title').textContent.trim();
            openTrackingForm(dealId, dealTitle);
        });
    });
}

/**
 * Load investment tracking data
 */
function loadInvestmentData() {
    const data = new FormData();
    data.append('action', 'dealroom_get_watchlist');
    data.append('nonce', dealroomInvestor.nonce);
    
    fetch(dealroomInvestor.ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Process the watchlist data and investment status
            processInvestmentData(data.data.watchlist);
        } else {
            console.error('Error loading investment data:', data.data.message);
        }
    })
    .catch(error => {
        console.error('Error loading investment data:', error);
    });
}

/**
 * Process investment data
 */
function processInvestmentData(watchlist) {
    // Get tracking status counts and totals
    let totalTracking = 0;
    let totalPotential = 0;
    let totalCommitted = 0;
    
    // Count by stage
    const stageCounts = {
        interested: 0,
        researching: 0,
        diligence: 0,
        negotiating: 0,
        committed: 0,
        passed: 0
    };
    
    // Get all investment data from localStorage
    const investments = {};
    watchlist.forEach(deal => {
        const storedData = localStorage.getItem(`dealroom_investment_${deal.id}`);
        if (storedData) {
            const investment = JSON.parse(storedData);
            investments[deal.id] = investment;
            
            // Update counts
            totalTracking++;
            stageCounts[investment.status]++;
            
            // Update totals
            if (investment.amount) {
                totalPotential += parseFloat(investment.amount);
                if (investment.status === 'committed') {
                    totalCommitted += parseFloat(investment.amount);
                }
            }
        }
    });
    
    // Update the UI
    document.getElementById('active-tracking-count').textContent = totalTracking;
    document.getElementById('total-potential').textContent = '$' + formatNumber(totalPotential);
    document.getElementById('committed-amount').textContent = '$' + formatNumber(totalCommitted);
    
    // Update stage counts
    Object.keys(stageCounts).forEach(stage => {
        const stageElement = document.querySelector(`#stage-${stage} .stage-count`);
        if (stageElement) {
            stageElement.textContent = stageCounts[stage];
        }
    });
    
    // Populate pipeline stages
    populatePipelineStages(watchlist, investments);
}

/**
 * Populate pipeline stages with deals
 */
function populatePipelineStages(watchlist, investments) {
    // Clear existing deals
    document.querySelectorAll('.stage-deals').forEach(stage => {
        stage.innerHTML = '';
    });
    
    // Map of deals by ID for quick lookup
    const dealsMap = {};
    watchlist.forEach(deal => {
        dealsMap[deal.id] = deal;
    });
    
    // Add deals to stages
    Object.keys(investments).forEach(dealId => {
        const investment = investments[dealId];
        const deal = dealsMap[dealId];
        
        if (!deal) return;
        
        const stageElement = document.querySelector(`#stage-${investment.status} .stage-deals`);
        if (stageElement) {
            const dealElement = document.createElement('div');
            dealElement.className = 'pipeline-deal';
            dealElement.setAttribute('data-id', dealId);
            
            dealElement.innerHTML = `
                <div class="pipeline-deal-header">
                    <h4>${deal.title}</h4>
                    ${investment.amount ? `<span class="deal-amount">$${formatNumber(investment.amount)}</span>` : ''}
                </div>
                <div class="pipeline-deal-company">${deal.organization_name}</div>
                <div class="pipeline-deal-actions">
                    <button class="view-deal" data-url="${deal.permalink}">
                        <span class="dashicons dashicons-visibility"></span>
                    </button>
                    <button class="edit-tracking" data-id="${dealId}">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                </div>
            `;
            
            stageElement.appendChild(dealElement);
            
            // Add event listeners
            dealElement.querySelector('.view-deal').addEventListener('click', function() {
                window.location.href = this.getAttribute('data-url');
            });
            
            dealElement.querySelector('.edit-tracking').addEventListener('click', function() {
                openTrackingForm(dealId, deal.title, investment);
            });
        }
    });
}

/**
 * Open the investment tracking form
 */
function openTrackingForm(dealId, dealTitle, existingData = null) {
    const form = document.getElementById('investment-tracking-form');
    const titleElement = document.getElementById('tracking-form-title');
    const dealIdInput = document.getElementById('tracking-deal-id');
    const statusSelect = document.getElementById('investment-status');
    const amountInput = document.getElementById('investment-amount');
    const notesInput = document.getElementById('investment-notes');
    
    // Set form title
    titleElement.textContent = `Track Investment: ${dealTitle}`;
    
    // Set deal ID
    dealIdInput.value = dealId;
    
    // Set existing data if available
    if (existingData) {
        statusSelect.value = existingData.status;
        amountInput.value = existingData.amount || '';
        notesInput.value = existingData.notes || '';
    } else {
        // Reset form
        statusSelect.value = 'interested';
        amountInput.value = '';
        notesInput.value = '';
    }
    
    // Show form
    form.style.display = 'block';
    
    // Scroll to form
    form.scrollIntoView({ behavior: 'smooth' });
    
    // Show investor tools tab
    document.querySelector('.tab-button[data-tab="investment-tracker"]').click();
}

/**
 * Save investment tracking data
 */
function saveInvestment() {
    const dealId = document.getElementById('tracking-deal-id').value;
    const status = document.getElementById('investment-status').value;
    const amount = document.getElementById('investment-amount').value;
    const notes = document.getElementById('investment-notes').value;
    
    // Save to localStorage for now
    const investmentData = {
        dealId,
        status,
        amount,
        notes,
        updatedAt: new Date().toISOString()
    };
    
    localStorage.setItem(`dealroom_investment_${dealId}`, JSON.stringify(investmentData));
    
    // Track server-side as well
    const data = new FormData();
    data.append('action', 'dealroom_track_investment');
    data.append('nonce', dealroomInvestor.nonce);
    data.append('deal_id', dealId);
    data.append('status', status);
    data.append('amount', amount);
    data.append('notes', notes);
    
    fetch(dealroomInvestor.ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Error tracking investment:', data.data.message);
        }
    })
    .catch(error => {
        console.error('Error tracking investment:', error);
    })
    .finally(() => {
        // Hide form
        document.getElementById('investment-tracking-form').style.display = 'none';
        
        // Reload investment data
        loadInvestmentData();
    });
}

/**
 * Format a number with commas
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}